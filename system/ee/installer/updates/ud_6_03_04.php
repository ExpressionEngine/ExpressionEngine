<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_3_4;

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
                'addConditionalFieldRequiredSyncFlag',
                'syncMemberStats',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }


    private function addConditionalFieldRequiredSyncFlag()
    {
        if (!ee()->db->field_exists('conditional_sync_required', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'conditional_sync_required' => array(
                        'type' => 'CHAR(1)',
                        'null' => false,
                        'default' => 'n'
                    )
                )
            );
        }
    }

    private function syncMemberStats()
    {
        if (ee()->config->item('ignore_member_stats') != 'y') {
            foreach (ee('Model')->get('Role')->all() as $role) {
                $role->total_members = null;
                $role->save();
            }
        }
    }
}

// EOF
