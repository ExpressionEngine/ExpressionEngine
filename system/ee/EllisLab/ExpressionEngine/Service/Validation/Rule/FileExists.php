<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Validation\Rule;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;
use EllisLab\ExpressionEngine\Service\Validation\ValidationRule;

/**
 * File Exists Validation Rule
 */
class FileExists extends ValidationRule {

	protected $fs;
	protected $all_values = array();

	public function validate($key, $value)
	{
		if ($this->getFilesystem()->exists(parse_config_variables($value, $this->all_values)))
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
		return 'invalid_path';
	}

	protected function getFilesystem()
	{
		if ( ! isset($this->fs))
		{
			$this->fs = new Filesystem();
		}

		return $this->fs;
	}

	public function setAllValues(array $values)
	{
		$this->all_values = $values;
	}
}

// EOF
