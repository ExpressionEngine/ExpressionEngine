<?php

namespace EllisLab\ExpressionEngine\Module\Member\Model;

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
 * ExpressionEngine Member Field Model
 *
 * @package		ExpressionEngine
 * @subpackage	Member
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class MemberField extends FieldModel {

	protected static $_primary_key = 'm_field_id';
	protected static $_table_name = 'member_fields';

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
	protected $m_field_order;

	public function getSettingsValues()
	{
		$values = parent::getValues();

		foreach ($values as $key => $value)
		{
			$values[str_replace('m_', '', $key)] =& $values[$key];
		}

		$values['field_show_fmt'] = $this->getProperty('m_field_fmt');
		$values['field_settings']['field_show_file_selector'] = 'n';

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
}
