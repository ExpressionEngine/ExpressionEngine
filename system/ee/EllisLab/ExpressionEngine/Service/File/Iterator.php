<?php

namespace EllisLab\ExpressionEngine\Service\File;

use FilesystemIterator;

class Iterator extends FilesystemIterator {

	protected $root_url;
	protected $root_path;

	public function __construct($path)
	{
		$flags = FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS | FilesystemIterator::KEY_AS_FILENAME;

		parent::__construct($path, $flags);

		$this->root_path = $path;
		$this->setInfoClass(__NAMESPACE__.'\\File');
	}

	public function setUrl($url)
	{
		$this->root_url = $url;
	}

	public function next()
	{
		parent::next();

		while ($this->valid() && $this->isDir())
		{
			parent::next();
		}
	}

	public function current()
	{
		if ($this->isDir())
		{
			return NULL;
		}

		$object = parent::current();
		$object->setDirectory($this->root_path);
		$object->setUrl($this->root_url);
		return $object;
	}

}

// EOF
