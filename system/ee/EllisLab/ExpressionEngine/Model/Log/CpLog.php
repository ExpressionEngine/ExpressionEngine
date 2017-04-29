<?php

namespace EllisLab\ExpressionEngine\Model\Log;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * ExpressionEngine CP Log Model
 *
 * @package		ExpressionEngine
 * @subpackage	Log
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class CpLog extends Model {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'cp_log';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'belongsTo'
		),
		'Member'	=> array(
			'type' => 'belongsTo'
		)
	);

	protected static $_validation_rules = array(
		'ip_address'  => 'ip_address'
	);

	protected $id;
	protected $site_id;
	protected $member_id;
	protected $username;
	protected $ip_address;
	protected $act_date;
	protected $action;

}

// EOF
