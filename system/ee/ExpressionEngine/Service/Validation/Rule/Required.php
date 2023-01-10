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
 * Required Validation Rule
 */
class Required extends ValidationRule
{
    public function validate($key, $value)
    {
        if (is_null($value)) {
            return $this->stop();
        }
        
        if (! is_array($value)) {
            $value = trim($value);
        }

        if (is_array($value)) {
            $value = array_filter($value, function($val) { 
                return $val !== '';
            });
            if (empty($value)) {
                return $this->stop();
            }
        }

        if ($value === '' or is_null($value)) {
            return $this->stop();
        }

        return true;
    }
}
