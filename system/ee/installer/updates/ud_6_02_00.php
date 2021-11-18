<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_2_0;

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
        $steps = new \ProgressIterator (
            [
                'addMemberValidationAction',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addMemberValidationAction()
    {

        $action = ee()->db->get_where('actions', array('class' => 'Member', 'method' => 'validate'));

        if ($action->num_rows() > 0) {
            return;
        }

        ee()->db->insert('actions', array(
            'class' => 'Member',
            'method' => 'validate',
        ));
    }
}

// EOF
