<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Validation\Rule;

use ExpressionEngine\Service\Validation\ValidationRule;

/**
 * Username Validation Rule
 */
class ValidUsername extends ValidationRule
{
    public const DISALLOWED_CHARACTERS_PATTERN = "/[\|'\"!<>\{\}]/";

    protected $last_error = '';

    public static function containsDisallowedCharacters($username)
    {
        return preg_match(self::DISALLOWED_CHARACTERS_PATTERN, (string) $username) === 1;
    }

    public static function stripDisallowedCharacters($username)
    {
        return preg_replace(self::DISALLOWED_CHARACTERS_PATTERN, '', (string) $username);
    }

    public function validate($key, $username)
    {
        $username = (string) $username;
        if (self::containsDisallowedCharacters($username)) {
            $this->last_error = 'invalid_characters_in_username';
            return false;
        }

        // Is username min length correct?
        $un_length = ee()->config->item('un_min_len');
        if (strlen($username) < ee()->config->item('un_min_len')) {
            $this->last_error = sprintf(lang('username_too_short'), $un_length);
            return false;
        }

        if (strlen($username) > USERNAME_MAX_LENGTH) {
            $this->last_error = 'username_too_long';
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
