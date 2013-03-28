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
 * ExpressionEngine Channel Parser Plugins
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

require_once APPPATH.'libraries/channel_entries_parser/plugins/Category.php';
require_once APPPATH.'libraries/channel_entries_parser/plugins/Header_and_footer.php';
require_once APPPATH.'libraries/channel_entries_parser/plugins/Custom_date.php';
require_once APPPATH.'libraries/channel_entries_parser/plugins/Custom_field_pair.php';
require_once APPPATH.'libraries/channel_entries_parser/plugins/Custom_field.php';
require_once APPPATH.'libraries/channel_entries_parser/plugins/Custom_member_field.php';
require_once APPPATH.'libraries/channel_entries_parser/plugins/Date.php';
require_once APPPATH.'libraries/channel_entries_parser/plugins/Relationship.php';
require_once APPPATH.'libraries/channel_entries_parser/plugins/Simple_conditional.php';
require_once APPPATH.'libraries/channel_entries_parser/plugins/Simple_variable.php';
require_once APPPATH.'libraries/channel_entries_parser/plugins/Switch.php';

// @todo check if plugin exists when registering
class EE_Channel_parser_plugins {

	protected $pair_plugins = array();
	protected $single_plugins = array();

	public function register_pair($class, $add_to_front = FALSE)
	{
		$fn = $add_to_front ? 'array_unshift' : 'array_push';
		$fn($this->pair_plugins, new $class());
	}

	public function register_single($class, $add_to_front = FALSE)
	{
		$fn = $add_to_front ? 'array_unshift' : 'array_push';
		$fn($this->single_plugins, new $class());
	}

	public function pair()
	{
		return $this->pair_plugins;
	}

	public function single()
	{
		return $this->single_plugins;
	}
}


interface EE_Channel_parser_plugin {

	public function disabled(array $disabled); // return bool
	public function replace($tagdata, EE_Channel_data_parser $obj, $pre); // return $tagdata

//	public function clear(); // remove leftovers?

}