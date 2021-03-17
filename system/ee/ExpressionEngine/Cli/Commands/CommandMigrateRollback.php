<?php

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
     * Public description of command
     * @var string
     */
    public $description = 'Rolls back all migrations from the most recent migration group.';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'Gets the most recent group of migrations and rolls them all back.';

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
        'steps,s:' => 'Specify the number of migrations to roll back',
    ];

    /**
     * Command can run without EE Core
     * @var boolean
     */
    public $standalone = false;

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        defined('PATH_THIRD') || define('PATH_THIRD', SYSPATH . 'user/addons/');

        // Specify the number of migrations to roll back
        $steps = $this->option('-s', -1);

        // Get all migrations in last batch and sort them
        $lastBatchofMigrations = ee('Model')->get('Migration')
            ->filter('migration_group', ee('Migration')->getLastMigrationGroup())
            ->order('migration_id', 'desc')
            ->all();

        if ($lastBatchofMigrations->count() === 0) {
            $this->complete("No migrations to rollback.");
        }

        $migrationsCount = 0;
        foreach ($lastBatchofMigrations as $migration) {
            $this->info('Rolling back: ' . $migration->migration);

            $migration->down();

            // Increment the number of migrations that have run
            $migrationsCount++;

            // If the number of migrations run equals our step count, exit with a message
            if ($migrationsCount == $steps) {
                $this->complete("Executed " . $migrationsCount . " migration" . (($steps > 1) ? 's' : '') . " successfully.");
            }
        }

        $this->complete('All migrations in group rolled back successfully!');
    }
}
