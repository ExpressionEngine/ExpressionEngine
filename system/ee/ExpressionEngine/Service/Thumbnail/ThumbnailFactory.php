<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Thumbnail;

use ExpressionEngine\Model\File;

/**
 * Thumbnail Service Factory
 */
class ThumbnailFactory
{
    public function get(File\FileSystemEntity $file = null)
    {
        $thumb = new Thumbnail($file);

        // If the thumbnail is missing, and this is an image file generate
        // the thumbnail now
        if (! $thumb->exists()
            && $file
            && $file->isFile()
            && $file->exists()
            && $file->isImage()) {
            $thumb = $this->make($file);
        }

        return $thumb;
    }

    public function make(File\FileSystemEntity $file)
    {
        // We only make thumbnails of images
        if ($file->isImage()) {
            ee()->load->library('filemanager');
            $dir = $file->UploadDestination;

            $success = ee()->filemanager->create_thumb(
                $file->getAbsolutePath(),
                array(
                    'directory' => $dir,
                    'server_path' => $dir->server_path,
                    'file_name' => $file->file_name,
                    'dimensions' => []
                ),
                true, // Regenerate thumbnails
                false // Regenerate all images
            );
        }

        $thumb = new Thumbnail($file);

        return $thumb;
    }
}

// EOF
