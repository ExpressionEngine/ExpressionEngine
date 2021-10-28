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
 * Writable Validation Rule
 */
class Writable extends ValidationRule
{
    protected $fs;
    protected $all_values = array();

    public function validate($key, $value)
    {
        return $this->getFilesystem()->isWritable(parse_config_variables($value, $this->all_values));
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
