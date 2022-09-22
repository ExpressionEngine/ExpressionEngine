<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Portage;

use StdClass;
use ZipArchive;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Model\Association\ToOne;
use ExpressionEngine\Service\Model\Association\ToMany;

/**
 * Portage Service: Export
 */
class PortageExport
{
    private $zip;

    private $site_id;
    private $portageData;
    private $path;
    private $modelFields = [];
    private $modelAssociations = [];
    private $modelKeyFields = [];

    private $channels = array();
    private $fields = array();
    private $field_groups = array();
    private $statuses = array();
    private $category_groups = array();
    private $upload_destinations = array();

    public function __construct()
    {
        if (! defined('JSON_PRETTY_PRINT')) {
            define('JSON_PRETTY_PRINT', 0);
        }
        $this->site_id = ee()->config->item('site_id');
    }

    /**
     * Export Portage to directory in user/cache folder
     *
     * @param array $elements
     * @return void
     */
    public function toDir($elements = [])
    {
        if (! is_dir(PATH_CACHE . 'portage/')) {
            ee('Filesystem')->mkdir(PATH_CACHE . 'portage/');
        }
        $this->path = PATH_CACHE . 'portage/export/';
        if (! is_dir($this->path)) {
            ee('Filesystem')->mkdir($this->path);
        }
        ee('Filesystem')->emptyDir($this->path, false);

        $this->portageData = new \StdClass();

        $this->portageData->uniqid = uniqid();
        $this->portageData->version = ee()->config->item('app_version');
        $this->portageData->components = [];

        // write add-ons and their versions
        if (empty($elements) || in_array('add-ons', $elements)) {
            $file = 'add-ons.json';
            $this->portageData->components[] = 'add-ons';
            $json = new \StdClass();
            $json->addons = [];
            $addons = ee('Addon')->installed();
            foreach ($addons as $addon => $info) {
                if ($info->get('built_in')) {
                    continue;
                }
                $record = new \StdClass();
                $record->name = $addon;
                $record->version = $info->getInstalledVersion();
                $json->records[] = $record;
            }
            ee('Filesystem')->write($this->path . $file, json_encode($json, JSON_PRETTY_PRINT));
        }

        // list all models, and grab those that have UUID - those are portable
        // for each model, find whether it has site or module restrictions
        $models = ee('App')->getModels();
        $portableModels = [];
        foreach ($models as $model => $class) {
            if ($model != 'ee:MemberGroup') {
                try {
                    $modelInstance = ee('Model')->make($model);
                    if ($modelInstance->hasNativeProperty('uuid')) {
                        $portableModels[$model] = [
                            'name' => $modelInstance->getName(),
                            'isPerSite' => $modelInstance->hasNativeProperty('site_id'),
                            'isPerModule' => $modelInstance->hasNativeProperty('module_id')
                        ];
                    }
                } catch (\Exception $e) {
                    //silently continue
                }
            }
            unset($modelInstance);
        }

        // write the individual model files
        foreach ($portableModels as $modelName => $modelData) {
            if (empty($elements) || in_array($modelName, $elements)) {

                $params = [];
                if ($modelData['isPerSite']) {
                    $params['site_id'] = [0, $this->site_id];
                }
                if ($modelData['isPerModule']) {
                    $params['module_id'] = 0;
                }
                $this->writeJsonForModel($modelName, $params);
            }
        }

        // write the main json file listing what we have in this portage
        $this->portageData->components = array_unique($this->portageData->components);
        ee('Filesystem')->write($this->path . 'portage.json', json_encode($this->portageData, JSON_PRETTY_PRINT));
    }

    /**
     * Write Portage JSON file for specific model
     *
     * @param [type] $model
     * @param array $filter
     * @return void
     */
    private function writeJsonForModel($model, $filter = [])
    {
        $modelRecords = ee('Model')->get($model);
        if (!empty($filter)) {
            foreach ($filter as $property => $value) {
                if (is_array($value)) {
                    $modelRecords->filter($property, 'IN', $value);
                } else {
                    $modelRecords->filter($property, $value);
                }
            }
        }
        $modelRecords = $modelRecords->all();
        if (count($modelRecords)) {
            $json = new \StdClass();
            $this->portageData->components[] = $model;
            $file = str_replace(':', '_', $model) . '.json';
            foreach ($modelRecords as $modelRecord) {
                $json->{$modelRecord->uuid} = $this->getDataFromModelRecord($model, $modelRecord);
            }

            ee('Filesystem')->write($this->path . $file, json_encode($json, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Collect the model data to be exported
     *
     * @param [type] $model model name
     * @param [type] $modelRecord model instance
     * @return object
     */
    public function getDataFromModelRecord($model, $modelRecord)
    {
        if (!isset($this->modelFields[$model])) {
            $this->modelFields[$model] = $modelRecord->getFields();
            $pk = $modelRecord->getPrimaryKey();
            $relationships = $modelRecord->getAllAssociations();
            $ownRelationships = $modelRecord->getMetaData('relationships');
            $this->modelKeyFields[$model] = [$pk];
            $this->modelAssociations[$model] = [
                'toOne' => [],
                'toMany' => []
            ];
            if (!empty($relationships)) {
                foreach ($relationships as $assocName => $relationship) {
                    if (! array_key_exists($assocName, $ownRelationships)) {
                        continue;
                    }
                    if ($relationship instanceof ToOne) {
                        if (!empty($relationship->getForeignKey())) {
                            $this->modelKeyFields[$model][] = $relationship->getForeignKey();
                        }
                        $this->modelAssociations[$model]['toOne'][] = $assocName;
                    } elseif ($relationship instanceof ToMany) {
                        $this->modelAssociations[$model]['toMany'][] = $assocName;
                    }
                }
            }
        }

        $record = new \StdClass();
        foreach ($this->modelFields[$model] as $property)
        {
            if (!in_array($property, $this->modelKeyFields[$model])) {
                $record->{$property} = $modelRecord->getRawProperty($property);
            }
        }
        $record->associationsByUuid = new \StdClass();
        if (!empty($this->modelAssociations[$model]['toOne'])) {
            foreach ($this->modelAssociations[$model]['toOne'] as $property) {
                if ($modelRecord->hasAssociation($property) && !is_null($modelRecord->{$property}) && $modelRecord->{$property}->hasNativeProperty('uuid')) {
                    $record->associationsByUuid->{$property} = new \StdClass();
                    $record->associationsByUuid->{$property}->model = $modelRecord->{$property}->getName();
                    $record->associationsByUuid->{$property}->related = $modelRecord->{$property}->uuid;
                }
            }
        }
        if (!empty($this->modelAssociations[$model]['toMany'])) {
            foreach ($this->modelAssociations[$model]['toMany'] as $property) {
                if ($modelRecord->hasAssociation($property) && isset($modelRecord->{$property}) && count($modelRecord->{$property})) {
                    $firstRecord = $modelRecord->{$property}->first();
                    if ($firstRecord->hasNativeProperty('uuid')) {
                        $record->associationsByUuid->{$property} = new \StdClass();
                        $record->associationsByUuid->{$property}->model = $firstRecord->getName();
                        $record->associationsByUuid->{$property}->related = $modelRecord->{$property}->pluck('uuid');
                    }
                }
            }
        }

        return $record;
    }

    /**
     * Export Portage to zip file
     *
     * @param array $elements
     * @return void
     */
    public function zip($elements = [])
    {
        // export to directory
        $this->toDir($elements);


        $this->zip = new ZipArchive();
        $location = PATH_CACHE . "portage/portage-" . $this->portageData->version . "-" . $this->portageData->uniqid . ".zip";

        $this->zip->open($location, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $this->zip->addFile($this->path . 'portage.json', 'portage.json');

        foreach ($this->portageData->components as $component) {
            $file = str_replace(':', '_', $component) . '.json';
            $this->zip->addFile($this->path . $file, $file);
        }

        $this->zip->close();

        return $location;
    }



    /**
     * Export a field
     *
     * @param Model $field Field to export
     * @param String $group Group name
     * @return void
     */
    private function exportField($field, $type = 'custom')
    {
        // already in process
        if (isset($this->fields[$field->getId()])) {
            return;
        }

        $this->fields[$field->getId()] = true;

        $file = '/' . $type . '_fields/' . $field->field_name . '.' . $field->field_type . '.json';

        $result = new StdClass();

        $result->label = $field->field_label;
        $result->order = $field->field_order;

        if ($field->hasProperty('field_instructions')) {
            $result->instructions = $field->field_instructions;
        }

        if ($field->field_required) {
            $result->required = 'y';
        }

        if ($field->hasProperty('field_search') && $field->field_search) {
            $result->search = 'y';
        }

        if ($field->hasProperty('field_is_hidden') && $field->field_is_hidden) {
            $result->is_hidden = 'y';
        }

        if (! $field->field_show_fmt) {
            $result->show_fmt = 'n';
        }

        if ($field->hasProperty('field_fmt') && $field->field_fmt != 'xhtml') {
            $result->fmt = $field->field_fmt;
        }

        if ($field->hasProperty('field_content_type') && $field->field_content_type != 'any') {
            $result->content_type = $field->field_content_type;
        }

        if ($field->field_list_items) {
            $result->list_items = explode("\n", trim($field->field_list_items));
        }

        if ($field->hasProperty('field_pre_populate')) {
            if ($field->field_pre_populate) {
                $result->pre_populate = 'y';
                $result->pre_channel_id = $field->field_pre_channel_id;
                $result->pre_field_id = $field->field_pre_field_id;
            } elseif (isset($field->field_settings) &&
                isset($field->field_settings['value_label_pairs']) &&
                ! empty($field->field_settings['value_label_pairs'])) {
                $result->pre_populate = 'v';
            }
        }

        if ($field->field_maxl && $field->field_maxl != 256) {
            $result->maxl = $field->field_maxl;
        }

        if ($field->field_text_direction && $field->field_text_direction != 'ltr') {
            $result->text_direction = $field->field_text_direction;
        }

        // fieldtype specific stuff
        // start by defining any that exist- then overwrite special cases
        if (isset($field->field_settings)) {
            $result->settings = $field->field_settings;
        }

        if ($field->field_type == 'file') {
            $result->settings = $this->exportFileFieldSettings($field);
        } elseif ($field->field_type == 'grid' || $field->field_type == 'file_grid') {
            $result->columns = $this->exportGridFieldColumns($field);
        } elseif ($field->field_type == 'relationship') {
            $result->settings = $this->exportRelationshipField($field);
        } elseif (in_array($field->field_type, array('textarea', 'rte'))) {
            $result->ta_rows = $field->field_ta_rows;
        } elseif ($field->field_type == 'fluid_field') {
            $result->settings = $this->exportFluidFieldField($field);
        }

        $field_json = json_encode($result, JSON_PRETTY_PRINT);

        $this->zip->addFromString($file, $field_json);
    }

    /**
     * Export an upload destination
     *
     * @param Integer $id Id of the destination (comes from the file field settings)
     * @return String Upload destination name
     */
    private function exportUploadDestination($id)
    {
        $dir = ee('Model')->get('UploadDestination', $id)->with('FileDimensions')->all()->first();

        $result = new StdClass();
        foreach ($dir->getFields() as $property)
        {
            $result->{$property} = $dir->{$property};
        }
        $result->name = $dir->name;

        $this->upload_destinations[$dir->name] = $result;

        return $result->name;
    }


    /**
     * Do some extra work for grid field exports
     *
     * @param Model $grid Channel field
     * @return [StdClass]() Array of grid columns
     */
    private function exportGridFieldColumns($grid)
    {
        ee()->load->model('grid_model');

        $columns = ee()->grid_model->get_columns_for_field($grid->getId(), $grid->getContentType());

        $result = array();

        foreach ($columns as $column) {
            if ($column['col_type'] == 'relationship') {
                // @TODO Actually export these things in a non-complicated manner
                $column['col_settings']['categories'] = array();
                $column['col_settings']['authors'] = array();
                $column['col_settings']['statuses'] = array();

                if (isset($column['col_settings']['channels'])) {
                    $this->exportRelatedChannels($column['col_settings']['channels']);
                    foreach ($column['col_settings']['channels'] as &$id) {
                        $channel = $this->channels[$id];
                        $id = $channel->channel_title;
                    }
                }
            } elseif ($column['col_type'] == 'file') {
                if ($column['col_settings']['allowed_directories'] != 'all') {
                    $this->exportUploadDestination($column['col_settings']['allowed_directories']);
                }
            }

            $col = new StdClass();

            unset(
                $column['col_id'],
                $column['col_order'],
                $column['field_id'],
                $column['content_type']
            );

            if ($column['col_width'] == 0) {
                unset($column['col_width']);
            }

            foreach ($column as $key => $value) {
                $simple_key = preg_replace('/^col_/', '', $key);
                $col->$simple_key = $value;
            }

            $result[] = $col;
        }

        return $result;
    }

    /**
     * Do some extra work for relationship field exports
     *
     * @param Model $field Channel field
     * @return StdClass Relationship settings description
     */
    private function exportRelationshipField($field)
    {
        $settings = $field->field_settings;

        $result = new StdClass();

        if ($settings['expired']) {
            $result->expired = 'y';
        }

        if ($settings['future']) {
            $result->future = 'y';
        }

        $result->allow_multiple = ($settings['allow_multiple']) ? 'y' : 'n';

        if ($settings['limit'] != 100) {
            $result->limit = $settings['limit'];
        }

        if ($settings['order_field'] != 'title') {
            $result->order_field = $settings['order_field'];
        }

        if ($settings['order_dir'] != 'asc') {
            $result->order_dir = $settings['order_dir'];
        }

        if (isset($settings['channels'])) {
            $this->exportRelatedChannels($settings['channels']);

            $result->channels = array();

            foreach ($settings['channels'] as $id) {
                $channel = $this->channels[$id];
                $result->channels[] = $channel->channel_title;
            }
        }

        return $result;
    }

    /**
     * Loops through an array of channels (by id) and exports any that have not
     * already been exported
     *
     * @param Array $channels an array of channel ids
     * @return void
     */
    private function exportRelatedChannels($channels)
    {
        $load_channels = array();

        foreach ($channels as $id) {
            if (! isset($this->channels[$id])) {
                $load_channels[] = $id;
            }
        }

        if (! empty($load_channels)) {
            $channels = ee('Model')->get('Channel', $load_channels)->all();

            foreach ($channels as $channel) {
                $this->exportChannel($channel);
            }
        }
    }

    /**
     * Does some extra work for fluid field field exports
     *
     * @param Model $field Channel field
     * @return StdClass Fluid Field settings description
     */
    private function exportFluidFieldField($field)
    {
        $settings = $field->field_settings;

        $result = new StdClass();
        $result->field_channel_fields = array();

        foreach ($settings['field_channel_fields'] as $field_id) {
            $field = ee('Model')->get('ChannelField', $field_id)->first();

            // In case there is no field.
            if ($field) {
                $result->field_channel_fields[] = $field->field_name;
                $this->exportField($field);
            }
        }

        return $result;
    }
}
