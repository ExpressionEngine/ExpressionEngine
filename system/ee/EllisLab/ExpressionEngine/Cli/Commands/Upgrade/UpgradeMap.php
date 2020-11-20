<?php

namespace EllisLab\ExpressionEngine\Cli\Commands\Upgrade;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

class UpgradeMap
{
    public static $versionsSupported = [
        '5.3.2',
        '5.3.1',
        '5.3.0',
        '5.2.6',
        '5.2.5',
        '5.2.4',
        '5.2.3',
        '5.2.2',
        '5.2.1',
        '5.2.0',
        '5.1.3',
        '5.1.2',
        '5.1.1',
        '5.1.0',
        '5.0.2',
        '5.0.1',
        '5.0.0',
        '4.3.8',
        '4.3.7',
        '4.3.6',
        '4.3.5',
        '4.3.4',
        '4.3.3',
        '4.3.2',
        '4.3.1',
        '4.3.0',
        '4.2.3',
        '4.2.2',
        '4.2.1',
        '4.2.0',
        '4.1.3',
        '4.1.2',
        '4.1.1',
        '4.1.0',
        '4.0.9',
        '4.0.8',
        '4.0.7',
        '4.0.6',
        '4.0.5',
        '4.0.4',
        '4.0.3',
        '4.0.2',
        '4.0.1',
        '4.0.0',
        '3.5.17',
        '3.5.16',
        '3.5.15',
        '3.5.14',
        '3.5.13',
        '3.5.12',
        '3.5.11',
        '3.5.10',
        '3.5.9',
        '3.5.8',
        '3.5.7',
        '3.5.6',
        '3.5.5',
        '3.5.4',
        '3.5.3',
        '3.5.2',
        '3.5.1',
        '3.5.0',
        '3.4.7',
        '3.4.6',
        '3.4.5',
        '3.4.4',
        '3.4.3',
        '3.4.2',
        '3.4.1',
        '3.4.0',
        '3.3.4',
        '3.3.3',
        '3.3.2',
        '3.3.1',
        '3.3.0',
        '3.2.1',
        '3.2.0',
        '3.1.4',
        '3.1.3',
        '3.1.2',
        '3.1.1',
        '3.1.0',
        '3.0.6',
        '3.0.5',
        '3.0.4',
        '3.0.3',
        '3.0.2',
        '3.0.1',
        '3.0.0',
        '2.11.9',
        '2.11.8',
        '2.11.7',
        '2.11.6',
        '2.11.5',
        '2.11.4',
        '2.11.3',
        '2.11.2',
        '2.11.1',
        '2.11.0',
        '2.10.3',
        '2.10.2',
        '2.10.1',
        '2.10.0',
        '2.9.3',
        '2.9.2',
        '2.9.1',
        '2.9.0',
        '2.8.1',
        '2.8.0',
        '2.7.3',
        '2.7.2',
        '2.7.1',
        '2.7.0',
        '2.6.1',
        '2.6.0',
        '2.5.5',
        '2.5.4',
        '2.5.3',
        '2.5.2',
        '2.5.1',
        '2.5.0',
        '2.4.0',
        '2.3.1',
        '2.3.0',
        '2.2.2',
        '2.2.1',
        '2.2.0',
        '2.1.5',
        '2.1.4',
        '2.1.3',
        '2.1.2',
        '2.1.1',
        '2.1.0',
    ];

    public static $versionMap = [
        '2.11.9',
        '3.0.4',
        '3.5.11',
        '4.0.0',
        '5.0.0',
    ];

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
            'config_path'       => 'expressionengine/config',
            'database_path'     => 'expressionengine/database',
            'config_file'       => 'config.php',
            'database_file'     => 'database.php',
            'template_path'     => 'expressionengine/templates',
            'index_file'        => 'index.php',
            'admin_file'        => 'admin.php',
            'index_file_old'    => 'index.php',
            'admin_file_old'    => 'admin.php',
        ];
    }

    public static function version_3_0_4_map()
    {
        return [
            'config_path'       => 'user/config',
            'database_path'     => 'user/config',
            'config_file'       => 'config.php',
            'database_file'     => 'config.php',
            'template_path'     => 'user/templates',
            'index_file'        => 'index.php',
            'admin_file'        => 'admin.php',
            'index_file_old'    => 'index.php',
            'admin_file_old'    => 'admin.php',
        ];
    }

    public static function version_3_5_11_map()
    {
        return [
            'config_path'       => 'user/config',
            'database_path'     => 'user/config',
            'config_file'       => 'config.php',
            'database_file'     => 'config.php',
            'template_path'     => 'user/templates',
            'index_file'        => 'index.php',
            'admin_file'        => 'admin.php',
            'index_file_old'    => 'index.php',
            'admin_file_old'    => 'admin.php',
        ];
    }

    public static function version_4_0_0_map()
    {
        return [
            'config_path'       => 'user/config',
            'database_path'     => 'user/config',
            'config_file'       => 'config.php',
            'database_file'     => 'config.php',
            'template_path'     => 'user/templates',
            'index_file'        => 'index.php',
            'admin_file'        => 'admin.php',
            'index_file_old'    => 'index.php',
            'admin_file_old'    => 'admin.php',
        ];
    }

    public static function version_5_0_0_map()
    {
        return [
            'config_path'       => 'user/config',
            'database_path'     => 'user/config',
            'config_file'       => 'config.php',
            'database_file'     => 'config.php',
            'template_path'     => 'user/templates',
            'index_file'        => 'index.php',
            'admin_file'        => 'admin.php',
            'index_file_old'    => 'index.php',
            'admin_file_old'    => 'admin.php',
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
