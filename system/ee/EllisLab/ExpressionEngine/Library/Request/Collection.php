<?php

namespace EllisLab\ExpressionEngine\Library\Request;

use EllisLab\ExpressionEngine\Library\Data\Collection;

class RequestCollection extends Collection {

	public $window = INF;
	public $callback = NULL;

	public function __construct($requests, $config = array())
	{
		$collection = array();
		$objs = array();
		$urls = array();

		foreach ($requests as $request)
		{
			if (is_subclass($request, 'Request'))
			{
				$objs[] = $request;
				continue;
			}

			if (filter_var($request, FILTER_VALIDATE_URL) === FALSE)
			{
				throw new \Exception('Invalid request URL');
			} else {
				$urls[] = $request;
			}
		}

		if ( ! (empty($urls) || empty($objs)))
		{
			throw new \Exception('Cannot mix data types when instantiating RequestCollection');
		}

		if ( ! empty($urls))
		{
			$collection = array_map(function($url) {
				$method = empty($config['method']) ? 'GetRequest' : ucfirst(strtolower($config['method'])) . 'Request';
				$request = new $method($url, $config['data']);

				if (isset($config['async']) && $config['async'] === TRUE)
				{
					$request = new AsynRequest($request);
				}

				return $request;
			}, $urls);
		}

		if ( ! empty($objs))
		{
			$collection = $objs;
		}

		return parent::__construct($collection);
	}

	public function exec($callback = NULL)
	{
	}

	public function setWindow($size)
	{
		$this->window = $size;
	}

	public function rollingCurl($requests)
	{
	}

}
