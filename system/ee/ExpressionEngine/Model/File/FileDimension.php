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

use ExpressionEngine\Service\Model\Model;

/**
 * File Dimension Model
 *
 * A model representing one of image manipulations that can be applied on
 * images uploaded to its corresponding upload destination.
 */
class FileDimension extends Model
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'file_dimensions';

    protected static $_events = array(
        'afterDelete',
    );

    protected static $_typed_columns = array(
        //'width'  => 'int',
        //'height' => 'int'
        'quality' => 'int',
    );

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'UploadDestination' => array(
            'type' => 'belongsTo',
            'from_key' => 'upload_location_id'
        ),
        'Watermark' => array(
            'type' => 'hasOne',
            'from_key' => 'watermark_id',
            'to_key' => 'wm_id'
        )
    );

    protected static $_validation_rules = array(
        'short_name' => 'required|xss|alphaDash|notNumeric|uniqueWithinSiblings[UploadDestination,FileDimensions]',
        'resize_type' => 'enum[crop,constrain]',
        'width' => 'isNatural|validateDimension',
        'height' => 'isNatural|validateDimension',
        'quality' => 'isNatural|lessThan[101]'
    );

    protected $id;
    protected $site_id;
    protected $upload_location_id;
    protected $title;
    protected $short_name;
    protected $resize_type;
    protected $width;
    protected $height;
    protected $watermark_id;
    protected $quality;

    public function onAfterDelete()
    {
        //delete the root manipulation folder
        $filesystem = $this->UploadDestination->getFilesystem();
        $manipulatedFolderPath = '_' . $this->short_name;
        if ($filesystem->exists($manipulatedFolderPath) && $filesystem->isDir($manipulatedFolderPath)) {
            $filesystem->deleteDir($manipulatedFolderPath);
        }
        //go into subfolder and delete manipulations there
        $folders = ee('Model')->get('Directory')->filter('upload_location_id', $this->UploadDestination->getId())->all();
        foreach ($folders as $folder) {
            $manipulatedFolderPath = $folder->getAbsolutePath() . '/_' . $this->short_name;
            if ($filesystem->exists($manipulatedFolderPath) && $filesystem->isDir($manipulatedFolderPath)) {
                $filesystem->deleteDir($manipulatedFolderPath);
            }
        }
    }

    /**
     * At least a height OR a width must be specified if there is no watermark selected
     */
    public function validateDimension($key, $value, $params, $rule)
    {
        if (empty($this->width) && empty($this->height) && empty($this->watermark_id)) {
            $rule->stop();

            return lang('image_manip_dimension_required');
        }

        return true;
    }

    /**
     * Calcuates the dimensions of a given file based on the constraints of this
     * modification
     *
     * @param File $file A File entity
     * @return array An associative array with 'width' and 'height' keys.
     */
    public function getNewDimensionsOfFile(File $file)
    {
        if (! $file->isImage()) {
            return false;
        }

        ee()->load->library('image_lib');
        ee()->image_lib->clear();

        $original_dimensions = explode(" ", $file->file_hw_original);

        // We do math on these values, so we'll coerce them into integers
        // now. Empty strings ('') turn into 0.
        $original_dimensions[0] = (int) $original_dimensions[0];
        $original_dimensions[1] = (int) $original_dimensions[1];

        // If we have 0 or negative numbers for the original height/width
        // this is not an image
        if ($original_dimensions[0] < 1 || $original_dimensions[1] < 1) {
            return false;
        }

        $width = $this->width;
        $height = $this->height;

        $force_master_dim = false;

        // If either h/w unspecified, calculate the other here
        if ($this->width == '' or $this->width == 0) {
            $width = ($original_dimensions[1] / $original_dimensions[0]) * $this->height;
            $force_master_dim = 'height';
        } elseif ($this->height == '' or $this->height == 0) {
            // Old h/old w * new width
            $height = ($original_dimensions[0] / $original_dimensions[1]) * $this->width;
            $force_master_dim = 'width';
        }

        // If the original is smaller than the thumb hxw, we'll make a copy rather than upsize
        if (($force_master_dim == 'height' && $original_dimensions[0] < $height) or
            ($force_master_dim == 'width' && $original_dimensions[1] < $width) or
            ($force_master_dim == false &&
                ($original_dimensions[1] < $width && $original_dimensions[0] < $height)
            )) {
            return array(
                'height' => $original_dimensions[0],
                'width' => $original_dimensions[1],
            );
        }

        $config = array(
            'source_image' => $file->getAbsolutePath(),
            'image_library' => ee()->config->item('image_resize_protocol'),
            'library_path' => ee()->config->item('image_library_path'),
            'maintain_ratio' => true,
            'width' => $width,
            'height' => $height,
            'master_dim' => $force_master_dim
        );

        if (isset($this->resize_type) && $this->resize_type == 'crop') {
            // Scale the larger dimension up so only one dimension of our
            // image fits within the desired dimension
            if ($original_dimensions[1] > $original_dimensions[0]) {
                $config['width'] = round($original_dimensions[1] * $height / $original_dimensions[0]);

                // If the new width ends up being smaller than the
                // resized width
                if ($config['width'] < $width) {
                    $config['width'] = $width;
                    $config['master_dim'] = 'width';
                }
            } elseif ($original_dimensions[0] > $original_dimensions[1]) {
                $config['height'] = round($original_dimensions[0] * $width / $original_dimensions[1]);

                // If the new height ends up being smaller than the
                // desired resized height
                if ($config['height'] < $height) {
                    $config['height'] = $height;
                    $config['master_dim'] = 'height';
                }
            }
            // If we're dealing with a perfect square image
            elseif ($original_dimensions[0] == $original_dimensions[1]) {
                // And the desired image is landscape, edit the
                // square image's width to fit
                if ($width > $height ||
                    $width == $height) {
                    $config['width'] = $width;
                    $config['master_dim'] = 'width';
                }
                // If the desired image is portrait, edit the
                // square image's height to fit
                elseif ($width < $height) {
                    $config['height'] = $height;
                    $config['master_dim'] = 'height';
                }
            }

            ee()->image_lib->initialize($config);

            $config['x_axis'] = ((ee()->image_lib->width / 2) - ($width / 2));
            $config['y_axis'] = ((ee()->image_lib->height / 2) - ($height / 2));
            $config['maintain_ratio'] = false;
            $config['width'] = $width;
            $config['height'] = $height;
        }

        ee()->image_lib->initialize($config);

        $dimensions = array(
            'height' => round(ee()->image_lib->height),
            'width' => round(ee()->image_lib->width),
        );

        return $dimensions;
    }

    /**
     * Uses the upload destination's server path to compute the absolute
     * path of the directory
     *
     * @return string The absolute path to the directory
     */
    public function getAbsolutePath()
    {
        return $this->UploadDestination->getAbsolutePath() . '/_' . $this->short_name . '/';
    }

    /**
     * Uses the upload destination's url to compute the absolute URL of
     * the directory
     *
     * @return string The absolute URL to the directory
     */
    public function getAbsoluteURL()
    {
        return $this->UploadDestination->getFilesystem()->getUrl('_' . rawurlencode($this->short_name) . '/');
    }
}

// EOF
