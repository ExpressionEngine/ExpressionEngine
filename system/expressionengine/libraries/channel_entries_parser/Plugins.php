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
class EE_Channel_parser_plugins {

	/**
	 * These do not actually behave separately at present, but you
	 * may encounter edge-case bugs if you try to parse single tags
	 * before the tag pairs are out of the way, so you're encouraged
	 * to use the correct type for your plugin.
	 */
	protected $pair_plugins = array();
	protected $single_plugins = array();

	public function __construct()
	{
		// Dear third party devs, use the register_plugin method on
		// EE->channel_entries_parser for your own additions. Gracias.

		// Prep built-in parsers. Don't mess with the order, it matters!
		$this->register_pair('EE_Channel_category_parser');
		$this->register_pair('EE_Channel_custom_field_pair_parser');
		$this->register_pair('EE_Channel_header_and_footer_parser');
		$this->register_pair('EE_Channel_relationship_parser');

		$this->register_single('EE_Channel_simple_conditional_parser');
		$this->register_single('EE_Channel_switch_parser');
		$this->register_single('EE_Channel_date_parser');
		$this->register_single('EE_Channel_simple_variable_parser');
		$this->register_single('EE_Channel_custom_date_parser');
		$this->register_single('EE_Channel_custom_field_parser');
		$this->register_single('EE_Channel_custom_member_field_parser');
	}

	// ------------------------------------------------------------------------

	/**
	 * Register a plugin that parses a pair tag.
	 *
	 * @param String	Class name of new plugin
	 */
	public function register_pair($class)
	{
		$this->pair_plugins[] = new $class();
	}

	// ------------------------------------------------------------------------

	/**
	 * Register a plugin that parses a single tag.
	 *
	 * @param String	Class name of new plugin
	 */
	public function register_single($class)
	{
		$this->single_plugins[] = new $class();
	}

	// ------------------------------------------------------------------------

	/**
	 * Pair tag parsing plugins
	 *
	 * @return Array	List of pair tag parsing plugins
	 */
	public function pair()
	{
		return $this->pair_plugins;
	}

	// ------------------------------------------------------------------------

	/**
	 * Single tag parsing plugins
	 *
	 * @return Array	List of single tag parsing plugins
	 */
	public function single()
	{
		return $this->single_plugins;
	}
}


// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Plugin Interface
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface EE_Channel_parser_plugin {

	/**
	 * Check if your plugin or something something parsed by it
	 * is in the disable= parameter.
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled);

	// ------------------------------------------------------------------------

	/**
	 * Do any pre-processing on the tagdata or other data available
	 * through the pre-parser.
	 *
	 * The return value of this method will be passed to the replace
	 * method as a third parameter.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return mixed	[optional]
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre);

	// ------------------------------------------------------------------------

	/**
	 * Replace all tags that this plugin can deal with.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $pre_process_result);

}