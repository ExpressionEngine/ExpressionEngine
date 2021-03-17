<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Migration;

use ExpressionEngine\Service\Database;
use ExpressionEngine\Model\Migration\Migration as MigrationModel;
use ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Migration Service Factory
 */
class Factory
{
    public $db;
    public $filesystem;
    public $migration;

    public function __construct(Database\Query $db, Filesystem $filesystem, MigrationModel $migration = null)
    {
        $this->db = $db;
        $this->filesystem = $filesystem;
        $this->migration = $migration;

        // Checks for exp_migrations table and creates it if it does not exist
        $this->ensureMigrationTableExists();
    }

    public function setMigration(MigrationModel $migration)
    {
        $this->migration = $migration;
    }

    public function getLastMigrationGroup()
    {
        $groups = ee('Model')->get('Migration')->fields('migration_group')->all()->pluck('migration_group');

        // No migrations yet, so we're on the first migration group
        if (count($groups) === 0) {
            return null;
        }

        // Return the highest group number
        return max($groups);
    }

    public function getNextMigrationGroup()
    {
        $lastMigrationGroup = $this->getLastMigrationGroup();

        // No migrations yet, so we're on the first migration group
        if (is_null($lastMigrationGroup)) {
            return 1;
        }

        // Return one more than the highest group number
        return ++$lastMigrationGroup;
    }

    /**
     * Checks for migration folder and creates it if it does not exist
     * @var boolean
     */
    public function ensureMigrationTableExists()
    {
        ee()->load->database();
        ee()->load->dbforge();

        if (! $this->db->table_exists('migrations')) {
            $fields = array(
                'migration_id' => array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'auto_increment' => true),
                'migration' => array('type' => 'text'),
                'migration_location' => array('type' => 'text'),
                'migration_group' => array('type' => 'int', 'constraint' => '10', 'unsigned' => true),
                'migration_run_date' => array('type' => 'datetime', 'null' => false),
            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('migration_id', true);
            ee()->dbforge->create_table('migrations');
        }
    }

    /**
     * Checks for migration folder and creates it if it does not exist
     * @var boolean
     */
    public function ensureMigrationFolderExists($location=null)
    {
        // If no location was passed, get one
        if (! isset($location)) {
            // If there is a migration with a location set, we use that
            if (isset($this->migration) && !is_null($this->migration->migration_location)) {
                $location = $this->migration->migration_location;
            } else {
                $location = 'ExpressionEngine';
            }
        }

        $migrationPath = $this->getMigrationPath($location);

        if (! $this->filesystem->isDir($migrationPath)) {
            $this->filesystem->mkdir($migrationPath);
        }
    }

    public function getMigrationPath($location='ExpressionEngine')
    {
        // If we set a migration model, use the location in the model
        if (isset($this->migration)) {
            $location = $this->migration->migration_location;
        }

        // Is the migration an addon?
        if ($location === 'myaddon') {
            // @todo
            // Find the addon migration path and set it here. Make sure it exists
            return PATH_THIRD . 'myaddon/database/migrations/';
        }

        return SYSPATH . "user/database/migrations/";
    }

    public function generateMigration($migrationName, $migrationLocation)
    {
        $this->migration = ee('Model')->make('Migration', [
            'migration' => $this->createNewTimestampedMigrationName($migrationName),
            'migration_group' => ee('Migration')->getNextMigrationGroup(),
            'migration_location' => $migrationLocation,
        ]);

        $this->ensureMigrationFolderExists($migrationLocation);

        return $this->migration;
    }

    public function createNewTimestampedMigrationName($migrationName)
    {
        return date('Y_m_d_His') . '_' . $this->snakeCase($migrationName);
    }

    public function getClassname()
    {
        $classname = substr($this->migration->migration, 18);
        $classname = $this->camelCase($classname);

        return '\\' . $classname;
    }

    public function getFilepath()
    {
        return $this->getMigrationPath() . $this->migration->migration . '.php';
    }

    public function getMigrateInstance()
    {
        // Checks for migration folder and creates it if it does not exist
        $this->ensureMigrationFolderExists();

        include_once($this->getFilepath());
        $classname = $this->getClassname();

        return new $classname();
    }

    public function up()
    {
        $migrationClass = $this->getMigrateInstance();
        $migrationClass->up();
        $this->migration->save();
    }

    public function down()
    {
        $migrationClass = $this->getMigrateInstance();
        $migrationClass->down();
        $this->migration->delete();
    }

    public function getNewMigrations()
    {
        $allExecutedMigrations = ee('Model')->get('Migration')->fields('migration')->all()->pluck('migration');
        $migrationPath = $this->getMigrationPath();

        $newMigrations = array();
        foreach ($this->filesystem->getDirectoryContents($migrationPath) as $file) {
            // If it's not a PHP file, it's not a migration
            if (!$this->endsWith($file, '.php')) {
                continue;
            }

            // Filter out the filepath and extension
            $migrationName = pathinfo($file, PATHINFO_FILENAME);

            // This migration has already run
            if (in_array($migrationName, $allExecutedMigrations)) {
                continue;
            }

            $newMigrations[] = $migrationName;
        }

        // Make sure they are in the correct order
        sort($newMigrations);

        return $newMigrations;
    }

    public function writeMigrationFileFromTemplate($templateName, $tablename)
    {
        if (!isset($this->migration)) {
            throw new \Exception("Cannot run writeMigrationFileFromTemplate without setting Migration Model", 1);
        }

        $migrationPath = $this->getMigrationPath();
        $this->ensureMigrationFolderExists();

        if (! $this->filesystem->isWritable($migrationPath)) {
            throw new \Exception("Error writing migration template. Ensure migration path is writable: $migrationPath", 1);
        }

        $templateClass = '\ExpressionEngine\Cli\Commands\Migration\Templates\\' . $templateName;

        $vars = [
            'classname' => $classname,
            'table' => $tablename,
        ];
        $template = new $templateClass($vars);
        $filecontents = $template->getParsedTemplate();

        $this->filesystem->write($this->getFilepath(), $filecontents);
    }

    // These string manipulation functions should be moved, but they are required for migrations
    public function snakeCase($str)
    {
        $str = strtolower($str);
        $str = str_replace('-', '_', $str);
        $str = str_replace(' ', '_', $str);

        return $str;
    }

    public function camelCase($str)
    {
        $str = mb_convert_case($str, MB_CASE_TITLE);
        $str = str_replace('-', '', $str);
        $str = str_replace('_', '', $str);
        $str = str_replace(' ', '', $str);

        return $str;
    }

    public function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }
}

// EOF
