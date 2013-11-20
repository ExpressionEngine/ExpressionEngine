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
		return "(?P<{$this->name}>" . $this->validator() . ")";
	}

	/**
	 * Validate the provided value against the segment rules
	 * 
	 * @param mixed $val The value to be checked 
	 * @access public
	 * @return bool
	 */
	public function validate($val) {
		$regex = "/" . $this->validator() . "/i";
		$result = preg_match($regex, $val);
		if ($result === False)
		{
			throw new Exception("Invalid rule in segment");
		}
	}

	/**
	 * Run through all the rules and combine them into one validator
	 * 
	 * @access public
	 * @return A regular expression for all of the segment's validators
	 */
	public function validator()
	{
		$compiled_rules = "";
		foreach ($this->rules as $rule)
		{
			// Place each rule inside an anchored lookahead,
			// this will match the entire string if the rule matches.
			// This allows rules to work together without consuming the match.
			$compiled_rules .= "(^(?={$rule->validator}$).*)";
		}
		return $compiled_rules;
	}

}
// END CLASS

/* End of file Segment.php */
/* Location: ./system/expressionengine/libraries/template_router/Segment.php */