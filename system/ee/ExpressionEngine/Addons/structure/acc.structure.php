<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
require_once PATH_ADDONS . 'structure/addon.setup.php';
require_once PATH_ADDONS . 'structure/mod.structure.php';

class Structure_acc
{
    public $name = 'Structure';
    public $id = 'structure-acc';

    public $description = 'Access your Structure Assets anywhere';
    public $sections = array();

    public $structure;
    public $installed = false;
    public $data = array();
    public $version;
    public $cache;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->version = STRUCTURE_VERSION;

        if (! isset($this->cache['module_id_query'])) {
            $results = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Structure'");
            $this->cache['module_id_query'] = $results;
        }

        if ($this->cache['module_id_query']->num_rows > 0) {
            $this->installed = true;
        }

        if ($this->installed === false) {
            return;
        }

        $this->structure = new Structure();
    }

    public function set_sections()
    {
        if ($this->installed === false) {
            $this->sections['Not Installed'] = "Structure is not installed.";
        } else {
            $this->sections['Assets'] = $this->get_assets();
        }
    }

    /**
     * Get Assets
     *
     * @access  public
     * @return  string
     */
    public function get_assets()
    {
        $data['theme_url'] = URL_THEMES;
        $data['asset_data'] = $this->structure->get_structure_channels('asset');

        if (! is_array($data['asset_data'])) {
            $data['asset_data'] = array();
        }

        ee()->load->library('general_helper');

        return ee()->general_helper->view('accessory', $data, true);
    }
}
