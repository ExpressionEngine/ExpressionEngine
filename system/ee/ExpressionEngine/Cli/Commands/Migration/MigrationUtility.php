<?php

namespace ExpressionEngine\Cli\Commands\Migration;

use ExpressionEngine\Cli\Status;
use ExpressionEngine\Cli\Exception;
use ExpressionEngine\Library\Filesystem\Filesystem;

class MigrationUtility
{
    /**
     * Path where migrations are stored
     * @var string
     */
    public static $migrationsPath = SYSPATH . "user/database/migrations/";

    /**
     * Checks for migration folder and creates it if it does not exist
     * @var boolean
     */
    public static function ensureMigrationFolderExists($output=null)
    {
        $filesystem = new Filesystem();

        try {
            if (! $filesystem->isDir(self::$migrationsPath)) {
                $filesystem->mkdir(self::$migrationsPath);
            }
        } catch (Exception $e) {
            if ($output) {
                $output->errln("<<red>>There were problems creating the migration directory:");
                $output->errln("  " . self::$migrationsPath);
                $output->errln("\nMake sure the migrations path exists and is writable.<<reset>>");
            }
            exit(Status::CANTCREAT);
        }
    }

    public static function snakeCase($str)
    {
        $str = strtolower($str);
        $str = str_replace('-', '_', $str);
        $str = str_replace(' ', '_', $str);

        return $str;
    }

    public static function camelCase($str)
    {
        $str = mb_convert_case($str, MB_CASE_TITLE);
        $str = str_replace('-', '', $str);
        $str = str_replace('_', '', $str);
        $str = str_replace(' ', '', $str);

        return $str;
    }
}
