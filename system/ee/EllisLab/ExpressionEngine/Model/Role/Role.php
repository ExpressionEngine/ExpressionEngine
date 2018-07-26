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
		'Members' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Member',
			'pivot' => array(
				'table' => 'members_roles'
			),
			'weak' => TRUE
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
