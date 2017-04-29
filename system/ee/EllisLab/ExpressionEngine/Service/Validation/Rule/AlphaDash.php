<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * ExpressionEngine Alphabetical and Dashes Validation Rule
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Rule
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class AlphaDash extends ValidationRule {

	public function validate($key, $value)
	{
		return (bool) preg_match("/^([-a-z0-9_-])+$/i", $value);
	}

	public function getLanguageKey()
	{
		return 'alpha_dash';
	}
}

// EOF
