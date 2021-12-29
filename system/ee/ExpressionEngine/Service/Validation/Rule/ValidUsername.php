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
 * Username Validation Rule
 */
class ValidUsername extends ValidationRule
{
    public function validate($key, $username)
    {
        if (preg_match("/[\|'\"!<>\{\}]/", $username)) {
            return 'invalid_characters_in_username';
        }

        // Is username min length correct?
        $un_length = ee()->config->item('un_min_len');
        if (strlen($username) < ee()->config->item('un_min_len')) {
            return sprintf(lang('username_too_short'), $un_length);
        }

        if (strlen($username) > USERNAME_MAX_LENGTH) {
            return 'username_too_long';
        }

        return true;
    }
}

// EOF
