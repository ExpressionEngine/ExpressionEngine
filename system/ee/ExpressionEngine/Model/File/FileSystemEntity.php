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

use ExpressionEngine\Model\Content\ContentModel;

/**
 * FileSystemEntity Model
 *
 * Can represent file or directory
 * Since both files and directories are listed in same table in database
 * (because they need to be fetched together as one list)
 * so we have this models that allows to manipulate them together
 *
 * ATTENTION: this model is build to only fetch the data, not create
 *
 */
class FileSystemEntity extends ContentModel
{
    protected static $_primary_key = 'file_id';
    protected static $_table_name = 'files';
    //protected static $_gateway_names = array('FileGateway', 'FileFieldDataGateway');

    protected static $_events = array(
        'beforeDelete',
        'beforeInsert',
        'beforeSave'
    );
    protected static $_binary_comparisons = array(
        'file_name'
    );

    protected static $_hook_id = 'file';

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'UploadDestination' => array(
            'type' => 'belongsTo',
            'to_key' => 'id',
            'from_key' => 'upload_location_id',
        ),
        'UploadAuthor' => array(
            'type' => 'BelongsTo',
            'model' => 'Member',
            'from_key' => 'uploaded_by_member_id'
        ),
        'ModifyAuthor' => array(
            'type' => 'BelongsTo',
            'model' => 'Member',
            'from_key' => 'modified_by_member_id'
        ),
        'Categories' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Category',
            'pivot' => array(
                'table' => 'file_categories',
                'left' => 'file_id',
                'right' => 'cat_id'
            )
        ),
        'FileCategories' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'Category',
            'pivot' => array(
                'table' => 'file_usage',
            )
        ),
        'FileEntries' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'ChannelEntry',
            'pivot' => array(
                'table' => 'file_usage',
            )
        ),
    );

    /*protected static $_field_data = array(
        'field_model' => 'FileField',
        'structure_model' => 'UploadDestination',
    );*/

    protected static $_validation_rules = array(
        'title' => 'xss',
        'description' => 'xss',
        'credit' => 'xss',
        'location' => 'xss',
    );

    protected $file_id;
    protected $model_type;
    protected $site_id;
    protected $title;
    protected $upload_location_id;
    protected $directory_id;
    protected $mime_type;
    protected $file_type;
    protected $file_name;
    protected $file_size;
    protected $description;
    protected $credit;
    protected $location;
    protected $uploaded_by_member_id;
    protected $upload_date;
    protected $modified_by_member_id;
    protected $modified_date;
    protected $file_hw_original;
    protected $total_records;

    protected $_baseServerPath;
    protected $_subfolderPath;
    protected $_exists;

    /**
     * A link back to the owning group object.
     *
     * @return  Structure   A link back to the Structure object that defines
     *                      this Content's structure.
     */
    public function getStructure()
    {
        return $this->UploadDestination;
    }

    /**
     * Determine whether or not this entity represents a file
     *
     * @return boolean
     */
    public function isFile()
    {
        return $this->model_type === 'File';
    }

    /**
     * Determine whether or not this entity represents a directory
     *
     * @return boolean
     */
    public function isDirectory()
    {
        return $this->model_type === 'Directory';
    }

    /**
     * Uses the file's mime-type to determine if the file is an image or not.
     *
     * @return bool TRUE if the file is an image, FALSE otherwise
     */
    public function isImage()
    {
        return ($this->isFile() && strpos($this->mime_type, 'image/') === 0);
    }

    /**
     * Uses the file's mime-type to determine if the file is an editable image or not.
     *
     * @return bool TRUE if the file is an editable image, FALSE otherwise
     */
    public function isEditableImage()
    {
        $imageMimes = [
            'image/gif', // .gif
            'image/jpeg', // .jpg, .jpe, .jpeg
            'image/pjpeg', // .jpg, .jpe, .jpeg
            'image/png', // .png
            'image/x-png', // .png
        ];
        if (defined('IMAGETYPE_WEBP')) {
            $imageMimes[] = 'image/webp'; // .webp
        }

        return (in_array($this->mime_type, $imageMimes));
    }

    /**
     * Uses the file's mime-type to determine if the file is an SVG or not.
     *
     * @return bool TRUE if the file is an SVG, FALSE otherwise
     */
    public function isSVG()
    {
        return ($this->isFile() && strpos($this->mime_type, 'image/svg') === 0);
    }

    /**
     * Get the subfolder path to the given file
     *
     * @return string
     */
    public function getSubfoldersPath()
    {
        if (is_null($this->_subfolderPath)) {
            $directory_id = $this->directory_id;
            $subfolders = [];
            while ($directory_id != 0) {
                $parent = $this->getModelFacade()->get('Directory', $directory_id)->fields('file_id', 'directory_id', 'file_name')->first(true);
                if (!empty($parent)) {
                    $directory_id = $parent->directory_id;
                    array_unshift($subfolders, $parent->file_name . '/');
                } else {
                    $directory_id = 0;
                }
            }
            $this->_subfolderPath = implode($subfolders);
        }

        return $this->_subfolderPath;
    }

    /**
     * Get base server path for file's upload location
     *
     * @return string
     */
    public function getBaseServerPath()
    {
        if (empty($this->_baseServerPath) && $this->UploadDestination->adapter == 'local') {
            $this->_baseServerPath = rtrim($this->UploadDestination->server_path, '\\/') . '/';
        }

        return $this->_baseServerPath;
    }

    /**
     * Get base url for upload location and folder
     *
     * @return string
     */
    public function getBaseUrl()
    {
        if (!$this->UploadDestination->exists()) {
            return null;
        }

        return $this->UploadDestination->getFilesystem()->getUrl($this->getSubfoldersPath());
    }

    /**
     * Perform some action on the file in a local context
     *
     * @param callable $callback
     * @return mixed
     */
    public function actLocally(callable $callback)
    {
        $filesystem = $this->getUploadDestination()->getFilesystem();
        $path = $this->getAbsolutePath();

        return $filesystem->actLocally($path, $callback);
    }

    /**
     * Uses the file's upload destination's server path to compute the absolute
     * path of the file
     *
     * @return string The absolute path to the file
     */
    public function getAbsolutePath()
    {
        return $this->getBaseServerPath() . $this->getSubfoldersPath() . $this->file_name;
    }

    /**
     * Uses the file's upload destination's server path to compute the absolute
     * path of the file
     *
     * @return string The absolute path to the file
     */
    public function getFilesystem()
    {
        // If we have already set $this->filesystem, returned cached version of it
        if ($this->filesystem) {
            return $this->filesystem;
        }

        // If this isnt a directory, return the upload path filesystem
        if (! $this->isDirectory()) {
            // Cache this filesystem
            $this->filesystem = $this->UploadDestination->getFilesystem();

            return $this->filesystem;
        }

        // Do we want to allow variable replacement in adapters that aren't local?
        $path = rtrim($this->getAbsolutePath(), '/') . '/';
        $adapter = $this->UploadDestination->getFilesystemAdapter();

        $filesystem = ee('File')->getPath($path, $adapter);
        $filesystem->setUrl($this->getAbsoluteUrl());

        $this->filesystem = $filesystem;

        return $this->filesystem;
    }

    /**
     * Uses the file's upload destination's server path to compute the absolute
     * thumbnail path of the file
     *
     * @return string The absolute path to the file
     */
    public function getAbsoluteThumbnailPath()
    {
        return $this->getBaseServerPath() . $this->getSubfoldersPath() . '_thumbs/' . $this->file_name;
    }

    /**
     * Uses the file's upload destination's url to compute the absolute URL of
     * the file
     *
     * @return string The absolute URL to the file
     */
    public function getAbsoluteURL()
    {
        if (!$this->UploadDestination->exists()) {
            return null;
        }

        return $this->UploadDestination->getFilesystem()->getUrl($this->getSubfoldersPath() . $this->file_name);
    }

    public function getAbsoluteManipulationPath($manipulation = 'thumbs')
    {
        return $this->getBaseServerPath() . $this->getSubfoldersPath() . '_' . $manipulation . '/' . $this->file_name;
    }

    public function getAbsoluteManipulationURL($manipulation = 'thumbs')
    {
        if (!$this->UploadDestination->exists()) {
            return null;
        }

        $filesystem = $this->UploadDestination->getFilesystem();

        if (! $filesystem->exists($this->getAbsoluteManipulationPath($manipulation))) {
            return $this->getAbsoluteURL();
        }

        return $filesystem->getUrl($this->getSubfoldersPath() . '_' . $manipulation . '/'  . $this->file_name);
    }

    /**
     * Uses the file's upload destination's URL to compute the absolute thumbnail
     *  URL of the file
     *
     * @return string The absolute thumbnail URL to the file
     */
    public function getAbsoluteThumbnailURL()
    {
        return $this->getAbsoluteManipulationURL('thumbs');
    }

    public function getThumbnailUrl()
    {
        return $this->getAbsoluteThumbnailURL();
    }

    public function deleteOriginalFile()
    {
         $this->UploadDestination->deleteOriginalFiles($this->getAbsolutePath());
    }

    public function deleteGeneratedFiles()
    {
        $filesystem = $this->UploadDestination->getFilesystem();

        $manipulations = ['thumbs', 'resize', 'crop', 'rotate', 'webp'];
        $manipulations = array_merge($manipulations, $this->UploadDestination->FileDimensions->pluck('short_name'));

        foreach ($manipulations as $manipulation) {
            $manipulatedFilePath = $this->getBaseServerPath() . $this->getSubfoldersPath() . '_' . $manipulation . '/' . $this->file_name;
            if ($filesystem->exists($manipulatedFilePath)) {
                $filesystem->delete($manipulatedFilePath);
            }
        }
    }

    public function deleteAllFiles()
    {
        if (! $this->UploadDestination->exists()) {
            return false;
        }
        $this->deleteOriginalFile();
        $this->deleteGeneratedFiles();
    }

    public function onBeforeSave()
    {
        $this->setProperty('modified_date', ee()->localize->now);
        $this->setProperty('modified_by_member_id', ee()->session->userdata('member_id'));
    }

    public function onBeforeInsert()
    {
        $this->setProperty('upload_date', ee()->localize->now);
        $this->setProperty('uploaded_by_member_id', ee()->session->userdata('member_id'));
    }

    public function onBeforeDelete()
    {
        if (ee('Request')->isPost() && ee('Request')->post('remove_files') !== null && ee('Request')->post('remove_files') != 1) {
            // this is hacky, but currently only way to prevent file deleting when upload destination is removed in CP
            return;
        }
        $this->deleteAllFiles();
    }

    /**
    * Determines if the member group (by ID) has access permission to this
    * upload destination.
    * @see UploadDestination::memberHasAccess
    *
    * @throws InvalidArgumentException
    * @param Member $member The Member
    * @return bool TRUE if access is granted; FALSE if access denied
    */
    public function memberHasAccess($member)
    {
        $dir = $this->UploadDestination;
        if (! $dir) {
            return false;
        }

        return $dir->memberHasAccess($member);
    }

    /**
     * Determines if the file exists
     *
     * @return bool TRUE if it does FALSE otherwise
     */
    public function exists()
    {
        if (! is_null($this->_exists)) {
            return $this->_exists;
        }

        if (!$this->UploadDestination->exists()) {
            return $this->_exists = false;
        }

        $filesystem = $this->UploadDestination->getFilesystem();

        return $this->_exists = $filesystem->exists($this->getAbsolutePath());
    }

    /**
     * Determines if the file is writable
     *
     * @return bool TRUE if it is FALSE otherwise
     */
    public function isWritable()
    {
        $filesystem = $this->UploadDestination->getFilesystem();

        return $filesystem->isWritable($this->getAbsolutePath());
    }

    /**
     * Cleans the values by stripping tags and trimming
     *
     * @param string $str The string to be cleaned
     * @return string A clean string
     */
    private function stripAndTrim($str)
    {
        return trim(strip_tags((string) $str));
    }

    public function set__title($value)
    {
        if (empty($value)) {
            $value = $this->getProperty('file_name');
        }
        $this->setRawProperty('title', $this->stripAndTrim($value));
    }

    public function set__description($value)
    {
        $this->setRawProperty('description', $this->stripAndTrim($value));
    }

    public function set__credit($value)
    {
        $this->setRawProperty('credit', $this->stripAndTrim($value));
    }

    public function set__location($value)
    {
        $this->setRawProperty('location', $this->stripAndTrim($value));
    }

    /**
     * Category setter for convenience to intercept the
     * 'categories' post array.
     */
    public function setCategoriesFromPost($categories)
    {
        // Currently cannot get multiple category groups through relationships
        $cat_groups = $this->UploadDestination->CategoryGroups->pluck('group_id');

        if (empty($categories)) {
            $this->Categories = null;

            return;
        }

        $set_cats = array();

        // Set the data on the fields in case we come back from a validation error
        foreach ($cat_groups as $cat_group) {
            if (array_key_exists('cat_group_id_' . $cat_group, $categories)) {
                $group_cats = $categories['cat_group_id_' . $cat_group];

                $cats = implode('|', $group_cats);

                $group_cat_objects = $this->getModelFacade()
                    ->get('Category')
                    ->filter('site_id', ee()->config->item('site_id'))
                    ->filter('cat_id', 'IN', $group_cats)
                    ->all();

                foreach ($group_cat_objects as $cat) {
                    $set_cats[] = $cat;
                }
            }
        }

        $this->Categories = $set_cats;
    }

    /**
     * Get an array of ids for files and folders that belong to this FileSystemEntity
     *
     * @return array
     */
    public function getChildIds()
    {
        if (!$this->isDirectory()) {
            return [];
        }

        $files = ee()->db->select(['file_id', 'directory_id', 'file_type'])
            ->where('upload_location_id', $this->upload_location_id)
            ->order_by('directory_id', 'asc')
            ->from('files')
            ->get()
            ->result_array();

        // Group file ids by their directory_id
        $grouped = array_reduce($files, function ($carry, $item) {
            $key = $item['directory_id'];
            if (!array_key_exists($key, $carry)) {
                $carry[$key] = [];
            }

            $carry[$key][] = $item['file_id'];

            return $carry;
        }, []);

        // If we do not have a group for this file system entity we can exit
        if (!array_key_exists($this->file_id, $grouped)) {
            return [];
        }

        $ids = [];
        $directories = [$this->file_id];

        while (!empty($directories)) {
            $next = [];
            foreach ($directories as $directory) {
                $next = array_merge($next, array_filter($grouped[$directory], function ($id) use ($grouped) {
                    return array_key_exists($id, $grouped);
                }));

                $ids = array_merge($ids, $grouped[$directory]);
            }
            $directories = $next;
        }

        return $ids;
    }
}

// EOF
