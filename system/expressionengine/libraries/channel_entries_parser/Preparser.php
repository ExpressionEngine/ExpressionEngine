<?php

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Pre-Parser
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_preparser {

	public $pairs = array();
	public $singles = array();

	public $subscriber_totals = array();
	public $modified_conditionals = array();

	protected $_prefix;
	protected $_tagdata;

	protected $_parser;
	protected $_channel;
	protected $_entry_ids;

	protected $_plugins;

	protected $_pair_data;
	protected $_single_data;

	public function __construct(Channel $channel, EE_Channel_parser $parser, $entry_ids, $config)
	{
		$this->_parser = $parser;
		$this->_channel = $channel;
		$this->_entry_ids = $entry_ids;
		
		$disabled = isset($config['disable']) ? $config['disable'] : array();

		$plugins = $parser->plugins();

		$this->_prefix = $parser->prefix();
		$this->_tagdata = $parser->tagdata();

		$this->pairs	= $this->_extract_prefixed(get_instance()->TMPL->var_pair);
		$this->singles	= $this->_extract_prefixed(get_instance()->TMPL->var_single);

		foreach ($plugins->pair() as $k => $plugin)
		{
			if ($plugin->disabled($disabled))
			{
				$this->_pair_data[$k] = NULL;
				continue;
			}

			$this->_pair_data[$k] = $plugin->pre_process($this->_tagdata, $this);
		}

		foreach ($plugins->single() as $k => $plugin)
		{
			$this->_single_data[$k] = $plugin->pre_process($this->_tagdata, $this);
		}

		$this->subscriber_totals	= $this->_subscriber_totals();
		$this->modified_conditionals = $this->_find_modified_conditionals();

	}

	public function entry_ids()
	{
		return $this->_entry_ids;
	}

	public function pair_data($key)
	{
		return $this->_pair_data[$key];
	}

	public function single_data($key)
	{
		return $this->_single_data[$key];
	}

	public function prefix()
	{
		return $this->_prefix;
	}

	public function channel()
	{
		return $this->_channel;
	}

	public function parser()
	{
		return $this->_parser;
	}

	public function has_tag($tagname)
	{
		return strpos($this->_tagdata, LD.$this->_prefix.$tagname) !== FALSE;
	}

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

	protected function _subscriber_totals()
	{
		$subscribers = array();
		
		if (strpos($this->_tagdata, LD.'comment_subscriber_total'.RD) !== FALSE
			&& isset(get_instance()->session->cache['channel']['entry_ids'])
			)
		{
			get_instance()->load->library('subscription');
			get_instance()->subscription->init('comment');
			$subscribers = get_instance()->subscription->get_subscription_totals('entry_id', get_instance()->session->cache['channel']['entry_ids']);
		}

		return $subscribers;
	}

	protected function _extract_prefixed(array $data)
	{
		if ( ! $this->_prefix)
		{
			return $data;
		}

		$filtered = array();

		$regex_prefix = '/^'.preg_quote($this->_prefix, '/').'[^:]+( |$)/';

		foreach (preg_grep($regex_prefix, array_keys($data)) as $key)
		{
			$filtered[$key] = $data[$key];
		}

		return $filtered;
	}

	public function _find_modified_conditionals()
	{
		$prefix = $this->_prefix;
		$all_field_names = array();

		if (strpos($this->_tagdata, LD.'if') === FALSE)
		{
			return array();
		}

		foreach($this->_channel->cfields as $site_id => $fields)
		{
			$all_field_names = array_unique(array_merge($all_field_names, $fields));
		}

		$modified_field_options = $prefix.implode('|'.$prefix, array_keys($all_field_names));
		$modified_conditionals = array();

		if (preg_match_all("/".preg_quote(LD)."((if:(else))*if)\s+(($modified_field_options):(\w+))(.*?)".preg_quote(RD)."/s", $this->_tagdata, $matches))
		{
			foreach($matches[5] as $match_key => $field_name)
			{
				$modified_conditionals[$field_name][] = $matches[6][$match_key];
			}
		}
		
		return array_map('array_unique', $modified_conditionals);
	}
}