<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\FluidField\Model;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Model\Content\FieldData;

/**
 * ExpressionEngine Fluid Field Model
 */
class FluidField extends Model
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'fluid_field_data';

    protected static $_typed_columns = array(
        'fluid_field_id' => 'int',
        'entry_id' => 'int',
        'field_group_id' => 'int',
        'field_id' => 'int',
        'field_data_id' => 'int',
        'order' => 'int',
    );

    protected static $_relationships = array(
        'ChannelEntry' => array(
            'type' => 'belongsTo',
            'model' => 'ee:ChannelEntry',
            'weak' => true,
            'inverse' => array(
                'name' => 'FluidField',
                'type' => 'hasMany',
                'weak' => true
            )
        ),
        'ChannelField' => array(
            'type' => 'belongsTo',
            'model' => 'ee:ChannelField',
            'weak' => true,
            'inverse' => array(
                'name' => 'FluidField',
                'type' => 'hasMany',
                'weak' => true
            )
        ),
        'ChannelFieldGroup' => array(
            'type' => 'belongsTo',
            'from_key' => 'field_group_id',
            'model' => 'ee:ChannelFieldGroup',
            'weak' => true,
            'inverse' => array(
                'name' => 'FluidField',
                'type' => 'hasMany',
                'weak' => true
            )
        ),
        'FieldField' => array(
            'type' => 'belongsTo',
            'from_key' => 'fluid_field_id',
            'to_key' => 'field_id',
            'model' => 'ee:ChannelField',
            'weak' => true,
            'inverse' => array(
                'name' => 'FluidField',
                'type' => 'hasOne',
                'weak' => true
            )
        )
    );

    protected static $_events = array(
        'afterDelete'
    );

    protected $id;
    protected $fluid_field_id;
    protected $entry_id;
    protected $field_group_id;
    protected $field_id;
    protected $field_data_id;
    protected $order;
    protected $group;

    public function onAfterDelete()
    {
        $table = 'channel_data_field_' . $this->field_id;

        if (ee()->db->table_exists($table)) {
            ee()->db->where('id', $this->field_data_id);
            ee()->db->delete($table);
        }
    }

    protected function getSessionCacheKey()
    {
        return "ChannelField/{$this->field_id}/Data/{$this->field_data_id}";
    }

    public function setFieldData(array $data)
    {
        $field_data = ee('Model')->make('FieldData')->forField($this->ChannelField);
        $field_data->set($data);
        ee()->session->set_cache(__CLASS__, $this->getSessionCacheKey(), $field_data);

        return $field_data;
    }

    public function fetchFieldData()
    {
        if (ee()->extensions->active_hook('fluid_field_get_field_data') === true) {
            $rows = ee()->extensions->call(
                'fluid_field_get_field_data',
                $this->field_id,
                $this->field_data_id
            );
        } else {
            ee()->db->where('id', $this->field_data_id);
            $rows = ee()->db->get('channel_data_field_' . $this->field_id)->result_array();
        }

        if (! empty($rows)) {
            return $rows[0];
        }

        return array();
    }

    public function getFieldData()
    {
        if (($field_data = ee()->session->cache(__CLASS__, $this->getSessionCacheKey(), false)) === false) {
            $field_data = $this->setFieldData($this->fetchFieldData());
        }

        if (ee()->extensions->active_hook('fluid_field_get_all_data') === true) {
            $field_data = ee()->extensions->call(
                'fluid_field_get_all_data',
                $field_data,
                $this->fluid_field_id
            );
        }

        return $field_data;
    }

    public function getField(FieldData $field_data = null)
    {
        $field = $this->ChannelField->getField();
        $field->setContentId($this->entry_id);

        $field_data = ($field_data) ?: $this->getFieldData();

        $field->setData($field_data->getProperty('field_id_' . $this->field_id));

        if ($field_data->getProperty('field_ft_' . $this->field_id) !== null) {
            $format = $field_data->getProperty('field_ft_' . $this->field_id);

            // Need to set this property because it will override the
            // format on successive calls to `getField()`
            $this->ChannelField->field_fmt = $format;
            $field->setFormat($format);
        }

        if ($field_data->getProperty('field_dt_' . $this->field_id) !== null) {
            $format = $field_data->getProperty('field_dt_' . $this->field_id);
            $field->setTimezone($format);
        }

        $field->setName('content');
        $field->setItem('fluid_field_data_id', $this->getId());

        return $field;
    }
}

// EOF