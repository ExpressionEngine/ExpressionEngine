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
 * ExpressionEngine Channel Entry Parser Factory
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_entries_parser {

	protected $_plugins;

	public function __construct()
	{
		require_once APPPATH.'libraries/channel_entries_parser/Preparser.php';
		require_once APPPATH.'libraries/channel_entries_parser/Parser.php';
		require_once APPPATH.'libraries/channel_entries_parser/Plugins.php';

		$this->_plugins = new EE_Channel_parser_plugins();
	}

	public function create($tagdata, $prefix = '')
	{
		return new EE_Channel_parser($tagdata, $prefix, $this->_plugins);
	}

	public function register_plugin($type, $class)
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

		$this->_plugins->$fn($class, $add_to_front);
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
	protected $_plugins;

	public function __construct($tagdata, $prefix, EE_Channel_parser_plugins $plugins)
	{
		$this->_prefix = $prefix;
		$this->_tagdata = $tagdata;
		$this->_plugins = $plugins;
	}

	public function tagdata()
	{
		return $this->_tagdata;
	}

	public function prefix()
	{
		return $this->_prefix;
	}

	public function plugins()
	{
		return $this->_plugins;
	}

	public function pre_parser(Channel $channel, array $entry_ids, array $config = array())
	{
		return new EE_Channel_preparser($channel, $this, $entry_ids, $config);
	}

	public function data_parser(EE_Channel_preparser $pre)
	{
		return new EE_Channel_data_parser($pre, $this);
	}

	/*

	// short way
	$p = new Parser($tagdata, $prefix);
	$p->parse($channel, $entries, array( ... ));

	// long way
	$pre = $p->pre_parser($channel);
	$parser = $p->data_parser($pre);

	$parser->parse($entries, array(
		'disable' => array('relationships', 'categories'),
		'callbacks' => array(
			'pre_loop' => array($this, 'method');
		)
	));

	*/
	public function parse(Channel $channel, array $entries, array $config = array())
	{
		$pre = $this->pre_parser($channel, array_keys($entries), $config);
		$parser = $this->data_parser($pre);

		return $parser->parse($entries, $config);
	}
}