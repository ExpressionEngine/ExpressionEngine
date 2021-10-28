<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_3_4_2;

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
                'add_enable_devlog_alerts',
                'addImageQualityColumn',
                'fix_file_dimension_site_ids'
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function add_enable_devlog_alerts()
    {
        ee()->config->update_site_prefs(
            array('enable_devlog_alerts' => 'n'),
            'all'
        );
    }

    /**
     * Adds a new image quality column to the file dimensions table
     */
    private function addImageQualityColumn()
    {
        ee()->smartforge->add_column(
            'file_dimensions',
            array(
                'quality' => array(
                    'type' => 'tinyint',
                    'constraint' => 1,
                    'unsigned' => true,
                    'default' => 90,
                )
            )
        );
    }

    /**
     * File dimensions were previously being saved with a site ID of 1 regardless
     * of actual site saved on, this corrects previously-saved file dimensions
     */
    private function fix_file_dimension_site_ids()
    {
        $upload_destinations = ee('Model')->get('UploadDestination')->all();

        foreach ($upload_destinations as $upload) {
            foreach ($upload->FileDimensions as $size) {
                if ($size->site_id != $upload->site_id) {
                    $size->site_id = $upload->site_id;
                    $size->save();
                }
            }
        }
    }
}

// EOF
