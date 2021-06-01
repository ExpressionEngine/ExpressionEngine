<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_6_1_0;

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
                '_addAllowPreview',
                'longerWatermarkImagePath',
                'addProTemplateSettings',
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addProTemplateSettings()
    {
        if (!ee()->db->field_exists('disable_frontedit', 'templates')) {
            ee()->smartforge->add_column(
                'templates',
                [
                    'disable_frontedit' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    ]
                ]
            );
        }
    }

    // Add in allow_preview y/n field so that Channels can have live preview disabled as a toggle
    private function _addAllowPreview()
    {
        if (!ee()->db->field_exists('allow_preview', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'allow_preview' => array(
                        'type' => 'CHAR',
                        'constraint' => 1,
                        'default' => 'y',
                        'null' => FALSE,
                    )
                )
            );

            ee()->db->update('channels', ['allow_preview' => 'y']);
        }
    }

    private function longerWatermarkImagePath()
    {
        $fields = array(
            'wm_image_path' => array(
                'name' => 'wm_image_path',
                'type' => 'varchar',
                'constraint' => '255',
                'null' => true,
                'default' => null
            ),
            'wm_test_image_path' => array(
                'name' => 'wm_test_image_path',
                'type' => 'varchar',
                'constraint' => '255',
                'null' => true,
                'default' => null
            )
        );

        ee()->smartforge->modify_column('file_watermarks', $fields);
    }

}

// EOF
