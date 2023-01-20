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
 * Email Validation Rule
 */
class Email extends ValidationRule
{
    public function validate($key, $value)
    {
        if ($value != filter_var($value, FILTER_SANITIZE_EMAIL) or ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }

    public function getLanguageKey()
    {
        return 'valid_email';
    }
}

// EOF
