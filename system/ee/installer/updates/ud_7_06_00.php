<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_6_0;

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
                'addFileUsageMemberColumn',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addFileUsageMemberColumn()
    {
        if (!ee()->db->field_exists('member_id', 'file_usage')) {
            ee()->smartforge->add_column(
                'file_usage',
                array(
                    'member_id' => array(
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => false,
                        'default' => 0
                    )
                )
            );
            ee()->smartforge->add_key('file_usage', 'member_id');
        }

        return true;
    }
}

// EOF
