<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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

require_once APPPATH.'libraries/template_router/converters/Alpha_dash.php';
require_once APPPATH.'libraries/template_router/converters/Alpha_numeric.php';
require_once APPPATH.'libraries/template_router/converters/Alpha.php';
require_once APPPATH.'libraries/template_router/converters/Base64.php';
require_once APPPATH.'libraries/template_router/converters/Category.php';
require_once APPPATH.'libraries/template_router/converters/Integer.php';
require_once APPPATH.'libraries/template_router/converters/Max_length.php';
require_once APPPATH.'libraries/template_router/converters/Min_length.php';
require_once APPPATH.'libraries/template_router/converters/Natural.php';
require_once APPPATH.'libraries/template_router/converters/Numeric.php';
require_once APPPATH.'libraries/template_router/converters/Pagination.php';
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
		ee()->lang->loadfile('template_router');
		// Register default converters
		$this->register('alpha', 'EE_template_router_alpha_converter');
		$this->register('alpha_dash', 'EE_template_router_alpha_dash_converter');
		$this->register('alpha_numeric', 'EE_template_router_alpha_numeric_converter');
		$this->register('base64', 'EE_template_router_base64_converter');
		$this->register('category', 'EE_template_router_category_converter');
		$this->register('integer', 'EE_template_router_integer_converter');
		$this->register('max_length', 'EE_template_router_max_length_converter');
		$this->register('min_length', 'EE_template_router_min_length_converter');
		$this->register('natural', 'EE_template_router_natural_converter');
		$this->register('numeric', 'EE_template_router_numeric_converter');
		$this->register('pagination', 'EE_template_router_pagination_converter');
		$this->register('regex', 'EE_template_router_regex_converter');
	}

	// ------------------------------------------------------------------------

	/**
	 * Load a validation rule with the provided constuctor $args
	 *
	 * @param mixed $converter  The registered name of the validation rule
	 * @param array $args       The initial arguments for initializing the validation rule
	 * @access public
	 * @return EE_template_router_converter  The instantiated validation rule
	 */
	public function load($converter, $args = array())
	{
		if (empty($this->converters[$converter]))
		{
			throw new Exception(lang('missing_rule') . $converter);
		}

		$class = $this->converters[$converter];

		if (empty($args))
		{
			$obj = new $class;
		}
		else
		{
			$obj = new ReflectionClass($class);
			$obj = $obj->newInstanceArgs($args);
		}

		return $obj;
	}

	// ------------------------------------------------------------------------

	/**
	 * Register a converter
	 *
	 * @param String	Class name of new converter
	 */
	public function register($name, $class)
	{
		$this->converters[$name] = $class;
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
interface EE_Template_router_converter
{

	/**
	 * Return a regular expression for validation
	 *
	 * @access public
	 * @return string	The compiled regular expression
	 */
	public function validator();

}