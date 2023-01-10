<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Service\Addon\Installer;

/**
 * Metaweblog API Module update class
 */
class Metaweblog_api_upd extends Installer
{
    public $has_cp_backend = 'y';

    public $actions = [
        [
            'method' => 'incoming',
            'csrf_exempt' => true
        ]
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Module Installer
     *
     * @access	public
     * @return	bool
     */
    public function install()
    {
        parent::install();

        ee()->load->dbforge();

        $fields = array(
            'metaweblog_id' => array(
                'type' => 'int',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
                'auto_increment' => true
            ),
            'metaweblog_pref_name' => array(
                'type' => 'varchar',
                'constraint' => '80',
                'null' => false,
                'default' => ''
            ),
            'metaweblog_parse_type' => array(
                'type' => 'varchar',
                'constraint' => '1',
                'null' => false,
                'default' => 'y'
            ),
            'entry_status' => array(
                'type' => 'varchar',
                'constraint' => '50',
                'null' => false,
                'default' => 'NULL'
            ),
            'channel_id' => array(
                'type' => 'int',
                'constraint' => '5',
                'unsigned' => true,
                'null' => false,
                'default' => 0
            ),
            'excerpt_field_id' => array(
                'type' => 'int',
                'constraint' => 7,
                'unsigned' => true,
                'null' => false,
                'default' => 0
            ),
            'content_field_id' => array(
                'type' => 'int',
                'constraint' => 7,
                'unsigned' => true,
                'null' => false,
                'default' => 0
            ),
            'more_field_id' => array(
                'type' => 'int',
                'constraint' => 7,
                'unsigned' => true,
                'null' => false,
                'default' => 0
            ),
            'keywords_field_id' => array(
                'type' => 'int',
                'constraint' => 7,
                'unsigned' => true,
                'null' => false,
                'default' => 0
            ),
            'upload_dir' => array(
                'type' => 'int',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
                'default' => 1
            ),
        );

        ee()->dbforge->add_field($fields);
        ee()->dbforge->add_key('metaweblog_id', true);
        ee()->dbforge->create_table('metaweblog_api', true);

        $data = array(
            'metaweblog_pref_name' => 'Default',
            'channel_id' => 1,
            'content_field_id' => 2
        );
        ee()->db->insert('metaweblog_api', $data);

        return true;
    }

    /**
     * Module Uninstaller
     *
     * @access	public
     * @return	bool
     */
    public function uninstall()
    {
        parent::uninstall();

        ee()->load->dbforge();

        ee()->dbforge->drop_table('metaweblog_api');

        return true;
    }

    /**
     * Module Updater
     *
     * @access	public
     * @return	bool
     */
    public function update($version = '')
    {
        if (version_compare($version, '2', '<') && ee()->db->table_exists('exp_metaweblog_api')) {
            $existing_fields = array();

            $new_fields = array('entry_status' => "`entry_status` varchar(50) NOT NULL default 'null' AFTER `metaweblog_parse_type`");

            $query = ee()->db->query("SHOW COLUMNS FROM exp_metaweblog_api");

            foreach ($query->result_array() as $row) {
                $existing_fields[] = $row['Field'];
            }

            foreach ($new_fields as $field => $alter) {
                if (! in_array($field, $existing_fields)) {
                    ee()->db->query("ALTER table exp_metaweblog_api ADD COLUMN {$alter}");
                }
            }
        }

        if (version_compare($version, '2.2', '<')) {
            $data = array(
                'csrf_exempt' => 1
            );

            ee()->db->where('class', 'Metaweblog_api');
            ee()->db->where('method', 'incoming');
            ee()->db->update('actions', $data);
        }

        if (version_compare($version, '2.3', '<')) {
            ee()->load->library('smartforge');
            ee()->smartforge->modify_column('metaweblog_api', array(
                'field_group_id' => array(
                    'name' => 'channel_id',
                    'type' => 'int',
                    'constraint' => '5',
                    'unsigned' => true,
                    'null' => false,
                    'default' => 0
                )
            ));
        }

        return true;
    }
}

// EOF
