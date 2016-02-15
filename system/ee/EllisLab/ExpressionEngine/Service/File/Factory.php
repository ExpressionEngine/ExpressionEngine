<?php

namespace EllisLab\ExpressionEngine\Service\File;

use Closure;

class Factory {

	public function getPath($path)
	{
		return new Directory($path);
	}
}
