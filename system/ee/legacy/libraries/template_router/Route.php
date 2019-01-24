<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Route
 */
class EE_Route {

	public $segments = array();
	public $variables = array();
	public $subpatterns = array();

	public $segment_regex = "
		(\/|
		(?P<static>[^{\/]*)                         # static rule data
		({
		(?P<variable>[^}:]*)                      # variable name
		(?:
			\:                                    # variable delimiter
			(?P<rules>.*?(regex\[\(.*?\)\])?.*?)  # rules
		)?
		})?)
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

	/**
	 * Route constructor
	 *
	 * @param string $route   The EE formatted route string
	 * @param bool $required  Set whether route segments are optional or required
	 * @access public
	 * @return void
	 */
	public function __construct($route, $required = FALSE)
	{
		require_once BASEPATH.'libraries/template_router/Part.php';
		require_once BASEPATH.'libraries/template_router/Segment.php';
		require_once BASEPATH.'libraries/template_router/Converters.php';
		ee()->lang->loadfile('template_router');
		$this->required = $required;
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
		$map = array_flip($this->subpatterns);

		foreach ($variables as $key => $val)
		{
			if( ! empty($map[$key])) {
				$hash = $map[$key];
				$this->variables[$hash]->set($hash, $val);
			}
		}

		foreach($this->segments as $segment)
		{
			if ($segment->hasValue())
			{
				$url[] =  urlencode($segment->value());
			}
		}

		return '/' . implode('/', $url);
	}

	/**
	 * Compile the route to a regular expression used for matching.
	 *
	 * @access public
	 * @return string  The compiled regular expression.
	 */
	public function compile()
	{
		$url = array();
		$index = 0;

		foreach($this->segments as $segment)
		{
			$regex = $segment->regex();

			$add_question_mark = ( ! $this->required);

			if (is_object($segment) && empty($segment->parts))
			{
			    $add_question_mark = FALSE;
			}

			if ($index < count($this->segments) - 1)
			{
			    $regex .= '\/';
			}

			if ($add_question_mark)
			{
			    $regex .= '?';
			}

			$url[] = $regex;
			$index++;
		}

		$parsed_route = implode('', $url);

		// anchor the beginning and end, and add optional trailing slash
		return "^{$parsed_route}\/?$";
	}

	/**
	 * Checks for equivalence, matches segment by segment
	 *
	 * @param string  EE formatted template route
	 * @access public
	 * @return bool  Returns True if routes are equivalent
	 */
	public function equals(EE_Route $route)
	{
		if(count($this->segments) != count($route->segments))
		{
			return FALSE;
		}

		foreach($this->segments as $index => $segment)
		{
			$comparison = $route->segments[$index];

			if ($comparison->static !== $segment->static)
			{
				return FALSE;
			}

			foreach ($segment->parts as $part_index => $part)
			{
				$comparison_part = $comparison->parts[$part_index];
				$part_rules = array_map('serialize', $part->rules);
				$comparison_rules = array_map('serialize', $comparison_part->rules);
				$diff = array_diff($part_rules, $comparison_rules);
				$comparison_diff = array_diff($comparison_rules, $part_rules);

				if( ! (empty($diff) && empty($comparison_diff)))
				{
					return FALSE;
				}
			}
		}

		return TRUE;
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
		// Make sure we have a trailing slash so segments parse correctly
		$route = trim($route, '/ ');
		$route = $route . '/';

		// Check for xss
		if ($route !== ee('Security/XSS')->clean($route))
		{
			throw new Exception(lang('invalid_route'));
		}

		$segments = $this->parse_segments($route);
		$index = 0;

		foreach ($segments as $segment)
		{
			$this->segments[$index] = $segment;

			foreach($segment->parts as $part)
			{
				$this->variables[$part->name] =& $this->segments[$index];
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
	 *			- variable : Segment's variable name
	 *			- rules : Segment's list of validators
	 *			- static : Bare segment string, only set if segment is static text
	 */
	public function parse_segments($route)
	{
		$pos = 0;
		$end = strlen($route);
		$used_names = array();
		$static = '';
		$variables = array();

		while ($pos < $end)
		{
			$result = preg_match("/{$this->segment_regex}/ix", $route, $matches, 0, $pos);

			if(empty($matches[0]))
			{
				break;
			}

			if ($result == 0)
			{
				break;
			}

			if ($matches[0] == '/')
			{
				$segments[] = new EE_Route_segment($static, $variables);
				$static = '';
				$variables = array();
			};

			if ( ! empty($matches['static']))
			{
				$static .= $matches['static'];
			}

			if ( ! empty($matches['variable']))
			{
				$variable = $matches['variable'];

				if (preg_match("/^[a-zA-Z0-9_\-]*$/ix", $variable))
				{
					// Subpattern names must be alpha numeric, start with a
					// non-digit and be less than 32 character long.
					// SHA1 in base36 = 31 characters + 1 character prefix
					$hash = 'e' . base_convert(sha1($variable), 16, 36);
					$this->subpatterns[$hash] = $variable;
					$static .= $hash;
				}
				else
				{
					throw new Exception(lang('invalid_variable') . $variable);
				}

				if (empty($matches['rules']))
				{
					// Segment variable with no rules should be equivalent to alpha-dash
					$rules = array($this->rules->load('alpha_dash'));
				}
				else
				{
					$rules = $this->parse_rules($matches['rules']);
				}

				if (in_array($hash, $used_names))
				{
					throw new Exception(lang('variable_in_use') . $variable);
				}

				$used_names[] = $hash;
				$variables[$hash] = new EE_Route_segment_part($hash, $rules);
			}

			$pos += strlen($matches[0]);
		}
		if ($pos < $end)
		{
			$remainder = substr($route, $pos);

			if ( (strpos($remainder, '{') === FALSE && strpos($remainder, '}')) === FALSE)
			{
				// Using entity so error msg displays correctly
				$route = str_replace('/', '&#47;', $route);
				throw new Exception(lang('invalid_route') . $route);
			}

			$segments[] = array('static' => $remainder);
		}

		return $segments;
	}

	/**
	 * Parse a URL segment for a list of validators and convert to a regular expression
	 *
	 * @param $rules string  An EE formatted validation string e.g.:
	 *						   "rule1[arg1,arg2...]|rule2|..."
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

				while ($valid === FALSE)
				{
					$regex .= substr($rules, $index, 1);
					$valid = @preg_match("/$regex/", null);
					$index++;

					if($end < $index)
					{
						throw new Exception(lang('invalid_regex'));
					}
				}

				$matches[0] = "regex[{$regex}]|";
				$matches['args'] = $regex;
				$args[] = $regex;
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

// EOF
