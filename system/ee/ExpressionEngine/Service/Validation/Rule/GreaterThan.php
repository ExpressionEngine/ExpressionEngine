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
 * Greater Than Validation Rule
 */
class GreaterThan extends ValidationRule
{
    public function validate($key, $value)
    {
        list($compare) = $this->assertParameters('compare_to');

        $compare = $this->numericOrConstantParameter($compare);

        if ($compare === false) {
            return false;
        }

        return ($value > $compare);
    }

    public function getLanguageKey()
    {
        return 'greater_than';
    }
}

// EOF
