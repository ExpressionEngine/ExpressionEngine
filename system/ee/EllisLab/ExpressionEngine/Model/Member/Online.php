<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Member;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Online Member
 */
class Online extends Model {

	protected static $_primary_key = 'online_id';
	protected static $_table_name = 'online_users';

	protected static $_relationships = [
		'Member' => [
			'type' => 'belongsTo'
		],
		'Site' => [
			'type' => 'belongsTo'
		]
	];

	protected $online_id;
	protected $site_id;
	protected $member_id;
	protected $in_forum;
	protected $name;
	protected $ip_address;
	protected $date;
	protected $anon;

}
// END CLASS

// EOF
