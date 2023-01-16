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

class UpgradeMap
{
    public static $versionsSupported = [];

    public static $versionNaming = [
        '7.0.0_rc.4' => '7.0.0-rc.4',
        '7.0.0_rc.3' => '7.0.0-rc.3',
        '7.0.0_rc.2' => '7.0.0-rc.2',
        '7.0.0_rc.1' => '7.0.0-rc.1',
        '6.1.0_rc_2' => '6.1.0_rc.2',
        '6.1.0_rc_1' => '6.1.0_rc.1',
        '6.0.0_rc_1' => '6.0.0_rc.1',
        '6.0.0_b_4' => '6.0.0_b.4',
        '6.0.0_b_3' => '6.0.0_b.3',
        '6.0.0_b_2' => '6.0.0_b.2',
        '6.0.0_b_1' => '6.0.0_b.1',
    ];

    public static $versionMap = [
        '2.11.9',
        '3.0.4',
        '3.5.11',
        '4.0.0',
        '5.0.0',
    ];

    public static function getVersionsSupported()
    {
        if (empty(self::$versionsSupported)) {
            $path = SYSPATH . 'ee/installer/updates/';

            if (! is_readable($path)) {
                return false;
            }

            $files = new \FilesystemIterator($path);

            foreach ($files as $file) {
                $file_name = $file->getFilename();

                if (preg_match('/^ud_0*(\d+)_0*(\d+)_0*(\d+)(_[a-z0-9_]*)?\.php$/', $file_name, $m)) {
                    $file_version = "{$m[1]}.{$m[2]}.{$m[3]}";

                    // Check for any alpha/beta versions.
                    if (!empty($m[4]) && substr($m[4], 0, 1) === '_') {
                        $file_version .= '_' . str_replace('_', '.', substr($m[4], 1));
                    }

                    array_unshift(self::$versionsSupported, $file_version);
                }
            }
            usort(self::$versionsSupported, 'version_compare');
            self::$versionsSupported = array_reverse(self::$versionsSupported);
        }
        return self::$versionsSupported;
    }

    // This is all the maps
    public static function get($version)
    {
        $versionName = self::parseVersion($version);

        $map = self::getMapVersion($version);

        if (! $map) {
            return false;
        }

        $functionName = "version_{$map}_map";

        return self::$functionName();
    }

    public static function prepare($filemap)
    {
        foreach ($filemap as $filemapKey => &$filemapValue) {

            // If it's a file
            if (strpos($filemapKey, '_file') !== false) {
                $filemapValue = ltrim($filemapValue, '/');
            } else {
                // Else, it's a directory and we need to make sure the system is in there
                // Let's check if it has the system path and a leading slash
                $filemapValue = ltrim($filemapValue, '/');
            }
        }

        return $filemap;
    }

    public static function version_2_11_9_map()
    {
        return [
            'config_path' => 'expressionengine/config',
            'database_path' => 'expressionengine/database',
            'config_file' => 'config.php',
            'database_file' => 'database.php',
            'template_path' => 'expressionengine/templates',
            'index_file' => 'index.php',
            'admin_file' => 'admin.php',
            'index_file_old' => 'index.php',
            'admin_file_old' => 'admin.php',
        ];
    }

    public static function version_3_0_4_map()
    {
        return [
            'config_path' => 'user/config',
            'database_path' => 'user/config',
            'config_file' => 'config.php',
            'database_file' => 'config.php',
            'template_path' => 'user/templates',
            'index_file' => 'index.php',
            'admin_file' => 'admin.php',
            'index_file_old' => 'index.php',
            'admin_file_old' => 'admin.php',
        ];
    }

    public static function version_3_5_11_map()
    {
        return [
            'config_path' => 'user/config',
            'database_path' => 'user/config',
            'config_file' => 'config.php',
            'database_file' => 'config.php',
            'template_path' => 'user/templates',
            'index_file' => 'index.php',
            'admin_file' => 'admin.php',
            'index_file_old' => 'index.php',
            'admin_file_old' => 'admin.php',
        ];
    }

    public static function version_4_0_0_map()
    {
        return [
            'config_path' => 'user/config',
            'database_path' => 'user/config',
            'config_file' => 'config.php',
            'database_file' => 'config.php',
            'template_path' => 'user/templates',
            'index_file' => 'index.php',
            'admin_file' => 'admin.php',
            'index_file_old' => 'index.php',
            'admin_file_old' => 'admin.php',
        ];
    }

    public static function version_5_0_0_map()
    {
        return [
            'config_path' => 'user/config',
            'database_path' => 'user/config',
            'config_file' => 'config.php',
            'database_file' => 'config.php',
            'template_path' => 'user/templates',
            'index_file' => 'index.php',
            'admin_file' => 'admin.php',
            'index_file_old' => 'index.php',
            'admin_file_old' => 'admin.php',
        ];
    }

    // Private functions
    private static function parseVersion($version)
    {

        // For early EE2 version that didn't use dotted syntax
        if (strpos($version, '.') == false) {
            $version = implode('.', str_split($version, 1));
        }

        return $version;
    }

    private static function getMapVersion($version)
    {
        foreach (self::$versionMap as $EEversion) {
            if (version_compare($EEversion, $version, '<=')) {
                return str_replace('.', '_', $EEversion);
            }
        }

        return false;
    }
}
