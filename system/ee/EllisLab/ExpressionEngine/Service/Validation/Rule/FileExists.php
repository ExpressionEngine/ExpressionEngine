<?php

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine File Exists Validation Rule
 *
 * @package		ExpressionEngine
 * @subpackage	Validation\Rule
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class FileExists extends ValidationRule {

	protected $fs;

	public function validate($key, $value)
	{
		if ($this->getFilesystem()->exists($value))
		{
			return TRUE;
		}

		// STOP if not exists, there's no point in further validating an
		// invalid file path
		if ($value !== NULL && $value !== '')
		{
			return $this->stop();
		}

		return FALSE;
	}

	public function getLanguageKey()
	{
		return 'file_exists';
	}

	protected function getFilesystem()
	{
		if ( ! isset($this->fs))
		{
			$this->fs = new Filesystem();
		}

		return $this->fs;
	}

}

// EOF
