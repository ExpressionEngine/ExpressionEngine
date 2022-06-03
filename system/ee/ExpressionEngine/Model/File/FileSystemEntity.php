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
        'beforeDelete'
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
    protected $_baseUrl;
    protected $_subfolderPath;

    /**
     * A link back to the owning group object.
     *
     * @return	Structure	A link back to the Structure object that defines
     *						this Content's structure.
     */
    public function getStructure()
    {
        return $this->UploadDestination;
    }

    public function get__width()
    {
        $dimensions = explode(" ", $this->getProperty('file_hw_original'));

        return $dimensions[1];
    }

    public function get__height()
    {
        $dimensions = explode(" ", $this->getProperty('file_hw_original'));

        return $dimensions[0];
    }

    public function get__file_hw_original()
    {
        if (empty($this->file_hw_original)) {
            ee()->load->library('filemanager');
            $image_dimensions = $this->actLocally(function($path) {
                return ee()->filemanager->get_image_dimensions($path);
            });
            if ($image_dimensions !== false) {
                $this->setRawProperty('file_hw_original', $image_dimensions['height'] . ' ' . $image_dimensions['width']);
            }
        }

        return $this->file_hw_original;
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
    private function getSubfoldersPath()
    {
        if (empty($this->_subfolderPath)) {
            $directory_id = $this->directory_id;
            $subfolders = [];
            while ($directory_id != 0) {
                $parent = $this->getModelFacade()->get('Directory', $directory_id)->fields('file_id', 'directory_id', 'file_name')->first();
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
    private function getBaseServerPath()
    {
        if (empty($this->_baseServerPath)) {
            $this->_baseServerPath = rtrim($this->UploadDestination->server_path, '\\/') . '/';
        }
        return $this->_baseServerPath;
    }

    /**
     * Get base url for upload location
     *
     * @return string
     */
    private function getBaseUrl()
    {
        if (empty($this->_baseUrl)) {
            $this->_baseUrl = rtrim($this->UploadDestination->url, '\\/') . '/';
        }
        return $this->_baseUrl;
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

        if($filesystem->isLocal()) {
            return $callback($path);
        }

        $tmp = $filesystem->copyToTempFile($path);
        $result = $callback($tmp['path']);

        fclose($tmp['file']);
        
        return $result;
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
     * thumbnail path of the file
     *
     * @return string The absolute path to the file
     */
    public function getAbsoluteThumbnailPath()
    {
        return $this->getBaseServerPath() . '_thumbs/' . $this->getSubfoldersPath() . $this->file_name;
    }

    /**
     * Uses the file's upload destination's url to compute the absolute URL of
     * the file
     *
     * @return string The absolute URL to the file
     */
    public function getAbsoluteURL()
    {
        return $this->UploadDestination->getFilesystem()->getUrl($this->getSubfoldersPath() . $this->file_name);
    }

    /**
     * Uses the file's upload destination's URL to compute the absolute thumbnail
     *  URL of the file
     *
     * @return string The absolute thumbnail URL to the file
     */
    public function getAbsoluteThumbnailURL()
    {
        $filesystem = $this->UploadDestination->getFilesystem();

        if (! $filesystem->exists($this->getAbsoluteThumbnailPath())) {
            return $this->getAbsoluteURL();
        }

        return $filesystem->getUrl('_thumbs/' . $this->getSubfoldersPath() . $this->file_name);
    }

    public function getThumbnailUrl()
    {
        return $this->getAbsoluteThumbnailURL();
    }

    public function onBeforeDelete()
    {
        $filesystem = $this->UploadDestination->getFilesystem();

        // Remove the file
        if ($this->exists()) {
            $filesystem->delete($this->getAbsolutePath());
        }

        // Remove the thumbnail if it exists
        if ($filesystem->exists($this->getAbsoluteThumbnailPath())) {
            $filesystem->delete($this->getAbsoluteThumbnailPath());
        }

        // Remove any manipulated files as well
        foreach ($this->UploadDestination->FileDimensions as $file_dimension) {
            $file = rtrim($file_dimension->getAbsolutePath(), '/') . '/' . $this->file_name;

            if ($filesystem->exists($file)) {
                $filesystem->delete($file);
            }
        }

        // Remove front-end manipulations
        $manipulations = ['resize', 'crop', 'rotate', 'webp'];
        foreach ($manipulations as $manipulation) {
            $ext = strrchr($this->file_name, '.');
            $basename = ($ext === false) ? $this->file_name : substr($this->file_name, 0, -strlen($ext));
            $renamer = strrchr($basename, '_');
            $basename = ($renamer === false) ? $basename : substr($basename, 0, -strlen($renamer));
            $pattern = rtrim($this->UploadDestination->server_path, '/') . '/_' . $manipulation . '/' . $basename . '_*';
            // Can't do a glob with flysys
            // foreach (glob($pattern) as $file) {
            //     $filesystem->delete($file);
            // }
            $files = $filesystem->getDirectoryContents(rtrim($this->UploadDestination->server_path, '/') . '/_' . $manipulation . '/');
            $files = array_filter($files, function($file) use($basename){
                return (strpos($file, "{$basename}_") === 0);
            });
            foreach($files as $file) {
                $filesystem->delete($file);
            }
        }
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
        $filesystem = $this->UploadDestination->getFilesystem();
        return $filesystem->exists($this->getAbsolutePath());
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
        $cat_groups = array();

        if ($this->UploadDestination->cat_group) {
            $cat_groups = explode('|', (string) $this->UploadDestination->cat_group);
        }

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
}

// EOF
