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
 * Enum Validation Rule
 */
class Enum extends ValidationRule
{
    public function validate($key, $value)
    {
        return in_array($value, $this->parameters);
    }

    /**
     * Return the language data for the validation error.
     */
    public function getLanguageData()
    {
        $list = implode(', ', $this->parameters);

        return array($this->getName(), $list);
    }
}

// EOF
