<?php

namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Model\Content\FieldModel;

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
 * ExpressionEngine Category Field Model
 *
 * @package		ExpressionEngine
 * @subpackage	Category
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class CategoryField extends FieldModel {

	protected static $_primary_key = 'field_id';
	protected static $_table_name = 'category_fields';

	protected static $_hook_id = 'category_field';

	protected static $_typed_columns = array(
		'field_ta_rows'        => 'int',
		'field_maxl'           => 'int',
		'field_required'       => 'boolString',
		'field_show_fmt'       => 'boolString',
		'field_order'          => 'int',
	);

	protected static $_relationships = array(
		'CategoryGroup' => array(
			'type' => 'belongsTo'
		)
	);

	protected static $_events = array(
		'beforeInsert'
	);

	protected static $_validation_rules = array(
		'field_type'     => 'required|enum[text,textarea,select]',
		'field_label'    => 'required|xss|noHtml',
		'field_name'     => 'required|alphaDash|unique[site_id]',
		'field_ta_rows'  => 'integer',
		'field_maxl'     => 'integer',
		'field_required' => 'enum[y,n]',
		'field_show_fmt' => 'enum[y,n]',
		'field_order'    => 'integer',
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


	public function getSettingsValues()
	{
		$values = parent::getSettingsValues();

		$this->getField()->setFormat($this->getProperty('field_default_fmt'));
		$values['field_settings']['field_show_file_selector'] = 'n';

		return $values;
	}

	public function getContentType()
	{
		return 'category';
	}

	/**
	 * New fields get appended
	 */
	public function onBeforeInsert()
	{
		if ($this->getProperty('field_list_items') == NULL)
		{
			$this->setProperty('field_list_items', '');
		}

		$field_order = $this->getProperty('field_order');

		if (empty($field_order))
		{
			$count = $this->getFrontend()->get('CategoryField')
				->filter('group_id', $this->getProperty('group_id'))
				->count();
			$this->setProperty('field_order', $count + 1);
		}
	}

	/**
	 * Update field formatting on existing categories
	 *
	 * @return void
	 */
	public function updateFormattingOnExisting()
	{
		ee()->db->update(
			$this->getDataTable(),
			array('field_ft_'.$this->field_id => $this->field_default_fmt)
		);
	}

	public function getStructure()
	{
		return $this->getCategoryGroup();
	}

	public function getDataTable()
	{
		return 'category_field_data';
	}
}

// EOF
