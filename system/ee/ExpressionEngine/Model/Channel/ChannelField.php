<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Channel;

use ExpressionEngine\Model\Content\FieldModel;
use ExpressionEngine\Service\Model\Collection;

/**
 * Channel Field Model
 */
class ChannelField extends FieldModel
{
    protected static $_primary_key = 'field_id';
    protected static $_table_name = 'channel_fields';

    protected static $_hook_id = 'channel_field';

    protected static $_typed_columns = array(
        'field_pre_populate' => 'boolString',
        'field_pre_channel_id' => 'int',
        'field_pre_field_id' => 'int',
        'field_ta_rows' => 'int',
        'field_maxl' => 'int',
        'field_required' => 'boolString',
        'field_search' => 'boolString',
        'field_is_hidden' => 'boolString',
        'field_is_conditional' => 'boolString',
        'field_show_fmt' => 'boolString',
        'field_order' => 'int',
        'field_settings' => 'base64Serialized',
        'legacy_field_data' => 'boolString',
    );

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'ChannelFieldGroups' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'ChannelFieldGroup',
            'pivot' => array(
                'table' => 'channel_field_groups_fields'
            ),
            'weak' => true
        ),
        'Channels' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Channel',
            'pivot' => array(
                'table' => 'channels_channel_fields'
            ),
            'weak' => true
        ),
        'SearchExcerpts' => array(
            'type' => 'hasMany',
            'model' => 'Channel',
            'to_key' => 'search_excerpt',
            'weak' => true
        ),
        'FieldConditionSets' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'FieldConditionSet',
            'pivot' => array(
                'table' => 'field_condition_sets_channel_fields'
            )
        ),
        'ChannelEntriesHiding' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'ChannelEntry',
            'pivot' => array(
                'table' => 'channel_entry_hidden_fields'
            )
        ),
        'UsesFieldConditions' => array(
            'type' => 'hasMany',
            'model' => 'FieldCondition',
            'from_key' => 'field_id',
            'to_key' => 'condition_field_id'
        ),
        'GridColumns' => array(
            'type' => 'hasMany',
            'model' => 'grid:GridColumn',
            'from_key' => 'field_id',
            'to_key' => 'field_id'
        )
    );

    protected static $_validation_rules = array(
        'site_id' => 'required|integer',
        'field_name' => 'required|alphaDash|unique|validateNameIsNotReserved|maxLength[32]|validateUniqueAmongFieldGroups',
        'field_label' => 'required|xss|noHtml|maxLength[50]',
        'field_type' => 'validateIsCompatibleWithPreviousValue',
        //	'field_list_items'     => 'required',
        'field_pre_populate' => 'enum[y,n,v]',
        'field_pre_channel_id' => 'integer',
        'field_pre_field_id' => 'integer',
        'field_ta_rows' => 'integer',
        'field_maxl' => 'integer',
        'field_required' => 'enum[y,n]',
        'field_search' => 'enum[y,n]',
        'field_is_hidden' => 'enum[y,n]',
        'field_is_conditional' => 'enum[y,n]',
        'field_show_fmt' => 'enum[y,n]',
        'field_order' => 'integer',
        'legacy_field_data' => 'enum[y,n]',
        'enable_frontedit' => 'enum[y,n]',
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
    protected $field_is_conditional;
    protected $field_fmt;
    protected $field_show_fmt;
    protected $field_order;
    protected $field_content_type;
    protected $field_settings;
    protected $legacy_field_data;
    protected $enable_frontedit = 'y';

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

        $old_field_type = $this->getBackup('field_type');
        if (! empty($old_field_type) && $old_field_type != $this->getFieldType()) {
            $field_settings = array();
        } else {
            $field_settings =$this->getSettingsValues();
        }

        $field = $this->getField($field_settings);
        $this->setProperty('field_settings', $field->saveSettingsForm($data));

        return $this;
    }

    public function onBeforeInsert()
    {
        if ($this->getProperty('field_list_items') == null) {
            $this->setProperty('field_list_items', '');
        }

        $field_order = $this->getProperty('field_order');

        if (empty($field_order)) {
            $field_order = $this->getModelFacade()->get('ChannelField')
                ->filter('site_id', 'IN', array(0, $this->site_id))
                ->count() + 1;
            $this->setProperty('field_order', $field_order);
        }
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
        $this->removeOrphanFieldConditionSets();

        foreach ($this->SearchExcerpts as $channel) {
            $channel->search_excerpt = null;
            $channel->save();
        }
    }

    /**
     * Removes condition sets that are not assigned anywhere else
     * At this point, the record in pivot table has already been removed, so we need to loop through all of those
     *
     * @return void
     */
    private function removeOrphanFieldConditionSets()
    {
        ee('Model')->get('FieldConditionSet')->with('ChannelFields')->filter('ChannelFields.field_id', null)->delete();
    }

    public function getAllChannels()
    {
        $channels = $this->Channels->indexByIds();

        foreach ($this->ChannelFieldGroups as $field_group) {
            foreach ($field_group->Channels as $channel) {
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
        foreach ($this->getRelatedChannelLayouts() as $channel_layout) {
            $field_layout = $channel_layout->field_layout;
            $field_info = array(
                'field' => 'field_id_' . $this->field_id,
                'visible' => true,
                'collapsed' => $this->getProperty('field_is_hidden')
            );
            $field_layout[0]['fields'][] = $field_info;

            $channel_layout->field_layout = $field_layout;
            $channel_layout->save();
        }
    }

    private function removeFromLayouts()
    {
        foreach ($this->getRelatedChannelLayouts() as $channel_layout) {
            $field_layout = $channel_layout->field_layout;

            foreach ($field_layout as $i => $section) {
                foreach ($section['fields'] as $j => $field_info) {
                    if ($field_info['field'] == 'field_id_' . $this->field_id) {
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

        if (! empty($fluid_fields)) {
            // Bulk remove all pivot references to this field from all fluid fields
            // though: @TODO Model relationships should have taken care of this...
            $fluid_field_data = ee('Model')->get('fluid_field:FluidField')
                ->filter('field_id', $this->getId())
                ->delete();
        }

        foreach ($fluid_fields as $fluid_field) {
            if (in_array($this->getId(), $fluid_field->field_settings['field_channel_fields'])) {
                $field_id = $this->getId();
                $settings = $fluid_field->field_settings;
                $settings['field_channel_fields'] = array_filter($settings['field_channel_fields'], function ($var) use ($field_id) {
                    return ($var != $field_id);
                });
                $fluid_field->field_settings = $settings;
                $fluid_field->save();
            }
        }
    }

    /**
     * The field name must not intersect witch Field Group short names
     *
     */
    public function validateUniqueAmongFieldGroups($key, $value, array $params = array())
    {
        // Check to see if we can find a channel field that matches the short name
        $channelFieldGroups = $this->getModelFacade()
            ->get('ChannelFieldGroup')
            ->filter('short_name', $value);

        // Make sure group short name is unique among channel fields
        foreach ($params as $field) {
            $channelFieldGroups->filter($field, $this->getProperty($field));
        }

        // If there are any matches, return the lang key of the error
        if ($channelFieldGroups->count() > 0) {
            return 'unique_among_field_groups';
        }

        // check member fields
        $unique = $this->getModelFacade()
            ->get('MemberField')
            ->filter('m_' . $key, $value);

        foreach ($params as $field) {
            $unique->filter('m_' . $field, $this->getProperty($field));
        }

        if ($unique->count() > 0) {
            return 'unique_among_member_fields'; // lang key
        }

        return true;
    }

    /**
     * If this entity is not new (an edit) then we cannot change this entity's
     * type to something incompatible with its initial type.
     */
    public function validateIsCompatibleWithPreviousValue($key, $value, $params, $rule)
    {
        if (! $this->isNew()) {
            $previous_value = $this->getBackup('field_type');

            if ($previous_value) {
                $compatibility = $this->getCompatibleFieldtypes();

                // If what we are set to now is not compatible to what we were
                // set to before the change, then we are invalid.
                if (! isset($compatibility[$previous_value])) {
                    // Reset it and return an error.
                    $this->field_type = $previous_value;

                    return lang('invalid_field_type');
                }
            }
        }

        return true;
    }
}

// EOF
