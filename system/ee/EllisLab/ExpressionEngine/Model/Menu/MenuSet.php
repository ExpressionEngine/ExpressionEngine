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
 * ExpressionEngine Menu Set Model
 *
 * @package		ExpressionEngine
 * @subpackage	Session
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
