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
    private $portableModels;

    public function __construct()
    {
        if (! defined('JSON_PRETTY_PRINT')) {
            define('JSON_PRETTY_PRINT', 0);
        }
        $this->site_id = ee()->config->item('site_id');
    }
    /**
     * list all models, and grab those that have UUID - those are portable
     * for each model, find whether it has site or module restrictions
     *
     * @return array
     */
    public function getPortableModels()
    {
        $models = ee('App')->getModels();
        $portableModels = [];
        $excludedModels = ['ee:MemberGroup'];
        // if we have just one site on this install, do not include site model
        if (! bool_config_item('multiple_sites_enabled')) {
            $excludedModels[] = 'ee:Site';
        }
        foreach ($models as $model => $class) {
            if (! in_array($model, $excludedModels)) {
                try {
                    $modelInstance = ee('Model')->make($model);
                    $uuidField = method_exists($modelInstance, 'getColumnPrefix') ? $modelInstance->getColumnPrefix() . 'uuid' : 'uuid';
                    if ($modelInstance->hasNativeProperty($uuidField)) {
                        $portableModels[$model] = [
                            'name' => $modelInstance->getName(),
                            'uuidField' => $uuidField,
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
        return $this->portableModels = $portableModels;
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
                $json->addons[] = $record;
            }
            ee('Filesystem')->write($this->path . $file, json_encode($json, JSON_PRETTY_PRINT));
        }

        $this->getPortableModels();
        if (! empty($elements)) {
            $this->portableModels = array_filter($this->portableModels, function($model) use ($elements) {
                return in_array($model, $elements);
            }, ARRAY_FILTER_USE_KEY);
        }

        foreach ($this->portableModels as $modelName => $modelData) {
            $this->ensureUuidForModels($modelName, $modelData['uuidField']);
        }

        // write the individual model files
        foreach ($this->portableModels as $modelName => $modelData) {
            if (empty($elements) || in_array($modelName, $elements)) {

                $params = [];
                if ($modelData['isPerSite']) {
                    $params['site_id'] = [0, $this->site_id];
                }
                if ($modelData['isPerModule']) {
                    $params['module_id'] = 0;
                }
                $this->writeJsonForModel($modelName, $params, $modelData['uuidField']);
            }
        }

        // write the main json file listing what we have in this portage
        $this->portageData->components = array_unique($this->portageData->components);
        ee('Filesystem')->write($this->path . 'portage.json', json_encode($this->portageData, JSON_PRETTY_PRINT));
    }

    /**
     * Making sure all records have UUID
     *
     * @param string $model
     * @return void
     */
    private function ensureUuidForModels($model, $uuidField)
    {
        $modelRecords = ee('Model')->get($model)->filter($uuidField, 'IS', null)->all();
        if (count($modelRecords)) {
            foreach ($modelRecords as $modelRecord) {
                $uuid = $modelRecord->$uuidField;
                // make sure UUIDs are set
                if (is_null($uuid)) {
                    $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
                    $modelRecord->setRawProperty($uuidField, $uuid);
                    $modelRecord->save();
                }
            }
        }
    }

    /**
     * Write Portage JSON file for specific model
     *
     * @param [type] $model
     * @param array $filter
     * @return void
     */
    private function writeJsonForModel($model, $filter = [], $uuidField = 'uuid')
    {
        if (in_array($model, ['ee:ChannelField', 'grid:GridColumn'])) {
            ee()->legacy_api->instantiate('channel_fields');
        }

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
                $uuid = $modelRecord->$uuidField;
                // build the data
                $json->$uuid = $this->getDataFromModelRecord($model, $modelRecord);
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
    public function getDataFromModelRecord($model, $modelRecord, $uuidField = 'uuid')
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
                        //continue;
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
            if ($property == $this->modelKeyFields[$model][0]) {
                continue; // do not set primary key
            }
            if (! in_array($property, $this->modelKeyFields[$model])) {
                // set the regular model fields
                $record->{$property} = $modelRecord->getRawProperty($property);
                // force some fields
                if ($property == 'legacy_field_data') {
                    $record->{$property} = 'n';
                }
            } else {
                // set the relationships to 0, if not empty
                // actual relationships will be set later
                $record->{$property} = is_null($modelRecord->$property) ? null : 0;
            }
        }

        // remap field settings to use UUID for relationships
        if (in_array($model, ['ee:ChannelField', 'grid:GridColumn'])) {
            $typeProperty = $model == 'grid:GridColumn' ? 'col_type' : 'field_type';
            $settingsProperty = $model == 'grid:GridColumn' ? 'col_settings' : 'field_settings';
            $ftClassName = ee()->api_channel_fields->include_handler($modelRecord->$typeProperty);
            $reflection = new \ReflectionClass($ftClassName);
            $instance = $reflection->newInstanceWithoutConstructor();
            if (isset($instance->relationship_field_settings)) {
                // update each relationship settings to use UUID
                foreach ($instance->relationship_field_settings as $setting => $settingModel) {
                    // force including these models into portage
                    if (is_array($record->$settingsProperty[$setting])) {
                        $relatedUuids = [];
                        foreach ($record->$settingsProperty[$setting] as $relatedId) {
                            if (is_numeric($relatedId)) {
                                $relatedSettingModelRecord = ee('Model')->get($settingModel, (int) $relatedId)->first();
                                if (!is_null($relatedSettingModelRecord)) {
                                    $relatedUuidField = method_exists($relatedSettingModelRecord, 'getColumnPrefix') ? $relatedSettingModelRecord->getColumnPrefix() . 'uuid' : 'uuid';
                                    $relatedUuids[] = $relatedSettingModelRecord->$relatedUuidField;
                                }
                            }
                        }
                        $record->$settingsProperty[$setting] = $relatedUuids;
                    } else if (is_numeric($record->$settingsProperty[$setting])) {
                        $relatedSettingModelRecord = ee('Model')->get($settingModel, (int) $record->$settingsProperty[$setting])->first();
                        if (!is_null($relatedSettingModelRecord)) {
                            $relatedUuidField = method_exists($relatedSettingModelRecord, 'getColumnPrefix') ? $relatedSettingModelRecord->getColumnPrefix() . 'uuid' : 'uuid';
                            $record->$settingsProperty[$setting] = $relatedSettingModelRecord->$relatedUuidField;
                        }
                    }
                }
            }
        }

        // set the relationships
        $record->associationsByUuid = new \StdClass();
        if (!empty($this->modelAssociations[$model]['toOne'])) {
            foreach ($this->modelAssociations[$model]['toOne'] as $property) {
                if (strpos($property, ':') !== false) {
                    $thirdPartyAssoc = explode(':', $property);
                    $modelRecord->alias($property, $thirdPartyAssoc[1]);
                    $property = $thirdPartyAssoc[1];
                }
                if ($modelRecord->hasAssociation($property) && !is_null($modelRecord->{$property})) {
                    $relatedUuidField = method_exists($modelRecord->{$property}, 'getColumnPrefix') ? $modelRecord->{$property}->getColumnPrefix() . 'uuid' : 'uuid';
                    if ($modelRecord->{$property}->hasNativeProperty($relatedUuidField)) {
                        $relatedModelName = $modelRecord->{$property}->getName();
                        //if the relationship is not included in portage, set the field to null
                        if (! array_key_exists($relatedModelName, $this->portableModels)) {
                            $modelRecord->{$property} = null;
                            continue;
                        }
                        $record->associationsByUuid->{$property} = new \StdClass();
                        $record->associationsByUuid->{$property}->model = $relatedModelName;
                        $record->associationsByUuid->{$property}->related = $modelRecord->{$property}->{$uuidField};
                    }
                }
            }
        }
        if (!empty($this->modelAssociations[$model]['toMany'])) {
            foreach ($this->modelAssociations[$model]['toMany'] as $property) {
                if (strpos($property, ':') !== false) {
                    $thirdPartyAssoc = explode(':', $property);
                    $modelRecord->alias($property, $thirdPartyAssoc[1]);
                    $property = $thirdPartyAssoc[1];
                }
                if ($modelRecord->hasAssociation($property) && !is_null($modelRecord->{$property}) && count($modelRecord->{$property})) {
                    $firstRecord = $modelRecord->{$property}->first();
                    $relatedUuidField = method_exists($firstRecord, 'getColumnPrefix') ? $firstRecord->getColumnPrefix() . 'uuid' : 'uuid';
                    if ($firstRecord->hasNativeProperty($relatedUuidField)) {
                        $relatedModelName = $firstRecord->getName();
                        //if the relationship is not included in portage, set the field to null
                        if (! array_key_exists($relatedModelName, $this->portableModels)) {
                            $modelRecord->{$property} = null;
                            continue;
                        }
                        $record->associationsByUuid->{$property} = new \StdClass();
                        $record->associationsByUuid->{$property}->model = $relatedModelName;
                        $record->associationsByUuid->{$property}->related = $modelRecord->{$property}->pluck($uuidField);
                    }
                }
            }
        }

        // -------------------------------------------
        // 'portage_export_after_model_export' hook.
        //  - Modify the record for JSON
        //
        if (ee()->extensions->active_hook('portage_export_after_model_export') === true) {
            $record = ee()->extensions->call('portage_export_after_model_export', $record, $model, $modelRecord);
        }
        //
        // -------------------------------------------

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

}
