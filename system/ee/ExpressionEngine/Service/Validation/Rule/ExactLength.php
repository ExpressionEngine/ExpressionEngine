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
 * Exact Length Validation Rule
 */
class ExactLength extends ValidationRule
{
    public function validate($key, $value)
    {
        ee()->load->helper('multibyte');

        list($length) = $this->assertParameters('length');

        $length = $this->numericOrConstantParameter($length);

        if ($length === false) {
            return false;
        }

        return (ee_mb_strlen($value) == $length);
    }

    public function getLanguageKey()
    {
        return 'exact_length';
    }
}

// EOF
