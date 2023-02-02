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

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Library\Filesystem\FilesystemException;

/**
 * A directory behaves just like the filesystem rooted at a certain path
 */
class Directory extends Filesystem
{
    protected $url;
    protected $root;

    // public function __construct($path)
    // {
    //     parent::__construct();
    //     $this->root = realpath($path);
    // }

    /**
     * @override
     */
    protected function normalize($path)
    {
        if ($path == '..' || strpos($path, '../') !== false) {
            throw new FilesystemException('Attempting to access file outside of directory.');
        }

        // return $this->flysystem->getAdapter()->applyPathPrefix($path);

        return $path;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getUrl($filename = null)
    {
        if (empty($this->url)) {
            if(!method_exists($this->getBaseAdapter(), 'getBaseUrl')) {
                throw new \Exception('No directory URL given.');
            }

            $this->url = $this->getBaseAdapter()->getBaseUrl();
        }

        $url = rtrim($this->url, '/') . '/';

        if (! isset($filename)) {
            return $url;
        }

        // We have places that are avoiding calling this method because of the
        // possible exception when file does not exist.  This may affect other
        // code (though initial searches suggest not) so leaving this comment.
        // if (! $this->exists($filename)) {
        //     throw new \Exception('File does not exist.');
        // }

        // URL Encode everything except the forward slashes
        return $url . str_replace("%2F", "/", rawurlencode(ltrim($filename, '/')));
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
