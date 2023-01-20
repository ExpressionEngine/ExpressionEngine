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

/**
 * File Model
 *
 * A model representing one of many possible upload destintations to which
 * files may be uploaded through the file manager or from the publish page.
 * Contains settings for this upload destination which describe what type of
 * files may be uploaded to it, as well as essential information, such as the
 * server paths where those files actually end up.
 */
class File extends FileSystemEntity
{
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
        if (empty($this->file_hw_original) && !empty($this->file_name)) {
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

    public function onBeforeInsert()
    {
        parent::onBeforeInsert();
        // file_type is set based on mime_type on initial upload
        // and cannot be changed
        $mimes = ee()->config->loadFile('mimes');
        $fileTypes = array_filter(array_keys($mimes), 'is_string');
        foreach ($fileTypes as $fileType) {
            if (in_array($this->getProperty('mime_type'), $mimes[$fileType])) {
                $this->setProperty('file_type', $fileType);
                return;
            }
        }
        //fallback to default
        $this->setProperty('file_type', 'other');
    }
}

// EOF
