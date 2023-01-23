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

use ExpressionEngine\Library\Filesystem\Adapter\Local;
use ExpressionEngine\Dependency\League\Flysystem;

/**
 * File Service Factory
 */
class Factory
{
    public function getPath($path, ?Flysystem\AdapterInterface $adapter = null)
    {
        if (is_null($adapter)) {
            $adapter = new Local(['path' => $path]);
        }else{
            $adapter->setPathPrefix($path);
        }
        
        return new Directory($adapter);
    }

    public function makeUpload()
    {
        return new Upload();
    }
}

// EOF
