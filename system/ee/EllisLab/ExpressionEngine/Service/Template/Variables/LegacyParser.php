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

	public function getSingleVariable($tag, $prefix = '')
	{
		$field_info = array();

		$unprefixed_tag	= preg_replace('/^'.$prefix.'/', '', $tag);
		$field_name 	= substr($unprefixed_tag.' ', 0, strpos($unprefixed_tag.' ', ' '));
		$param_string	= substr($unprefixed_tag.' ', strlen($field_name));

		$modifier = '';
		$modifier_loc = strpos($field_name, ':');

		if ($modifier_loc !== FALSE)
		{
			$modifier = substr($field_name, $modifier_loc + 1);
			$field_name = substr($field_name, 0, $modifier_loc);
		}

		$field_info['field_name'] = $field_name;
		$field_info['params'] = (trim($param_string)) ? $this->assignTagParameters($param_string) : array();
		$field_info['modifier'] = $modifier;

		return $field_info;
	}

	public function assignTagParameters($param_string, array $defaults = [])
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
