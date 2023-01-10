<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Service\Addon\Installer;

/**
 * Stats Module update class
 */
class Stats_upd extends Installer
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Module Updater
     *
     * @access	public
     * @return	bool
     */
    public function update($current = '')
    {
        if (version_compare($current, $this->version, '==')) {
            return false;
        }

        if (version_compare($current, '2.0', '<')) {
            ee()->load->dbforge();
            ee()->dbforge->drop_column('stats', 'weblog_id');
        }

        // Add stat sync action
        if (version_compare($current, '2.1', '<')) {

            // Create syncing action
            $data = [
                'class' => 'Stats',
                'method' => 'sync_stats',
                'csrf_exempt' => 1,
            ];

            ee()->db->insert('actions', $data);
        }

        if (version_compare($current, '2.2', '<')) {
            $fields = array(
                'recent_member' => array('type' => 'varchar', 'constraint' => '75', 'null' => false)
            );
            ee()->load->library('smartforge');
            ee()->smartforge->modify_column('stats', $fields);
        }

        return true;
    }
}
// END CLASS

// EOF
