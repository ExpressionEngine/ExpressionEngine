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
 * Screen Name Validation Rule
 */
class ValidScreenName extends ValidationRule
{
    protected $last_error = '';

    public function validate($key, $screen_name)
    {
        $screen_name = (string) $screen_name;
        if (preg_match('/[\{\}<>]/', $screen_name)) {
            $this->last_error = 'disallowed_screen_chars';
            return false;
        }

        if (strlen($screen_name) > USERNAME_MAX_LENGTH) {
            $this->last_error = 'screenname_too_long';
            return false;
        }

        if (trim(preg_replace("/&nbsp;*/", '', $screen_name)) == '') {
            $this->last_error = 'screen_name_taken';
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
