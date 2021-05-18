<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Thumbnail;

use ExpressionEngine\Model\File\File;
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
     * @var str The path to the thumbnail
     */
    protected $path;

    /**
     * @var boolean Whether the file is missing
     */
    protected $missing = false;

    /**
     * Constructor: sets the url and path properties based on the arguments
     *
     * @param File $file (optional) A File entity from which we'll calculate the
     *   thumbnail url and path.
     */
    public function __construct(File $file = null)
    {
        $this->setDefault();

        if ($file) {
            if (! $file->exists()) {
                $this->setMissing();
            } elseif ($file->isImage()) {
                $this->url = $file->getAbsoluteThumbnailURL();
                $this->path = $file->getAbsoluteThumbnailPath();
            }
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
    }

    /**
     * Determines if the file exists
     *
     * @return bool TRUE if it does FALSE otherwise
     */
    public function exists()
    {
        return file_exists($this->path);
    }

    /**
     * Determines if the file is writable
     *
     * @return bool TRUE if it is FALSE otherwise
     */
    public function isWritable()
    {
        return is_writable($this->path);
    }
}

// EOF
