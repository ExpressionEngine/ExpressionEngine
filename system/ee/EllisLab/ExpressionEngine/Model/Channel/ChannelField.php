<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Channel;

use EllisLab\ExpressionEngine\Model\Content\FieldModel;
use EllisLab\ExpressionEngine\Service\Model\Collection;

/**
 * Channel Field Model
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
		'legacy_field_data'    => 'boolString',
	);

	protected static $_relationships = array(
		'ChannelFieldGroups' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'ChannelFieldGroup',
			'pivot' => array(
				'table' => 'channel_field_groups_fields'
			),
			'weak' => TRUE
		),
		'Channels' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Channel',
			'pivot' => array(
				'table' => 'channels_channel_fields'
			),
			'weak' => TRUE
		),
		'SearchExcerpts' => array(
			'type' => 'hasMany',
			'model' => 'Channel',
			'to_key' => 'search_excerpt',
			'weak' => TRUE
		),
	);

	protected static $_validation_rules = array(
		'site_id'              => 'required|integer',
		'field_name'           => 'required|alphaDash|unique|validateNameIsNotReserved|maxLength[32]',
		'field_label'          => 'required|maxLength[50]',
		'field_type'           => 'validateIsCompatibleWithPreviousValue',
	//	'field_list_items'     => 'required',
		'field_pre_populate'   => 'enum[y,n,v]',
		'field_pre_channel_id' => 'integer',
		'field_pre_field_id'   => 'integer',
		'field_ta_rows'        => 'integer',
		'field_maxl'           => 'integer',
		'field_required'       => 'enum[y,n]',
		'field_search'         => 'enum[y,n]',
		'field_is_hidden'      => 'enum[y,n]',
		'field_show_fmt'       => 'enum[y,n]',
		'field_order'          => 'integer',
		'legacy_field_data'    => 'enum[y,n]',
	);

	protected static $_events = array(
		'beforeInsert',
		'afterInsert',
		'beforeDelete',
	);

	protected $field_id;
	protected $site_id;
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
	protected $legacy_field_data;

	public function getStructure()
	{
		return $this->getChannelFieldGroups()->first();
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

		$this->field_order = $this->getModelFacade()->get('ChannelField')
			->filter('site_id', 'IN', array(0, $this->site_id))
			->count() + 1;
	}

	public function onAfterInsert()
	{
		parent::onAfterInsert();
		$this->addToLayouts();
	}

	public function onBeforeDelete()
	{
		$this->removeFromLayouts();
		$this->removeFromFluidFields();

		foreach ($this->SearchExcerpts as $channel)
		{
			$channel->search_excerpt = NULL;
			$channel->save();
		}
	}

	public function getAllChannels()
	{
		$channels = $this->Channels->indexByIds();

		foreach ($this->ChannelFieldGroups as $field_group)
		{
			foreach ($field_group->Channels as $channel)
			{
				$channels[$channel->getId()] = $channel;
			}
		}

		return new Collection($channels);
	}

	private function getRelatedChannelLayouts()
	{
		return $this->getModelFacade()->get('ChannelLayout')
			->filter('channel_id', $this->getAllChannels()->getIds())
			->all();
	}

	private function addToLayouts()
	{
		foreach ($this->getRelatedChannelLayouts() as $channel_layout)
		{
			$field_layout = $channel_layout->field_layout;
			$field_info = array(
				'field'     => 'field_id_' . $this->field_id,
				'visible'   => TRUE,
				'collapsed' => $this->getProperty('field_is_hidden')
			);
			$field_layout[0]['fields'][] = $field_info;

			$channel_layout->field_layout = $field_layout;
			$channel_layout->save();
		}
	}

	private function removeFromLayouts()
	{
		foreach ($this->getRelatedChannelLayouts() as $channel_layout)
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

	private function removeFromFluidFields()
	{
		$fluid_fields = $this->getModelFacade()->get('ChannelField')
			->filter('field_type', 'fluid_field')
			->all();

		if ( ! empty($fluid_fields))
		{
			// Bulk remove all pivot references to this field from all fluid fields
			// though: @TODO Model relationships should have taken care of this...
			$fluid_field_data = ee('Model')->get('fluid_field:FluidField')
				->filter('field_id', $this->getId())
				->delete();
		}

		foreach ($fluid_fields as $fluid_field)
		{
			if (in_array($this->getId(), $fluid_field->field_settings['field_channel_fields']))
			{
				$field_id = $this->getId();
				$settings = $fluid_field->field_settings;
				$settings['field_channel_fields'] = array_filter($settings['field_channel_fields'], function ($var) use($field_id){
					return ($var != $field_id);
				});
				$fluid_field->field_settings = $settings;
				$fluid_field->save();
			}
		}
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
