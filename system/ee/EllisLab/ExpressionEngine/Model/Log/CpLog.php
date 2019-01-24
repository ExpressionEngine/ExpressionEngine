<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Log;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * CP Log Model
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
