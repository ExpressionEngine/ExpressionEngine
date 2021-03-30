<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Model\Migration\Migration;

/**
 * Run migrations
 */
class CommandMigrateReset extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Migrate reset';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'migrate:reset';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php migrate:reset';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
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
        // Get all migrations
        $migrations = ee('Model')->get('Migration')->order('migration_id', 'desc')->all();

        if ($migrations->count() === 0) {
            $this->complete("No migrations to rollback.");
        }

        foreach ($migrations as $migration) {
            $this->info('Rolling back: ' . $migration->migration);

            $migration->down();
        }

        $this->complete('All migrations have been rolled back successfully!');
    }
}
