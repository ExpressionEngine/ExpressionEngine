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

use EllisLab\ExpressionEngine\Library\Data\Collection;

/**
 * Curl AsyncRequest
 */
class AsyncRequest extends Request {

	private $request;

	public function __construct(Request $request)
	{
		$this->request = $request;
		$this->url = $request->url;
		$this->config = $request->config;
	}

}

// EOF
