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

require_once APPPATH.'libraries/template_router/converters/Integer.php';
require_once APPPATH.'libraries/template_router/converters/Max_length.php';
require_once APPPATH.'libraries/template_router/converters/Min_length.php';
require_once APPPATH.'libraries/template_router/converters/Regex.php';

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Converters
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Template_router_converters {

	public $converters = array();

	public function __construct()
	{
		// Register default converters
		$this->register('int', 'EE_template_router_integer_converter');
		$this->register('max_length', 'EE_template_router_max_length_converter');
		$this->register('min_length', 'EE_template_router_min_length_converter');
		$this->register('regex', 'EE_template_router_regex_converter');
	}

	// ------------------------------------------------------------------------

	/**
	 * Register a converter
	 *
	 * @param String	Class name of new converter
	 */
	public function register($name, $class)
	{
		$obj = new $class;

		if ( ! $obj instanceOf EE_Template_router_converter)
		{
			throw new InvalidArgumentException($class.' must implement the EE_Template_router_converter interface.');
		}

		$this->converters[$name] = $obj;
	}

}

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Rule Converter Implementation 
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
interface EE_Template_router_converter {

	/**
	 * Return a regular expression for validation
	 * 
	 * @param mixed  	The arguments for the converter 
	 * @access public
	 * @return string	The compiled regular expression
	 */
	public function regex($args);

}