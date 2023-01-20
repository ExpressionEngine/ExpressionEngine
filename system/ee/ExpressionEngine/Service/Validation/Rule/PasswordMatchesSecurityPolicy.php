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
 * Password Validation Rule
 */
class PasswordMatchesSecurityPolicy extends ValidationRule
{
    public function validate($key, $value)
    {
        switch (ee()->config->item('password_security_policy')) {
            case 'none':
                return true;
                break;
            case 'strong':
                return ee('Member')->calculatePasswordComplexity($value) >= 60;
                break;
            case 'y':
            case 'basic':
            case 'good':
            default:
                return ee('Member')->calculatePasswordComplexity($value) >= 40;
                break;
        }

        return true;
    }

    public function getLanguageKey()
    {
        return 'not_secure_password';
    }
}

// EOF
