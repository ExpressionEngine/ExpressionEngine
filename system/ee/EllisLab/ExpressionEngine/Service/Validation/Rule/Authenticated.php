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
 * @since		Version 3.5.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Authentication Rule
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Rule
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
