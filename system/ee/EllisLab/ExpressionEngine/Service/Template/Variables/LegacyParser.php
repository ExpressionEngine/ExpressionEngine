<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Template\Variables;

/**
 * Legacy Variable Parsing Service
 * This modernizes our interface for a few common methods from legacy code
 */
class LegacyParser {

	/**
	 * Given a template variable string, this parses out parameters and modifiers
	 * e.g. {variable:modifier param1='foo' param2='bar'} returns:
	 *
	 * 		array(
	 * 			'field_name' => 'variable',
	 * 			'params' => array(
	 * 				'param1' => 'foo',
	 * 				'param2' => 'bar',
	 * 			),
	 * 			'modifier' => 'modifier'
	 * 		)
	 *
	 * Note: 'field_name' is used instead of just 'name' or 'variable_name' as this was
	 * originally a method of the legacy Channel Fields API, and the LegacyParser
	 * is a non-breaking API change
	 *
	 * @param  string $template_var Template variable to get the name, modifier, and parameters from
	 * @param  string $prefix Options prefix
	 * @return array Variable name, modifier, and parameters
	 */
	public function parseVariableProperties($template_var, $prefix = '')
	{
		$props = array();

		$unprefixed_var	= preg_replace('/^'.$prefix.'/', '', $template_var);
		$field_name 	= substr($unprefixed_var.' ', 0, strpos($unprefixed_var.' ', ' '));
		$param_string	= substr($unprefixed_var.' ', strlen($field_name));

		$modifier = '';
		$modifier_loc = strpos($field_name, ':');

		if ($modifier_loc !== FALSE)
		{
			$modifier = substr($field_name, $modifier_loc + 1);
			$field_name = substr($field_name, 0, $modifier_loc);
		}

		$props['field_name'] = $field_name;
		$props['params'] = (trim($param_string)) ? $this->parseTagParameters($param_string) : array();
		$props['modifier'] = $modifier;

		return $props;
	}

	/**
	 * Parse Tag Parameters
	 *
	 * @param  string $param_string A string of parameters, e.g. param1='foo' param2='bar'
	 * @param  array  $defaults     Optional default values
	 * @return array Parameters in key (parameter) => value form. FALSE when no parameters exist
	 */
	public function parseTagParameters($param_string, array $defaults = [])
	{
		if ($param_string == "")
		{
			return FALSE;
		}

		// remove comments before assigning
		$param_string = preg_replace("/\{!--.*?--\}/s", '', $param_string);

		// Using octals for quotes prevents awkward quote escaping for the PHP string and for the regex
		// \047 - Single quote octal
		// \042 - Double quote octal

		// matches[0] => attribute and value
		// matches[1] => attribute name
		// matches[2] => single or double quote
		// matches[3] => attribute value

		$bs = '\\'; // single backslash
		preg_match_all("/(\S+?)\s*=\s*($bs$bs?)(\042|\047)([^\\3]*?)\\2\\3/is", $param_string, $matches, PREG_SET_ORDER);

		if (count($matches) > 0)
		{
			$result = array();

			foreach($matches as $match)
			{
				$result[$match[1]] = (trim($match[4]) == '') ? $match[4] : trim($match[4]);
			}

			foreach ($defaults as $name => $default_value)
			{
				if ( ! isset($result[$name])
					OR (is_numeric($default_value) && ! is_numeric($result[$name])))
				{
					$result[$name] = $default_value;
				}
			}

			return $result;
		}

		return FALSE;
	}
}
// END CLASS

// EOF
