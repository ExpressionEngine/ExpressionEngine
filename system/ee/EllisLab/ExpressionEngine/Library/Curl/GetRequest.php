<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Library\Curl;

/**
 * Curl GET Request
 */
class GetRequest extends Request {

	public function __construct($url, $data = array(), $callback = NULL)
	{
		if ( ! empty($data))
		{
			$url = trim($url, '/') . '?' . http_build_query($data);
		}

		return parent::__construct($url, array(), $callback);
	}

}

// EOF
