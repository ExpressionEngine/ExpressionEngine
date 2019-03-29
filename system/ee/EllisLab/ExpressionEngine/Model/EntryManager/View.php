<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\EntryManager;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 *
 */
class View extends Model {

	protected static $_primary_key = 'view_id';
	protected static $_table_name = 'entry_manager_views';

	protected static $_typed_columns = [
		'view_id' => 'int',
		'name'    => 'string'
	];

	protected static $_relationships = [
		'Columns' => [
			'type'  => 'hasMany',
			'model' => 'EntryManagerViewColumn'
		]
	];

	protected static $_validation_rules = [
		'name' => 'required|unique'
	];

	protected $view_id;
	protected $name;
}

// EOF
