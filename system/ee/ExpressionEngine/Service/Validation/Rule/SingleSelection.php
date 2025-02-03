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
 * SingleSelection Validation Rule
 */
class SingleSelection extends ValidationRule
{
    public function validate($key, $value)
    {
        if (is_array($value) && count($value) > 1) {
            return $this->stop();
        }

        return true;
    }

    public function getLanguageKey()
    {
        return 'multiple_selection_not_allowed';
    }
}
