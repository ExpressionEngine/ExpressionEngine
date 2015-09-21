<?php

namespace EllisLab\ExpressionEngine\Library\Request;

use EllisLab\ExpressionEngine\Library\Data\Collection;

class RequestCollection extends Collection {

	public $window = INF;
	public $callback = NULL;

	public function __construct($requests, $config = array())
	{
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
