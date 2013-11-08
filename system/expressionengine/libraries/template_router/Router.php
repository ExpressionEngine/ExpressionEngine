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

	public $rules_regex = "
		(?P<rule>[^\|\[]*)    # rule name
		(?:
			\[
			(?P<args>[^\]]+)  # rule arguments
			\]
		)?
		\|?                   # optional delimiter
	";

    public function __construct()
    {
		require_once APPPATH.'libraries/template_router/Converters.php';
		$this->rules = new EE_Template_router_converters();
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
		$rules = $segment['rules'];
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
			if (empty($this->rules->converters[$matches['rule']]))
			{
				throw new Exception("Converter not found: $rule");
			}
			$matches['args'] = empty($matches['args']) ? null : $matches['args'];
			$rule = $this->rules->converters[$matches['rule']]->regex($matches['args']);
			// Place each rule inside an anchored lookahead,
			// this will match the entire string if the rule matches.
			// This allows rules to work together without consuming the match.
			$parsed_rules[] = "(^(?={$rule}$).*)";
			$pos += strlen($matches[0]);
		}
		return $parsed_rules;
    }

}
// END CLASS

/* End of file Router.php */
/* Location: ./system/expressionengine/libraries/template_router/Router.php */