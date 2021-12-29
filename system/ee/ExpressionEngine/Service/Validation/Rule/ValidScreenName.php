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
 * Screen Name Validation Rule
 */
class ValidScreenName extends ValidationRule
{
    public function validate($key, $screen_name)
    {
        if (preg_match('/[\{\}<>]/', $screen_name)) {
            return 'disallowed_screen_chars';
        }

        if (strlen($screen_name) > USERNAME_MAX_LENGTH) {
            return 'screenname_too_long';
        }

        if (trim(preg_replace("/&nbsp;*/", '', $this->screen_name)) == '') {
            return 'screen_name_taken';
        }

        return true;
    }
}

// EOF
