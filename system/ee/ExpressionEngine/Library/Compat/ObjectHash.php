<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Compat;

/**
 * Preserve native object hash strings without the PHP 8.6 deprecation.
 */
class ObjectHash
{
    public static function get($object)
    {
        if (PHP_VERSION_ID < 80100) {
            return spl_object_hash($object);
        }

        // Since PHP 8.1, native hashes are a padded object ID followed by zeros.
        return sprintf('%016x0000000000000000', spl_object_id($object));
    }
}
