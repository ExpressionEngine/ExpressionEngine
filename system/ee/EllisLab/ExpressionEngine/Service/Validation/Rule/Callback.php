<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * Callback Validation Rule
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
