<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_20;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        $steps = new \ProgressIterator([
            'addCaptchaIndexes',
        ]);

        foreach ($steps as $step) {
            $this->$step();
        }

        return true;
    }

    /**
     * Add indexes to improve CAPTCHA cleanup and abuse checks.
     *
     * @return bool
     */
    public function addCaptchaIndexes()
    {
        ee()->smartforge->add_key('captcha', 'date', 'date');
        ee()->smartforge->add_key('captcha', ['ip_address', 'date'], 'ip_address_date_idx');

        return true;
    }
}

// EOF
