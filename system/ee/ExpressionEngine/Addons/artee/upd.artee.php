<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\Artee\ArteeHelper;
use ExpressionEngine\Service\Addon\Installer;

class Artee_upd extends Installer
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
     * Install Artee
     */
    public function install()
    {
        parent::install();

        // -------------------------------------------
        //  Create the exp_artee_toolsets table
        // -------------------------------------------
        if (!ee()->db->table_exists('artee_toolsets')) {
            ee()->load->dbforge();
            ee()->dbforge->add_field(array(
                'toolset_id'   => array('type' => 'int', 'constraint' => 6, 'unsigned' => true, 'auto_increment' => true),
                'toolset_name' => array('type' => 'varchar', 'constraint' => 32),
                'settings'    => array('type' => 'text')
            ));

            ee()->dbforge->add_key('toolset_id', true);
            ee()->dbforge->create_table('artee_toolsets');
        }

        // -------------------------------------------
        //  Populate it
        // -------------------------------------------
        $toolbars = ArteeHelper::defaultToolbars();

        foreach ($toolbars as $name => &$toolbar) {
            $config_settings = array_merge(ArteeHelper::defaultConfigSettings(), array('toolbar' => $toolbar));

            $config = ee('Model')->make('artee:Toolset');
            $config->toolset_name = $name;
            $config->settings = $config_settings;
            $config->save();
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Update Artee.
     */
    public function update($current = '')
    {
        if (version_compare($current, '6.0.0', '<')) {
            $data = array(
                'class' => 'Artee',
                'method' => 'pages_autocomplete'
            );

            ee()->db->insert('actions', $data);
        }

        // -------------
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Uninstall Artee.
     */
    public function uninstall()
    {
        parent::uninstall();

        // Drop the exp_artee_configs table
        ee()->load->dbforge();
        ee()->dbforge->drop_table('artee_toolsets');

        return true;
    }
}
