<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Menu;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Menu Set Model
 */
class MenuSet extends Model {

	protected static $_primary_key = 'set_id';
	protected static $_table_name = 'menu_sets';

	protected static $_validation_rules = array(
		'name' => 'required|noHtml|unique'
	);

	protected static $_relationships = array(
		'Items' => array(
			'model' => 'MenuItem',
			'type' => 'HasMany'
		),
		'MemberGroups' => array(
			'model' => 'MemberGroup',
			'type' => 'HasMany',
			'to_key' => 'menu_set_id',
			'weak' => TRUE
		),
	);

	protected $set_id;
	protected $name;

}
