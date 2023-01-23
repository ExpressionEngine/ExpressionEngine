<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_1_2;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    public function do_update()
    {
        $fields = array(
            'show_sidebar' => array(
                'type' => 'char',
                'constraint' => 1,
                'null' => false,
                'default' => 'n'
            )
        );

        ee()->smartforge->add_column('members', $fields, 'quick_tabs');

        $fields = array(
            'm_field_cp_reg' => array(
                'type' => 'char',
                'constraint' => 1,
                'null' => false,
                'default' => 'n'
            )
        );

        ee()->smartforge->add_column('member_fields', $fields, 'm_field_reg');

        $fields = array(
            'member_groups' => array(
                'name' => 'member_groups',
                'type' => 'varchar',
                'constraint' => 255,
                'null' => false
            )
        );

        ee()->smartforge->modify_column('accessories', $fields);

        $fields = array(
            'can_edit_html_buttons' => array(
                'type' => 'char',
                'constraint' => 1,
                'null' => false,
                'default' => 'n'
            )
        );

        ee()->smartforge->add_column('member_groups', $fields, 'can_view_profiles');

        ee()->db->set('can_edit_html_buttons', 'y');
        ee()->db->where('can_access_cp', 'y');
        ee()->db->update('member_groups');

        if (ee()->db->table_exists('comments')) {
            ee()->db->set('location', '');
            ee()->db->where('location', '0');
            ee()->db->update('comments');
        }

        // Remove allow_multi_emails from config
        ee()->config->_update_config(array(), array('allow_multi_emails' => ''));

        return true;
    }
}
/* END CLASS */

// EOF
