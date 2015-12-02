<?php

namespace EllisLab\ExpressionEngine\Service\File;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

// a directory behaves just like the filesystem rooted at a certain path
class Directory extends Filesystem {

	protected $root;

	public function __construct($path)
	{
		$this->root = realpath($path);
	}

	/**
	 * @override
	 */
	protected function normalize($path)
	{
		$path = realpath($this->root.'/'.$path);

		if (strpos($path, $this->root) !== 0)
		{
			throw new FilesystemException('Attempting to access file outside of directory.');
		}

		return $path;
	}

	public function all()
	{
		return new Iterator($this->root);
	}
}
