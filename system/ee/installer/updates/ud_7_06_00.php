<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_6_0;

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
                'addSnippetAndVariablesVersions',
                'addLastEditorId',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addSnippetAndVariablesVersions()
    {
        ee()->smartforge->modify_column(
            'revision_tracker',
            [
                'item_id' => [
                    'name' => 'item_id',
                    'type' => 'int',
                    'constraint' => 10,
                    'unsigned' => true,
                    'null' => true,
                    'default' => null
                ]
            ]
        );
        if (!ee()->db->field_exists('variable_id', 'revision_tracker')) {
            ee()->smartforge->add_column(
                'revision_tracker',
                array(
                    'variable_id' => array(
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null
                    )
                ),
                'item_id'
            );
        }
        if (!ee()->db->field_exists('snippet_id', 'revision_tracker')) {
            ee()->smartforge->add_column(
                'revision_tracker',
                array(
                    'snippet_id' => array(
                        'type' => 'int',
                        'constraint' => 10,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null
                    )
                ),
                'item_id'
            );
        }

        ee()->smartforge->add_key('revision_tracker', 'snippet_id', 'snippet_id');
        ee()->smartforge->add_key('revision_tracker', 'variable_id', 'variable_id');
    }

    private function addLastEditorId()
    {
        if (! ee()->db->field_exists('edit_member_id', 'channel_titles')) {
            ee()->smartforge->add_column(
                'channel_titles',
                [
                    'edit_member_id' => [
                        'type' => 'int',
                        'constraint' => 10,
                        'default' => 0,
                        'unsigned' => true,
                        'null' => false
                    ]
                ],
                'edit_date'
            );
            ee()->smartforge->add_key('channel_titles', 'edit_member_id');
        }
    }
}

// EOF
