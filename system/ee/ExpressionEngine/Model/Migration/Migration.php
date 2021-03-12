<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Migration;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Cli\Commands\Migration\MigrationUtility;

/**
 * Migration Model
 */
class Migration extends Model
{
    protected static $_primary_key = 'migration_id';
    protected static $_table_name = 'migrations';

    protected static $_validation_rules = array(
        'migration' => 'required',
        'migration_group' => 'required',
    );

    protected static $_events = array(
        'beforeSave',
    );

    // Properties
    protected $migration_id;
    protected $migration;
    protected $migration_location;
    protected $migration_group;

    // Sets the migration location to core by default
    public function onBeforeSave()
    {
        if (empty($this->getProperty('migration_location'))) {
            $this->setProperty('migration_location', 'ExpressionEngine');
        }
    }

    public function getClassname()
    {
        $classname = substr($this->migration, 18);
        $classname = MigrationUtility::camelCase($classname);

        return '\\' . $classname;
    }

    public function getFilepath()
    {
        return MigrationUtility::$migrationsPath . $this->migration . '.php';
    }

    public function getMigrateInstance()
    {
        include_once($this->getFilepath());
        $classname = $this->getClassname();

        return new $classname();
    }

    public function up()
    {
        $migration = $this->getMigrateInstance();
        $migration->up();
    }

    public function down()
    {
        $migration = $this->getMigrateInstance();
        $migration->down();
    }

    /**
     * Checks for migration folder and creates it if it does not exist
     * @var boolean
     */
    public static function ensureMigrationTableExists()
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

            return true;
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
}

// EOF
