<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Low Multibyte class, for optional multibyte support
 */
class Pro_multibyte
{
    /**
     * Magic Call Method
     *
     * @see        __callStatic()
     */
    public function __call($name, $args)
    {
        return $this->__callStatic($name, $args);
    }

    /**
     * Magic Call Method
     *
     * @access     public
     * @return     mixed
     */
    public static function __callStatic($name, $args)
    {
        // Compose multibyte function
        $function = MB_ENABLED ? 'mb_' . $name : $name;

        if (function_exists($function)) {
            // Execute
            return call_user_func_array($function, $args);
        } else {
            // Or throw error
            throw new Exception($function . ' is not a valid function.');
        }
    }
}
// End of file Pro_multibyte.php
