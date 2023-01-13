<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Legacy Model Class
 */
class EE_Model
{
    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        log_message('debug', "Model Class Initialized");
    }

    /**
     * __get
     *
     * Allows models to access CI's loaded classes using the same
     * syntax as controllers.
     *
     * @access private
     */
    public function __get($key)
    {
        return ee()->$key;
    }
}
// END Model Class

class_alias('EE_Model', 'CI_Model');

// EOF
