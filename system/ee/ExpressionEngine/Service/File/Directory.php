<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\File;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Library\Filesystem\FilesystemException;

/**
 * A directory behaves just like the filesystem rooted at a certain path
 */
class Directory extends Filesystem
{
    protected $url;
    protected $root;

    public function __construct($path)
    {
        $this->root = realpath($path);
    }

    /**
     * @override
     */
    protected function normalize($path)
    {
        $path = $this->root . '/' . $path;

        if ($path == '..' || strpos($path, '../') !== false) {
            throw new FilesystemException('Attempting to access file outside of directory.');
        }

        return $path;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getUrl($filename = null)
    {
        if (! isset($this->url)) {
            throw new \Exception('No directory URL given.');
        }

        if (! isset($filename)) {
            return $this->url;
        }

        if (! $this->exists($filename)) {
            throw new \Exception('File does not exist.');
        }

        return rtrim($this->url, '/') . '/' . $filename;
    }

    public function getPath($path)
    {
        return $this->normalize($path);
    }

    public function all()
    {
        $it = new Iterator($this->root);
        $it->setUrl($this->url);

        return new FilterIterator($it);
    }
}

// EOF
