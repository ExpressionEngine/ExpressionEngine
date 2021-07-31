<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Model\Migration\Migration;

/**
 * Run migrations
 */
class CommandMigrateAll extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Migrate';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'migrate:all';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php migrate:all';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'steps,s:' => 'command_migrate_all_option_steps',
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

        $location = 'all';

        $migrationGroup = ee('Migration')->getNextMigrationGroup();
        $ran = ee('Migration')->migrateAllByType($location, $migrationGroup, $steps);

        foreach ($ran as $ranMigration) {
            $this->info(lang('command_migrate_all_migrated') . $ranMigration);
        }

        $this->complete('command_migrate_all_all_migrations_completed');
    }
}
