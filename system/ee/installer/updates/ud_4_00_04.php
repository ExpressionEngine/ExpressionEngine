<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
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
            ee('Model')->get('ChannelLayout')
                ->filter('channel_id', 'NOT IN', $channel_ids)
                ->delete();
        }
    }
}
// END CLASS

// EOF
