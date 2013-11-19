<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Router Segment Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Router_segment {

    public function __construct($name, $rules)
    {
		$this->name = $name;
		$this->rules = $rules;
	}

	/**
	 * Compile the segment down to a named regex
	 * 
	 * @access public
	 * @return string A regular expression for the segment
	 */
	public function regex()
	{
		return "(?P<{$this->name}>" . $this->validator . ")";
	}

	/**
	 * Run through all the rules and combine them into one validator
	 * 
	 * @access public
	 * @return A regular expression for all of the segments validators
	 */
	public function validator()
	{
		$compiled_rules = "";
		foreach ($this->rules as $rule) {
			$compiled_rules .= "(^(?={$rule->validator}$).*)";
		}
		return $compiled_rules;
	}

}
// END CLASS

/* End of file Segment.php */
/* Location: ./system/expressionengine/libraries/template_router/Segment.php */