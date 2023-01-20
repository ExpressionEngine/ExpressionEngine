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
 * Installation and Update Javascript
 */
class Javascript
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $file = EE_APPPATH . 'javascript/compressed/jquery/jquery.js';

        $contents = file_get_contents($file);

        header('Content-Length: ' . strlen($contents));
        header("Content-type: text/javascript");
        exit($contents);
    }
}
// END CLASS

// EOF
