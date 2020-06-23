<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\Teepee\TeepeeHelper;
use ExpressionEngine\Service\Addon\Installer;

class Teepee_upd extends Installer
{

    public $actions = [
        [
            'method' => 'pages_autocomplete'
        ]
    ];
    public $has_cp_backend = 'y';
    public $has_publish_fields = 'n';

    public function __construct()
    {
        parent::__construct();
    }

    // --------------------------------------------------------------------

    /**
     * Install Teepee
     */
    public function install()
    {
        parent::install();

        // -------------------------------------------
        //  Create the exp_teepee_toolsets table
        // -------------------------------------------
        if (!ee()->db->table_exists('teepee_toolsets')) {
            ee()->load->dbforge();
            ee()->dbforge->add_field(array(
                'toolset_id'   => array('type' => 'int', 'constraint' => 6, 'unsigned' => true, 'auto_increment' => true),
                'toolset_name' => array('type' => 'varchar', 'constraint' => 32),
                'settings'    => array('type' => 'text')
            ));

            ee()->dbforge->add_key('toolset_id', true);
            ee()->dbforge->create_table('teepee_toolsets');
        }

        // -------------------------------------------
        //  Populate it
        // -------------------------------------------
        $toolbars = TeepeeHelper::defaultToolbars();

        foreach ($toolbars as $name => &$toolbar) {
            $config_settings = array_merge(TeepeeHelper::defaultConfigSettings(), array('toolbar' => $toolbar));

            $config = ee('Model')->make('teepee:Toolset');
            $config->toolset_name = $name;
            $config->settings = $config_settings;
            $config->save();
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Update Teepee.
     */
    public function update($current = '')
    {
        if (version_compare($current, '6.0.0', '<')) {
            $data = array(
                'class' => 'Teepee',
                'method' => 'pages_autocomplete'
            );

            ee()->db->insert('actions', $data);
        }

        // -------------
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Uninstall Teepee.
     */
    public function uninstall()
    {
        parent::uninstall();

        // Drop the exp_teepee_configs table
        ee()->load->dbforge();
        ee()->dbforge->drop_table('teepee_toolsets');

        return true;
    }
}
