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
