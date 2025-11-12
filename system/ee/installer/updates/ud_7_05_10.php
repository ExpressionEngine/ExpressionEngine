<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_10;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        $steps = new \ProgressIterator(
            [
                'ensureRoleChannelDefault',

            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    // Upgrades from pre-v3 may not have a default set for the cp_homepage_channel field
	// in exp_role_settings, which may cause a MySQL error in ensureBuiltinRoles
	// Also run in 7.4.0
	private function ensureRoleChannelDefault()
	{
		ee()->db->query("ALTER TABLE exp_role_settings ALTER COLUMN cp_homepage_channel SET DEFAULT 0");
	}


}

// EOF
