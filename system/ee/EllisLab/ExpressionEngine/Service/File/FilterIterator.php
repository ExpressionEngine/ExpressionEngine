<?php

namespace EllisLab\ExpressionEngine\Service\File;

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
