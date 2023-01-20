<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Migration;

use ExpressionEngine\Service\Model\Model;

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
    protected $migration_run_date;

    // Sets the migration location to core by default
    public function onBeforeSave()
    {
        if (empty($this->getProperty('migration_location'))) {
            $this->setProperty('migration_location', 'ExpressionEngine');
        }

        // Set the datetime to right now
        $this->setProperty('migration_run_date', date('Y-m-d H:i:s'));
    }

    public function getClassname()
    {
        return ee('Migration', $this)->getClassname();
    }

    public function getFilepath()
    {
        return ee('Migration', $this)->getFilepath();
    }

    public function up()
    {
        return ee('Migration', $this)->up();
    }

    public function down()
    {
        return ee('Migration', $this)->down();
    }
}

// EOF
