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
 * Not empty Dependency Validation Rule
 *
 * 'nickname' => 'whenNotEmpty|min_length[5]'
 * 'email' => 'whenNotEmpty[newsletter]|email'
 */
class WhenNotEmpty extends ValidationRule
{
    protected $all_values = array();

    public function validate($key, $value)
    {
        if (empty($this->parameters)) {
            return (isset($value) && $value !== '' && !is_null($value)) ? true : $this->skip();
        }

        foreach ($this->parameters as $field_name) {
            if (! array_key_exists($field_name, $this->all_values) || $this->all_values[$field_name] === '' || is_null($this->all_values[$field_name])) {
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
