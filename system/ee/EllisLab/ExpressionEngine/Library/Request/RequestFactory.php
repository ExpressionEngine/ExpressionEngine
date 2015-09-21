<?php

namespace EllisLab\ExpressionEngine\Library\Request;

class RequestFactory {

	public function get($url, $data, $callback = NULL)
	{
		return new GetRequest($url, $data, $callback);
	}

	public function post($url, $data, $callback = NULL)
	{
		return new PostRequest($url, $data, $callback);
	}

}
