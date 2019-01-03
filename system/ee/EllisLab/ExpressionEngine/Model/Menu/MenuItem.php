<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Menu;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Menu Item Model
 */
class MenuItem extends Model {

	protected static $_primary_key = 'item_id';
	protected static $_table_name = 'menu_items';

	protected static $_validation_rules = array(
		'type' => 'required|enum[link,addon,submenu]',
		'name' => 'validateWhenTypeIs[link,submenu]|noHtml|required',
		'data' => 'validateWhenTypeIs[link,addon]|required'
	);

	protected static $_relationships = array(
		'Set' => array(
			'model' => 'MenuSet',
			'type' => 'belongsTo'
		),
		'Children' => array(
			'model' => 'MenuItem',
			'type' => 'hasMany',
			'to_key' => 'parent_id'
		),
		'Parent' => array(
			'model' => 'MenuItem',
			'type' => 'belongsTo',
			'from_key' => 'parent_id'
		)
	);

	protected $item_id;
	protected $parent_id;
	protected $set_id;
	protected $name;
	protected $data;
	protected $type;
	protected $sort;

	public function validateWhenTypeIs($key, $value, $parameters, $rule)
	{
		$type = $this->getProperty('type');

		return in_array($type, $parameters) ? TRUE : $rule->skip();
	}

}
