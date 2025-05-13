<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\Rte\RteHelper;
use ExpressionEngine\Service\Addon\Installer;

class Rte_upd extends Installer
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
     * Install Rte
     */
    public function install()
    {
        parent::install();

        $this->install_rte_toolsets_table();

        return true;
    }

    public function install_rte_toolsets_table()
    {
        // -------------------------------------------
        //  Create the exp_rte_toolsets table
        // -------------------------------------------
        ee()->load->dbforge();
        if (ee()->db->table_exists('rte_toolsets')) {
            ee()->dbforge->drop_table('rte_toolsets');
        }

        ee()->dbforge->add_field(array(
            'toolset_id' => array(
                'type' => 'int',
                'constraint' => 6,
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'toolset_name' => array(
                'type' => 'varchar',
                'constraint' => 32,
            ),
            'toolset_type' => array(
                'type' => 'varchar',
                'constraint' => 32,
            ),
            'settings' => array(
                'type' => 'text',
            )
        ));
        ee()->dbforge->add_key('toolset_id', true);
        ee()->dbforge->create_table('rte_toolsets');

        // -------------------------------------------
        //  Populate it
        // -------------------------------------------
        foreach (['ckeditor', 'redactorX'] as $toolset_type) {
            $toolbars = ee('rte:' . ucfirst($toolset_type) . 'Service')->defaultToolbars();
            foreach ($toolbars as $name => $toolbar) {
                $config_settings = array_merge(ee('rte:' . ucfirst($toolset_type) . 'Service')->defaultConfigSettings(), array('toolbar' => $toolbar));
                $config = ee('Model')->make('rte:Toolset');
                $config->toolset_name = $name;
                $config->toolset_type = $toolset_type;
                $config->settings = $config_settings;
                $config->save();
            }
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Update Rte.
     */
    public function update($current = '')
    {
        if (version_compare($current, '2.2.0', '<')) {
            $check = ee('db')->where('toolset_type', 'redactorX')->get('rte_toolsets');
            if ($check->num_rows() == 0) {
                $toolbars = ee('rte:RedactorXService')->defaultToolbars();
                foreach ($toolbars as $name => $toolbar) {
                    $config_settings = array_merge(ee('rte:RedactorXService')->defaultConfigSettings(), array('toolbar' => $toolbar));
                    $config = ee('Model')->make('rte:Toolset');
                    $config->toolset_name = $name;
                    $config->toolset_type = 'redactorX';
                    $config->settings = $config_settings;
                    $config->save();
                }
            }
        }

        if (version_compare($current, '2.1.0', '<')) {
            ee()->db->where('class', 'Rte')
                ->where('method', 'get_js')
                ->delete('actions');

            ee()->db->where('class', 'Rte_ext')->delete('extensions');

            if (ee()->db->table_exists('rte_toolsets') && ! ee()->db->field_exists('toolset_type', 'rte_toolsets')) {
                $fields = [
                    'toolset_type' => array(
                        'type' => 'varchar',
                        'constraint' => 32,
                    ),
                ];
                ee()->load->dbforge();
                ee()->dbforge->add_column('rte_toolsets', $fields);

                // Then we'll update each of the models with the setting
                $configs = ee('Model')->get('rte:Toolset')->all();

                foreach ($configs as &$config) {
                    $config->toolset_type = 'ckeditor';
                    $config->save();
                }

                //install Redactor toolsets
                $toolbars = ee('rte:RedactorService')->defaultToolbars();
                foreach ($toolbars as $name => $toolbar) {
                    $config_settings = array_merge(ee('rte:RedactorService')->defaultConfigSettings(), array('toolbar' => $toolbar));
                    $config = ee('Model')->make('rte:Toolset');
                    $config->toolset_name = $name;
                    $config->toolset_type = 'redactor';
                    $config->settings = $config_settings;
                    $config->save();
                }
            }
        }

        if (version_compare($current, '2.0.1', '<')) {
            ee()->db->where('class', 'Rte_ext')->delete('extensions');
        }

        if (version_compare($current, '2.0.0', '<')) {
            $data = array(
                'class' => 'Rte',
                'method' => 'pages_autocomplete'
            );

            ee()->db->insert('actions', $data);

            $this->install_rte_toolsets_table();
        }

        // -------------
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Uninstall Rte.
     */
    public function uninstall()
    {
        parent::uninstall();

        // Drop the exp_rte_configs table
        ee()->load->dbforge();
        ee()->dbforge->drop_table('rte_toolsets');

        return true;
    }
}
