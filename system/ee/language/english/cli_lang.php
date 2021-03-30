<?php

$lang = array(
    'command_cache_clear_description'      => 'Clears all ExpressionEngine caches',
    'command_cache_clear_summary'          => '',

    'command_list_description'             => 'Lists all available commands',
    'command_list_summary'                 => 'This gives a full listing of all commands',

    'command_make_addon_description'       => 'Creates a new add-on',
    'command_make_addon_summary'           => '',

    'command_make_command_description'     => 'Creates a new CLI command for an add-on',
    'command_make_command_summary'         => '',

    'command_make_migration_description'   => 'Creates a new migration',
    'command_make_migration_summary'       => 'This generates a new migration for the core or for an add-on',

    'command_make_model_description'       => 'Creates a new model for an add-on',
    'command_make_model_summary'           => '',

    'command_migrate_description'          => 'Runs specified migrations (all, core, or add-ons)',
    'command_migrate_summary'              => 'Loops through the core migrations folder, and add-on migrations folder and executes all migrations that have not previously been run. If running all migrations, core migrations will all execute first, then add-on migrations. When migrations are being run for multiple add-ons, all migrations for each add-on are grouped together and ran together',

    'command_migrate_addon_description'    => 'Runs add-on migrations',
    'command_migrate_addon_summary'        => 'Loops through the addon folders and runs all migrations that have not previously been run. If running all addons, migrations will be grouped by add-on and run together',

    'command_migrate_all_description'      => 'Runs core migrations, then each add-on\'s migrations',
    'command_migrate_all_summary'          => 'Loops through the core migrations folder, and add-on migrations folder and executes all migrations that have not previously been run. Core migrations will all execute first, then add-on migrations. When migrations are being run for multiple add-ons, all migrations for each add-on are grouped together and ran together',

    'command_migrate_core_description'     => 'Runs core migrations',
    'command_migrate_core_summary'         => 'Loops through the core migrations folder and executes all migrations that have not previously been run',

    'command_migrate_reset_description'    => 'Rolls back all migrations',
    'command_migrate_reset_summary'        => 'Rolls back all migrations at once',

    'command_migrate_rollback_description' => 'Rolls back most recent migration group',
    'command_migrate_rollback_summary'     => 'Gets the most recent group of migrations and rolls them all back',

    'command_update_description'           => 'Updates ExpressionEngine',
    'command_update_summary'               => 'Runs all available ExpressionEngine updates',

    'command_update_prepare_description'   => 'Prepare a site to be upgraded using these files',
    'command_update_prepare_summary'       => 'This command copies all files necessary for upgrading into a different ExpressionEngine site and restructures it',

    'command_update_run_hook_description'  => 'Runs update hooks from your upgrade.config.php file',
    'command_update_run_hook_summary'      => 'This will run one of the preflight or postflight hooks as defined in the upgrade.config.php file. This can be a destructive action, so use with caution',
);

// EOF
