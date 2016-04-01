<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Route Segment Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EE_Route_segment {

	public $static;
	public $values;
	public $parts;
	public $isset = FALSE;

	/**
	 * __construct
	 *
	 * @param string $segment The full segment string with {vars} replaced with the var hash
	 * @param array $parts EE_Route_segment_part[]
	 * @access public
	 * @return void
	 */
	public function __construct($static, $parts)
	{
		$this->static = $static;
		$this->parts = $parts;
		ee()->lang->loadfile('template_router');
	}

	/**
	 * Compile the segment down to a regex
	 *
	 * @access public
	 * @return string A regular expression for the segment
	 */
	public function regex()
	{
		return "(" . $this->validator() . ")";
	}

	/**
	 * Validate the provided value against the segment rules
	 *
	 * @param mixed $val The variable to be checked
	 * @param mixed $val The value to be checked
	 * @access public
	 * @return bool
	 */
	public function validate($variable, $val)
	{
		return $this->parts[$variable]->validate($val);
	}

	/**
	 * Run through all the parts and combine them into one validator
	 *
	 * @access public
	 * @return A regular expression for all of the segment's validators
	 */
	public function validator()
	{
		$compiled = $this->static;

		foreach ($this->parts as $part)
		{
			$compiled = str_replace($part->name, $part->regex(), $compiled);
		}

		return $compiled;
	}

	/**
	 * hasValue returns true if this segment has a value that should be
	 * used when building the route. This will always be true for static
	 * segments and variable segments which have had their value set.
	 *
	 * @access public
	 * @return void
	 */
	public function hasValue()
	{
		return empty($this->parts) || $this->isset;
	}

	public function set($variable, $val)
	{
		$this->parts[$variable]->set($val);
		$this->isset = TRUE;
	}

	public function value()
	{
		$value = $this->static;

		foreach ($this->parts as $part)
		{
			$value = str_replace($part->name, $part->value, $value);
		}

		return $value;
	}

}
// END CLASS

// EOF
