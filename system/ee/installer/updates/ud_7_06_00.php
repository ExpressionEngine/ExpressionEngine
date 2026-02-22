<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
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
                'addRevisionCommentRequiredToChannels',
                'addRevisionCommentToEntryVersioning',
                'addRevisionCommentToLayouts',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function addRevisionCommentRequiredToChannels()
    {
        if (! ee()->db->field_exists('revision_comment_required', 'channels')) {
            ee()->smartforge->add_column(
                'channels',
                array(
                    'revision_comment_required' => array(
                        'type' => 'CHAR',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    )
                )
            );
        }
    }

    private function addRevisionCommentToEntryVersioning()
    {
        if (! ee()->db->field_exists('revision_comment', 'entry_versioning')) {
            ee()->smartforge->add_column(
                'entry_versioning',
                array(
                    'revision_comment' => array(
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                        'null' => true
                    )
                )
            );
        }
    }

    private function addRevisionCommentToLayouts()
    {
        $layouts = ee('Model')->get('ChannelLayout')->all();

        foreach ($layouts as $layout) {
            $field_layout = $layout->field_layout;
            $updated = false;

            foreach ($field_layout as $i => $section) {
                if ($section['name'] !== 'revisions') {
                    continue;
                }

                $fields = $section['fields'];
                $has_revision_comment = false;
                foreach ($fields as $field) {
                    if (isset($field['field']) && $field['field'] === 'revision_comment') {
                        $has_revision_comment = true;
                        break;
                    }
                }

                if (! $has_revision_comment) {
                    $insert_at = null;
                    foreach ($fields as $index => $field) {
                        if (isset($field['field']) && $field['field'] === 'versioning_enabled') {
                            $insert_at = $index + 1;
                            break;
                        }
                    }

                    $new_field = array(
                        'field' => 'revision_comment',
                        'visible' => true,
                        'collapsed' => false,
                        'width' => 100
                    );

                    if ($insert_at === null) {
                        $fields[] = $new_field;
                    } else {
                        array_splice($fields, $insert_at, 0, [$new_field]);
                    }

                    $field_layout[$i]['fields'] = $fields;
                    $updated = true;
                }
            }

            if ($updated) {
                $layout->field_layout = $field_layout;
                $layout->save();
            }
        }
    }
}

// EOF
