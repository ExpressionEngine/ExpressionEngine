<?php

namespace EllisLab\ExpressionEngine\Service\Config;

class Factory {

	public function directory($path)
	{
		return new Directory($path);
	}

	public function file($path)
	{
		return new File($path);
	}
}