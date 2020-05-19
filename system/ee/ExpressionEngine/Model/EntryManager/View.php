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
 *
 */
class View extends Model {

	protected static $_primary_key = 'view_id';
	protected static $_table_name = 'entry_manager_views';

	protected static $_typed_columns = [
		'view_id' => 'int',
		'channel_id' => 'int',
		'name'    => 'string'
	];

	protected static $_relationships = [
		'Columns' => [
			'type'  => 'hasMany',
			'model' => 'EntryManagerViewColumn'
		],
		'Roles' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Role',
			'pivot' => array(
				'table' => 'entry_manager_views_roles'
			),
			'weak' => TRUE
		),
		'Channels' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Channel',
			'pivot' => array(
				'table' => 'entry_manager_views_channels'
			),
			'weak' => TRUE
		),
	];

	protected static $_validation_rules = [
		'channel_id' => 'required',
		'name' => 'required|unique'
	];

	protected static $_events = array(
		'beforeSave'
	);

	protected $view_id;
	protected $channel_id;
	protected $name;

	public function onBeforeSave()
	{
		foreach (array('channel_url', 'channel_lang') as $column)
		{
			$value = $this->getProperty($column);

			if (empty($value))
			{
				$this->setProperty($column, '');
			}
		}
	}
}

// EOF
