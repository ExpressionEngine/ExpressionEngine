<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_4_0_4;

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
                'removeOrhpanedLayouts'
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function removeOrhpanedLayouts()
    {
        $channel_ids = ee('Model')->get('Channel')
            ->fields('channel_id')
            ->all()
            ->getIds();

        if (! empty($channel_ids)) {
            $layouts = ee()->db->where_not_in('channel_id', $channel_ids)->get('layout_publish');
            if ($layouts->num_rows() > 0) {
                foreach ($layouts->result() as $layout) {
                    ee()->db->where('layout_id', $layout->layout_id);
                    ee()->db->delete('layout_publish_member_groups');
                    ee()->db->where('layout_id', $layout->layout_id);
                    ee()->db->delete('layout_publish');
                }
            }
        }
    }
}
// END CLASS

// EOF
