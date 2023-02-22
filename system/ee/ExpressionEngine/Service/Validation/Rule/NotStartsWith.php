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
 * Not Starts With Validation Rule
 */
class NotStartsWith extends ValidationRule
{
    public function validate($key, $value)
    {
        list($startsWith) = $this->assertParameters('startsWith');

        return (empty($value) || strpos($value, $startsWith) !== 0);
    }

    public function getLanguageKey()
    {
        return 'not_starts_with';
    }
}

// EOF
