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

    /**
     * Checks for migration folder and creates it if it does not exist
     * @var boolean
     */
    public static function ensureMigrationTableExists($output=null)
    {
        ee()->load->database();
        ee()->load->dbforge();

        if (! ee()->db->table_exists('migrations')) {
            $fields = array(
                'migration_id' => array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'auto_increment' => true),
                'migration' => array('type' => 'text'),
                'migration_location' => array('type' => 'text'),
                'migration_group' => array('type' => 'int', 'constraint' => '10', 'unsigned' => true)
            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('migration_id', true);
            ee()->dbforge->create_table('migrations');

            $output->outln('Migration table created.');
        }
    }

    public static function getLastMigrationGroup()
    {
        $groups = ee('Model')->get('Migration')->fields('migration_group')->all()->pluck('migration_group');

        // No migrations yet, so we're on the first migration group
        if (count($groups) === 0) {
            return null;
        }

        // Return the highest group number
        return max($groups);
    }

    public static function getNextMigrationGroup()
    {
        $lastMigrationGroup = self::getLastMigrationGroup();

        // No migrations yet, so we're on the first migration group
        if (is_null($lastMigrationGroup)) {
            return 1;
        }

        // Return one more than the highest group number
        return ++$lastMigrationGroup;
    }

    public static function generateFileName($name)
    {
        return date('Y_m_d_His') . '_' . self::snakeCase($name) . '.php';
    }

    public static function generateClassName($name)
    {
        return self::camelCase($name);
    }

    public static function parseForTablename($name)
    {
        $name = self::snakeCase($name);
        $words = explode('_', $name);
        $words = array_diff($words, ['create', 'update', 'table']);
        $name = implode('_', $words);

        return $name;
    }

    private static function snakeCase($str)
    {
        $str = strtolower($str);
        $str = str_replace('-', '_', $str);
        $str = str_replace(' ', '_', $str);

        return $str;
    }

    private static function camelCase($str)
    {
        $str = mb_convert_case($str, MB_CASE_TITLE);
        $str = str_replace('-', '', $str);
        $str = str_replace('_', '', $str);
        $str = str_replace(' ', '', $str);

        return $str;
    }
}
