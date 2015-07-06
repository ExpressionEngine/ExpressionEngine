<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model;

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
 * ExpressionEngine Channel Field Model
 *
 * @package		ExpressionEngine
 * @subpackage	Category
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ChannelField extends FieldModel {

	protected static $_primary_key = 'field_id';
	protected static $_table_name = 'channel_fields';

	protected static $_typed_columns = array(
		'field_pre_populate'   => 'boolString',
		'field_pre_channel_id' => 'int',
		'field_pre_field_id'   => 'int',
		'field_ta_rows'        => 'int',
		'field_maxl'           => 'int',
		'field_required'       => 'boolString',
		'field_search'         => 'boolString',
		'field_is_hidden'      => 'boolString',
		'field_show_fmt'       => 'boolString',
		'field_order'          => 'int',
	);

	protected static $_relationships = array(
		'ChannelFieldGroup' => array(
			'weak' => TRUE,
			'type' => 'belongsTo'
		),
		'Channel' => array(
			'type' => 'belongsTo',
			'from_key' => 'group_id',
			'to_key' => 'field_group'
		),
	);

	protected static $_validation_rules = array(
		'site_id'              => 'required|integer',
		'group_id'             => 'required|integer',
		'field_name'           => 'required|unique[site_id]',
		'field_label'          => 'required',
		'field_list_items'     => 'required',
		'field_pre_populate'   => 'enum[y,n]',
		'field_pre_channel_id' => 'integer',
		'field_pre_field_id'   => 'integer',
		'field_ta_rows'        => 'integer',
		'field_maxl'           => 'integer',
		'field_required'       => 'enum[y,n]',
		'field_search'         => 'enum[y,n]',
		'field_is_hidden'      => 'enum[y,n]',
		'field_show_fmt'       => 'enum[y,n]',
		'field_order'          => 'integer',
	);

	protected $field_id;
	protected $site_id;
	protected $group_id;
	protected $field_name;
	protected $field_label;
	protected $field_instructions;
	protected $field_type;
	protected $field_list_items;
	protected $field_pre_populate;
	protected $field_pre_channel_id;
	protected $field_pre_field_id;
	protected $field_ta_rows;
	protected $field_maxl;
	protected $field_required;
	protected $field_text_direction;
	protected $field_search;
	protected $field_is_hidden;
	protected $field_fmt;
	protected $field_show_fmt;
	protected $field_order;
	protected $field_content_type;
	protected $field_settings;

	public function getStructure()
	{
		return $this->getChannelFieldGroup();
	}

	public function getDataTable()
	{
		return 'channel_data';
	}

	protected function getContentType()
	{
		return 'channel';
	}

	public function getSettingsValues()
	{
		$values = parent::getSettingsValues();

		$values['field_settings'] = $this->getProperty('field_settings') ?: array();

		return $values;
	}

	public function set__field_settings($settings)
	{
		$this->setRawProperty('field_settings', base64_encode(serialize($settings)));
	}

	public function get__field_settings()
	{
		return unserialize(base64_decode($this->field_settings));
	}

}
