<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Model\Migration\Migration;

/**
 * Run migrations
 */
class CommandMigrateRollback extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Migrate rollback';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'migrate:rollback';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php migrate:rollback';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'steps,s:' => 'command_migrate_rollback_option_steps',
    ];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        // Specify the number of migrations to roll back
        $steps = $this->option('-s', -1);

        // Get all migrations in last batch and sort them
        $lastBatchofMigrations = ee('Model')->get('Migration')
            ->filter('migration_group', ee('Migration')->getLastMigrationGroup())
            ->order('migration_id', 'desc')
            ->all();

        if ($lastBatchofMigrations->count() === 0) {
            $this->complete('command_migrate_rollback_no_migrations_to_rollback');
        }

        $migrationsCount = 0;
        foreach ($lastBatchofMigrations as $migration) {
            $this->info(lang('command_migrate_rollback_rolling_back') . $migration->migration);

            $migration->down();

            // Increment the number of migrations that have run
            $migrationsCount++;

            // If the number of migrations run equals our step count, exit with a message
            if ($migrationsCount == $steps) {
                $this->complete($migrationsCount . lang('command_migrate_rollback_migrations_executed_successfully'));
            }
        }

        $this->complete('command_migrate_rollback_all_migrations_rolled_back');
    }
}
