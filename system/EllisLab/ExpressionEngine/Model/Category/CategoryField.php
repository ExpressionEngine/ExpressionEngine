<?php

namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Model\Content\FieldModel;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Category Field Model
 *
 * @package		ExpressionEngine
 * @subpackage	Category
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class CategoryField extends FieldModel {

	protected static $_primary_key = 'field_id';
	protected static $_table_name = 'category_fields';

	protected static $_relationships = array(
		'CategoryGroup' => array(
			'type' => 'belongsTo'
		)
	);

	protected $field_id;
	protected $site_id;
	protected $group_id;
	protected $field_name;
	protected $field_label;
	protected $field_type;
	protected $field_list_items;
	protected $field_maxl;
	protected $field_ta_rows;
	protected $field_default_fmt;
	protected $field_show_fmt;
	protected $field_text_direction;
	protected $field_required;
	protected $field_order;

	public function getDataTable()
	{
		return 'category_field_data';
	}
}