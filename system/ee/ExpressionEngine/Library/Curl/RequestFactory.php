<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Curl;

/**
 * Curl Request Factory
 */
class RequestFactory
{
    public function get($url, $data = array(), $callback = null)
    {
        return new GetRequest($url, $data, $callback);
    }

    public function post($url, $data = array(), $callback = null)
    {
        return new PostRequest($url, $data, $callback);
    }
}

// EOF
