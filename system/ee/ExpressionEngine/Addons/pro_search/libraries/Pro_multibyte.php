<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Low Multibyte class, for optional multibyte support
 *
 * @package        pro_search
 * @author         ExpressionEngine
 * @link           https://eeharbor.com/pro-search
 * @copyright      Copyright (c) 2022, ExpressionEngine
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
