<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\File;

use ExpressionEngine\Model\Content\StructureModel;
use ExpressionEngine\Model\Member\Member;

/**
 * File Upload Location Model
 *
 * A model representing one of many possible upload destintations to which
 * files may be uploaded through the file manager or from the publish page.
 * Contains settings for this upload destination which describe what type of
 * files may be uploaded to it, as well as essential information, such as the
 * server paths where those files actually end up.
 */
class UploadDestination extends StructureModel
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'upload_prefs';

    protected static $_events = array(
        'beforeSave',
        'afterUpdate',
    );

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'Roles' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Role',
            'pivot' => array(
                'table' => 'upload_prefs_roles',
                'left' => 'upload_id',
                'right' => 'role_id'
            )
        ),
        'Module' => array(
            'type' => 'belongsTo',
            'model' => 'Module',
            'to_key' => 'module_id'
        ),
        'CategoryGroups' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'CategoryGroup',
            'pivot' => array(
                'table' => 'upload_prefs_category_groups',
                'left' => 'upload_location_id',
                'right' => 'group_id'
            )
        ),
        'Files' => array(
            'type' => 'hasMany',
            'model' => 'File',
            'to_key' => 'upload_location_id'
        ),
        'FileDimensions' => array(
            'type' => 'hasMany',
            'model' => 'FileDimension',
            'to_key' => 'upload_location_id'
        )
    );

    protected static $_type_classes = array(
        'LocalPath' => 'ExpressionEngine\Model\File\Column\LocalPath',
    );

    protected static $_typed_columns = array(
        'server_path' => 'LocalPath',
        'allowed_types' => 'pipeDelimited',
        'allow_subfolders' => 'boolString',
        'subfolders_on_top' => 'boolString',
        'adapter_settings' => 'json',
    );

    protected static $_validation_rules = array(
        'name' => 'required|xss|noHtml|unique[site_id]',
        'adapter' => 'required',
        'allow_subfolders' => 'enum[y,n]',
        'subfolders_on_top' => 'enum[y,n]',
        'default_modal_view' => 'enum[list,thumb]',
        'max_size' => 'numeric|greaterThan[0]',
        'max_height' => 'isNatural',
        'max_width' => 'isNatural'
    );

    protected $_property_overrides = array();

    protected $id;
    protected $site_id;
    protected $name;
    protected $adapter;
    protected $adapter_settings;
    protected $server_path;
    protected $url;
    protected $allowed_types;
    protected $allow_subfolders;
    protected $subfolders_on_top;
    protected $default_modal_view;
    protected $max_size;
    protected $max_height;
    protected $max_width;
    protected $properties;
    protected $pre_format;
    protected $post_format;
    protected $file_properties;
    protected $file_pre_format;
    protected $file_post_format;
    protected $cat_group;
    protected $batch_location;
    protected $module_id;

    private $filesystem = null;

    protected $_manipulationsToOperate;
    protected $_exists;

    /**
     * Because of the 'upload_preferences' Config value, the data in the DB
     * is not always authoritative. So we will need to get any override data
     * from the Config object
     *
     * @see Entity::_construct()
     * @param array $data An associative array of property data
     * @return void
     */
    public function __construct(array $data = array())
    {
        parent::__construct($data);

        // @TODO THOU SHALT INJECT ALL THY DEPENDENCIES
        if (ee()->config->item('upload_preferences') !== false) {
            $this->_property_overrides = ee()->config->item('upload_preferences');
        }
    }

    /**
     * Returns the property value using the overrides if present
     *
     * @param str $name The name of the property to fetch
     * @return mixed The value of the property
     */
    public function __get($name)
    {
        $value = parent::__get($name);

        return $this->fetchOverride($name, $value);
    }

    public function onBeforeSave()
    {
        $allowed_types = $this->getProperty('allowed_types');
        if (! is_array($allowed_types)) {
            $allowed_types = [$allowed_types];
        }
        if (in_array('--', $allowed_types)) {
            $allowed_types =  ['all'];
        }
        if (empty($allowed_types)) {
            $allowed_types = ['img'];
        }
        $this->setProperty('allowed_types', $allowed_types);

        $this->_manipulationsToOperate = $this->FileDimensions;
    }

    public function onAfterUpdate()
    {
        // because FileDimensions are not manipulated separately from UploadDestination
        // the ones to be removed are removed with 'soft delete' by dropComplement()
        // which does not trigger any kind of delete event
        // so we'll trigger the event here
        if (! empty($this->_manipulationsToOperate)) {
            $manipulationsToKeep = $this->_manipulationsToOperate->pluck('id');
            // grab fresh list
            $existingManipulations = ee('Model')->get('FileDimension')->filter('upload_location_id', $this->getId())->all()->indexBy('id');
            foreach ($manipulationsToKeep as $manipulationId) {
                if (isset($existingManipulations[$manipulationId])) {
                    unset($existingManipulations[$manipulationId]);
                }
            }

            if (! empty($existingManipulations)) {
                foreach (array_keys($existingManipulations) as $manipulationId) {
                    if ($manipulationId != '') {
                        $manipulation = ee('Model')->get('FileDimension', $manipulationId)->first();
                        if (!empty($manipulation)) {
                            $manipulation->onAfterDelete();
                        }
                    }
                }
            }

        }
    }

    /**
     * Upload paths may have a {base_url} or {base_path} in them, so we need
     * to parse those but also take into account when an upload destination
     * belongs to another site
     *
     * @param string $value Value of property to parse
     * @return string Upload path or URL with variables parsed
     */
    private function parseConfigVars($value)
    {
        $overrides = array();

        if ($this->getProperty('site_id') != 0 && $this->getProperty('site_id') != ee()->config->item('site_id')) {
            $overrides = ee()->config->get_cached_site_prefs($this->getProperty('site_id'));
        }

        return parse_config_variables((string) $value, $overrides);
    }

    /**
     * Fetches the override, if there is one, and processes the config vars
     * if needed
     *
     * @param str  $name        The name of the property to fetch
     * @param str  $default     Default value if value not present
     * @param bool $config_only Only apply config override without config variables parsing
     * @return mixed The value of the property or NULL if there was no override
     */
    private function fetchOverride($name, $default = null, $config_only = false)
    {
        $value = $default;

        if ($this->hasOverride($name)) {
            $value = $this->_property_overrides[$this->id][$name];
        }

        if ($config_only) {
            return $value;
        }

        if ($name == 'url' or $name == 'server_path') {
            $value = $this->parseConfigVars((string) $value);
        }

        return $value;
    }

    /**
     * Returns the property value using the overrides if present
     *
     * @param str $name The name of the property to fetch
     * @return mixed The value of the property
     */
    public function getProperty($name)
    {
        $value = parent::getProperty($name);

        return $this->fetchOverride($name, $value);
    }

    /**
     * Returns the property value using the overrides if present, but WITHOUT
     * config variable parsing
     *
     * @param str $name The name of the property to fetch
     * @return mixed The value of the property
     */
    public function getConfigOverriddenProperty($name)
    {
        $value = parent::getProperty($name);

        return $this->fetchOverride($name, $value, true);
    }

    /**
     * Check if have an override for this directory and that it's an
     * array (as it should be)
     *
     * @param str $name The name of the property to check
     * @return bool Property is overridden?
     */
    private function hasOverride($name)
    {
        return (isset($this->_property_overrides[$this->id])
            && is_array($this->_property_overrides[$this->id])
            && array_key_exists($name, $this->_property_overrides[$this->id]));
    }

    /**
     * Custom setter for server path to ensure it's saved with a trailing slash
     *
     * @param str $value Value to set on property
     * @return void
     */
    protected function set__server_path($value)
    {
        $this->setRawProperty('server_path', $this->getWithTrailingSlash($value));
    }

    /**
     * Custom setter for URL to ensure it's saved with a trailing slash
     *
     * @param str $value Value to set on property
     * @return void
     */
    protected function set__url($value)
    {
        $this->setRawProperty('url', $this->getWithTrailingSlash($value));
    }

    /**
     * Appends a trailing slash on to a value that doesn't have it
     *
     * @param string $path Path string to ensure has a trailing slash
     * @return void
     */
    private function getWithTrailingSlash($path)
    {
        if (! empty($path) && substr($path, -1) != '/' and substr($path, -1) != '\\') {
            $path .= '/';
        }

        return $path;
    }

    public function getValidationData()
    {
        return array_merge(parent::getValidationData(), [
            'filesystem_provider' => $this,
        ]);
    }

    /**
     * Get the backing filesystem adapter for this upload destination
     */
    public function getFilesystemAdapter($overrides = [])
    {
        // Do we want to allow variable replacement in adapters that aren't local?
        $path = $this->parseConfigVars((string) $this->getProperty('server_path'));
        $adapterName = $this->adapter ?? 'local';
        $adapterSettings = array_merge([
            'path' => $path,
            'server_path' => $this->server_path,
            'url' => $this->url
        ], $this->adapter_settings ?? [], $overrides);
        $adapter = ee('Filesystem/Adapter')->make($adapterName, $adapterSettings);

        return $adapter;
    }

    /**
     * Get the backing filesystem for this upload destination
     */
    public function getFilesystem()
    {
        if ($this->filesystem) {
            return $this->filesystem;
        }

        // Do we want to allow variable replacement in adapters that aren't local?
        $path = $this->parseConfigVars((string) $this->getProperty('server_path')); 
        $adapter = $this->getFilesystemAdapter();

        $filesystem = ee('File')->getPath($path, $adapter);
        $filesystem->setUrl($this->getProperty('url'));
        $this->filesystem = $filesystem;

        return $this->filesystem;
    }

    /**
     * Eager load the contents of the underlying filesystem by listing the files
     * and storing results in the cached adapter
     *
     * @return Filesystem
     */
    public function eagerLoadContents()
    {
        // This will effectively eager load the directory and speed up checks
        // for file existence, especially in remote filesystems.  This might
        // make more sense to move into file listing controllers eventually
        $this->getFilesystem();
        $path = $this->parseConfigVars((string) $this->getProperty('server_path'));
        $this->filesystem->getDirectoryContents($path, true);

        return $this->filesystem;
    }

    /**
     * Gets the map of upload location directories and files as nested array
     *
     * @param boolean $includeHiddenFiles
     * @param boolean $includeHiddenFolders
     * @param boolean $includeIndex
     * @param boolean $ignoreAllowedTypes
     * @return array
     */
    public function getDirectoryMap($path = '/', $fullPathAsKey = false, $includeHiddenFiles = false, $includeHiddenFolders = false, $includeIndex = false, $ignoreAllowedTypes = false)
    {
        if (! $this->getFilesystem()->isReadable($path)) {
            return [];
        }

        $map = array();
        $indexFiles = array('index.html', 'index.htm', 'index.php');

        foreach ($this->getFilesystem()->getDirectoryContents($path) as $filePath) {
            $pathInfo = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filePath));
            $fileName = array_pop($pathInfo);
            $isDir = $this->getFilesystem()->isDir($filePath);

            if (empty(trim($fileName, '.'))) {
                continue;
            }

            if (! $includeHiddenFiles && substr($fileName, 0, 1) == '.') {
                continue;
            }

            if (! $includeHiddenFolders && $isDir && substr($fileName, 0, 1) == '_') {
                continue;
            }

            if (! $includeIndex && in_array($fileName, $indexFiles)) {
                continue;
            }

            if (! $isDir && ! $ignoreAllowedTypes && ! in_array('all', $this->allowed_types)) {
                $isOfAllowedMimeType = false;
                foreach ($this->allowed_types as $allowed_type) {
                    if (ee('MimeType')->isOfKind($this->getFilesystem()->getMimetype($filePath), $allowed_type)) {
                        $isOfAllowedMimeType = true;

                        break;
                    }
                }
                if (! $isOfAllowedMimeType) {
                    continue;
                }
            }

            $key = $fullPathAsKey ? $filePath : $fileName;

            if ($isDir && !bool_config_item('file_manager_compatibility_mode')) {
                $map[$key] = $this->getDirectoryMap($filePath, $fullPathAsKey, $includeHiddenFiles, $includeHiddenFolders, $includeIndex, $ignoreAllowedTypes);
            } else {
                $map[$key] = $fileName;
            }
        }

        return $map;
    }

    /**
     * Return list of all files in directory, with subfolders
     *
     * @return array
     */
    public function getAllFileNames() {
        $directoryMap = $this->getDirectoryMap();
        $flatDirectoryMap = [];
        $this->flattenDirectoryMap($flatDirectoryMap, $directoryMap);
        $files = array_keys($flatDirectoryMap);
        return $files;
    }

    private function flattenDirectoryMap(&$flatMap = [], $nestedMap = [], $keyPrefix = '/')
    {
        foreach ($nestedMap as $key => $val) {
            $flatKey = rtrim($keyPrefix . $key, '/');
            if (! isset($flatMap[$flatKey])) {
                $flatMap[$flatKey] = $flatKey;
            }
            if (is_array($val)) {
                $flatMap[$flatKey] = $this->flattenDirectoryMap($flatMap, $val, $flatKey . '/');
            }
        }
    }

    public function syncFiles($files, $allSizes = [], $replaceSizeIds = [])
    {
        $errors = array();
        $file_data = array();
        $replace_sizes = array();

        $id = $this->getId();
        $missing_only_sizes = (array_key_exists($id, $allSizes) && is_array($allSizes[$id])) ? $allSizes[$id] : array();

        if (is_array($replaceSizeIds)) {
            foreach ($replaceSizeIds as $resize_id) {
                if (!empty($resize_id) && isset($allSizes[$id][$resize_id])) {
                    $replace_sizes[$resize_id] = $allSizes[$id][$resize_id];
                    unset($missing_only_sizes[$resize_id]);
                }
            }
        }

        ee()->load->library('filemanager');
        $mimes = ee()->config->loadFile('mimes');
        $fileTypes = array_filter(array_keys($mimes), 'is_string');

        $filesystem = $this->getFilesystem();

        foreach ($files as $filePath) {
            $fileInfo = $filesystem->getWithMetadata($filePath);
            if (!isset($fileInfo['basename'])) {
                $fileInfo['basename'] = basename($fileInfo['path']);
            }
            $mime = ($fileInfo['type'] != 'dir') ? $filesystem->getMimetype($filePath) : 'directory';

            if ($mime == 'directory' && (!$this->allow_subfolders || bool_config_item('file_manager_compatibility_mode'))) {
                //silently continue on subfolders if those are not allowed
                continue;
            }

            if (empty($mime)) {
                $errors[$fileInfo['basename']] = lang('invalid_mime');

                continue;
            }

            $file = $this->getFileByPath($filePath);

            // Clean filename
            $clean_filename = ee()->filemanager->clean_subdir_and_filename($fileInfo['path'], $id, array(
                'convert_spaces' => false,
                'ignore_dupes' => true
            ));

            if ($fileInfo['path'] != $clean_filename) {
                // Make sure clean filename is unique
                $clean_filename = ee()->filemanager->clean_subdir_and_filename($clean_filename, $id, array(
                    'convert_spaces' => false,
                    'ignore_dupes' => false
                ));
                // Rename the file
                if (! $filesystem->rename($fileInfo['path'], $clean_filename)) {
                    $errors[$fileInfo['path']] = lang('invalid_filename');
                    continue;
                }

                $filesystem->delete($fileInfo['path']);
                $fileInfo['basename'] = $filesystem->basename($clean_filename);
            }

            if (! empty($file)) {
                // It exists, but do we need to change sizes or add a missing thumb?

                if (! $file->isEditableImage()) {
                    continue;
                }

                // Note 'Regular' batch needs to check if file exists- and then do something if so
                if (! empty($replace_sizes)) {
                    $thumb_created = ee()->filemanager->create_thumb(
                        $file->getAbsolutePath(),
                        array(
                            'directory' => $this,
                            'server_path' => $this->getProperty('server_path'),
                            'file_name' => $fileInfo['basename'],
                            'dimensions' => $replace_sizes,
                            'mime_type' => $mime
                        ),
                        true,	// Create thumb
                        false	// Overwrite existing thumbs
                    );

                    if (! $thumb_created) {
                        $errors[$fileInfo['basename']] = lang('thumb_not_created');
                    }
                }

                // Now for anything that wasn't forcably replaced- we make sure an image exists
                $thumb_created = ee()->filemanager->create_thumb(
                    $file->getAbsolutePath(),
                    array(
                        'directory' => $this,
                        'server_path' => $this->getProperty('server_path'),
                        'file_name' => $fileInfo['basename'],
                        'dimensions' => $missing_only_sizes,
                        'mime_type' => $mime
                    ),
                    true, 	// Create thumb
                    true 	// Don't overwrite existing thumbs
                );

                // Update dimensions
                $image_dimensions = $file->actLocally(function ($path) {
                    return ee()->filemanager->get_image_dimensions($path);
                });
                $file->setRawProperty('file_hw_original', $image_dimensions['height'] . ' ' . $image_dimensions['width']);
                $file->file_size = $fileInfo['size'];
                if ($file->file_type === null) {
                    $file->setProperty('file_type', 'other'); // default
                    foreach ($fileTypes as $fileType) {
                        if (in_array($file->getProperty('mime_type'), $mimes[$fileType])) {
                            $file->setProperty('file_type', $fileType);
                            break;
                        }
                    }
                }
                $file->save();

                continue;
            }

            $file = ee('Model')->make('FileSystemEntity');
            $file_data = [
                'upload_location_id' => $this->getId(),
                'site_id' => ee()->config->item('site_id'),
                'model_type' => ($mime == 'directory') ? 'Directory' : 'File',
                'mime_type' => $mime,
                'file_name' => $fileInfo['basename'],
                'file_size' => isset($fileInfo['size']) ? $fileInfo['size'] : 0,
                'uploaded_by_member_id' => ee()->session->userdata('member_id'),
                'modified_by_member_id' => ee()->session->userdata('member_id'),
                'upload_date' => $fileInfo['timestamp'],
                'modified_date' => $fileInfo['timestamp']
            ];
            $pathInfo = explode('/', trim(str_replace(DIRECTORY_SEPARATOR, '/', $filePath), '/'));
            //get the subfolder info, but at the same time, skip if no subfolder are allowed
            if (count($pathInfo) > 1) {
                if (!$this->allow_subfolders || bool_config_item('file_manager_compatibility_mode')) {
                    continue;
                }
                array_pop($pathInfo);
                $directory = $this->getFileByPath(implode('/', $pathInfo));
                $file_data['directory_id'] = !is_null($directory) ? $directory->getId() : 0;
            }
            $file->set($file_data);
            if ($file->isEditableImage()) {
                try {
                    $image_dimensions = $file->actLocally(function ($path) {
                        return ee()->filemanager->get_image_dimensions($path);
                    });
                    $file_data['file_hw_original'] =  $image_dimensions['height'] . ' ' . $image_dimensions['width'];
                    $file->setRawProperty('file_hw_original', $file_data['file_hw_original']);
                } catch (\Exception $e) {
                    //do nothing
                }
            }
            //$file->save(); need to fallback to old saving because of the checks

            try {
                $saved = ee()->filemanager->save_file(
                    $file->getAbsolutePath(),
                    $id,
                    $file_data,
                    false
                );
            } catch (\Exception $e) {
                $errors[$fileInfo['basename']] = $e->getMessage();
                continue;
            }

            if (! $saved['status']) {
                $errors[$fileInfo['basename']] = $saved['message'];
            }
        }

        if (count($errors)) {
            return $errors;
        }

        return true;
    }

    /**
     * Give the file relative path, returns File mode
     *
     * @param string $filePath
     * @return File
     */
    public function getFileByPath($filePath)
    {
        $pathInfo = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filePath));
        $depth = count($pathInfo) - 1;
        $directory_id = 0;
        foreach ($pathInfo as $i => $fileOrDirName) {
            if (empty($fileOrDirName)) {
                continue;
            }
            $model = ($i == $depth) ? 'FileSystemEntity' : 'Directory';
            $file = ee('Model')
                ->get($model)
                ->filter('upload_location_id', $this->getId())
                ->filter('directory_id', $directory_id)
                ->filter('file_name', $fileOrDirName)
                ->first();
            if (! empty($file)) {
                $directory_id = $file->file_id;
            }
        }

        return $file;
    }

    /**
     * Get the subdirectories nested array
     *
     * @param [type] $key_value
     * @param boolean $root_only
     * @return array
     */
    public function buildDirectoriesDropdown($directory_id, $icon = false, $path = '', $root_only = true)
    {
        $children = [];
        $i = 0;
        $directoriesCount = 0;
        do {
            $directories = ee('Model')->get('Directory')->fields('file_id', 'upload_location_id', 'directory_id', 'file_name', 'title');
            if ($root_only) {
                $directories = $directories->filter('upload_location_id', $directory_id)->filter('directory_id', 0)->all(true);
            } else {
                $directories = $directories->filter('directory_id', $directory_id)->all(true);
            }

            $directoriesCount = count($directories);
            if ($directoriesCount > 0) {
                foreach ($directories as $i => $directory) {
                    $i++;
                    if (!empty($directory)) {
                        $folder_icon = $directory->title;
                        if ($icon) {
                            $folder_icon = '<i class="fal fa-folder"></i>' . $directory->title;
                            $icon = true;
                        }
                        $path = $path . urlencode($directory->file_name) . '/';
                        $children[$this->getId() . '.' . $directory->getId()] = [
                            'label' => $folder_icon,
                            'upload_location_id' => $this->getId(),
                            'path' => $path,
                            'directory_id' => $directory->getId(),
                            'children' => $this->buildDirectoriesDropdown($directory->file_id, $icon, $path, false)
                        ];
                    }
                }
            }
        } while ($directoriesCount > ($i + 1));

        return $children;
    }

    /**
     * Determines if the member has access permission to this
     * upload destination.
     *
     * @throws InvalidArgumentException
     * @param Member $member The Member
     * @return bool TRUE if access is granted; FALSE if access denied
     */
    public function memberHasAccess(Member $member)
    {
        if (ee('Permission')->isSuperAdmin()) {
            return true;
        }

        $assigned_dirs = $member->getAssignedUploadDestinations()->pluck('id');

        return in_array($this->getId(), $assigned_dirs);
    }

    /**
     * Determines if the directory exists
     *
     * @return bool TRUE if it does FALSE otherwise
     */
    public function exists()
    {
        if (! is_null($this->_exists)) {
            return $this->_exists;
        }

        try {
            return $this->_exists = $this->getFilesystem()->exists('');
        } catch (\Exception $e) {
            return $this->_exists = false;
        }
    }

    /**
     * Determines if the directory is writable
     *
     * @return bool TRUE if it is FALSE otherwise
     */
    public function isWritable()
    {
        return $this->getFilesystem()->isWritable('');
    }

    /**
     * Returns a collection of all the custom fields available for files in this Upload Destination
     *
     * @return Collection A collection of fields
     */
    public function getAllCustomFields()
    {
        return [];
    }

    public function deleteOriginalFiles($path)
    {
        $filesystem = $this->getFilesystem();

        // Remove the file
        if ($filesystem->exists($path)) {
            return $filesystem->delete($path);
        }

        return false;
    }

    public function deleteGeneratedFiles($path)
    {
        $filesystem = $this->getFilesystem();
        $filename = $filesystem->filename($path);
        $dirname = $filesystem->dirname($path);
        $basename = $filesystem->basename($path);

        // Remove the thumbnail if it exists
        if ($filesystem->exists("{$dirname}/_thumbs/{$filename}")) {
            $filesystem->delete("{$dirname}/_thumbs/{$filename}");
        }

        // Remove any manipulated files as well
        foreach ($this->FileDimensions as $file_dimension) {
            $file = rtrim($file_dimension->getAbsolutePath(), '/') . '/' . $filename;

            if ($filesystem->exists($file)) {
                $filesystem->delete($file);
            }
        }

        // Remove front-end manipulations
        $manipulations = ['resize', 'crop', 'rotate', 'webp'];
        $renamer = strrchr($basename, '_');
        $basename = ($renamer === false) ? $basename : substr($basename, 0, -strlen($renamer));

        foreach ($manipulations as $manipulation) {
            if ($filesystem->exists("{$dirname}/_{$manipulation}/")) {
                $files = $filesystem->getDirectoryContents("{$dirname}/_{$manipulation}/");
                $files = array_filter($files, function ($file) use ($basename) {
                    return (strpos($file, "{$basename}_") === 0);
                });
                foreach ($files as $file) {
                    $filesystem->delete($file);
                }
            }
        }
    }
}

// EOF
