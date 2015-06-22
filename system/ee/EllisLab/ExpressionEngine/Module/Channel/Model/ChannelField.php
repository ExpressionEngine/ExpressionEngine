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

	protected static $_relationships = array(
		'ChannelFieldGroup' => array(
			'type' => 'belongsTo'
		),
		'Channel' => array(
			'type' => 'belongsTo',
			'from_key' => 'group_id',
			'to_key' => 'field_group'
		),
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

	public function set__field_settings($settings)
	{
		$this->setRawProperty('field_settings', base64_encode(serialize($settings)));
	}

	public function get__field_settings()
	{
		return unserialize(base64_decode($this->field_settings));
	}

}
