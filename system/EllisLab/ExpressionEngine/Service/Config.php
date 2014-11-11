<?php

namespace EllisLab\ExpressionEngine\Service;

/**
*
*/
class Config
{
	private $config;

	function __construct()
	{
		$this->config =& get_config();
	}

	public function item($name)
	{
		if (isset($config[$name]))
		{
			return $config[$name];
		}
		else
		{
			return FALSE;
		}
	}
}
