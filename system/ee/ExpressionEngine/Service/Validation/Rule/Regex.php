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

use ExpressionEngine\Service\Validation\ValidationRule;

/**
 * Regular Expression Validation Rule
 */
class Regex extends ValidationRule
{
    public function validate($key, $value)
    {
        list($regex) = $this->assertParameters('expression');

        return (bool) preg_match($regex, $value);
    }
}
