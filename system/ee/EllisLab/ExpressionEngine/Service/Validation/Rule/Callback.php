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
 * ExpressionEngine Callback Validation Rule
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Rule
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Callback extends ValidationRule {

	protected $callback = NULL;
	protected $last_error = '';

	public function __construct($callback)
	{
		$this->callback = $callback;
	}

	public function validate($key, $value)
	{
		$result = call_user_func($this->callback, $key, $value, $this->parameters, $this);

		if ($result !== TRUE)
		{
			$this->last_error = $result;

			return FALSE;
		}

		return TRUE;
	}

	public function getLanguageKey()
	{
		return $this->last_error;
	}
}

// EOF
