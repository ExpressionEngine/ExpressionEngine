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
use ExpressionEngine\Library\Filesystem\Filesystem;
use ExpressionEngine\Model\Migration\Migration;

/**
 * Run migrations
 */
class CommandMigrate extends Cli
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
    public $signature = 'migrate';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php migrate';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'steps,s:'     => 'command_migrate_option_steps',
        'everything,e' => 'command_migrate_option_everything',
        'all'          => 'command_migrate_option_all',
        'core,c'       => 'command_migrate_option_core',
        'addon,a:'     => 'command_migrate_option_addon',
        'addons'       => 'command_migrate_option_addons',
    ];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        // Specify the number of migrations to run
        $steps = $this->option('-s', -1);

        $all = $this->option('--everything', false) || $this->option('--all', false);
        $core = $this->option('--core', false);
        $addons = $this->option('--addons', false);
        $addon = $this->option('--addon', false);

        // Lets figure out the location we're migrating
        $location = null;

        // If core is set, it means location is EE core
        if ($core) {
            $location = 'ExpressionEngine';
        } elseif ($all) {
            $location = 'all';
        } elseif ($addons) {
            $location = 'addons';
        } elseif ($addon) {
            $location = $addon;
        }

        // No location set. Lets ask and default to all
        if (is_null($location)) {
            $availableMigrationLocations = ee('Migration')->getAvailableLocations();
            if (count($availableMigrationLocations) === 0) {
                $this->complete('command_migrate_all_migrations_ran');
            }
            $location = $this->ask(lang('command_migrate_what_is_location') . ' [' . implode(', ', $availableMigrationLocations) . ']', 'all');
        }

        // No location set, even after
        if (! $location) {
            $this->fail('command_migrate_error_please_select_location');
        }

        $migrationGroup = ee('Migration')->getNextMigrationGroup();
        $ran = ee('Migration')->migrateAllByType($location, $migrationGroup, $steps);

        foreach ($ran as $ranMigration) {
            $this->info(lang('command_migrate_migrated') . $ranMigration);
        }

        $this->complete('command_migrate_all_migrations_completed');
    }
}
