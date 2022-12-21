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

use ExpressionEngine\Model\Channel\ChannelField;
use ExpressionEngine\Addons\Grid\Model\GridColumn;
use ExpressionEngine\Model\File\UploadDestination;

/**
 * Portage Service: Portage
 */
class PortageImport
{
    const CACHE_KEY = '/portage-import';

    /**
     * @var Int Id of the site to import to
     */
    private $site_id = 1;

    /**
     * Base Portage info
     *
     * @var array / bool
     */
    private $base;

    /**
     * Components / models that can be ste as related as part of this portage
     *
     * @var array
     */
    private $components = array();

    /**
     * Valid elements / model instances that will be saved
     *
     * @var array
     */
    public $portageImportElements = array();

    /**
     * Instances that will be saved, grouped by model
     *
     * @var array
     */
    public $portageImportElementsByModel = array();

    /**
     * Existing elements / model instances in portable format
     *
     * @var array
     */
    public $existingElements = array();

    /**
     * Associations by UUID
     *
     * @var array
     */
    private $associations = array();

    /**
     * @var String containing the path to the channel set
     */
    private $path;

    /**
     * @var ImportResult containing the result of the import
     */
    private $result;

    /**
     * @var Array of things that would create duplicates and need to be renamed
     *
     * Looks like so:
     *		[model => [shortname] => [field_to_change => newvalue]]
     *
     * The shortname will always be the name as specified in the channel set
     * definition so that we can relate entities by name. The _original_ shortname
     * is the key on the above arrays. Tread carefully, in this class aliases should
     * never be used for identification. Do not trust `$model->shortname`.
     */
    private $aliases = array();

    // ID of log record
    private $importId;

    // EE version
    private $version;

    //Portage Uniqid
    private $uniqid;

    private $offset = 0;
    private $currentComponent;
    private $updating = false;

    public function __construct($site_id)
    {
        $this->site_id = $site_id;
        $this->result = new ImportResult();
    }

    /**
     * Create a set object from the contents of an item in the $_FILES array
     *
     * @param Array $upload Element in the $_FILES array
     * @return Portage Channel set object
     */
    public function zip(array $upload)
    {
        $location = $upload['tmp_name'];
        $name = $upload['name'];

        $dir = $this->extractZip($location, $name);
        $this->setPath($dir);

        return $this;
    }

    /**
     * Create a set object from a directory
     *
     * @param String $dir Path to the channel set directory
     * @return Portage Channel set object
     */
    public function dir($dir)
    {
        $this->setPath($dir);
        return $this;
    }

    /**
     * Set path to directory
     *
     * @return String Filesystem path to this set
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get path to directory
     *
     * @return String Filesystem path to this set
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get list of components to be imported
     *
     * @return array
     */
    public function getComponents()
    {
        return $this->components;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getUniqid()
    {
        return $this->uniqid;
    }

    /**
     * @return ImportResult containing the result of the import
     */
    public function getValidationResult()
    {
        return $this->result;
    }

    public function resetValidationResult()
    {
        $this->result = new ImportResult();
        $this->cache();
    }

    public function setImportId($importId)
    {
        $this->importId = $importId;
        $this->cache();
    }

    /**
     * Portage the site id
     *
     * @param Int Id of the site we're on
     * @return void
     */
    public function setSiteId($site_id)
    {
        $this->site_id = $site_id;
    }

    /**
     * Validate the set before import
     *
     * @return ImportResult
     */
    public function validate()
    {
        $this->base = $this->_checkJsonExistAndValid('portage.json');
        if ($this->base === false) {
            return $this->result;
        }

        //might be worth to allow skipping version checks for EE and addons?

        // can only import between same minor versions
        if (isset($this->base['version'])) {
            $version = explode('.', $this->base['version']);
            $app_version = explode('.', ee()->config->item('app_version'));
            if ($app_version[0] != $version[0] || $app_version[1] != $version[1]) {
                $this->result->addError(lang('portage_incompatible'));
                return $this->result;
            }
        }

        $this->components = $this->base['components'];
        $this->version = $this->base['version'];
        $this->uniqid = $this->base['uniqid'];

        //try to install / update missing addons
        if (false !== ($key = array_search('add-ons', $this->components))) {
            unset($this->components[$key]);
            $addonsNotCompatible = false;
            $json = $this->_checkJsonExistAndValid('add-ons.json');
            if ($json === false) {
                return $this->result;
            }
            foreach ($json['addons'] as $addonPortage) {
                $addon = ee('Addon')->get($addonPortage['name']);
                // we only check fieldtypes, but should we care about every add-on maybe?
                if (!$addon->hasFieldtype()) {
                    continue;
                }
                if (empty($addon)) {
                    $this->result->addError(sprintf(lang('portage_addon_missing'), $addonPortage['name']));
                    $addonsNotCompatible = true;
                    continue;
                }
                $version = explode('.', $addonPortage['version']);
                if (!$addon->isInstalled()) {
                    $this->result->addError(sprintf(lang('portage_addon_not_installed'), $addon->getName()));
                    $addonsNotCompatible = true;
                    continue;
                }
                $addonVersion = explode('.', $addon->getInstalledVersion());
                if ($addonVersion[0] != $version[0] || $addonVersion[1] != $version[1]) {
                    $this->result->addError(sprintf(lang('portage_addon_incompatible'), $addon->getName()));
                    $addonsNotCompatible = true;
                    continue;
                }
            }
            if ($addonsNotCompatible) {
                return $this->result;
            }

            // include component into list of things to import

        }

        $this->components = array_values($this->components); // update the keys
        foreach ($this->components as $model)
        {
            $file = str_replace(':', '_', $model) . '.json';
            $json = $this->_checkJsonExistAndValid($file);
            if ($json === false) {
                return $this->result;
            }
        }

        $this->cache();

        return $this->result;
    }

        /**
     * Take the zip and extract it to the cache path with the given file name.
     *
     * @param String $file_name name to use for the extracted directory
     * @return Portage Channel set importer instance
     */
    public function extractZip($location, $file_name)
    {
        $zip = new \ZipArchive();

        if ($zip->open($location) !== true) {
            throw new ImportException('Zip file not readable.');
        }

        $this->ensureNoPHP($zip);

        // create a temporary directory for the contents in our cache folder
        if (! is_dir(PATH_CACHE . 'portage/')) {
            ee('Filesystem')->mkdir(PATH_CACHE . 'portage/');
        }
        $tmp_dir = 'portage/tmp_' . ee('Encrypt')->generateKey();
        ee('Filesystem')->mkdir(PATH_CACHE . $tmp_dir, false);

        // extract the archive
        if ($zip->extractTo(PATH_CACHE . $tmp_dir) !== true) {
            throw new ImportException('Could not extract zip file.');
        }

        // Check for an identically named subfolder inside the extracted archive
        $new_path = PATH_CACHE . $tmp_dir . '/';

        if (is_dir($new_path . basename($file_name, '.zip'))) {
            $new_path .= basename($file_name, '.zip');
        }

        return $new_path;
    }

    /**
     * Ensure there are no PHP files inside the archive before we extract them
     * on to the server
     *
     * @param Resource $zip Opened ZipArchive file
     */
    protected function ensureNoPHP($zip)
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (stripos($zip->getNameIndex($i), '.php') !== false) {
                throw new ImportException('Cannot extract archive that contains PHP files.');
            }
        }
    }

    /**
     * Deletes the source files used in the import
     */
    public function cleanUpSourceFiles()
    {
        //ee('Filesystem')->delete($this->getPath());
    }

    /**
     * Consider this private. It's for relationship use only.
     */
    public function getIdsForChannels(array $titles)
    {
        $channels = array();

        foreach ($titles as $title) {
            if (isset($this->channels[$title])) {
                $channel = $this->channels[$title];
                $channels[$title] = $channel->getId();
            }
        }

        return $channels;
    }

    /**
     * Save all of the Portage entities
     *
     * @return void
     */
    public function saveComponent($component)
    {
        $this->loadCache();

        if (! isset($this->portageImportElementsByModel[$component]) || empty($this->portageImportElementsByModel[$component])) {
            return false;
        }

        // save everything - some relationships might still be missing
        foreach ($this->portageImportElementsByModel[$component] as $uuid)
        {
            $modelInstance = ee('Model')->make($component);
            $modelData = unserialize(ee('Filesystem')->read($this->path . 'runtime/' . $uuid));
            $modelInstance->fill($modelData['values']);

            // -------------------------------------------
            // 'portage_import_before_save' hook.
            //  - Modify the model before saving
            //
            if (ee()->extensions->active_hook('portage_import_before_save') === true) {
                $modelInstance = ee()->extensions->call('portage_import_before_save', $this, $uuid, $modelInstance);
            }
            //
            // -------------------------------------------

            // upload locations need to be created, if possible
            if ($modelInstance instanceof UploadDestination && $modelInstance->adapter == 'local') {
                $parsedServerPath = rtrim(parse_config_variables($modelInstance->server_path), '\\/') . DIRECTORY_SEPARATOR;
                if ((DIRECTORY_SEPARATOR == '/' && strpos($parsedServerPath, '/') === 0) || (DIRECTORY_SEPARATOR == '\\' && strpos($parsedServerPath, ':') === 1)) {
                    ee('Filesystem')->mkDir($parsedServerPath);
                }
            }

            // save the model, for the first time
            $modelInstance->save();

            // and write the modified model back into file
            ee('Filesystem')->write($this->path . 'runtime/' . $uuid, $modelInstance->serialize(), true);

        }
    }

    public function relateComponent($component)
    {
        $this->loadCache();

        if (! isset($this->portageImportElementsByModel[$component]) || empty($this->portageImportElementsByModel[$component])) {
            return false;
        }

        // always load this, as third-party extensions might be using it
        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');

        // now, set the relationships
        foreach ($this->portageImportElementsByModel[$component] as $uuid)
        {
            $modelInstance = ee('Model')->make($component);
            $modelData = unserialize(ee('Filesystem')->read($this->path . 'runtime/' . $uuid));
            $modelInstance->fill($modelData['values']);

            $uuidField = method_exists($modelInstance, 'getColumnPrefix') ? $modelInstance->getColumnPrefix() . 'uuid' : 'uuid';
            if (isset($this->associations[$uuid]) || $modelInstance instanceof ChannelField) {
                if (isset($this->associations[$uuid])) {
                    foreach ($this->associations[$uuid] as $relationship => $relatioshipData) {
                        //perhaps we need to add an alias
                        if (strpos($relatioshipData['model'], ':') !== false && substr($relatioshipData['model'], 0, 3) != 'ee:') {
                            $thirdPartyAssoc = explode(':', $relatioshipData['model']);
                            $modelInstance->alias($thirdPartyAssoc[0] . ':' . $relationship, $relationship);
                        }
                        // only if there are some data and the related models are included in portage
                        if (! empty($relatioshipData) && ! empty($relatioshipData['related']) && in_array($relatioshipData['model'], $this->components)) {
                            $relatedUuids = $relatioshipData['related'];
                            if (! is_array($relatedUuids)) {
                                $modelInstance->{$relationship} = ee('Model')->get($relatioshipData['model'])->filter($uuidField, $relatedUuids)->first();
                            } else {
                                $modelInstance->{$relationship} = ee('Model')->get($relatioshipData['model'])->filter($uuidField, 'IN', $relatedUuids)->all(); //is_array($related) ? new Collection($related) : $related;
                            }
                        }
                    }
                }
                // if this is fieldtype, convert UUIDs back to IDs
                if ($modelInstance instanceof ChannelField || $modelInstance instanceof GridColumn) {
                    $typeProperty = $modelInstance instanceof GridColumn ? 'col_type' : 'field_type';
                    $settingsProperty = $modelInstance instanceof GridColumn ? 'col_settings' : 'field_settings';
                    $ftClassName = ee()->api_channel_fields->include_handler($modelInstance->$typeProperty);
                    $modelInstance = $this->setFieldSettingsProperty($modelInstance, $typeProperty, $settingsProperty, $ftClassName);
                }

                // -------------------------------------------
                // 'portage_import_before_relationships_save' hook.
                //  - Modify the model before second round of saving (with relationships)
                //
                if (ee()->extensions->active_hook('portage_import_before_relationships_save') === true) {
                    $modelInstance = ee()->extensions->call('portage_import_before_relationships_save', $this, $uuid, $modelInstance);
                }
                //
                // -------------------------------------------

                // we need to ensure afterUpdate routines are being run
                if ($modelInstance instanceof ChannelField) {
                    $modelInstance->markAsDirty('uuid');
                }

                // save with all relationships
                $modelInstance->save();

                // Grids have an extra model/table that needs to be saved
                if ($modelInstance instanceof ChannelField && in_array($modelInstance->field_type, ['grid', 'file_grid'])) {
                    ee()->load->model('grid_model');
                    ee()->grid_model->create_field($modelInstance->getId(), 'channel');
                    $columns = ee('Model')->get('grid:GridColumn')->filter('field_id', $modelInstance->getId())->all();
                    foreach ($columns as $column)
                    {
                        $column = $column->toArray();
                        ee()->api_channel_fields->setup_handler($column['col_type']);
                        ee()->api_channel_fields->set_datatype(
                            $column['col_id'],
                            $column['col_settings'],
                            array(),
                            true,
                            false,
                            array(
                                'id_field' => 'col_id',
                                'type_field' => 'col_type',
                                'col_settings_method' => 'grid_settings_modify_column',
                                'col_prefix' => 'col',
                                'fields_table' => 'grid_columns',
                                'data_table' => 'channel_grid_field_' . $modelInstance->getId(),
                            )
                        );
                    }
                }

                // -------------------------------------------
                // 'portage_import_after_relationships_save' hook.
                //  - Do extra stuff after model import is complete
                //
                if (ee()->extensions->active_hook('portage_import_after_relationships_save') === true) {
                    ee()->extensions->call('portage_import_after_relationships_save', $this, $uuid, $modelInstance);
                }
                //
                // -------------------------------------------

            }
        }

        // clear caches
        ee()->functions->clear_caching('all');
        ee('CP/JumpMenu')->clearAllCaches();
    }

    /**
     * Set field / column settings property for fieldtypes
     *
     * @param object $modelInstance field/column model instance
     * @param string $typeProperty field/column type
     * @param string $settingsProperty name of settings property
     * @param string $ftClassName class name of fieldtype
     * @return void
     */
    public function setFieldSettingsProperty($modelInstance, $typeProperty, $settingsProperty, $ftClassName)
    {
        $reflection = new \ReflectionClass($ftClassName);
        $instance = $reflection->newInstanceWithoutConstructor();
        if (isset($instance->relationship_field_settings)) {
            $ftSettings = $modelInstance->$settingsProperty;
            foreach ($instance->relationship_field_settings as $setting => $settingModel) {
                // force including these models into portage
                if (is_array($ftSettings[$setting])) {
                    $relatedIds = [];
                    foreach ($ftSettings[$setting] as $relatedUuid) {
                        if (substr_count($relatedUuid, '-') == 4) { //looks like UUID
                            $relatedSettingModelRecord = ee('Model')->get($settingModel)->filter('uuid', $relatedUuid)->first();
                            if (!is_null($relatedSettingModelRecord)) {
                                $relatedIds[] = $relatedSettingModelRecord->getId();
                            }
                        }
                    }
                    $ftSettings[$setting] = $relatedIds;
                } else if (substr_count($ftSettings[$setting], '-') == 4) {//looks like UUID
                    $relatedSettingModelRecord = ee('Model')->get($settingModel)->filter('uuid', $ftSettings[$setting])->first();
                    if (!is_null($relatedSettingModelRecord)) {
                        $ftSettings[$setting] = $relatedSettingModelRecord->getId();
                    }
                }
            }
            $modelInstance->$settingsProperty = $ftSettings;
        }
        return $modelInstance;
    }

    /**
     * Portage manual overrides
     *
     * @return void
     */
    public function setAliases($aliases)
    {
        $this->aliases = $aliases;
        $this->cache();
    }

    public function loadComponent($model)
    {
        $this->loadCache();
        ee('PortageExport')->getPortableModels();

        $file = str_replace(':', '_', $model) . '.json';
        $json = $this->_checkJsonExistAndValid($file);
        if ($json === false) {
            return $this->result;
        }

        $currentSite = ee('Model')->get('Site', $this->site_id)->first();

        // get all models in batch
        $emptyModelInstance = ee('Model')->make($model);
        $uuidField = method_exists($emptyModelInstance, 'getColumnPrefix') ? $emptyModelInstance->getColumnPrefix() . 'uuid' : 'uuid';
        unset($emptyModelInstance);
        $allModelInstances = ee('Model')->get($model)->filter($uuidField, 'IN', array_keys($json))->all()->indexBy($uuidField);
        foreach ($json as $uuid => $modelPortage) {
            //should we skip as told by user?
            if (isset($this->aliases[$model]) && isset($this->aliases[$model][$uuid]) && $this->aliases[$model][$uuid]['portage__action'] == 'skip') {
                continue;
            }

            // grab the matching model, or the model we need to overwrite
            if (isset($this->aliases[$model]) && isset($this->aliases[$model][$uuid]) && $this->aliases[$model][$uuid]['portage__action'] == 'overwrite' && isset($this->aliases[$model][$uuid]['portage__duplicates']) && !empty($this->aliases[$model][$uuid]['portage__duplicates'])) {
                $modelInstance = ee('Model')->get($model, (int) $this->aliases[$model][$uuid]['portage__duplicates'])->first();
                //overwrite UUID
                $modelInstance->setRawProperty($uuidField, $uuid);
            } else {
                $modelInstance = isset($allModelInstances[$uuid]) ? $allModelInstances[$uuid] : null;
            }
            //if the model exists, and is same, we just skip
            $currentState = [];
            if (!is_null($modelInstance)) {
                $currentState = (array) ee('PortageExport')->getDataFromModelRecord($model, $modelInstance);
                $currentState['associationsByUuid'] = (array) $currentState['associationsByUuid']; // ensure it's in same array format that portage is
                if ($currentState == $modelPortage) {
                    continue;
                }
                // write the current model state to memory
                // $this->existingElements[$uuid] = $currentState;
            }

            // log this change
            $importLog = ee('Model')->make('PortageImportLog');
            $importLog->import_id = $this->importId;
            $importLog->portage_action = !empty($currentState) ? 'update' : 'create';
            $importLog->model_name = $model;
            $importLog->model_uuid = $uuid;
            $importLog->model_prev_state = $currentState;
            $importLog->save();

            if (!empty($modelPortage['associationsByUuid'])) {
                // set the associations
                $this->associations[$uuid] = $modelPortage['associationsByUuid'];
            }
            unset($modelPortage['associationsByUuid']);

            if (empty($modelInstance)) {
                $modelInstance = ee('Model')->make($model);
            }
            $modelInstance->set($modelPortage);

            // when working with custom fields, we need to pass field settings as individual properties for validation
            if (array_key_exists('field_settings', $modelPortage) && is_array($modelPortage['field_settings'])) {
                $modelInstance->field_settings = $modelPortage['field_settings'];
            }

            // set overrides posted in the form
            
            if (isset($this->aliases[$model]) && isset($this->aliases[$model][$uuid])) {
                foreach ($this->aliases[$model][$uuid] as $field => $value) {
                    if (strpos($field, 'portage__') !== 0) {
                        $modelInstance->$field = $value;
                    }
                }
            }

            // site is usually required, so set to current site by default
            if ($modelInstance->hasAssociation('Site')) {
                if (in_array($model, ['ee:ChannelField', 'ee:ChannelFieldGroup'])) {
                    // fields and groups are shared by default
                    $modelInstance->site_id = 0;
                } else {
                    $modelInstance->Site = $currentSite;
                }
            }

            if (isset($allModelInstances[$uuid])) {
                unset($allModelInstances[$uuid]);
            }

            // set the relationships with the models that already exist
            $modelInstance = $this->_setExistingAssociations($uuid, $modelInstance);

            // enforce UUID for new models
            $modelInstance->setUuid($uuid);

            $result = $modelInstance->validate();

            if ($result->failed()) {
                foreach ($result->getFailed() as $field => $rules) {
                    $this->result->addModelError($modelInstance, $field, $rules);
                }
            } else {
                // because we could have a lot of models, write those to filesystem
                ee('Filesystem')->write($this->path . 'runtime/' . $uuid, $modelInstance->serialize());
                $this->portageImportElements[$uuid] = $uuid;
                $this->portageImportElementsByModel[$model][$uuid] = $uuid;
            }
        }

        $this->cache();
    }


    private function _setExistingAssociations($uuid, $modelInstance)
    {
        $uuidField = method_exists($modelInstance, 'getColumnPrefix') ? $modelInstance->getColumnPrefix() . 'uuid' : 'uuid';
        if (isset($this->associations[$uuid])) {
            foreach ($this->associations[$uuid] as $relationship => $relatioshipData) {
                //perhaps we need to add an alias
                if (strpos($relatioshipData['model'], ':') !== false && substr($relatioshipData['model'], 0, 3) != 'ee:') {
                    $thirdPartyAssoc = explode(':', $relatioshipData['model']);
                    $modelInstance->alias($thirdPartyAssoc[0] . ':' . $relationship, $relationship);
                }
                
                // only if there are some data and the related models are included in portage
                if (! empty($relatioshipData) && ! empty($relatioshipData['related']) && in_array($relatioshipData['model'], $this->components)) {
                    if (! is_array($relatioshipData['related'])) {
                        $modelInstance->{$relationship} = ee('Model')->get($relatioshipData['model'])->filter($uuidField, $relatioshipData['related'])->first();
                    } else {
                        $modelInstance->{$relationship} = ee('Model')->get($relatioshipData['model'])->filter($uuidField, 'IN', $relatioshipData['related'])->all();
                    }
                }
            }
        }
        return $modelInstance;
    }

    /**
     * Extract JSON file and make sure it's valid
     *
     * @param [type] $file
     * @return array
     */
    private function _checkJsonExistAndValid($file)
    {
        if (! file_exists($this->path . $file)) {
            $this->result->addError(sprintf(lang('portage_file_invalid'), $file));
            return false;
        }

        $json = json_decode(file_get_contents($this->path . $file), true);

        if (empty($json)) {
            $this->result->addError(sprintf(lang('portage_file_invalid'), $file));
            return false;
        }

        return $json;
    }

    
    /**
     * Save the data to cache
     *
     * @return bool TRUE if it saved; FALSE if not
     */
    protected function cache()
    {
        $data = [
            'result' => $this->result,
            'components' => $this->components,
            'currentComponent' => $this->currentComponent,
            'aliases' => $this->aliases,
            'associations' => $this->associations,
            'portageImportElements' => $this->portageImportElements,
            'portageImportElementsByModel' => $this->portageImportElementsByModel,
            'version' => $this->version,
            'uniqid' => $this->uniqid,
            'importId' => $this->importId,
            'updating' => $this->updating,
            'offset' => $this->offset
        ];

        return ee()->cache->save(self::CACHE_KEY, $data, 360000);
    }

    public function loadCache()
    {
        $data = ee()->cache->get(self::CACHE_KEY);
        if (! is_array($data)) {
            return false;
        }
        foreach ($data as $key => $val) {
            $this->$key = $val;
        }
        return $data;
    }

}
