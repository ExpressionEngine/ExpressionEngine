<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_2_0;

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
            array(
                '_update_session_table',
                '_update_password_lockout_table',
                '_update_members_table',
                '_update_files_table',
                '_update_comments_table',
                '_update_template_groups',
                '_alter_sidebar_deft',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Update Session Table
     *
     * This method updates the sessions table to add an index on
     * `last_activity` as it should help speed up session gc on large sites
     * secondly, updating `user_agent` to VARCHAR(120) to more closely
     * match what's going on in CodeIgniter's session class.
     *
     * @return 	void
     */
    private function _update_session_table()
    {
        // Add an index on last_activity
        ee()->smartforge->add_key('sessions', 'last_activity', 'last_activity_idx');

        $field = array(
            'user_agent' => array(
                'name' => 'user_agent',
                'type' => 'VARCHAR',
                'constraint' => 120
            )
        );

        ee()->smartforge->modify_column('sessions', $field);
    }

    /**
     * Update Password lockout Table
     *
     * This method updates the password_lockout table, updating `user_agent`
     * to VARCHAR(120) to more closely match what's going on in CodeIgniter's session class.
     *
     * @return 	void
     */
    private function _update_password_lockout_table()
    {
        $field = array(
            'user_agent' => array(
                'name' => 'user_agent',
                'type' => 'VARCHAR',
                'constraint' => 120
            )
        );

        ee()->smartforge->modify_column('password_lockout', $field);
    }
    /**
     * Update members table
     *
     * Oh this is fun!  So since we're implementing a better password hashing
     * scheme, we'll bump up the `password` field in exp_members to
     * be able to handle hashing algorithims such as sha256/sha512.
     * Additionally, we're adding a salt column to use for salting the
     * users passwords.
     */
    private function _update_members_table()
    {
        // Update password column to VARCHAR(128)
        $field = array(
            'password' => array(
                'name' => 'password',
                'type' => 'VARCHAR',
                'constraint' => 128
            )
        );

        ee()->smartforge->modify_column('members', $field);

        // Add a salt column VARCHAR(128)
        $field = array(
            'salt' => array(
                'type' => 'VARCHAR',
                'constraint' => 128,
                'default' => '',
                'null' => false
            )
        );

        ee()->smartforge->add_column('members', $field);

        // Add a remember_me column VARCHAR(32)
        $field = array(
            'remember_me' => array(
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => '',
                'null' => false
            )
        );

        ee()->smartforge->add_column('members', $field);
    }

    /**
     * Add caption field to files
     */
    private function _update_files_table()
    {
        $field = array(
            'caption' => array(
                'type' => 'text'
            )
        );

        ee()->smartforge->add_column('files', $field);
    }

    /**
     * Adds an index on exp_comments(comment_date)
     */
    private function _update_comments_table()
    {
        ee()->smartforge->add_key('comments', 'comment_date', 'comment_date_idx');
    }

    /**
     * Adds an index on template_groups(group_name) & template_groups(group_order)
     */
    private function _update_template_groups()
    {
        ee()->smartforge->add_key('template_groups', 'group_name', 'group_name_idx');
        ee()->smartforge->add_key('template_groups', 'group_order', 'group_order_idx');
    }

    /**
     * Alter Sidebar state default
     */
    private function _alter_sidebar_deft()
    {
        ee()->db->query("ALTER TABLE exp_members ALTER COLUMN show_sidebar SET DEFAULT 'n'");
    }
}
/* END CLASS */

// EOF
