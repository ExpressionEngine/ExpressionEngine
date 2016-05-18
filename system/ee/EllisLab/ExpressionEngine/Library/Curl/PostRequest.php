<?php

namespace EllisLab\ExpressionEngine\Library\Curl;

class PostRequest extends Request {

	public function __construct($url, $data = array(), $callback = NULL)
	{
		$config = array();

		if ( ! empty($data))
		{
			$config[CURLOPT_POST] = 1;
			$config[CURLOPT_POSTFIELDS] = $data;
		}

		return parent::__construct($url, $config, $callback);
	}

}

// EOF
