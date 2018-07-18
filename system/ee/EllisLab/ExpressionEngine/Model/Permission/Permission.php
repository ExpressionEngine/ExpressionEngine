<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Permission;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Permission Model
 */
class Permission extends Model {

	protected static $_primary_key = 'permission_id';
	protected static $_table_name = 'permissions';

	protected static $_typed_columns = [
		'permission_id' => 'int',
		'role_id'       => 'int',
		'site_id'       => 'int',
	];

	protected static $_relationships = [
		'MemberGroup' => [
			'type' => 'belongsTo'
		],
		'Site' => [
			'type' => 'belongsTo'
		]
	];

	protected static $_validation_rules = [
		'permission_id' => 'required',
		'role_id'       => 'required',
		'site_id'       => 'required',
		'permission'    => 'required',
	];

	protected static $_events = [];

	// Properties
	protected $permission_id;
	protected $role_id;
	protected $site_id;
	protected $permission;

}

// EOF
