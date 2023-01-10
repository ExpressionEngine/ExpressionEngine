<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_1_0;

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
                'modifyCpHomepageChannelColumnOnMembers',
                'jsonifyEntryManagerViews',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function modifyCpHomepageChannelColumnOnMembers()
    {
        ee()->smartforge->modify_column(
            'members',
            [
                'cp_homepage_channel' => [
                    'name' => 'cp_homepage_channel',
                    'type' => 'text',
                    'null' => true
                ]
            ]
        );
    }

    private function jsonifyEntryManagerViews()
    {
        $viewsQuery = ee('db')->select('view_id, columns')->from('entry_manager_views')->get();
        if (!empty($viewsQuery)) {
            foreach ($viewsQuery->result_array() as $row) {
                if (strpos($row['columns'], 's:') === 0) {
                    ee('db')->where('view_id', $row['view_id'])->update('entry_manager_views', ['columns' => json_encode(unserialize($row['columns']))]);
                }
            }
        }
    }
}

// EOF
