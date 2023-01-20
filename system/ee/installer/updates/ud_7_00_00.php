<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_0_0;

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
                'clearJumpCaches',
                'addDismissedBannerToMember',
                'installNewProAddons',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function clearJumpCaches()
    {
        ee('CP/JumpMenu')->clearAllCaches();
    }

    private function addDismissedBannerToMember()
    {
        if (!ee()->db->field_exists('dismissed_banner', 'members')) {
            ee()->smartforge->add_column(
                'members',
                [
                    'dismissed_banner' => [
                        'type' => 'char',
                        'constraint' => 1,
                        'default' => 'n',
                        'null' => false
                    ]
                ]
            );
        }
    }

    private function installNewProAddons()
    {
        // If a low addon is installed, install the pro version,
        // (which will migrate from low to pro)
        foreach (['low_search', 'low_variables'] as $lowAddon) {
            $addon = ee('Addon')->get($lowAddon);

            if ($addon && $addon->isInstalled()) {
                if (!isset(ee()->addons)) {
                    ee()->load->library('addons');
                }

                $proAddon = str_replace('low', 'pro', $lowAddon);

                ee()->addons->install_modules([$proAddon]);
            }
        }
    }
}

// EOF
