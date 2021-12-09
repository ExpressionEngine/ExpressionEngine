<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands\Upgrade;

use ExpressionEngine\Library\Filesystem\Filesystem;

class UpgradeUtility
{
    public static function run()
    {
        // self::install_modules();
        self::remove_installer_directory();
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
            'search'
        ];

        ee()->load->library('addons');
        ee()->addons->install_modules($required_modules);

        $consent = ee('Addon')->get('consent');
        $consent->installConsentRequests();
    }

    protected static function remove_installer_directory()
    {
        $filesystem = new Filesystem();

        $installerPath = SYSPATH . 'ee/installer';

        if ($filesystem->isDir($installerPath)) {
            $filesystem->deleteDir($installerPath);
        }
    }
}
