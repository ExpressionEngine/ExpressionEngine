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
 * Channel Module update
 */
class Channel_upd
{
    public $version = '2.1.1';

    /**
     * Module Installer
     *
     * @access	public
     * @return	bool
     */
    public function install()
    {
        $data = array(
            'module_name' => 'Channel',
            'module_version' => $this->version,
            'has_cp_backend' => 'n'
        );

        ee()->db->insert('modules', $data);

        $data = array(
            'class' => 'Channel',
            'method' => 'submit_entry'
        );

        ee()->db->insert('actions', $data);

        $data = array(
            'class' => 'Channel',
            'method' => 'smiley_pop'
        );

        ee()->db->insert('actions', $data);

        $data = array(
            'class' => 'Channel',
            'method' => 'combo_loader'
        );

        ee()->db->insert('actions', $data);

        $data = array(
            'class' => 'Channel',
            'method' => 'live_preview',
            'csrf_exempt' => 1
        );

        ee()->db->insert('actions', $data);

        ee()->db->insert('content_types', array('name' => 'channel'));

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
        ee()->db->where('module_name', 'Channel');
        $query = ee()->db->get();

        ee()->db->delete('module_member_roles', array('module_id' => $query->row('module_id')));
        ee()->db->delete('modules', array('module_name' => 'Channel'));
        ee()->db->delete('actions', array('class' => 'Channel'));
        ee()->db->delete('actions', array('class' => 'Channel_mcp'));

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
