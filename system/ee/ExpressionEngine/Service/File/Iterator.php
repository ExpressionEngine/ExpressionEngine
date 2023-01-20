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

use FilesystemIterator;

/**
 * File Service Iterator
 */
class Iterator extends FilesystemIterator
{
    protected $root_url;
    protected $root_path;

    public function __construct($path)
    {
        $flags = FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS | FilesystemIterator::KEY_AS_FILENAME;

        parent::__construct($path, $flags);

        $this->root_path = $path;
        $this->setInfoClass(__NAMESPACE__ . '\\File');
    }

    public function setUrl($url)
    {
        $this->root_url = $url;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        parent::next();

        while ($this->valid() && $this->isDir()) {
            parent::next();
        }
    }

    public function current()
    {
        if ($this->isDir()) {
            return null;
        }

        $object = parent::current();
        $object->setDirectory($this->root_path);
        $object->setUrl($this->root_url);

        return $object;
    }
}

// EOF
