<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_5_3;

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
            array(
                '_change_site_preferences_column_type',
                '_truncate_tables',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    /**
     * Changes column type for the `site_system_preferences` column in
     * `sites` from TEXT to MEDIUMTEXT
     */
    private function _change_site_preferences_column_type()
    {
        ee()->smartforge->modify_column(
            'sites',
            array(
                'site_system_preferences' => array(
                    'name' => 'site_system_preferences',
                    'type' => 'mediumtext'
                )
            )
        );
    }

    /**
     * Truncates `security_hashes` and `throttle` tables in response to bug
     * #17795 where these tables may not be emptied regularly. Now that the
     * fix is in place, to help prevent a case where EE will hang when
     * trying to clear 15 million records based on a non-indexed date field,
     * let's just clear out the tables.
     */
    private function _truncate_tables()
    {
        ee()->db->truncate('security_hashes');
        ee()->db->truncate('throttle');
    }
}
/* END CLASS */

// EOF
