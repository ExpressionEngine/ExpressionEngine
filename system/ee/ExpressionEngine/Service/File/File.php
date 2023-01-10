<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\File;

use SplFileObject;

/**
 * File Service File
 */
class File extends SplFileObject
{
    protected $url;
    protected $thumb_url;
    protected $directory;

    public function setDirectory($path)
    {
        $this->directory = $path;
    }

    public function getDirectory()
    {
        return $this->directory;
    }

    public function setUrl($url)
    {
        $this->url = rtrim($url, '/');
    }

    public function setThumbnailUrl($url)
    {
        $this->thumb_url = rtrim($url, '/');
    }

    public function getUrl()
    {
        return $this->url . '/' . $this->getFilename();
    }

    public function getThumbnailUrl()
    {
        if (! isset($this->thumb_url)) {
            return $this->getUrl();
        }

        return $this->thumb_url . '/' . $this->getFilename();
        ;
    }

    public function getMimeType()
    {
        return ee('MimeType')->ofFile($this->getRealPath());
    }

    public function isImage()
    {
        return (strpos($this->getMimeType(), 'image/') === 0);
    }

    public function __get($key)
    {
        if ($key == 'file_name') {
            return $this->getFilename();
        }
    }
}

// EOF
