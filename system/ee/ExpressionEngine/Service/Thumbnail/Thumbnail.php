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
use InvalidArgumentException;

/**
 * Thumbnail Service
 */
class Thumbnail
{
    /**
     * @var str The url to the thumbnail
     */
    protected $url;

    /**
     * HTML code to use as thumbnail
     *
     * @var string $tag
     */
    protected $tag;

    /**
     * @var str The path to the thumbnail
     */
    protected $path;

    /**
     * @var boolean Whether the file is missing
     */
    protected $missing = false;

    /**
     * Filesystem where the thumbnail is being stored
     *
     * @var ExpresionEngine\Library\Filesystem\Filesystem|null
     */
    protected $filesystem = null;

    protected $_exists;

    /**
     * Constructor: sets the url and path properties based on the arguments
     *
     * @param File $file (optional) A File entity from which we'll calculate the
     *   thumbnail url and path.
     */
    public function __construct(File\FileSystemEntity $file = null)
    {
        $this->setDefault();

        if ($file) {
            if (! $file->exists()) {
                $this->setMissing();
                return;
            } elseif ($file->isDirectory()) {
                $this->tag = '<i class="fal fa-folder fa-3x"></i>';
            } elseif ($file->isEditableImage() || $file->isSVG()) {
                $this->url = $file->getAbsoluteThumbnailURL();
                $this->path = $file->getAbsoluteThumbnailPath();
                $this->tag = '<img src="' . $this->url . '" alt="' . $file->title . '" title="' . $file->title .'" class="thumbnail_img" />';
            } else {
                switch ($file->file_type) {
                    case 'doc':
                        $this->tag = '<span class="sr-only">' . lang('file_icon') . '</span><i class="fal fa-file-alt fa-3x"></i>';
                        if (strpos($file->mime_type, 'pdf') !== false) {
                            $this->tag = '<span class="sr-only">' . lang('pdf_file_icon') . '</span><i class="fal fa-file-pdf fa-3x"></i>';
                        } elseif (strpos($file->mime_type, 'html') !== false || strpos($file->mime_type, 'css') !== false || strpos($file->mime_type, 'xml') !== false) {
                            $this->tag = '<span class="sr-only">' . lang('file_icon') . '</span><i class="fal fa-file-code fa-3x"></i>';
                        } elseif (strpos($file->mime_type, 'excel') !== false || strpos($file->mime_type, 'spreadsheet') !== false || strpos($file->mime_type, 'csv') !== false) {
                            $this->tag = '<span class="sr-only">' . lang('spreadsheet_file_icon') . '</span><i class="fal fa-file-spreadsheet fa-3x"></i>';
                        } elseif (strpos($file->mime_type, 'word') !== false || strpos($file->mime_type, 'document.text') !== false || strpos($file->mime_type, 'richtext') !== false || strpos($file->mime_type, 'rtf') !== false) {
                            $this->tag = '<span class="sr-only">' . lang('word_icon') . '</span><i class="fal fa-file-word fa-3x"></i>';
                        } elseif (strpos($file->mime_type, 'powerpoint') !== false || strpos($file->mime_type, 'presentation') !== false) {
                            $this->tag = '<span class="sr-only">' . lang('powerpoint_icon') . '</span><i class="fal fa-file-powerpoint fa-3x"></i>';
                        }
                        break;
                    case 'archive':
                        $this->tag = '<span class="sr-only">' . lang('archive_icon') . '</span><i class="fal fa-file-archive fa-3x"></i>';
                        break;
                    case 'audio':
                        $this->tag = '<span class="sr-only">' . lang('audio_icon') . '</span><i class="fal fa-file-audio fa-3x"></i>';
                        break;
                    case 'video':
                        $this->tag = '<span class="sr-only">' . lang('video_icon') . '</span><i class="fal fa-file-video fa-3x"></i>';
                        break;
                    default:
                        $this->tag = '<span class="sr-only">' . lang('text_icon') . '</span><i class="fal fa-file fa-3x"></i>';
                        break;
                }
            }

            $this->filesystem = $file->UploadDestination->getFilesystem();
        }
    }

    public function __get($name)
    {
        if (! property_exists($this, $name)) {
            throw new InvalidArgumentException("No such property: '{$name}' on " . get_called_class());
        }

        return $this->$name;
    }

    /**
     * Sets the url and path properties to the default image
     *
     * @return void
     */
    public function setDefault()
    {
        $this->url = PATH_CP_GBL_IMG . 'missing.jpg';
        $this->path = PATH_THEMES . 'asset/img/missing.jpg';
    }

    /**
     * Sets the url and path properties to the missing image
     *
     * @return void
     */
    public function setMissing()
    {
        $this->missing = true;
        $this->url = PATH_CP_GBL_IMG . 'missing.jpg';
        $this->path = PATH_THEMES . 'asset/img/missing.jpg';
        $this->tag = '<i class="fal fa-exclamation-triangle fa-3x"></i>';
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
        return $this->_exists = ($this->filesystem) ? $this->filesystem->exists($this->path) : false;
    }

    /**
     * Determines if the file is writable
     *
     * @return bool TRUE if it is FALSE otherwise
     */
    public function isWritable()
    {
        return ($this->filesystem) ? $this->filesystem->isWritable($this->path) : false;
    }
}

// EOF
