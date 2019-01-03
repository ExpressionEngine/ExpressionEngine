<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\File;

/**
 * File Service Filter Iterator
 */
class FilterIterator extends \FilterIterator {

	public function accept()
	{
		$inner = $this->getInnerIterator();

		if (is_null($inner))
		{
			return FALSE;
		}

		if ($inner->isDir())
		{
			return FALSE;
		}

		$file = $inner->getFilename();

		if ($file == '')
		{
			return FALSE;
		}

		if ($file[0] == '.')
		{
			return FALSE;
		}

		if ($file == 'index.html')
		{
			return FALSE;
		}

		return TRUE;
	}

}

// EOF
