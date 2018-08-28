<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Role;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * RoleSetting Model
 */
class RoleSetting extends Model {

	protected static $_primary_key = 'role_id';
	protected static $_table_name = 'role_settings';

	protected static $_typed_columns = [
		'role_id'                 => 'int',
		'is_locked'               => 'boolString',
		'exclude_from_moderation' => 'boolString',
		'include_in_authorlist'   => 'boolString',
		'include_in_memberlist'   => 'boolString',
	];

	protected static $_relationships = [
		'MenuSet' => array(
			'type' => 'belongsTo',
			'from_key' => 'menu_set_id'
		),
		'Role' => array(
			'type' => 'belongsTo',
		),
	];

	protected static $_validation_rules = [
		'role_id' => 'required',
	];

	// protected static $_events = [];

	// Properties
	protected $role_id;
	protected $site_id;
	protected $is_locked;
	protected $menu_set_id;
	protected $mbr_delete_notify_emails;
	protected $exclude_from_moderation;
	protected $search_flood_control;
	protected $prv_msg_send_limit;
	protected $prv_msg_storage_limit;
	protected $include_in_authorlist;
	protected $include_in_memberlist;
	protected $cp_homepage;
	protected $cp_homepage_channel;
	protected $cp_homepage_custom;

}

// EOF
