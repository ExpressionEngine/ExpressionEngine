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

use ExpressionEngine\Library\Data\Collection;

/**
 * Curl AsyncRequest
 */
class AsyncRequest extends Request
{
    private $request;
    public $url;
    public $config;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->url = $request->url;
        $this->config = $request->config;
    }
}

// EOF
