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

	public function regex($args);

}