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

/**
 * File Service Factory
 */
class Factory
{
    public function getPath($path)
    {
        return new Directory($path);
    }

    public function makeUpload()
    {
        return new Upload();
    }
}

// EOF
