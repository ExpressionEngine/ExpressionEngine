<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_0_0_b_3;

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
            'addAllowPhpConfig'
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addAllowPhpConfig()
    {
        //any of templates use PHP?
        $allow_php = 'n';
        $query = ee()->db->select('template_id')->where('allow_php', 'y')->get('templates');
        if ($query->num_rows() > 0) {
            $allow_php = 'y';
        }
        ee('Config')->getFile()->set('allow_php', $allow_php, true);
    }
}

// EOF
