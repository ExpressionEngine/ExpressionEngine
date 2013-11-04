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
 * ExpressionEngine Router Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Router extends CI_Router {

	public $segment_regex = "
    	(?P<static>[^{]*)                     # static rule data
    	{	
    	(?P<variable>[a-zA-Z_][a-zA-Z0-9_]*)  # variable name
    	(?:
    	    \:                                # variable delimiter
    	    (?P<rules>.*)                     # rules
    	)?
		}
	";

    public function __construct()
    {
    }

	public function parse_route($route)
	{
		$parsed_segments = array();
		$segments = $this->parse_segments($route);
		foreach ($segments as $segment)
		{
			if ( ! empty($segment['static']))
			{
				$parsed_segments[] = $segment['static'];
			}
			else
			{
				$rules = $this->parse_rules($segment);
				$parsed_segments[] = "(?P<{$segment['variable']}>" . implode('', $rules) . ")";
			}
		}
		// backslash escaped for preg_match
		$parsed_route = implode('\/', $parsed_segments);
		// anchor the beginning and end, and add optional trailing slash
		return "^{$parsed_route}\/?$";
	}

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
			$pos += strlen($matches[0]);
		}
		if ($pos < $end)
		{
			$remainder = substr($route, $pos);
			if ( (strpos($remainder, '<') && strpos($remainder, '>')) === False)
			{
				throw new Exception("Invalid URL Route: $route");
			}
			$segments[] = array('static' => $remainder);
		}
		return $segments;
    }

    public function parse_rules($segment)
    {
    }

}
// END CLASS

/* End of file Router.php */
/* Location: ./system/expressionengine/libraries/template_router/Router.php */