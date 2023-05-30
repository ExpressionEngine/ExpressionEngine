<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_2_1_3;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    public function do_update()
    {
        $layouts = ee()->db->get('layout_publish');

        if ($layouts->num_rows() === 0) {
            return true;
        }

        $layouts = $layouts->result_array();

        foreach ($layouts as &$layout) {
            $old_layout = unserialize($layout['field_layout']);

            foreach ($old_layout as $tab => &$fields) {
                $field_keys = array_keys($fields);

                foreach ($field_keys as &$key) {
                    if ($key == 'channel') {
                        $key = 'new_channel';
                    }
                }

                $fields = array_combine($field_keys, $fields);
            }

            $layout['field_layout'] = serialize($old_layout);
        }

        ee()->db->update_batch('layout_publish', $layouts, 'layout_id');

        return true;
    }
}
/* END CLASS */

// EOF
