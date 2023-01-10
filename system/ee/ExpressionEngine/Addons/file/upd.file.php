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
 * File Module update class
 */
class File_upd
{
    public $version = '1.1.0';

    /**
     * Module Installer
     *
     * @access	public
     * @return	bool
     */
    public function install()
    {
        $data = array(
            'module_name' => 'File',
            'module_version' => $this->version,
            'has_cp_backend' => 'n'
        );

        ee()->db->insert('modules', $data);

        $data = array(
            'class' => 'File',
            'method' => 'addonIcon',
            'csrf_exempt' => 1
        );

        ee()->db->insert('actions', $data);

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
        ee()->db->select('module_id');
        ee()->db->from('modules');
        ee()->db->where('module_name', 'File');
        $query = ee()->db->get();

        ee()->db->delete('module_member_roles', array('module_id' => $query->row('module_id')));
        ee()->db->delete('modules', array('module_name' => 'File'));

        return true;
    }

    /**
     * Module Updater
     *
     * @access	public
     * @return	bool
     */
    public function update()
    {
        return true;
    }
}
// END CLASS

// EOF
