<?php

namespace EllisLab\ExpressionEngine\Service\File;

use SplFileObject;

class File extends SplFileObject {

	protected $url;
	protected $directory;

	public function setDirectory($path)
	{
		$this->directory = $path;
	}

	public function getDirectory()
	{
		return $this->directory;
	}

	public function setUrl($url)
	{
		$this->url = rtrim($url, '/').'/'.$this->getFilename();
	}

	public function getUrl()
	{
		return $this->url;
	}

}
