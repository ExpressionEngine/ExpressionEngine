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
 * ExpressionEngine Route Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Route {

	public $segments = array();
	public $variables = array();

	public $segment_regex = "
    	(?P<static>[^{]*)                     # static rule data
    	{	
    	(?P<variable>[a-zA-Z_][a-zA-Z0-9_]*)  # variable name
    	(?:
    	    \:                                # variable delimiter
    	    (?P<rules>.*?)                    # rules
    	)?
		}
	";

	public $rules_regex = "
		(?P<rule>[^\|\[]*)    # rule name
		(?:
			\[
			(?P<args>[^\]]+)  # rule arguments
			\]
		)?
		\|?                   # optional delimiter
	";

    public function __construct($route)
    {
		require_once APPPATH.'libraries/template_router/Segment.php';
		require_once APPPATH.'libraries/template_router/Converters.php';
		$this->rules = new EE_Template_router_converters();
		$this->parse_route($route);
    }

	/**
	 * Build a URL for the route.
	 * 
	 * @param array $variables  An associative array of values for each named variable
	 * @access public
	 * @return string  The URL with all values set
	 */
	public function build(array $variables = array())
	{
		$url = array();
		foreach ($variables as $key => $val)
		{
			$this->variables[$key]->set($val);
		}
		foreach($this->segments as $segment)
		{
			if (is_string($segment))
			{
				$url[] = $segment;
			} else {
				if (empty($segment->value))
				{
					throw new Exception("Segment '{$segment->name}' missing value.");
				}
				$url[] =  $segment->value;
			}
		}
		return implode('', $url);
	}

	/**
	 * Compile the route to a regular expression used for matching.
	 * 
	 * @access public
	 * @return string  The compiled regular expression.
	 */
	public function compile() {
		$url = array();
		foreach($this->segments as $segment)
		{
			if (is_string($segment))
			{
				$url[] = $segment;
			} else {
				$url[] = $segment->regex();
			}
		}
		$parsed_route = implode('', $url);
		// backslash escaped for preg_match
		$parsed_route = str_replace('/', '\/', $parsed_route);
		// anchor the beginning and end, and add optional trailing slash
		return "^{$parsed_route}\/?$";
	}

	/**
	 * Parse the route and set the segments and named variables for this route.
	 * 
	 * @param string  EE formatted template route 
	 * @access public
	 * @return void
	 */
	public function parse_route($route)
	{
		$route = trim($route, '/');
		$segments = $this->parse_segments($route);
		$index = 0;
		foreach ($segments as $segment)
		{
			if ( ! empty($segment['static']))
			{
				$this->segments[] = $segment['static'];
			}
			else
			{
				$rules = $this->parse_rules($segment['rules']);
				$segment = new EE_Route_segment($segment['variable'], $rules);
				$this->segments[$index] = $segment;
				$this->variables[$segment->name] =& $this->segments[$index];
			}
			$index++;
		}
	}

    /**
     * Parses a EE formatted template route into segments
     * 
     * @param string $route 
     * @access public
	 * @return array
	 * 		    - variable : Segment's variable name
	 * 		    - rules : Segment's list of validators
	 * 		    - static : Bare segment string, only set if segment is static text
     */
    public function parse_segments($route)
    {
		$pos = 0;
		$end = strlen($route);
		$used_names = array();
		while ($pos < $end)
		{
			$segment = array();
			$result = preg_match("/{$this->segment_regex}/ix", $route, $matches, 0, $pos);
			if ($result == 0)
			{
				break;
			}
			if ( ! empty($matches['static']))
			{
				$segments[] = array('static' => $matches['static']);
			}
			$segment['variable'] = $matches['variable'];
			if ( ! empty($matches['rules']))
			{
				$segment['rules'] = $matches['rules'];
			}
			if (in_array($segment['variable'], $used_names))
			{
				throw new Exception("URL variable '{$segment['variable']}' already in use.");
			}
			$used_names[] = $segment['variable'];
			$segments[] = $segment;
			$pos += strlen($matches[0]);
		}
		if ($pos < $end)
		{
			$remainder = substr($route, $pos);
			if ( (strpos($remainder, '{') === False && strpos($remainder, '}')) === False)
			{
				throw new Exception("Invalid URL Route: $route");
			}
			$segments[] = array('static' => $remainder);
		}
		return $segments;
    }

    /**
     * Parse a URL segment for a list of validators and convert to a regular expression
     * 
	 * @param $rules string  An EE formatted validation string e.g.:
	 * 						   "rule1[arg1,arg2...]|rule2|..."
     * @access public
     * @return EE_Template_router_converter[]  An array of initialized validation rules
     */
    public function parse_rules($rules)
    {
		$pos = 0;
		$end = strlen($rules);
		$used_rules = array();
		$parsed_rules = array();
		while ($pos < $end)
		{
			$result = preg_match("/{$this->rules_regex}/ix", $rules, $matches, 0, $pos);
			if ($result == 0)
			{
				break;
			}
			$args = array();
			// Not even Xzibit would try to parse a regex with a regex.
			// So we'll treat regexes as a special case and concatenate and
			// validate until we have a valid regular expression.
			if ($matches['rule'] == 'regex')
			{
				$index = $pos + 7;
				$regex = substr($matches[0], 6, 1);
				$valid = @preg_match("/$regex/", null);
				while ($valid == False)
				{
					$regex .= substr($rules, $index, 1);
					$valid = @preg_match("/$regex/", null);
					$index++;
					if($end < $index) {
						throw new Exception("Invalid Regular Expression");
					}
				}
				$matches[0] = "regex[{$regex}]|";
				$matches['args'] = $regex;
			}
			elseif( ! empty($matches['args']))
			{
				$args = explode(',', $matches['args']);
				array_walk($args, 'trim');
			}
			$parsed_rules[] = $this->rules->load($matches['rule'], $args);
			$pos += strlen($matches[0]);
		}
		return $parsed_rules;
    }

}
// END CLASS

/* End of file Route.php */
/* Location: ./system/expressionengine/libraries/template_router/Route.php */