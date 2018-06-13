<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * Authentication Rule
 */
class Authenticated extends ValidationRule {

	public function validate($key, $password)
	{
		$auth_timeout = in_array('useAuthTimeout', $this->parameters);

		if ($auth_timeout && ee('Session')->isWithinAuthTimeout())
		{
			ee('Session')->resetAuthTimeout();
			return TRUE;
		}

		ee()->load->library('auth');
		$validate = ee()->auth->authenticate_id(
			ee()->session->userdata('member_id'),
			$password
		);

		if ($validate !== FALSE && $auth_timeout)
		{
			ee('Session')->resetAuthTimeout();
		}

		return ($validate !== FALSE) ? TRUE : $this->stop();
	}

	public function getLanguageKey()
	{
		return 'auth_password';
	}
}

// EOF
