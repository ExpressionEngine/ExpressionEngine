<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\File;

/**
 * File Service Factory
 */
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
