<?php

class Pro_upd
{
    public $has_cp_backend = 'n';
    public $has_publish_fields = 'n';

    public function __construct()
    {
    	$addon = ee('Addon')->get('pro');

        $this->version = $addon->getVersion();
        $this->module_name = str_replace('_upd', '', get_class($this));
    }

    public function install()
    {
        $mod_data = array(
            'module_name'        => $this->module_name,
            'module_version'     => $this->version,
            'has_cp_backend'     => $this->has_cp_backend,
            'has_publish_fields' => $this->has_publish_fields,
        );

        ee()->functions->clear_caching('db');
        ee()->db->insert('modules', $mod_data);

        return true;
    }

    public function update($current = '')
    {
        if ($current == '' or version_compare($current, $this->version, '==')) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        // --------------------------------------
        // Get module ID
        // --------------------------------------

        $module_id = ee()->db->select('module_id')
            ->from('modules')
            ->where('module_name', $this->module_name)
            ->get()->row('module_id');

        // --------------------------------------
        // Remove references from module_member_groups
        // --------------------------------------

        ee()->db->where('module_id', $module_id);
        ee()->db->delete('module_member_groups');

        // --------------------------------------
        // Remove references from modules
        // --------------------------------------

        ee()->db->where('module_name', $this->module_name);
        ee()->db->delete('modules');

        // --------------------------------------
        // Remove references from actions
        // --------------------------------------

        ee()->db->where('class', $this->module_name);
        ee()->db->delete('actions');

        // --------------------------------------
        // Remove references from extensions
        // --------------------------------------

        ee()->db->where('class', $this->module_name . '_ext');
        ee()->db->delete('extensions');

        return true;
    }
}
