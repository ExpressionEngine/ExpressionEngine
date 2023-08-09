<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_0;

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
                'modifyUploadPrefsTable'
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function modifyUploadPrefsTable()
    {
        if (! ee()->db->field_exists('resolve_unique_filename', 'upload_prefs')) {
            ee()->smartforge->add_column(
                'upload_prefs',
                [
                    'resolve_unique_filename' => [
                        'type' => 'enum',
                        'constraint' => "'y','n'",
                        'default' => 'n',
                        'null' => false
                    ]
                ],
                'subfolders_on_top'
            );
        }
    }
}

// EOF
