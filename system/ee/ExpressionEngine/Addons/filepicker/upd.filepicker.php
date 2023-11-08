<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * File Picker Module update class
 */
class Filepicker_upd
{
    public $version = '1.0';

    /**
     * Module Installer
     *
     * @access	public
     * @return	bool
     */
    public function install()
    {
        $mod_data = array(
            'module_name' => 'Filepicker',
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n'
        );

        ee()->db->insert('modules', $mod_data);

        // Install default upload directories
        $site_id = 1;
        $member_directories = array();

        // When installing, ee()->config will contain the installer app values,
        // not the ExpressionEngine application values.
        // So fetch them from the model - dj
        if (ee()->db->table_exists('config')) {
            $member_prefs = ee('Model')->get('Config')
                ->filter('site_id', $site_id)
                ->filter('key', 'IN', ee()->config->divination('member'))
                ->all()
                ->getDictionary('key', 'value');
        } else {
            $sites = ee()->db->get_where('sites', ['site_id' => $site_id]);
            $site = $sites->result_array();
            $member_prefs = unserialize(base64_decode($site[0]['site_member_preferences']));
        }

        $member_directories['Avatars'] = array(
            'server_path' => $member_prefs['avatar_path'],
            'url' => $member_prefs['avatar_url'],
            'allowed_types' => ['img'],
            'max_width' => $member_prefs['avatar_max_width'],
            'max_height' => $member_prefs['avatar_max_height'],
            'max_size' => $member_prefs['avatar_max_kb'],
        );

        $member_directories['Signature Attachments'] = array(
            'server_path' => $member_prefs['sig_img_path'],
            'url' => $member_prefs['sig_img_url'],
            'allowed_types' => ['img'],
            'max_width' => $member_prefs['sig_img_max_width'],
            'max_height' => $member_prefs['sig_img_max_height'],
            'max_size' => $member_prefs['sig_img_max_kb'],
        );

        $member_directories['PM Attachments'] = array(
            'server_path' => $member_prefs['prv_msg_upload_path'],
            'url' => str_replace('avatars', 'pm_attachments', $member_prefs['avatar_url']),
            'allowed_types' => ['img'],
            'max_size' => $member_prefs['prv_msg_attach_maxsize']
        );

        $existing = ee('Model')->get('UploadDestination')
            ->fields('name')
            ->filter('name', 'IN', array_keys($member_directories))
            ->filter('site_id', 'IN', [0, $site_id])
            ->all()
            ->pluck('name');

        foreach ($existing as $name) {
            unset($member_directories[$name]);
        }

        foreach ($member_directories as $name => $data) {
            $dir = ee('Model')->make('UploadDestination', $data);
            $dir->site_id = $site_id;
            $dir->name = $name;
            $dir->module_id = 1; // this is a terribly named column - should be called `hidden`
            $dir->save();
        }

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
        $mod_id = ee()->db->select('module_id')
            ->get_where('modules', array(
                'module_name' => 'Filepicker'
            ))->row('module_id');

        ee()->db->where('module_id', $mod_id)
            ->delete('module_member_roles');

        ee()->db->where('module_name', 'Filepicker')
            ->delete('modules');

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
        return true;
    }
}

// EOF
