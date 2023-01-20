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

/**
 * Run migrations
 */
class CommandMigrateAddon extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Migrate Addon';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'migrate:addon';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php migrate:addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'steps,s:'     => 'command_migrate_addon_option_steps',
        'everything,e' => 'command_migrate_addon_option_everything',
        'all'          => 'command_migrate_addon_option_all',
        'addon,a:'     => 'command_migrate_addon_option_addon',
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
        $addon = $this->option('--addon', false);

        // Lets figure out the location we're migrating
        $location = null;

        // If all is set, it means location is all addons
        if ($all) {
            $location = 'addons';
        } elseif ($addon) {
            $location = $addon;
        }

        // No location set. Lets ask and default to all
        if (is_null($location)) {
            $availableMigrationLocations = ee('Migration')->getAddonsWithMigrations();
            if (count($availableMigrationLocations) === 0) {
                $this->complete('command_migrate_addon_all_migrations_ran');
            }
            $location = $this->ask(lang('command_migrate_addon_ask_location_of_migration') . ' [all, ' . implode(', ', $availableMigrationLocations) . ']', 'addons');
        }

        // No location set, even after
        if (! $location) {
            $this->fail('command_migrate_addon_error_no_location_set');
        }

        // Location all means all addons
        if ($location == 'all') {
            $location = 'addons';
        }

        $migrationGroup = ee('Migration')->getNextMigrationGroup();
        $ran = ee('Migration')->migrateAllByType($location, $migrationGroup, $steps);

        foreach ($ran as $ranMigration) {
            $this->info(lang('command_migrate_addon_migrated') . $ranMigration);
        }

        $this->complete('command_migrate_addon_all_migrations_completed');
    }
}
