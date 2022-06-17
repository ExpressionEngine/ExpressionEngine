<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_0_0_a_1;

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
                'removeDismissedProBannerSetting',
                'installPro',
            ]
        );

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    private function removeDismissedProBannerSetting()
    {
        // TODO:
        // Remove database flag for this
    }

    private function installPro()
    {
        // Check to see if pro is installed and install it
        $pro = ee('Addon')->get('pro');

        if ($pro && ! $pro->isInstalled()) {
            if (!isset(ee()->addons)) {
                ee()->load->library('addons');
            }

            ee()->addons->install_modules(['pro']);
        } elseif ($pro && $pro->hasUpdate()) {
            // Pro was installed and has an update
            $class = $pro->getInstallerClass();
            $UPD = new $class();
            $UPD->update($pro->getInstalledVersion());
        }
    }
}

// EOF
