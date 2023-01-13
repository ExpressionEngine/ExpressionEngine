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
 * Presence Dependency Validation Rule
 *
 * 'nickname' => 'whenPresent|min_length[5]'
 * 'email' => 'whenPresent[newsletter]|email'
 */
class WhenPresent extends ValidationRule
{
    protected $all_values = array();

    public function validate($key, $value)
    {
        if (empty($this->parameters)) {
            return isset($value) ? true : $this->skip();
        }

        foreach ($this->parameters as $field_name) {
            if (! array_key_exists($field_name, $this->all_values)) {
                return $this->skip();
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
