<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_2_1;

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
        // 2.1.3 was missing this from its schema
        ee()->smartforge->add_column(
            'member_groups',
            array(
                'can_access_fieldtypes' => array(
                    'type' => 'char',
                    'constraint' => 1,
                    'default' => 'n',
                    'null' => false
                )
            ),
            'can_access_files'
        );

        ee()->db->set('can_access_fieldtypes', 'y');
        ee()->db->where('group_id', 1);
        ee()->db->update('member_groups');

        ee()->db->set('group_id', 4);
        ee()->db->where('group_id', 0);
        ee()->db->update('members');

        return true;
    }
}
/* END CLASS */

// EOF
