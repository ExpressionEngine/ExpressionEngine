<?php

namespace EllisLab\ExpressionEngine\Model\Menu;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.4
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Menu Item Model
 *
 * @package		ExpressionEngine
 * @subpackage	Session
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class MenuItem extends Model {

	protected static $_primary_key = 'item_id';
	protected static $_table_name = 'menu_items';

	protected static $_validation_rules = array(
		'type' => 'required|enum[link,addon,submenu]',
		'name' => 'validateWhenTypeIs[link,submenu]|noHtml|required',
		'data' => 'required'
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
