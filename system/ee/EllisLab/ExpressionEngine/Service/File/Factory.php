<?php

namespace EllisLab\ExpressionEngine\Service\File;

class Factory {

	public function getPath($path)
	{
		return new Directory($path);
	}

	public function makeUpload()
	{
		return new Upload();
	}
}

// EOF
