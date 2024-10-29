<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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

    protected $stepsRemaining;
    protected $respectMigrationGroups;

    public function __construct(Database\Query $db, Filesystem $filesystem, MigrationModel $migration = null)
    {
        $this->db = $db;
        $this->filesystem = $filesystem;
        $this->migration = $migration;

        // Load the logger
        if (! isset(ee()->logger)) {
            ee()->load->library('logger');
        }

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
        if (! $this->db->table_exists('migrations')) {
            // Load DBForge if the table doesnt exist to create the table
            ee()->load->database();
            ee()->load->dbforge();

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
    public function ensureMigrationFolderExists($location = null)
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

        // In this case, location is an add-on, so we need the add-on folder to exist
        if ($location !== 'ExpressionEngine') {
            // Make sure the add-on exists
            if (!ee('Addon')->get($location)) {
                throw new \Exception(lang('cli_error_the_specified_addon_does_not_exist'), 1);
            }
        }

        $migrationPath = $this->getMigrationPath($location);

        if (! $this->filesystem->isDir($migrationPath)) {
            $this->filesystem->mkdir($migrationPath);
        }
    }

    public function getMigrationPath($location = 'ExpressionEngine')
    {
        // If we set a migration model, use the location in the model
        if (isset($this->migration)) {
            $location = $this->migration->migration_location;
        }

        if (is_null($location)) {
            $location = 'ExpressionEngine';
        }

        // Standard location
        if ($location === 'ExpressionEngine') {
            return SYSPATH . "user/database/migrations/";
        }
        $addonLocation = PATH_THIRD . $location . '/database/migrations/';

        return $addonLocation;
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

        return $classname;
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
        $classname = '\\' . $this->getClassname();

        return new $classname();
    }

    public function up()
    {
        ee()->logger->developer('Running migration: ' . $this->migration->migration, true, 604800);

        $migrationClass = $this->getMigrateInstance();
        $migrationClass->up();
        $this->migration->save();
    }

    public function down()
    {
        ee()->logger->developer('Rolling back migration: ' . $this->migration->migration, true, 604800);

        $migrationClass = $this->getMigrateInstance();
        $migrationClass->down();
        $this->migration->delete();
    }

    public function getNewMigrations($location = null)
    {
        $allExecutedMigrations = ee('Model')->get('Migration')
            ->fields('migration')
            ->all()
            ->pluck('migration');

        $migrationPath = $this->getMigrationPath($location);

        $newMigrations = [];

        // If theres no migrations folder, then there are no new migrations, so we just return here
        if (! $this->filesystem->isDir($migrationPath)) {
            return $newMigrations;
        }

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

    public function migrateAllByType($type, $migrationGroup = null, $stepsRemaining = -1)
    {
        // If we dont have a migration group for this run, lets set one
        if (is_null($migrationGroup)) {
            $migrationGroup = $this->getNextMigrationGroup();
        }

        // Keep track of the migrations that run
        $ran = [];
        $this->stepsRemaining = $stepsRemaining;

        // If all, run core then all addons
        if (strtolower($type) === 'all') {
            $ranCore = $this->migrateAllByType('core', $migrationGroup, $this->stepsRemaining);
            $ranAddons = $this->migrateAllByType('addons', $migrationGroup, $this->stepsRemaining);
            $ran = array_merge($ran, $ranCore, $ranAddons);

            // After migrating core and addons, we're done
            return $ran;
        }

        // If addons, loop through each add-on and migrate it
        if ($type === 'addons') {
            $addons = $this->getAddonsWithMigrations();
            foreach ($addons as $addon) {
                $ranAddons = $this->migrateAllByType($addon, $migrationGroup, $this->stepsRemaining);
                $ran = array_merge($ran, $ranAddons);
            }

            // After migrating each addon, we're done
            return $ran;
        }

        // If set to core, lets change that to ExpressionEngine
        if (strtolower($type) === 'core') {
            $type = 'ExpressionEngine';
        }

        // Get all the new migrations for a type
        $newMigrations = $this->getNewMigrations($type);

        foreach ($newMigrations as $migrationName) {
            if ($this->stepsRemaining == 0) {
                break;
            }
            $migrationData = [
                'migration' => $migrationName,
                'migration_group' => $migrationGroup,
                'migration_location' => $type,
            ];

            $migration = ee('Model')->make('Migration', $migrationData);
            $migration->up();
            $ran[] = $migrationName;

            $this->stepsRemaining--;
        }

        return $ran;
    }

    public function rollbackAllByType($type, $respectMigrationGroups = true, $stepsRemaining = -1)
    {
        // Keep track of the migrations that have rolled back
        $rolledback = [];
        $this->respectMigrationGroups = $respectMigrationGroups;
        $this->stepsRemaining = $stepsRemaining;

        // If all, run addons then core
        if (strtolower($type) === 'all' || strtolower($type) === 'reset') {
            $rolledbackAddons = $this->rollbackAllByType('addons', $this->respectMigrationGroups, $this->stepsRemaining);
            $rolledbackCore = $this->rollbackAllByType('core', $this->respectMigrationGroups, $this->stepsRemaining);
            $rolledback = array_merge($rolledback, $rolledbackAddons, $rolledbackCore);

            // After migrating core and addons, we're done
            return $rolledback;
        }

        // If addons, loop through each add-on and migrate it
        if ($type === 'addons') {
            $addons = $this->getAddonsThatRanMigrations();

            foreach ($addons as $addon) {
                $rolledbackAddons = $this->rollbackAllByType($addon, $this->respectMigrationGroups, $this->stepsRemaining);
                $rolledback = array_merge($rolledback, $rolledbackAddons);
            }

            // After migrating each addon, we're done
            return $rolledback;
        }

        // If set to core, lets change that to ExpressionEngine
        if (strtolower($type) === 'core') {
            $type = 'ExpressionEngine';
        }

        // Get all the ran migrations for a type. This is our base case
        $ranMigrations = ee('Model')->get('Migration')
            ->filter('migration_location', $type)
            ->order('migration_id', 'desc')
            ->all();

        $migrationGroup = null;
        foreach ($ranMigrations as $migration) {
            if ($this->stepsRemaining == 0) {
                break;
            }

            // This allows us to know the first migration group that was ran
            $migrationGroup = $migrationGroup ?: $migration->migration_group;

            // If we are respecting migration groups and the groups have changed, we can safely break out of the loop
            if ($this->respectMigrationGroups && $migrationGroup != $migration->migration_group) {
                break;
            }

            $migration->down();
            $rolledback[] = $migration->migration;

            $this->stepsRemaining--;
        }

        return $rolledback;
    }

    public function getAvailableLocations()
    {
        $locations = [];

        if (count($this->getNewMigrations('ExpressionEngine')) > 0) {
            $locations[] = 'core';
        }

        $addonsWithMigrations = $this->getAddonsWithMigrations();
        if (count($addonsWithMigrations) > 0) {
            $locations[] = 'addons';
            $locations = array_merge($locations, $addonsWithMigrations);
        }

        if (count($locations) > 0) {
            $locations = array_merge(['all'], $locations);
        }

        return $locations;
    }

    public function getAddonsWithMigrations()
    {
        $addons = [];

        foreach ($this->filesystem->getDirectoryContents(PATH_THIRD) as $name) {
            // Skip non-directories
            if (! $this->filesystem->isDir($name)) {
                continue;
            }

            // Skip add-ons without migrations folder
            if (! $this->filesystem->isDir($name . '/database/migrations/')) {
                continue;
            }

            // There is a /database/migrations/ folder for this addon, so lets get the shortname
            $addon_shortname = explode('/', $name);
            $addon_shortname = end($addon_shortname);

            // now lets get all the new migrations from the shortname
            $newMigrationsForAddon = $this->getNewMigrations($addon_shortname);
            if (!empty($newMigrationsForAddon)) {
                // If there are new migrations, add this add-on to the list
                $addons[] = $addon_shortname;
            }
        }

        return $addons;
    }

    public function getAddonsThatRanMigrations()
    {
        // Get all migrations that are not core, and pluck the location
        $migrations = ee('Model')->get('Migration')
            ->filter('migration_location', '!=', 'ExpressionEngine')
            ->all()->pluck('migration_location');

        // Reduce to a unique array and then sort
        $migrations = array_unique($migrations);
        sort($migrations);

        return $migrations;
    }

    public function writeMigrationFileFromTemplate($templateName, $templateVariables)
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

        $vars = array_merge(['classname' => $this->getClassname()], $templateVariables);

        $template = new $templateClass($vars);

        try {
            // Get the parsed template. This will throw an error
            $filecontents = $template->getParsedTemplate();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
        }

        $this->filesystem->write($this->getFilepath(), $filecontents);

        ee()->logger->developer('Created new migration: ' . $this->migration->migration, true, 604800);
    }

    // These string manipulation functions should be moved, but they are required for migrations
    public function snakeCase($str)
    {
        $str = strtolower($str);
        $str = str_replace(['-', ' '], '_', $str);

        return $str;
    }

    public function camelCase($str)
    {
        $str = mb_convert_case($str, MB_CASE_TITLE);
        $str = str_replace(['-', '_', ' '], '', $str);

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
