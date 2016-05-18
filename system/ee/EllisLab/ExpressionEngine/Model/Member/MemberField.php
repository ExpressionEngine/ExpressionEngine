<?php

namespace EllisLab\ExpressionEngine\Model\Member;

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
 * ExpressionEngine Member Field Model
 *
 * @package		ExpressionEngine
 * @subpackage	Member
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class MemberField extends FieldModel {

	protected static $_primary_key = 'm_field_id';
	protected static $_table_name = 'member_fields';

	protected static $_hook_id = 'member_field';

	protected static $_events = array(
		'beforeInsert'
	);

	protected static $_validation_rules = array(
		'm_field_type'  => 'required|enum[text,textarea,select]',
		'm_field_label' => 'required|xss|noHtml',
		'm_field_name'  => 'required|alphaDash|unique'
	);

	protected $m_field_id;
	protected $m_field_name;
	protected $m_field_label;
	protected $m_field_description;
	protected $m_field_type;
	protected $m_field_list_items;
	protected $m_field_ta_rows;
	protected $m_field_maxl;
	protected $m_field_width;
	protected $m_field_search;
	protected $m_field_required;
	protected $m_field_public;
	protected $m_field_reg;
	protected $m_field_cp_reg;
	protected $m_field_fmt;
	protected $m_field_show_fmt;
	protected $m_field_order;
	protected $m_field_text_direction;

	public function getSettingsValues()
	{
		$values = parent::getSettingsValues();
		$values['field_settings']['field_show_file_selector'] = 'n';

		foreach (array('field_list_items', 'field_ta_rows', 'field_maxl', 'field_show_fmt', 'field_text_direction') as $setting)
		{
			$values['field_settings'][$setting] = $this->getProperty('m_'.$setting);
		}

		$this->getField()->setFormat($this->getProperty('m_field_fmt'));

		return $values;
	}

	public function getValues()
	{
		$values = parent::getValues();

		foreach ($values as $key => $value)
		{
			$values[str_replace('m_', '', $key)] =& $values[$key];
		}

		return $values;
	}

	/**
	 * New fields get appended
	 */
	public function onBeforeInsert()
	{
		if ($this->getProperty('m_field_list_items') == NULL)
		{
			$this->setProperty('m_field_list_items', '');
		}

		$field_order = $this->getProperty('m_field_order');

		if (empty($field_order))
		{
			$count = $this->getFrontend()->get('MemberField')->count();
			$this->setProperty('m_field_order', $count + 1);
		}
	}

	public function getStructure()
	{
		return $this;
	}

	public function getContentType()
	{
		return 'member';
	}

	public function getDataTable()
	{
		return 'member_data';
	}

	protected function getFieldType()
	{
		return $this->m_field_type;
	}

	/**
	 * Override FieldModel method to set our custom table column prefix
	 */
	public function getColumnPrefix()
	{
		return 'm_';
	}

	/**
	 * Override the set method so we can auto-prefix our properties
	 *
	 * @param mixed $key
	 * @param mixed $value
	 * @access public
	 * @return void
	 */
	public function __set($key, $value)
	{
		parent::__set($this->prefix($key), $value);
	}

	/**
	 * Override the get method so we can auto-prefix our properties
	 *
	 * @param mixed $key
	 * @param mixed $value
	 * @access public
	 * @return void
	 */
	public function __get($key)
	{
		return parent::__get($this->prefix($key));
	}

	public function __isset($key)
	{
		return property_exists($this, $this->prefix($key));
	}

	private function prefix($key)
	{
		if (substr($key, 0, 2) !== 'm_')
		{
			$key = "m_" . $key;
		}

		return $key;
	}
}

// EOF
