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
 * XSS Validation Rule
 */
class Xss extends ValidationRule
{
    public function validate($key, $value)
    {
        return ($value == ee('Security/XSS')->clean($value)) ? true : $this->stop();
    }

    public function getLanguageKey()
    {
        return sprintf(lang('invalid_xss_check'), ee('CP/URL')->make('homepage'));
    }
}
