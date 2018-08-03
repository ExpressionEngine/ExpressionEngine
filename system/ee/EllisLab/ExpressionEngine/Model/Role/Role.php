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
 * Role Model
 */
class Role extends Model {

	protected static $_primary_key = 'role_id';
	protected static $_table_name = 'roles';

	protected static $_typed_columns = [
		'role_id' => 'int',
	];

	protected static $_relationships = [
		'RoleGroups' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'RoleGroup',
			'pivot' => array(
				'table' => 'roles_role_groups'
			),
			'weak' => TRUE
		),
		'Members' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Member',
			'pivot' => array(
				'table' => 'members_roles'
			),
			'weak' => TRUE
		),
		'AssignedChannels' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Channel',
			'pivot' => array(
				'table' => 'channel_member_roles'
			)
		),
		'AssignedModules' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Module',
			'pivot' => array(
				'table' => 'module_member_roles'
			)
		),
		'AssingedStatuses' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Status',
			'pivot' => array(
				'table' => 'statuses_roles',
				'left' => 'role_id',
				'right' => 'status_id'
			)
		),
		'AssingedTemplates' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Template',
			'pivot' => array(
				'table' => 'templates_roles',
				'left' => 'role_id',
				'right' => 'template_id'
			)
		),
		'AssignedTemplateGroups' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'TemplateGroup',
			'pivot' => array(
				'table' => 'template_groups_roles',
				'left'  => 'role_id',
				'right' => 'template_group_id'
			)
		),
		'AssingedUploadDestinations' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'UploadDestination',
			'pivot' => array(
				'table' => 'upload_prefs_roles',
				'left' => 'role_id',
				'right' => 'upload_id'
			)
		),
	];

	protected static $_validation_rules = [
		'role_id' => 'required',
		'name'    => 'required',
	];

	// protected static $_events = [];

	// Properties
	protected $role_id;
	protected $name;
	protected $description;

}

// EOF
