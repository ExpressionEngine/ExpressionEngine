<?php

namespace ExpressionEngine\Structure\Conduit;

use ExpressionEngine\Structure\Conduit\FluxNav;

class McpNav extends FluxNav
{
    public $sql;

    protected function defaultItems()
    {
        $default_items = array();

        $this->sql = new \Sql_structure();
        $settings = $this->sql->get_settings();

        // set nav based on permissions
        if ($this->sql->user_access('perm_admin_channels', $settings) || $this->sql->user_access('perm_admin_structure', $settings)) {
            $default_items['index'] = lang('pages');
        }

        if ($this->sql->user_access('perm_admin_channels', $settings)) {
            $default_items['channel_settings'] = lang('cp_channel_settings_title');
        }

        if ($this->sql->user_access('perm_admin_structure', $settings)) {
            $default_items['module_settings'] = lang('cp_module_settings_title');
        }
        if ($this->sql->user_access('perm_view_validation', $settings)) {
            $default_items['validation'] = lang('validation');
        }

        if ($this->sql->user_access('perm_view_nav_history', $settings)) {
            $default_items['nav_history'] = lang('history');
        }

        return $default_items;
    }

    protected function defaultButtons()
    {
        return array();
    }

    protected function defaultActiveMap()
    {
        $this->sql = new \Sql_structure();
        $settings = $this->sql->get_settings();

        $active_map = array();

        if ($this->sql->user_access('perm_admin_channels', $settings) || $this->sql->user_access('perm_admin_structure', $settings)) {
            $active_map['structure'] = 'index';
        }

        return $active_map;
    }

    public function postGenerateNav()
    {
        // Any code that you want run after the nav is generated (maybe turn the current page into a folderlist)
    }

    public function deferGenerate()
    {
        // // Example of how you might defer generation of nav:
        // if ($this->last_seg === 'groups') {
        //     return true;
        // }
        // return true;
    }
}
