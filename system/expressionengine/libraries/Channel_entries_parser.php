<?php

class EE_Channel_entries_parser {

	protected $_plugins;

	public function __construct()
	{
		require_once APPPATH.'libraries/channel_entries_parser/Preparser.php';
		require_once APPPATH.'libraries/channel_entries_parser/Parser.php';
		require_once APPPATH.'libraries/channel_entries_parser/Plugins.php';

		$plugins = new EE_Channel_parser_plugins();

		// don't mess with the order, it matters!
		$plugins->register_single('EE_Channel_simple_conditional_parser');
		$plugins->register_single('EE_Channel_switch_parser');
		$plugins->register_single('EE_Channel_date_parser');
		$plugins->register_single('EE_Channel_simple_variable_parser');
		$plugins->register_single('EE_Channel_custom_date_parser');
		$plugins->register_single('EE_Channel_custom_field_parser');
		$plugins->register_single('EE_Channel_custom_member_field_parser');

		$plugins->register_pair('EE_Channel_category_parser');
		$plugins->register_pair('EE_Channel_custom_field_pair_parser');
		$plugins->register_pair('EE_Channel_header_and_footer_parser');

		$this->_plugins = $plugins;
	}

	public function create($tagdata, $prefix = '')
	{
		return new EE_Channel_parser($tagdata, $prefix);
	}

	public function register_plugin($type, $class, $add_to_front = FALSE)
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

	public function plugins()
	{
		return $this->_plugins;
	}
}


class EE_Channel_parser {

	protected $_prefix;
	protected $_tagdata;

	public function __construct($tagdata, $prefix)
	{
		$this->_prefix = $prefix;
		$this->_tagdata = $tagdata;
	}

	public function tagdata()
	{
		return $this->_tagdata;
	}

	public function prefix()
	{
		return $this->_prefix;
	}

	public function pre_parser(Channel $channel)
	{
		return new EE_Channel_preparser($channel, $this);
	}

	public function data_parser(EE_Channel_preparser $pre, $relationship_parser = NULL)
	{
		return new EE_Channel_data_parser($pre, $relationship_parser);
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
		$pre = $this->pre_parser($channel);
		$parser = $this->data_parser($pre);

		return $parser->parse($entries, $config);
	}
}