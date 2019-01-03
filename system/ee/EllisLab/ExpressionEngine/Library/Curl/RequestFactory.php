<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\Curl;

/**
 * Curl Request Factory
 */
class RequestFactory {

	public function get($url, $data = array(), $callback = NULL)
	{
		return new GetRequest($url, $data, $callback);
	}

	public function post($url, $data = array(), $callback = NULL)
	{
		return new PostRequest($url, $data, $callback);
	}

}

// EOF
