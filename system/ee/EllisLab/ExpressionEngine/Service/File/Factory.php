<?php

namespace EllisLab\ExpressionEngine\Service\File;

use Closure;

class Factory {

	public function __construct(array $dirs)
	{
		$this->directories = $dirs;
	}

	public function get($dir)
	{
		$dir = $this->ensurePrefix($dir);

		if ( ! array_key_exists($dir, $this->directories))
		{
			throw new \Exception('Cannot find named directory: "'.$dir.'."');
		}

		$path = $this->directories[$dir];

		if ($path instanceOf Closure)
		{
			$path = $path();
		}

		return $this->getPath($path);
	}

	public function getPath($path)
	{
		return new Directory($path);
	}

	private function ensurePrefix($dir)
	{
		if (strpos($dir, ':') > 0)
		{
			return $dir;
		}

		return 'ee:'.$dir;
	}
}
