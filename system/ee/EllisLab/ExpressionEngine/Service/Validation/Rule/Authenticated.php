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
		ee()->load->library('auth');
		$validate = ee()->auth->authenticate_id(
			ee()->session->userdata('member_id'),
			$password
		);

		return ($validate !== FALSE) ? TRUE : $this->stop();
	}

	public function getLanguageKey()
	{
		return 'auth_password';
	}
}

// EOF
