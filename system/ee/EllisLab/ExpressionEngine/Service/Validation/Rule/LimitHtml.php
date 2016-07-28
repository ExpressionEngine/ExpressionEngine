<?php

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Limited HTML Validation Rule
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Rule
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class LimitHtml extends ValidationRule {

	public function validate($key, $value)
	{
		if (preg_match_all('/<(\w+)/', $value, $matches))
		{
			// There may be some regex to do this more efficiently
			foreach ($matches[1] as $tag)
			{
				if ( ! in_array($tag, $this->parameters))
				{
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	/**
	 * Return the language data for the validation error.
	 */
	public function getLanguageData()
	{
		$list = implode(', ', $this->parameters);
		return array($this->getName(), $list);
	}
}

// EOF
