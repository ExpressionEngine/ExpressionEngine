<?php

namespace EllisLab\ExpressionEngine\Model\Addon;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Module Model
 *
 * @package		ExpressionEngine
 * @subpackage	Addon
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Module extends Model {

	protected static $_primary_key = 'module_id';
	protected static $_table_name = 'modules';

	protected static $_relationships = array(
		'AssignedModules' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'MemberGroup',
			'pivot' => array(
				'table' => 'module_member_groups'
			)
		),
		'UploadDestination' => array(
			'type' => 'hasMany'
		)
	);

	protected static $_typed_columns = array(
		'has_cp_backend'     => 'boolString',
		'has_publish_fields' => 'boolString',
	);

	protected static $_validation_rules = array(
		'has_cp_backend'     => 'enum[y,n]',
		'has_publish_fields' => 'enum[y,n]'
	);

	protected $module_id;
	protected $module_name;
	protected $module_version;
	protected $has_cp_backend;
	protected $has_publish_fields;
}

// EOF
