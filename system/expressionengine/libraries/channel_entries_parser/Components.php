<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */

// ------------------------------------------------------------------------

require_once APPPATH.'libraries/channel_entries_parser/components/Category.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Custom_date.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Custom_field.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Custom_field_pair.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Custom_member_field.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Date.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Grid.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Header_and_footer.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Relationship.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Simple_conditional.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Simple_variable.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Switch.php';

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Components
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_parser_components {

	/**
	 * The difference between these is in what variable array is passed
	 * to them from the template->var_* arrays.
	 * They also order differently. Pairs are done before all else to
	 * discourage edge-case bugs where a pair's opening tag is accidentally
	 * replace.
	 * You may encounter edge-case bugs if you try to parse single tags
	 * before the tag pairs are out of the way, so you're encouraged
	 * to use the correct type for your component.
	 */
	protected $pair = array();
	protected $single = array();
	protected $once = array();

	public function __construct()
	{
		// Dear third party devs, use the register_component method on
		// EE->channel_entries_parser for your own additions. Gracias.

		// Prep built-in parsers. Don't mess with the order, it matters!
		$this->register_pair('EE_Channel_header_and_footer_parser');

		$this->register_once('EE_Channel_category_parser');
		$this->register_once('EE_Channel_grid_parser');
		$this->register_once('EE_Channel_custom_field_pair_parser');
		$this->register_once('EE_Channel_relationship_parser');
		$this->register_once('EE_Channel_switch_parser');

		$this->register_single('EE_Channel_simple_conditional_parser');
		$this->register_single('EE_Channel_date_parser');
		$this->register_single('EE_Channel_simple_variable_parser');
		$this->register_single('EE_Channel_custom_date_parser');
		$this->register_single('EE_Channel_custom_field_parser');
		$this->register_single('EE_Channel_custom_member_field_parser');
	}

	// ------------------------------------------------------------------------

	/**
	 * Register a component that parses a pair tag.
	 *
	 * @param String	Class name of new component
	 */
	public function register_pair($class)
	{
		$obj = new $class;

		if ( ! $obj instanceOf EE_Channel_parser_component)
		{
			throw new InvalidArgumentException($class.' must implement the EE_Channel_parser_component interface.');
		}

		$this->pair[] = $obj;
	}

	// ------------------------------------------------------------------------

	/**
	 * Register a component that parses a single tag.
	 *
	 * @param String	Class name of new component
	 */
	public function register_single($class)
	{
		$obj = new $class;

		if ( ! $obj instanceOf EE_Channel_parser_component)
		{
			throw new InvalidArgumentException($class.' must implement the EE_Channel_parser_component interface.');
		}

		$this->single[] = $obj;
	}

	// ------------------------------------------------------------------------

	/**
	 * Register a component that only runs once regardless of tag names.
	 *
	 * @param String	Class name of new component
	 */
	public function register_once($class)
	{
		$obj = new $class;

		if ( ! $obj instanceOf EE_Channel_parser_component)
		{
			throw new InvalidArgumentException($class.' must implement the EE_Channel_parser_component interface.');
		}

		$this->once[] = $obj;
	}

	// ------------------------------------------------------------------------

	/**
	 * Pair tag parsing components
	 *
	 * @return Array	List of pair tag parsing components
	 */
	public function pair()
	{
		return $this->pair;
	}

	// ------------------------------------------------------------------------

	/**
	 * Single tag parsing components
	 *
	 * @return Array	List of single tag parsing components
	 */
	public function single()
	{
		return $this->single;
	}

	// ------------------------------------------------------------------------

	/**
	 * Single tag parsing components
	 *
	 * @return Array	List of single tag parsing components
	 */
	public function once()
	{
		return $this->once;
	}
}


// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Component Interface
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface EE_Channel_parser_component {

	/**
	 * Check if your component or something something parsed by it
	 * is in the disable= parameter.
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre);

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
	 * Replace all tags that this component can deal with.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $pre_process_result);

}