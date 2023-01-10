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
 * Not Banned Validation Rule
 */
class NotBanned extends ValidationRule
{
    protected $last_error = '';

    public function validate($key, $value)
    {
        if (ee()->session->ban_check($key, $value)) {
            $this->last_error = $key . '_taken';
            return false;
        }

        return true;
    }

    public function getLanguageKey()
    {
        return $this->last_error;
    }
}

// EOF
