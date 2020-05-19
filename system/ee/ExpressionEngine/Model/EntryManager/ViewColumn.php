<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\EntryManager;

use ExpressionEngine\Service\Model\Model;

/**
 * Entry Manager View Column Model
 */
class ViewColumn extends Model {

	protected static $_primary_key = 'column_view_id';
	protected static $_table_name = 'entry_manager_views_columns';

	protected static $_typed_columns = [
		'column_view_id' => 'int',
		'view_id'        => 'int',
		'identifier'     => 'string',
		'order'          => 'int'
	];

	protected static $_relationships = [
		'View' => [
			'type'  => 'belongsTo',
			'model' => 'EntryManagerView'
		]
	];

	protected static $_validation_rules = [
		'name'       => 'required|unique',
		'view_id'    => 'required',
		'identifier' => 'required',
		'order'      => 'required|isNatural'
	];

	protected $column_view_id;
	protected $view_id;
	protected $identifier;
	protected $order;
}

// EOF
