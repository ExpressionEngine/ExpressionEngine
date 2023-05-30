<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_0_4;

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
            'addreCaptchaAction'
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addreCaptchaAction()
    {

        $action = ee()->db->get_where('actions', array('method' => 'recaptcha_check'));

        if ($action->num_rows() > 0) {
            return;
        }

        ee()->db->insert('actions', array(
            'class' => 'Member',
            'method' => 'recaptcha_check',
            'csrf_exempt' => 0
        ));
    }

}

// EOF
