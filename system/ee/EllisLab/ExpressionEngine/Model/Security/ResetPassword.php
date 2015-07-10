<?php

namespace EllisLab\ExpressionEngine\Model\Security;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Reset Password Model
 *
 * @package		ExpressionEngine
 * @subpackage	Security
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
