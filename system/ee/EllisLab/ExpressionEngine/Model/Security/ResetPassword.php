<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Security;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Reset Password Model
 */
class ResetPassword extends Model {

	protected static $_primary_key = 'reset_id';
	protected static $_table_name = 'reset_password';

	protected static $_relationships = array(
		'Member'	=> array(
			'type' => 'belongsTo'
		)
	);

	protected $reset_id;
	protected $member_id;
	protected $resetcode;
	protected $date;
}

// EOF
