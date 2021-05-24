<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Service\Addon\Installer;

/**
 * IP to Nation Update / Installer Class
 */
class Ip_to_nation_upd extends Installer
{
    public $has_cp_backend = 'y';

    /**
      * Constructor
      */
    public function __construct()
    {
        parent::__construct();

        ee()->load->dbforge();
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

        ee()->dbforge->drop_table('ip2nation');

        $fields = array(
            'ip_range_low' => array(
                'type' => 'VARBINARY',
                'constraint' => 16,
                'null' => false,
                'default' => 0
            ),
            'ip_range_high' => array(
                'type' => 'VARBINARY',
                'constraint' => 16,
                'null' => false,
                'default' => 0
            ),
            'country' => array(
                'type' => 'char',
                'constraint' => 2,
                'null' => false,
                'default' => ''
            )
        );

        ee()->dbforge->add_field('id');
        ee()->dbforge->add_field($fields);
        ee()->dbforge->add_key(array('ip_range_low', 'ip_range_high'));
        ee()->dbforge->create_table('ip2nation');

        ee()->dbforge->drop_table('ip2nation_countries');

        $fields = array(
            'code' => array(
                'type' => 'varchar',
                'constraint' => 2,
                'null' => false,
                'default' => ''
            ),
            'banned' => array(
                'type' => 'varchar',
                'constraint' => 1,
                'null' => false,
                'default' => 'n'
            )
        );

        ee()->dbforge->add_field($fields);
        ee()->dbforge->add_key('code', true);
        ee()->dbforge->create_table('ip2nation_countries');

        ee()->config->_update_config(array(
            'ip2nation' => 'y',
            'ip2nation_db_date' => 1335677198
        ));

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

        ee()->dbforge->drop_table('ip2nation');
        ee()->dbforge->drop_table('ip2nation_countries');

        //  Remove a couple items from the file

        ee()->config->_update_config(
            array(),
            array(
                'ip2nation' => '',
                'ip2nation_db_date' => ''
            )
        );

        return true;
    }

    /**
     * Module Updater
     *
     * @access	public
     * @return	bool
     */
    public function update($current = '')
    {
        if ($current == '' or version_compare($current, $this->version, '==')) {
            return false;
        }

        if (version_compare($current, '2.0', '<')) {
            // can't use this column as a Primary Key because the ip2nation db has duplicate values in the ip column ::sigh::
            //			ee()->db->query("ALTER TABLE `exp_ip2nation` DROP KEY `ip`");
            //			ee()->db->query("ALTER TABLE `exp_ip2nation` ADD PRIMARY KEY `ip` (`ip`)");
            ee()->db->query("ALTER TABLE `exp_ip2nation_countries` DROP KEY `code`");
            ee()->db->query("ALTER TABLE `exp_ip2nation_countries` ADD PRIMARY KEY `code` (`code`)");
        }

        // Version 2.2 (02/27/2010) and 2.3 (11/19/2010) used an included sql file from ip2nation.com
        // File is no longer included and table truncated in 3.0, so removing that code
        // They should update IP lists via CP going forward

        // Version 3 switches to the MaxMind Geolite dataset for
        // IPv6 ip address support. This requires a significant schema
        // change to efficiently split the data.
        if (version_compare($current, '3.0', '<')) {
            // clear the ip data
            ee()->db->truncate('ip2nation');

            // next, change the ip column to support IPv6 sizes
            // and change the name since we now do range queries
            ee()->dbforge->modify_column('ip2nation', array(
                'ip' => array(
                    'name' => 'ip_range_low',
                    'type' => 'VARBINARY',
                    'constraint' => 16,
                    'null' => false,
                    'default' => 0
                )
            ));

            // and add a column for the upper end of the range
            ee()->dbforge->add_column('ip2nation', array(
                'ip_range_high' => array(
                    'type' => 'VARBINARY',
                    'constraint' => 16,
                    'null' => false,
                    'default' => 0
                )
            ));
        }

        return true;
    }
}
// END CLASS

// EOF
