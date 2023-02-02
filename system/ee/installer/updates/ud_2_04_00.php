<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_4_0;

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
        ee()->load->dbforge();

        $steps = new \ProgressIterator(
            array(
                '_update_watermarks_table',
                '_update_file_dimensions_table',
                '_update_files_table',
                '_add_developer_log_table',
                '_create_remember_me',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Update Watermarks Table
     *
     * Renames watermark offset columns to be more consistent with CodeIgniter
     *
     * @return	void
     */
    private function _update_watermarks_table()
    {
        // Rename offset columns
        ee()->smartforge->modify_column(
            'file_watermarks',
            array(
                'wm_x_offset' => array(
                    'name' => 'wm_hor_offset',
                    'type' => 'int'
                ),
                'wm_y_offset' => array(
                    'name' => 'wm_vrt_offset',
                    'type' => 'int'
                )
            )
        );
    }

    /**
     * Update File Dimensions Table
     *
     * Adds a site_id column to file_dimensions table
     *
     * @return	void
     */
    private function _update_file_dimensions_table()
    {
        ee()->smartforge->add_column(
            'file_dimensions',
            array(
                'site_id' => array(
                    'type' => 'int',
                    'constraint' => 4,
                    'unsigned' => true,
                    'default' => '1',
                    'null' => false
                )
            )
        );
    }

    /**
     * Update Files Table
     *
     * Adds extra metadata fields to file table, and deletes old custom fields
     *
     * @return	void
     */
    private function _update_files_table()
    {
        ee()->smartforge->add_column(
            'files',
            array(
                'credit' => array(
                    'type' => 'varchar',
                    'constraint' => 255
                ),
                'location' => array(
                    'type' => 'varchar',
                    'constraint' => 255
                )
            )
        );

        // Rename "caption" field to "description"
        ee()->smartforge->modify_column(
            'files',
            array(
                'caption' => array(
                    'name' => 'description',
                    'type' => 'text'
                ),
            )
        );

        // Drop the 6 custom fields
        for ($i = 1; $i < 7; $i++) {
            ee()->smartforge->drop_column('files', 'field_' . $i);
            ee()->smartforge->drop_column('files', 'field_' . $i . '_fmt');
        }

        // Drop 'metadata' and 'status' fields
        ee()->smartforge->drop_column('files', 'metadata');
        ee()->smartforge->drop_column('files', 'status');
    }

    /**
     * Add Developer Log table
     *
     * @return	void
     */
    private function _add_developer_log_table()
    {
        ee()->dbforge->add_field(
            array(
                'log_id' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'auto_increment' => true
                ),
                'timestamp' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true
                ),
                'viewed' => array(
                    'type' => 'char',
                    'constraint' => 1,
                    'default' => 'n'
                ),
                'description' => array(
                    'type' => 'text',
                    'null' => true
                ),
                'function' => array(
                    'type' => 'varchar',
                    'constraint' => 100,
                    'null' => true
                ),
                'line' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => true
                ),
                'file' => array(
                    'type' => 'varchar',
                    'constraint' => 255,
                    'null' => true
                ),
                'deprecated_since' => array(
                    'type' => 'varchar',
                    'constraint' => 10,
                    'null' => true
                ),
                'use_instead' => array(
                    'type' => 'varchar',
                    'constraint' => 100,
                    'null' => true
                )
            )
        );

        ee()->dbforge->add_key('log_id', true);
        ee()->smartforge->create_table('developer_log');
    }

    /**
     * Adds the new remember_me table and drops the remember_me column
     * from the member table
     *
     * @return 	void
     */
    private function _create_remember_me()
    {
        // Hotness coming up, drop it!
        ee()->smartforge->drop_column('members', 'remember_me');

        // This has the same structure as sessions, except for the
        // primary key and "last_activity" fields. Also added site_id back
        // for this table so that we can count active remember me's per
        // member per site
        ee()->dbforge->add_field(array(
            'remember_me_id' => array(
                'type' => 'VARCHAR',
                'constraint' => 40,
                'default' => '0'
            ),
            'member_id' => array(
                'type' => 'INT',
                'constraint' => 10,
                'default' => '0'
            ),
            'ip_address' => array(
                'type' => 'VARCHAR',
                'constraint' => 16,
                'default' => '0'
            ),
            'user_agent' => array(
                'type' => 'VARCHAR',
                'constraint' => 120,
                'default' => ''
            ),
            'admin_sess' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => '0'
            ),
            'site_id' => array(
                'type' => 'INT',
                'constraint' => 4,
                'default' => '1'
            ),
            'expiration' => array(
                'type' => 'INT',
                'constraint' => 10,
                'default' => '0'
            ),
            'last_refresh' => array(
                'type' => 'INT',
                'constraint' => 10,
                'default' => '0'
            )
        ));

        ee()->dbforge->add_key('remember_me_id', true);
        ee()->dbforge->add_key('member_id');

        ee()->smartforge->create_table('remember_me');
    }
}
/* END CLASS */

// EOF
