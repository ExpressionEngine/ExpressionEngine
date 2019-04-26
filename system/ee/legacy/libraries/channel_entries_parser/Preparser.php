<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Channel Pre-Parser
 */
class EE_Channel_preparser {

	public $pairs = array();
	public $singles = array();

	public $subscriber_totals = array();
	public $field_names = [];
	public $grid_field_names = [];
	public $fluid_field_names = [];

	protected $_prefix;
	protected $_tagdata;

	protected $_parser;
	protected $_channel;
	protected $_site_ids;
	protected $_entry_ids;

	protected $_components;
	protected $_disabled;

	protected $_pair_data;
	protected $_single_data;
	protected $_once_data;

	/**
	 * The Preparser
	 *
	 * Instantiated by the pre_parser factory in EE_Channel_parser. Please
	 * try to use that whenever possible.
	 *
	 * Parsing happens in two steps. We first take a look at the tagdata and
	 * doing any required prep works. This lets us avoid heavy computation in
	 * the replacement loop. The pre-parser is step one.
	 *
	 * @param channel   - The current channel object. Used to get access to the
	 *					  custom fields. They are stored in public arrays so we
	 *					  cannot assume they remain unchanged =( .
	 *
	 * @param parser	- A channel parser object which gives us access to the
	 *					  tagdata, prefix information, and parser components.
	 *
	 * @param site_ids  - An array of site IDs that the entries for this
	 *                    preparser belong to.
	 *
	 * @param entry_ids - An array of entry IDs. This can be used to retrieve
	 *					  additional data ahead of time. A good example of that
	 *					  would be the relationship parser.
	 *
	 * @param config    - A configuration array:
	 *
	 *	 disabled:	(array) Skip specific parsing steps
	 *				Takes the same values as the channel module's disable
	 *				parameter, which is one of its uses.
	 */
	public function __construct(Channel $channel, EE_Channel_parser $parser, $site_ids, $entry_ids, $config)
	{
		// Setup object state

		$this->_parser = $parser;
		$this->_channel = $channel;
		$this->_site_ids = $site_ids;
		$this->_entry_ids = $entry_ids;

		$this->_prefix	= $parser->prefix();
		$this->_tagdata = $parser->tagdata();

		$this->pairs	= $this->_extract_prefixed(ee()->TMPL->var_pair);
		$this->singles	= $this->_extract_prefixed(ee()->TMPL->var_single);

		// Get subscriber totals and modified conditionals
		$this->subscriber_totals	 = $this->_subscriber_totals();
		$this->field_names = $this->getFieldNamesInTagdata();
		$this->grid_field_names = $this->getFieldNamesInTagdata('gfields');
		$this->fluid_field_names = $this->getFieldNamesInTagdata('ffields');

		// Run through component pre_processing steps, skipping any that
		// were specified as being disabled.

		$tagdata  = $this->_tagdata;
		$components  = $parser->components();
		$disabled = isset($config['disable']) ? $config['disable'] : array();

		foreach (array('pair', 'once', 'single') as $fn)
		{
			foreach ($components->$fn() as $k => $component)
			{
				$skip	 = (bool) $component->disabled($disabled, $this);
				$obj_key = spl_object_hash($component);

				$var = '_'.$fn.'_data';
				$this->_disabled[$obj_key]  = $skip;
				$this->{$var}[$obj_key] = $skip ? NULL : $component->pre_process($tagdata, $this);
			}

		}
	}

	/**
	 * Site IDs getter
	 *
	 * Returns the site IDs that this pre-parser has entry IDs for.
	 *
	 * @return array	site IDs
	 */
	public function site_ids()
	{
		return $this->_site_ids;
	}

	/**
	 * Entry IDs getter
	 *
	 * Returns the entry ids that this pre-parser is capable of processing.
	 *
	 * @return array	entry IDs
	 */
	public function entry_ids()
	{
		return $this->_entry_ids;
	}

	/**
	 * Pair tag data getter
	 *
	 * Returns the data of the preprocessing step of a given component.
	 *
	 * @return mixed	Pair tag preprocessing results
	 */
	public function pair_data($obj)
	{
		return $this->_pair_data[spl_object_hash($obj)];
	}

	/**
	 * Single tag data getter
	 *
	 * Returns the data of the preprocessing step of a given component.
	 *
	 * @return mixed	Single tag preprocessing results
	 */
	public function single_data($obj)
	{
		return $this->_single_data[spl_object_hash($obj)];
	}

	/**
	 * Single tag data getter
	 *
	 * Returns the data of the preprocessing step of a given component.
	 *
	 * @return mixed	Once tag preprocessing results
	 */
	public function once_data($obj)
	{
		return $this->_once_data[spl_object_hash($obj)];
	}

	/**
	 * Single tag data setter
	 *
	 * Sets the data passed to the replace method of a given component.
	 *
	 * @return EE_Channel_parser_component	Component object to set data for
	 * @return mixed	Data to set for component
	 */
	public function set_once_data($obj, $data)
	{
		return $this->_once_data[spl_object_hash($obj)] = $data;
	}

	/**
	 * Prefix getter
	 *
	 * @return string
	 */
	public function prefix()
	{
		return $this->_prefix;
	}

	/**
	 * Channel getter
	 *
	 * Returns the channel object that this parser is operating with.
	 *
	 * @return Object<Channel>
	 */
	public function channel()
	{
		return $this->_channel;
	}

	/**
	 * Parser getter
	 *
	 * Returns the parser object that this preparser is operating with.
	 *
	 * @return Object<EE_Channel_parser>
	 */
	public function parser()
	{
		return $this->_parser;
	}

	/**
	 * Disabled lookup
	 *
	 * We skip processing on disabled components.
	 *
	 * @param Object<EE_Channel_parser_component> component to check
	 *
	 * @return Boolean	Component is disabled
	 */
	public function is_disabled(EE_Channel_parser_component $obj)
	{
		return $this->_disabled[spl_object_hash($obj)];
	}

	/**
	 * Tag lookup
	 *
	 * Utility method for components to check if a tag exists in their
	 * preprocessing step. This frequently acts as a performance shortcut
	 * to avoid unnecessary processing.
	 *
	 * Caution: Adds the prefix.
	 *
	 * @return Boolean	tag is in tagdata
	 */
	public function has_tag($tagname)
	{
		return strpos($this->_tagdata, LD.$this->_prefix.$tagname) !== FALSE;
	}

	/**
	 * Tag Pair lookup
	 *
	 * Utility method for components to check if a tag exists in their
	 * preprocessing step. This frequently acts as a performance shortcut
	 * to avoid unnecessary processing.
	 *
	 * Caution: Adds the prefix.
	 *
	 * @return Boolean	tag pair is in tagdata
	 */
	public function has_tag_pair($tagname)
	{
		$start = strpos($this->_tagdata, LD.$this->_prefix.$tagname);

		if ($start === FALSE)
		{
			return FALSE;
		}

		$end = strpos($this->_tagdata, LD.'/'.$this->_prefix.$tagname, $start);

		return $end !== FALSE;
	}

	/**
	 * Extract prefixed keys
	 *
	 * Utility method to extract array data whose keys starts with the
	 * current prefix. This is used on var_single and var_pair to reduce
	 * the number of iterations done in the parser when most tags have
	 * a different prefix.
	 *
	 * @return mixed	filtered array
	 */
	protected function _extract_prefixed(array $data)
	{
		if ( ! $this->_prefix)
		{
			return $data;
		}

		$filtered = array();
		$tagdata  = $this->_tagdata;
		$regex_prefix = '/^'.preg_quote($this->_prefix, '/').'.*+( |$)/';

		foreach (preg_grep($regex_prefix, array_keys($data)) as $key)
		{
			$filtered[$key] = $data[$key];
		}

		return $filtered;
	}

	/**
	 * Comment subscriber lookup
	 *
	 * Not entirely sure this should be here. It falls into a similar realm
	 * as categories, so we might be able to do it earlier. It's fine for now.
	 *
	 * @return subscriber information
	 */
	protected function _subscriber_totals()
	{
		$subscribers = array();

		if (strpos($this->_tagdata, LD.'comment_subscriber_total'.RD) !== FALSE
			&& isset(ee()->session->cache['channel']['entry_ids'])
			)
		{
			ee()->load->library('subscription');
			ee()->subscription->init('comment');
			$subscribers = ee()->subscription->get_subscription_totals('entry_id', ee()->session->cache['channel']['entry_ids']);
		}

		return $subscribers;
	}

	/**
	 * Get an array of field names that are present in the tagdata
	 *
	 * @return Array Field names
	 */
	protected function getFieldNamesInTagdata($type = 'cfields')
	{
		$all_field_names = array();
		$present_field_names = array();

		foreach($this->channel()->$type as $site_id => $fields)
		{
			$all_field_names = array_unique(array_merge($all_field_names, $fields));
		}

		foreach (array_keys($all_field_names) as $name)
		{
			if (strpos($this->_tagdata, $name) !== FALSE)
			{
				$present_field_names[] = $name;
			}
		}

		return $present_field_names;
	}
}
