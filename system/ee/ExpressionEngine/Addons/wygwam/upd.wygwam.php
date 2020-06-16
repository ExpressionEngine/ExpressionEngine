<?php

use ExpressionEngine\Addons\Wygwam\Helper;

use ExpressionEngine\Service\Addon\Installer;

/**
 * Wygwam Update Class
 *
 * @package   Wygwam
 * @author    EEHarbor <help@eeharbor.com>
 * @copyright Copyright (c) Copyright (c) 2016 EEHarbor
 */

class Wygwam_upd extends Installer
{

    public $actions = [
        [
            'method' => 'send_email'
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
     * Install Wygwam
     */
    public function install()
    {
        parent::install();

        // -------------------------------------------
        //  Create the exp_wygwam_configs table
        // -------------------------------------------

        if (!ee()->db->table_exists('wygwam_configs')) {
            ee()->dbforge->add_field(array(
                'config_id'   => array('type' => 'int', 'constraint' => 6, 'unsigned' => true, 'auto_increment' => true),
                'config_name' => array('type' => 'varchar', 'constraint' => 32),
                'settings'    => array('type' => 'text')
            ));

            ee()->dbforge->add_key('config_id', true);
            ee()->dbforge->create_table('wygwam_configs');
        }

        // -------------------------------------------
        //  Populate it
        // -------------------------------------------
        $toolbars = Helper::defaultToolbars();

        foreach ($toolbars as $name => &$toolbar) { // WTF PHP
            $config_settings = array_merge(Helper::defaultConfigSettings(), array('toolbar' => $toolbar));

            /**
             * @var $config \EEHarbor\Wygwam\Model\Config
             */
            $config = ee('Model')->make('wygwam:Config');
            $config->config_name = $name;
            $config->settings = $config_settings;
            $config->save();
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Update Wygwam.
     */
    public function update($current = '')
    {
        if (version_compare($current, '6.0.0', '<')) {
            $data = array(
                'class' => 'Wygwam',
                'method' => 'pages_autocomplete'
            );

            ee()->db->insert('actions', $data);
        }

        // -------------
        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Uninstall Wygwam.
     */
    public function uninstall()
    {
        parent::uninstall();

        // Drop the exp_wygwam_configs table
        ee()->load->dbforge();
        ee()->dbforge->drop_table('wygwam_configs');

        return true;
    }
}
