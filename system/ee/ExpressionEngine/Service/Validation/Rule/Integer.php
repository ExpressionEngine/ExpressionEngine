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
 * Integer Validation Rule
 */
class Integer extends ValidationRule
{
    public function validate($key, $value)
    {
        return (bool) preg_match('/^[\-+]?[0-9]+$/', $value);
    }

    public function getLanguageKey()
    {
        return 'integer';
    }
}

// EOF
