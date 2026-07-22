<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
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
    /**
     * Get the stored original image width.
     *
     * @return string|null Original image width as a numeric string, or null when unavailable.
     */
    public function get__width()
    {
        $dimensions = $this->getOriginalDimensions();

        return $dimensions === null ? null : $dimensions['width'];
    }

    /**
     * Get the stored original image height.
     *
     * @return string|null Original image height as a numeric string, or null when unavailable.
     */
    public function get__height()
    {
        $dimensions = $this->getOriginalDimensions();

        return $dimensions === null ? null : $dimensions['height'];
    }

    /**
     * Parse the stored original image dimensions as an atomic pair.
     *
     * @return array|null Height and width as positive numeric strings, or null when invalid.
     */
    private function getOriginalDimensions()
    {
        $value = $this->getProperty('file_hw_original');

        if (! is_string($value)) {
            return null;
        }

        $dimensions = explode(' ', $value);

        if (count($dimensions) !== 2) {
            return null;
        }

        list($height, $width) = $dimensions;

        if (
            ! ctype_digit($height)
            || ! ctype_digit($width)
            || (int) $height <= 0
            || (int) $width <= 0
        ) {
            return null;
        }

        return compact('height', 'width');
    }

    public function get__title()
    {
        return \htmlspecialchars((string)$this->getRawProperty('title'));
    }

    public function get__file_hw_original()
    {
        if (empty($this->file_hw_original) && !empty($this->file_name)) {
            ee()->load->library('filemanager');
            try {
                $image_dimensions = $this->actLocally(function ($path) {
                    return ee()->filemanager->get_image_dimensions($path);
                });
            } catch (\LogicException $e) {
                return $this->file_hw_original;
            }
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
