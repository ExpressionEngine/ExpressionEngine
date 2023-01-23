<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands\Upgrade;

use ExpressionEngine\Library\Filesystem\Filesystem;

class UpgradeUtility
{
    public static function run()
    {
        // self::install_modules();
        self::rename_installer();
    }

    protected static function install_modules()
    {
        $required_modules = [
            'channel',
            'comment',
            'consent',
            'member',
            'stats',
            'rte',
            'file',
            'filepicker',
            'relationship',
            'search',
            'pro'
        ];

        ee()->load->library('addons');
        ee()->addons->install_modules($required_modules);

        $consent = ee('Addon')->get('consent');
        $consent->installConsentRequests();
    }

    protected static function rename_installer()
    {
        $installerPath = SYSPATH . 'ee/installer';

        // Generate the new path by suffixing a dotless version number
        $new_path = str_replace(
            'installer',
            'installer_' . APP_VER . '_' . uniqid(),
            $installerPath
        );

        // Move the directory
        return ee('Filesystem')->rename($installerPath, $new_path);
    }
}
