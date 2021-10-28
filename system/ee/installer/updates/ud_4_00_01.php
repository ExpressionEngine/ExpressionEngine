<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_4_0_1;

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
                'removeOrhpanedLayouts',
                'resyncLayouts'
            )
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function removeOrhpanedLayouts()
    {
        // This field addition ported from ud_4_03_00 because otherwise model throws mysql error

        ee()->smartforge->add_column(
            'member_groups',
            array(
                'can_manage_consents' => array(
                    'type' => 'CHAR',
                    'constraint' => 1,
                    'default' => 'n',
                    'null' => false,
                )
            )
        );

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

    private function resyncLayouts()
    {
        // Fix for running this update routine in a >= 4.1 context, preview_url
        // column must be present to access Channel model below
        ee()->smartforge->add_column(
            'channels',
            array(
                'preview_url' => array(
                    'type' => 'VARCHAR(100)',
                    'null' => true,
                )
            )
        );
    }
}
// END CLASS

// EOF
