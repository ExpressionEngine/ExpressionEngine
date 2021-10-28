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
 * Hex Color Validation Rule
 */
class HexColor extends ValidationRule
{
    public function validate($key, $value)
    {
        return (bool) preg_match('/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value);
    }

    public function getLanguageKey()
    {
        return 'hex_color';
    }
}
