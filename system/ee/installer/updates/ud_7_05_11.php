<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_11;

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
                'updateConditionalSetsCFPrimary',

            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    // Add missing primary key which old updates missed
    private function updateConditionalSetsCFPrimary()
    {
        if (! ee()->db->table_exists('field_condition_sets_channel_fields')) {
		    return;
		}

		$query = ee()->db->query("SHOW INDEX FROM " . ee()->db->dbprefix . "field_condition_sets_channel_fields WHERE Key_name = 'Primary'");

		if ($query->num_rows() !== 0) {
		    // Primary key already exists
			return;
		}

		// Drop the old non-primary indexes
		// Smartforge will check that they exist
		ee()->smartforge->drop_key('field_condition_sets_channel_fields', 'condition_set_id');
		ee()->smartforge->drop_key('field_condition_sets_channel_fields', 'field_id');

		// Add the combo primary key
		ee()->smartforge->add_key('field_condition_sets_channel_fields', ['condition_set_id', 'field_id'], 'PRIMARY');
    }
}

// EOF
