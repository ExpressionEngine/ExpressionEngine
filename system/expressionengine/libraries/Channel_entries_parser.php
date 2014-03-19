<?php

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Entry Parser Factory
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_entries_parser {

	protected $_components;

	public function __construct()
	{
		require_once APPPATH.'libraries/channel_entries_parser/Preparser.php';
		require_once APPPATH.'libraries/channel_entries_parser/Parser.php';
		require_once APPPATH.'libraries/channel_entries_parser/Components.php';

		$this->_components = new EE_Channel_parser_components();
	}

	// --------------------------------------------------------------------

	/**
	 * The main parser factory
	 *
	 * @param tagdata - The chunk of template that the parser will process
	 *					this is usually the content of the channel entries tag.
	 *
	 * @param prefix  - A prefix to apply to all data and tags.Allow for nesting
	 *					of similar tags.
	 *
	 * @return Object<EE_Channel_parser>
	 */
	public function create($tagdata, $prefix = '')
	{
		return new EE_Channel_parser($tagdata, $prefix, $this->_components);
	}

	// --------------------------------------------------------------------

	/**
	 * Register a channel parser component
	 *
	 * These are for tags within the channel module. If you're a third
	 * party reading this, think twice! You probably want a field type.
	 * If you're really sure, please make your tag names obvious!
	 *
	 * @param type    - single|pair depending on whether or not this is a tag
	 *					pair or a single tag. Tag pairs are done first.
	 *
	 * @param class   - Class name of the component. Must be included when this
	 *					is called. Must implement the EE_Channel_parser_component
	 *					interface found in channel_entries_parser/Components.php.
	 * @return void
	 */
	public function register_component($type, $class)
	{
		switch ($type)
		{
			case 'single': $fn = 'register_single';
				break;
			case 'pair': $fn = 'register_pair';
				break;
			default:
				throw new InvalidArgumentException('$type must be "single" or "pair"');
		}

		$this->_components->$fn($class, $add_to_front);
	}
}


// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Entry Parser
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_parser {

	protected $_prefix;
	protected $_tagdata;
	protected $_components;

	/**
	 * Instantiated by EE_Channel_entries_parser::create(), please use that
	 * and refer to its documentation for parameter explanations.
	 */
	public function __construct($tagdata, $prefix, EE_Channel_parser_components $components)
	{
		$this->_prefix = $prefix;
		$this->_tagdata = $tagdata;
		$this->_components = $components;
	}

	// --------------------------------------------------------------------

	/**
	 * Tagdata getter
	 *
	 * Returns the tag chunk that the parser should process
	 *
	 * @return String	tagdata
	 */
	public function tagdata()
	{
		return $this->_tagdata;
	}

	// --------------------------------------------------------------------

	/**
	 * Prefix getter
	 *
	 * Returns a prefix if one was specified in the constructor.
	 *
	 * @return String	prefix
	 */
	public function prefix()
	{
		return $this->_prefix;
	}

	// --------------------------------------------------------------------

	/**
	 * Components getter
	 *
	 * Components handle all of the actual heavy lifting. You can add your
	 * own by calling EE->Channel_entries_parser->register_component().
	 *
	 * @return Object<EE_Channel_parser_components>
	 */
	public function components()
	{
		return $this->_components;
	}

	// --------------------------------------------------------------------

	/**
	 * Parser untilty function parser
	 *
	 * Does in one call what the pre_parser() and data_parser() typically
	 * do in two calls. A little less flexible since you don't have access
	 * to the preparser before the data is parsed, but usually this is what
	 * you want.
	 *
	 * @param channel - The current channel object. Used to get access to the
	 *					custom fields. They are stored in public arrays so we
	 *					cannot assume they remain unchanged =( .
	 *
	 * @param data - An array of data arrays. Required: 'entries'.
	 *
	 *	  entries	 =>	array of {entry_id => row_data} that should be
	 *					used as the data for the template.
	 *	  categories => array of {category_id => cat_data}
	 *
	 *	  Other keys as required by parsing components.
	 *
	 * @param config  - Additional configuration options, such as
	 *
	 *	  disabled   => Skip specific parsing steps
	 *	  callbacks  => Hook into certain parsing steps for more processing
	 *
	 * @return string	Parsed tagdata
	 */
	public function parse(Channel $channel, array $data, array $config = array())
	{
		// Gather site IDs
		$site_ids = array();
		foreach ($data['entries'] as $entry)
		{
			$site_ids[] = $entry['site_id'];
		}

		$parser = $this->data_parser(
			$this->pre_parser($channel, array_unique($site_ids), array_keys($data['entries']), $config)
		);

		return $parser->parse($data, $config);
	}

	// --------------------------------------------------------------------

	/**
	 * The pre-parser factory
	 *
	 * Parsing happens in two steps. We first take a look at the tagdata and
	 * doing any required prep works. This lets us avoid heavy computation in
	 * the replacement loop. The pre-parser is step one.
	 *
	 * @param channel   - The current channel object. Used to get access to the
	 *					  custom fields. They are stored in public arrays so we
	 *					  cannot assume they remain unchanged =( .
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
	 *
	 * @return Object<EE_Channel_preparser>
	 */
	public function pre_parser(Channel $channel, array $site_ids, array $entry_ids, array $config = array())
	{
		return new EE_Channel_preparser($channel, $this, $site_ids, $entry_ids, $config);
	}

	// --------------------------------------------------------------------

	/**
	 * Data parser
	 *
	 * After the tagdata has been processed by the preparser, it's time to
	 * iterate over the actual data rows and assemble a final template. That
	 * is what the data parser is for.
	 *
	 * @param Object<EE_Channel_preparser> - a preparsed tag chunk
	 *
	 * @return Object<EE_Channel_data_parser>
	 */
	public function data_parser(EE_Channel_preparser $pre)
	{
		return new EE_Channel_data_parser($pre, $this);
	}
}