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
                'addFieldNotes',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addFieldNotes()
    {
        if (!ee()->db->field_exists('field_notes', 'channel_fields')) {
            ee()->smartforge->add_column(
                'channel_fields',
                [
                    'field_notes' => [
                        'type' => 'text',
                        'null' => true
                    ]
                ],
                'field_instructions'
            );
        }

        if (!ee()->db->field_exists('field_notes', 'category_fields')) {
            ee()->smartforge->add_column(
                'category_fields',
                [
                    'field_notes' => [
                        'type' => 'text',
                        'null' => true
                    ]
                ],
                'field_label'
            );
        }

        if (!ee()->db->field_exists('m_field_notes', 'member_fields')) {
            ee()->smartforge->add_column(
                'member_fields',
                [
                    'm_field_notes' => [
                        'type' => 'text',
                        'null' => true
                    ]
                ],
                'm_field_description'
            );
        }
    }
}

// EOF
