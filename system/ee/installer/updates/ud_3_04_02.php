<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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
        $upload_destinations = ee('db')->from('upload_prefs')->get();

        foreach ($upload_destinations->result() as $upload) {
            $FileDimensions = ee('db')->where('upload_location_id', $upload->id)->from('file_dimensions')->get();
            foreach ($FileDimensions->result() as $size) {
                if ($size->site_id != $upload->site_id) {
                    ee('db')->where('id', $size->id)->update('file_dimensions', ['site_id' => $upload->site_id]);
                }
            }
        }
    }
}

// EOF
