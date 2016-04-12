<?php

namespace EllisLab\ExpressionEngine\Model\Channel;

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
 * ExpressionEngine Channel Field Model
 *
 * @package		ExpressionEngine
 * @subpackage	Category
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class ChannelField extends FieldModel {

	protected static $_primary_key = 'field_id';
	protected static $_table_name = 'channel_fields';

	protected static $_hook_id = 'channel_field';

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
		'field_settings'       => 'base64Serialized',
	);

	protected static $_relationships = array(
		'ChannelFieldGroup' => array(
			'type' => 'belongsTo'
		),
		'Channel' => array(
			'type' => 'belongsTo',
			'from_key' => 'group_id',
			'to_key' => 'field_group',
			'weak' => TRUE
		),
	);

	protected static $_validation_rules = array(
		'site_id'              => 'required|integer',
//		'group_id'             => 'required|integer',
		'field_name'           => 'required|unique[site_id]|validateNameIsNotReserved',
		'field_label'          => 'required',
		'field_type'           => 'validateIsCompatibleWithPreviousValue',
	//	'field_list_items'     => 'required',
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

	protected static $_events = array(
		'beforeInsert',
		'afterInsert',
		'beforeDelete',
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

	public function getContentType()
	{
		return 'channel';
	}

	public function getSettingsValues()
	{
		$values = parent::getSettingsValues();

		$values['field_settings'] = $this->getProperty('field_settings') ?: array();

		return $values;
	}

	public function set(array $data = array())
	{
		parent::set($data);

		$field = $this->getField($this->getSettingsValues());
		$this->setProperty('field_settings', $field->saveSettingsForm($data));

		return $this;
	}

	public function onBeforeInsert()
	{
		if ($this->field_order)
		{
			return;
		}

		$this->field_order = $this->getFrontend()->get('ChannelField')
			->filter('group_id', $this->group_id)
			->filter('site_id', $this->site_id)
			->count() + 1;
	}

	public function onAfterInsert()
	{
		parent::onAfterInsert();

		foreach ($this->ChannelFieldGroup->Channels as $channel)
		{
			foreach ($channel->ChannelLayouts as $channel_layout)
			{
				$field_layout = $channel_layout->field_layout;
				$field_info = array(
					'field'     => 'field_id_' . $this->field_id,
					'visible'   => TRUE,
					'collapsed' => FALSE
				);
				$field_layout[0]['fields'][] = $field_info;

				$channel_layout->field_layout = $field_layout;
				$channel_layout->save();
			}
		}
	}

	public function onBeforeDelete()
	{
		foreach ($this->ChannelFieldGroup->Channels as $channel)
		{
			foreach ($channel->ChannelLayouts as $channel_layout)
			{
				$field_layout = $channel_layout->field_layout;

				foreach ($field_layout as $i => $section)
				{
					foreach ($section['fields'] as $j => $field_info)
					{
						if ($field_info['field'] == 'field_id_' . $this->field_id)
						{
							array_splice($field_layout[$i]['fields'], $j, 1);
							break 2;
						}
					}
				}

				$channel_layout->field_layout = $field_layout;
				$channel_layout->save();
			}
		}
	}

	public function getCompatibleFieldtypes()
	{
		$fieldtypes = array();
		$compatibility = array();

		foreach (ee('Addon')->installed() as $addon)
		{
			if ($addon->hasFieldtype())
			{
				foreach ($addon->get('fieldtypes', array()) as $fieldtype => $metadata)
				{
					if (isset($metadata['compatibility']))
					{
						$compatibility[$fieldtype] = $metadata['compatibility'];
					}
				}

				$fieldtypes = array_merge($fieldtypes, $addon->getFieldtypeNames());
			}
		}

		if ($this->field_type)
		{
			if ( ! isset($compatibility[$this->field_type]))
			{
				return array($this->field_type => $fieldtypes[$this->field_type]);
			}

			$my_type = $compatibility[$this->field_type];

			$compatible = array_filter($compatibility, function($v) use($my_type)
			{
				return $v == $my_type;
			});

			$fieldtypes = array_intersect_key($fieldtypes, $compatible);
		}

		asort($fieldtypes);

		return $fieldtypes;
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

	/**
	 * If this entity is not new (an edit) then we cannot change this entity's
	 * type to something incompatible with its initial type.
	 */
	public function validateIsCompatibleWithPreviousValue($key, $value, $params, $rule)
	{
		if ( ! $this->isNew() )
		{
			$previous_value = $this->getBackup('field_type');

			if ($previous_value)
			{
				$compatibility = $this->getCompatibleFieldtypes();

				// If what we are set to now is not compatible to what we were
				// set to before the change, then we are invalid.
				if ( ! isset($compatibility[$previous_value]))
				{
					// Reset it and return an error.
					$this->field_type = $previous_value;
					return lang('invalid_field_type');
				}
			}
		}

		return TRUE;
	}

}

// EOF
