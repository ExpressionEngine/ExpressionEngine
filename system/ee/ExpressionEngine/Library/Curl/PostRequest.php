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
 * Curl POST Request
 */
class PostRequest extends Request
{
    public function __construct($url, $data = array(), $callback = null)
    {
        $config = array();

        if (! empty($data)) {
            $config['CURLOPT_POST'] = 1;
            $config['CURLOPT_POSTFIELDS'] = http_build_query($data);
        }

        parent::__construct($url, $config, $callback);
    }
}

// EOF
