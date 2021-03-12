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
            $this->setProperty('migration_location', 'user');
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
}

// EOF
