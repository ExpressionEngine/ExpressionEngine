<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_3_9;

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
                'ensureCorrectSiteIdOnSubfolders',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function ensureCorrectSiteIdOnSubfolders()
    {
        $uploadLocations = ee()->db->select('id, site_id')->from('upload_prefs')->where('site_id != 1')->get();
        if ($uploadLocations->num_rows() > 0) {
            ee()->db->query("UPDATE exp_files, (SELECT id, site_id FROM exp_upload_prefs) AS prefs SET exp_files.site_id = prefs.site_id WHERE exp_files.file_type = 'directory' AND prefs.id = exp_files.upload_location_id");
        }
    }
}

// EOF
