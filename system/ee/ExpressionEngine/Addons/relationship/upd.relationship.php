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
 * Relationship Module update class
 */
class Relationship_upd
{
    private $name = 'Relationship';
    public $version = '1.0.0';

    /**
     * Module Installer
     *
     * @return	bool
     */
    public function install()
    {
        ee()->db->insert(
            'modules',
            array(
                'module_name' => $this->name,
                'module_version' => $this->version,
                'has_cp_backend' => 'n',
                'has_publish_fields' => 'n'
            )
        );

        ee()->db->insert_batch(
            'actions',
            array(
                array(
                    'class' => $this->name,
                    'method' => 'entryList'
                )
            )
        );

        return true;
    }

    /**
     * Module Uninstaller
     *
     * @return	bool
     */
    public function uninstall()
    {
        $module_id = ee()->db->select('module_id')
            ->get_where('modules', array('module_name' => $this->name))
            ->row('module_id');

        ee()->db->delete(
            'module_member_roles',
            array('module_id' => $module_id)
        );

        ee()->db->delete(
            'modules',
            array('module_name' => $this->name)
        );

        ee()->db->where('class', $this->name)
            ->or_where('class', $this->name . '_mcp')
            ->delete('actions');

        return true;
    }

    /**
     * Module Updater
     *
     * @return	bool
     */
    public function update($current = '')
    {
        return true;
    }
}
// END CLASS

// EOF
