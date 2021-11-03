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
 * Password Validation Rule
 */
class PasswordSecure extends ValidationRule
{
    public function validate($key, $value)
    {
        switch (ee()->config->item('require_secure_passwords')) {
            case 'n':
                return true;
                break;
            case 'a':
                return ee('Member')->calculatePasswordComplexity($value) >= 40;
                break;
            case 's':
                return ee('Member')->calculatePasswordComplexity($value) >= 60;
                break;
            case 'y':
            default:
                $count = array('uc' => 0, 'lc' => 0, 'num' => 0);

                $pass = preg_quote($value, "/");

                $len = strlen($pass);

                for ($i = 0; $i < $len; $i++) {
                    $n = substr($pass, $i, 1);

                    if (preg_match("/^[[:upper:]]$/", $n)) {
                        $count['uc']++;
                    } elseif (preg_match("/^[[:lower:]]$/", $n)) {
                        $count['lc']++;
                    } elseif (preg_match("/^[[:digit:]]$/", $n)) {
                        $count['num']++;
                    }
                }

                foreach ($count as $val) {
                    if ($val == 0) {
                        return false;
                    }
                }
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
