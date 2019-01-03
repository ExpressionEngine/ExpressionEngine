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
 * Curl POST Request
 */
class PostRequest extends Request {

	public function __construct($url, $data = array(), $callback = NULL)
	{
		$config = array();

		if ( ! empty($data))
		{
			$config['CURLOPT_POST'] = 1;
			$config['CURLOPT_POSTFIELDS'] = http_build_query($data);
		}

		return parent::__construct($url, $config, $callback);
	}

}

// EOF
