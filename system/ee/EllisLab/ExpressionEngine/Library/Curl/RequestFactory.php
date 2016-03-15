<?php

namespace EllisLab\ExpressionEngine\Library\Curl;

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
