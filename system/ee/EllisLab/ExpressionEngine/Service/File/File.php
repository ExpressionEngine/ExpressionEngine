<?php

namespace EllisLab\ExpressionEngine\Service\File;

use SplFileObject;

class File extends SplFileObject {

	protected $directory;

	public function setDirectory($path)
	{
		$this->directory = $path;
	}

	public function getDirectory()
	{
		return $this->directory;
	}

}
