<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_4_6;

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
        $steps = new \ProgressIterator(
            [
                'preserveAutoLoginBehaviorWithNoActivation',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * In previous versions if you set the "Account activation type" to "none"
     * then new members will automatically be logged in after they register.
     *
     * The "Auto-login upon activation" toggle allows administrators to change
     * this behavior but the default setting is "no" to align with the default
     * account activation type of "email".
     *
     * @return void
     */
    private function preserveAutoLoginBehaviorWithNoActivation()
    {
        if (ee()->config->item('req_mbr_activation') == 'none') {
            ee()->config->_update_config(['activation_auto_login' => 'y']);
        }
    }
}

// EOF
