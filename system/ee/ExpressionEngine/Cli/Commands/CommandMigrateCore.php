<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;

/**
 * Run migrations
 */
class CommandMigrateCore extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Migrate Core';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'migrate:core';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php migrate:core';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'steps,s:' => 'Specify the number of migrations to run',
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
        // Specify the number of migrations to run
        $steps = $this->option('-s', -1);

        $location = 'ExpressionEngine';

        $migrationGroup = ee('Migration')->getNextMigrationGroup();
        $ran = ee('Migration')->migrateAllByType($location, $migrationGroup, $steps);

        foreach ($ran as $ranMigration) {
            $this->info(lang('command_migrate_core_migrated') . $ranMigration);
        }

        $this->complete('command_migrate_core_all_migrations_completed');
    }
}
