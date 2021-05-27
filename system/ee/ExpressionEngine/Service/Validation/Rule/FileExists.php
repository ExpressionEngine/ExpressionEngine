<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Validation\Rule;

use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Service\Validation\ValidationRule;

/**
 * File Exists Validation Rule
 */
class FileExists extends ValidationRule
{
    protected $fs;
    protected $all_values = array();

    public function validate($key, $value)
    {
        if ($this->getFilesystem()->exists(parse_config_variables($value, $this->all_values))) {
            return true;
        }

        // STOP if not exists, there's no point in further validating an
        // invalid file path
        if ($value !== null && $value !== '') {
            return $this->stop();
        }

        return false;
    }

    public function getLanguageKey()
    {
        return 'invalid_path';
    }

    protected function getFilesystem()
    {
        if (! isset($this->fs)) {
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
