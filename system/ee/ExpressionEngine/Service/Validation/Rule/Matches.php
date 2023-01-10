<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Validation\Rule;

use ExpressionEngine\Service\Validation\ValidationRule;

/**
 * Matches Validation Rule
 */
class Matches extends ValidationRule
{
    protected $all_values = array();

    public function validate($key, $value)
    {
        foreach ($this->parameters as $field_name) {
            if (! array_key_exists($field_name, $this->all_values)) {
                return isset($value); // both not set technically matches
            }

            if ($this->all_values[$field_name] != $value) {
                return false;
            }
        }

        return true;
    }

    public function setAllValues(array $values)
    {
        $this->all_values = $values;
    }
}

// EOF
