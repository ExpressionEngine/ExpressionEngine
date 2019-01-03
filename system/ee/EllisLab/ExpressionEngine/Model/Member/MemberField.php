<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Member;

use EllisLab\ExpressionEngine\Model\Content\FieldModel;

/**
 * Member Field Model
 */
class MemberField extends FieldModel {

	protected static $_primary_key = 'm_field_id';
	protected static $_table_name = 'member_fields';

	protected static $_hook_id = 'member_field';

	protected static $_events = array(
		'afterSave',
		'beforeInsert'
	);

	protected static $_validation_rules = array(
		'm_field_type'        => 'required|enum[text,textarea,select,date,url]',
		'm_field_label'       => 'required|xss|noHtml|maxLength[50]',
		'm_field_name'        => 'required|alphaDash|unique|validateNameIsNotReserved|maxLength[32]',
		'm_legacy_field_data' => 'enum[y,n]'
	);

	protected static $_typed_columns = array(
		'm_field_settings'          => 'json',
		'm_field_exclude_from_anon' => 'boolString',
		'm_legacy_field_data'       => 'boolString',
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
	protected $m_field_exclude_from_anon;
	protected $m_field_order;
	protected $m_field_text_direction;
	protected $m_field_settings;
	protected $m_legacy_field_data;

	public function getSettingsValues()
	{
		$values = parent::getSettingsValues();

		$this->getField($values)->setFormat($this->getProperty('m_field_fmt'));

		$values['field_settings'] = $this->getProperty('m_field_settings') ?: array();

		$values['field_settings']['field_show_file_selector'] = 'n';

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

	public function set(array $data = array())
	{
		parent::set($data);

		$field = $this->getField($this->getSettingsValues());
		$this->setProperty('m_field_settings', $field->saveSettingsForm($data));

		return $this;
	}

	/**
	 * Clear MemberGroup member field cache
	 */
	public function onAfterSave()
	{
		ee()->session->set_cache('EllisLab::MemberGroupModel', 'getCustomFields', NULL);
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
			$count = $this->getModelFacade()->get('MemberField')->count();
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

	protected function getForeignKey()
	{
		return 'member_id';
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

	/**
	 * Validate the field name to avoid variable name collisions
	 */
	public function validateNameIsNotReserved($key, $value, $params, $rule)
	{
		if (in_array($value, ee()->cp->invalid_custom_field_names()))
		{
			return lang('reserved_word');
		}

		return TRUE;
	}

}

// EOF
