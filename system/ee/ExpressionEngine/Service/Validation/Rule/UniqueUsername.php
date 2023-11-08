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

class UniqueUsername extends ValidationRule
{
    /**
     * Check to see if the username is unique on the site
     *
     * @return boolean TRUE if it's unique, FALSE if it already exists
     */
    public function validate($key, $value)
    {
        $count = ee('Model')->get('Member')
            ->filter('username', (string) $value)
            ->count();

        return ($count <= 0);
    }

    public function getLanguageKey()
    {
        return 'username_taken';
    }
}

// EOF
