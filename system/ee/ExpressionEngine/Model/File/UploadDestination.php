<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
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
        $this->setProperty('allowed_types', $allowed_types);
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

        if ($this->getProperty('site_id') != ee()->config->item('site_id')) {
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
     * Returns the propety value using the overrides if present, but WITHOUT
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
     * @param str $path Path string to ensure has a trailing slash
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
    public function getFilesystemAdapter()
    {
        // Do we want to allow variable replacement in adapters that aren't local?
        $path = $this->parseConfigVars((string) $this->getProperty('server_path'));
        $adapterName = $this->adapter ?? 'local';
        $adapterSettings = array_merge([
            'path' => $path,
            'server_path' => $this->server_path,
            'url' => $this->url
        ], $this->adapter_settings ?? []);
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

        // This will effectively eager load the directory and speed up checks
        // for file existence, especially in remote filesystems.  This might
        // make more sense to move into file listing controllers eventually
        $filesystem->getDirectoryContents($path, true);
        $this->filesystem = $filesystem;

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
                $directories = $directories->filter('upload_location_id', $directory_id)->filter('directory_id', 0)->all();
            } else {
                $directories = $directories->filter('directory_id', $directory_id)->all();
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
        try {
            return $this->getFilesystem()->exists('');
        } catch (\LogicException $e) {
            return false;
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
            if($filesystem->exists("{$dirname}/_{$manipulation}/")) {
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
