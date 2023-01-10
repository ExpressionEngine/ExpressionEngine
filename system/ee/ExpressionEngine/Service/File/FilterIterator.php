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

/**
 * File Service Filter Iterator
 */
class FilterIterator extends \FilterIterator
{
    #[\ReturnTypeWillChange]
    public function accept()
    {
        $inner = $this->getInnerIterator();

        if (is_null($inner)) {
            return false;
        }

        if ($inner->isDir()) {
            return false;
        }

        $file = $inner->getFilename();

        if ($file == '') {
            return false;
        }

        if ($file[0] == '.') {
            return false;
        }

        if ($file == 'index.html') {
            return false;
        }

        return true;
    }
}

// EOF
