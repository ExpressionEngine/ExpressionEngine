<?php

namespace EllisLab\ExpressionEngine\Service\File;

use FilesystemIterator;

class Iterator extends FilesystemIterator {

	protected $root_path;

	public function __construct($path)
	{
		$flags = FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS | FilesystemIterator::KEY_AS_FILENAME;

		parent::__construct($path, $flags);

		$this->root_path = $path;
		$this->setInfoClass(__NAMESPACE__.'\\File');
	}

	public function current()
	{
		$object = parent::current();
		$object->setDirectory($this->root_path);
		return $object;
	}

}
