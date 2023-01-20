<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_4_2_2;

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
                'checkFileDirectoryPaths'
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function checkFileDirectoryPaths()
    {
        $warning = false;
        $directories = array();
        // Get all of the file upload directories and see if any are using themes/ee
        $upload_destinations =  ee('db')->from('upload_prefs')->get();

        foreach ($upload_destinations->result() as $upload) {
            if (strpos($upload->server_path, 'themes/ee/site/default/asset/img/') !== false) {
                $warning = true;
                $directories[$upload->server_path] = $upload->name;
            }
        }

        //ee()->update_notices->clear(); die;

        if ($warning) {
            $msg = 'The themes/ee/ folder may be overwritten during upgrade. The following directories should be moved:<br><br>';

            foreach ($directories as $path => $name) {
                $msg .= $name . ': ' . $path . '<br>';
            }

            $msg .= 'See the <a href="' . DOC_URL . 'installation/version_notes_4.2.2.html">version notes</a> for details.';

            ee()->update_notices->setVersion('4.2.2');
            ee()->update_notices->header('File upload directory found in themes/ee/');
            ee()->update_notices->item($msg);
        }
    }
}

// EOF
