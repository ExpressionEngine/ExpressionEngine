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
 * Curl GET Request
 */
class GetRequest extends Request
{
    public function __construct($url, $data = array(), $callback = null)
    {
        if (! empty($data)) {
            $url = trim($url, '/') . '?' . http_build_query($data);
        }

        parent::__construct($url, array(), $callback);
    }
}

// EOF
