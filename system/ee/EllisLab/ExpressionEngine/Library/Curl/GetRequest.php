<?php

namespace EllisLab\ExpressionEngine\Library\Curl;

class GetRequest extends Request {

	public function __construct($url, $data = array(), $callback = NULL)
	{
		if ( ! empty($data))
		{
			$url = trim($url, '/') . '/' . http_build_query($data);
		}

		return parent::__construct($url, array(), $callback);
	}

}

// EOF
