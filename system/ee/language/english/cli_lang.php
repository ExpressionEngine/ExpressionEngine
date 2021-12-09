<?php

$lang = array(
    // All generic CLI lang entries
    'cli_error_no_command_given'                   => 'No command given. Try `php eecli.php list` for full list of commands.',
    'cli_error_command_not_found'                  => 'Command not found. Try `php eecli.php list` for full list of commands.',
    'cli_error_ee_not_installed'                   => 'EE is not currently installed.',
    'cli_error_is_required'                        => 'Field is required.',
    'cli_error_is_required_field'                  => 'Field is required. Field: ',
    'cli_option_help'                              => 'See help menu for given command',
    'cli_error_the_specified_addon_does_not_exist' => 'The specified add-on does not exist',

    // Lang entries for command cache:clear
    'command_cache_clear_description'              => 'Clears all ExpressionEngine caches',
    'command_cache_clear_summary'                  => '',
    'command_cache_clear_option_type'              => 'Type of cache to clear (default: all)',
    'command_cache_clear_cache_does_not_exist'     => 'Cache does not exist. Use --help to see available caches.',
    'command_cache_clear_caches_cleared'           => ' caches are cleared!',

    // Lang entries for command list
    'command_list_description'                  => 'Lists all available commands',
    'command_list_summary'                      => 'This gives a full listing of all commands',
    'command_list_all_available_commands'       => 'All Available Commands:',
    'command_list_run_with_help'                => 'Run a command with --help for more information',
    'command_list_command_header'               => 'Command',
    'command_list_description_header'           => 'Description',

    // Lang entries for command make:addon
    'command_make_addon_description'            => 'Creates a new add-on',
    'command_make_addon_summary'                => 'This interactively generates an EE addon directly in your user directory',
    'command_make_addon_lets_build_addon'       => 'Let\'s build your add-on!',
    'command_make_addon_description_question'   => 'description?',
    'command_make_addon_version_question'       => 'version?',
    'command_make_addon_author_question'        => 'author?',
    'command_make_addon_author_url_question'    => 'author URL?',
    'command_make_addon_have_settings_question' => 'have settings?',
    'command_make_addon_lets_build'             => 'Let\'s build!',
    'command_make_addon_created_successfully'   => 'Your add-on has been created successfully!',
    'command_make_addon_what_hooks_to_use'      => 'What hooks would you like to use? (Read more: https://docs.expressionengine.com/latest/development/extensions.html)',
    'command_make_addon_ext_hooks'              => 'Extension hooks:',
    'command_make_addon_ft_compatibility'       => 'Fieldtype compatibility?',
    'command_make_addon_what_type_of_addon'     => 'What type of addon would you like to create?',
    'command_make_addon_select_proper_addon'    => 'Please select a proper add-on type.',
    'command_make_addon_what_is_name'           => 'What is the name of your add-on?',
    'command_make_addon_does_your'              => 'Does your ',
    'command_make_addon_addon_name_required'    => 'Addon name is required.',
    // make:addon options
    'command_make_addon_option_extension'       => 'Create an extension',
    'command_make_addon_option_plugin'          => 'Create a plugin',
    'command_make_addon_option_fieldtype'       => 'Create a fieldtype',
    'command_make_addon_option_module'          => 'Create a module',
    'command_make_addon_option_typography'      => 'Should use plugin typography',
    'command_make_addon_option_has'             => 'Add-on has settings (yes/no)',
    'command_make_addon_option_version'         => 'Version of the add-on',
    'command_make_addon_option_description'     => 'Description of the add-on',
    'command_make_addon_option_author'          => 'Author of the add-on',
    'command_make_addon_option_author_url'      => 'Author url of the add-on',
    'command_make_addon_option_services'        => 'Services to create. Multi-pass option.',
    'command_make_addon_option_models'          => 'Models to create. Multi-pass option.',
    'command_make_addon_option_commands'        => 'Commands to create. Multi-pass option.',
    'command_make_addon_option_consents'        => 'Consents. Multi-pass option.',
    'command_make_addon_option_cookies'         => 'Cookies to create, with a colon separating name and value (i.e. name:value). Multi-pass option.',
    'command_make_addon_option_hooks'           => 'Hooks in use. Multi-pass option.',

    // Lang entries for command make:command
    'command_make_command_description'          => 'Creates a new CLI command for an add-on',
    'command_make_command_summary'              => 'This interactively generates a CLI command for an existing third-party addon',
    'command_make_command_lets_build_command'   => 'Let\'s build your command!',
    'command_make_command_ask_description'      => 'Command description?',
    'command_make_command_ask_signature'        => 'Command signature? (i.e. make:magic)',
    'command_make_command_lets_build'           => 'Let\'s build!',
    'command_make_command_created_successfully' => 'Your command has been created successfully!',
    'command_make_command_ask_command_name'     => 'Command name?',
    'command_make_command_ask_addon'            => 'What addon do you want to add this to?',
    // make:command options
    'command_make_command_option_addon'         => 'Folder for third-party add-on you want to add command to',
    'command_make_command_option_description'   => 'Description of command',
    'command_make_command_option_signature'     => 'Signature for command (i.e. make:magic)',

    // Lang entries for command make:migration
    'command_make_migration_description'                        => 'Creates a new migration',
    'command_make_migration_summary'                            => 'This generates a new migration for the core or for an add-on',
    'command_make_migration_what_is_migration_name'             => 'What is the name of your migration?',
    'command_make_migration_no_name_specified'                  => 'No migration name specified. For help with this command, use --help',
    'command_make_migration_using_migration_name'               => 'Using migration name:      ',
    'command_make_migration_table_creating_migration'           => 'Creating migration: ',
    'command_make_migration_table_migration_action'             => '  Migration Action: ',
    'command_make_migration_table_type_name'                    => '  Type name:        ',
    'command_make_migration_table_class_name'                   => '  Class name:       ',
    'command_make_migration_table_file_location'                => '  File Location:    ',
    'command_make_migration_table_template_name'                => '  Template name:    ',
    'command_make_migration_successfully_wrote_file'            => 'Successfully wrote new migration file.',
    'command_make_migration_what_table_is_migration_for'        => 'What table is this migration for?',
    'command_make_migration_ask_migration_action'               => 'What is the migration action',
    'command_make_migration_ask_migration_category'             => 'What is the migration category',
    'command_make_migration_where_to_generate_migration'        => 'Where should we generate this migration? (ExpressionEngine or existing add-on)',

    // make:migration options
    'command_make_migration_option_name'                        => 'Name of migration',
    'command_make_migration_option_table'                       => 'Table name',
    'command_make_migration_option_status'                      => 'Status name',
    'command_make_migration_option_location'                    => 'Migration location. Current options are ExpressionEngine or shortname of an add-on that is currently installed. Defaults to ExpressionEngine.',
    'command_make_migration_option_create'                      => 'Specify command is a create command',
    'command_make_migration_option_update'                      => 'Specify command is an update command',

    // make:migration Error message
    'command_make_migration_missing_required_template_variable' => 'Missing required variable(s) for parsing migration template: ',

    // Lang entries for command make:prolet
    'command_make_prolet_description'                  => 'Creates a new prolet for an add-on',
    'command_make_prolet_summary'                      => 'This interactively generates an EE Prolet for an existing third-party addon',
    'command_make_prolet_lets_build_prolet'            => 'Let\'s build a new prolet!',
    'command_make_prolet_ask_prolet_name'              => 'What is the prolet name?',
    'command_make_prolet_ask_addon'                    => 'What addon is the prolet being added to?',
    'command_make_prolet_ask_description'              => 'What is the Prolet description?',
    'command_make_prolet_building_prolet'              => 'Building Prolet.',
    'command_make_prolet_created_successfully'         => 'Prolet created successfully!',
    'command_make_prolet_ask_widget_name'              => 'What is the name of the widget?',
    'command_make_prolet_generating_widget'            => 'Generating widget.',
    'command_make_prolet_widget_created_successfully'  => 'Widget created successfully!',
    'command_make_prolet_error_addon_must_have_module' => 'To generate a prolet, the add-on must have a module file.',
    'command_make_prolet_error_addon_must_have_icon'   => 'To generate a prolet, the add-on must have an icon. To generate a default icon, use --generate-icon.',

    // make:prolet options
    'command_make_prolet_option_addon'                 => 'Folder for third-party add-on you want to add prolet to',
    'command_make_prolet_option_description'           => 'Description of prolet',
    'command_make_prolet_option_has_widget'            => 'Create a widget for the add-on after generating the prolet (optional)',
    'command_make_prolet_option_widget_name'           => 'Name of widget',
    'command_make_prolet_option_generate_icon'         => 'Generate a default addon icon file when creating a prolet',

    // Lang entries for command make:widget
    'command_make_widget_description'                 => 'Generates widgets for existing add-ons.',
    'command_make_widget_lets_build_widget'           => 'Let\'s build a widget!',
    'command_make_widget_ask_widget_name'             => 'What is the widget name?',
    'command_make_widget_ask_addon'                   => 'What add-on is this for?',
    'command_make_widget_building_widget'             => 'Building Widget.',
    'command_make_widget_created_successfully'        => 'Widget created successfully!',
    'command_make_widget_option_addon'                => 'Name of addon',

    // Lang entries for command make:model
    'command_make_model_description'                            => 'Creates a new model for an add-on',
    'command_make_model_summary'                                => 'This interactively generates an EE model for an existing third-party addon',
    'command_make_model_lets_build_model'                       => 'Let\'s build your model!',
    'command_make_model_lets_build'                             => 'Let\'s build!',
    'command_make_model_created_successfully'                   => 'Your model has been created successfully!',
    'command_make_model_ask_model_name'                         => 'Model name?',
    'command_make_model_ask_addon'                              => 'What addon do you want to add this to?',
    // make:model options
    'command_make_model_option_addon' => 'Folder for third-party add-on you want to add model to',

    // Lang entries for command migrate
    'command_migrate_description'                  => 'Runs specified migrations (all, core, or add-ons)',
    'command_migrate_summary'                      => 'Loops through the core migrations folder, and add-on migrations folder and executes all migrations that have not previously been run. If running all migrations, core migrations will all execute first, then add-on migrations. When migrations are being run for multiple add-ons, all migrations for each add-on are grouped together and ran together',
    'command_migrate_all_migrations_ran'           => 'All available migrations have already run.',
    'command_migrate_what_is_location'             => 'What is the location of your migration?',
    'command_migrate_error_please_select_location' => 'Please select location of migration using --core, --everything, --addons, or --addon=addon_name.',
    'command_migrate_migrated'                     => 'Migrated: ',
    'command_migrate_all_migrations_completed'     => 'All migrations completed successfully!',
    // migrate options
    'command_migrate_option_steps'                 => 'Specify the number of migrations to run',
    'command_migrate_option_everything'            => 'Run all migrations. Core runs first, all add-on migrations, one at a time.',
    'command_migrate_option_all'                   => 'Run all migrations. Alias for --everything',
    'command_migrate_option_core'                  => 'Run only core migrations. This excludes all add-on migrations.',
    'command_migrate_option_addon'                 => 'Run migration only for specified addon.',
    'command_migrate_option_addons'                => 'Run migration only for specified addon.',

    // Lang entries for command migrate:addon
    'command_migrate_addon_description'               => 'Runs add-on migrations',
    'command_migrate_addon_summary'                   => 'Loops through the addon folders and runs all migrations that have not previously been run. If running all addons, migrations will be grouped by add-on and run together',
    'command_migrate_addon_all_migrations_ran'        => 'All available add-on migrations have already run.',
    'command_migrate_addon_ask_location_of_migration' => 'What is the location of your migration?',
    'command_migrate_addon_error_no_location_set'     => 'Please select location of migration using --everything, or --addon=addon_name.',
    'command_migrate_addon_migrated'                  => 'Migrated: ',
    'command_migrate_addon_all_migrations_completed'  => 'All migrations completed successfully!',
    // migrate:addon options
    'command_migrate_addon_option_steps'              => 'Specify the number of migrations to run',
    'command_migrate_addon_option_everything'         => 'Run all addn-on migrations',
    'command_migrate_addon_option_all'                => 'Run all addn-on migrations. Alias for --everything',
    'command_migrate_addon_option_addon'              => 'Run migration only for specified addon.',

    // Lang entries for command migrate:all
    'command_migrate_all_description'              => 'Runs core migrations, then each add-on\'s migrations',
    'command_migrate_all_summary'                  => 'Loops through the core migrations folder, and add-on migrations folder and executes all migrations that have not previously been run. Core migrations will all execute first, then add-on migrations. When migrations are being run for multiple add-ons, all migrations for each add-on are grouped together and ran together',
    'command_migrate_all_migrated'                 => 'Migrated: ',
    'command_migrate_all_all_migrations_completed' => 'All migrations completed successfully!',
    // migrate:all options
    'command_migrate_all_option_steps'             => 'Specify the number of migrations to run',

    // Lang entries for command migrate:core
    'command_migrate_core_description'                          => 'Runs core migrations',
    'command_migrate_core_summary'                              => 'Loops through the core migrations folder and executes all migrations that have not previously been run',
    'command_migrate_core_migrated'                             => 'Migrated: ',
    'command_migrate_core_all_migrations_completed'             => 'All migrations completed successfully!',
    // migrate:core options
    'command_migrate_core_option_steps'                         => 'Specify the number of migrations to run',

    // Lang entries for command migrate:reset
    'command_migrate_reset_description'                         => 'Rolls back all migrations',
    'command_migrate_reset_summary'                             => 'Rolls back all migrations at once',
    'command_migrate_reset_no_migrations_to_rollback'           => 'No migrations to rollback.',
    'command_migrate_reset_rolling_back'                        => 'Rolling back: ',
    'command_migrate_reset_all_migrations_rolled_back'          => 'All migrations have been rolled back successfully!',

    // Lang entries for command migrate:rollback
    'command_migrate_rollback_description'                      => 'Rolls back most recent migration group',
    'command_migrate_rollback_summary'                          => 'Gets the most recent group of migrations and rolls them all back',
    'command_migrate_rollback_no_migrations_to_rollback'        => 'No migrations to rollback.',
    'command_migrate_rollback_rolling_back'                     => 'Rolling back: ',
    'command_migrate_rollback_migrations_executed_successfully' => ' migrations executed successfully.',
    'command_migrate_rollback_all_migrations_rolled_back'       => 'All migrations in group rolled back successfully!',
    // migrate:rollback options
    'command_migrate_rollback_option_steps'                     => 'Specify the number of migrations to roll back',

    // Lang entries for command update
    'command_update_description'                                => 'Updates ExpressionEngine',
    'command_update_summary'                                    => 'Runs all available ExpressionEngine updates',
    'command_update_is_already_up_to_date'                      => ' is already up-to-date!',
    'command_update_new_version_available'                      => 'There is a new version of ExpressionEngine available:',
    'command_update_confirm_upgrade'                            => 'Would you like to upgrade?',
    'command_update_not_run'                                    => 'Update not run.',
    'command_update_success'                                    => 'Success! Create something awesome!',
    'command_update_indicated_upgrade_all_addons'               => 'You have indicated you want to upgrade all addons.',
    'command_update_confirm_addon_upgrade'                      => 'Are you sure? This may be a destructive action.',
    'command_update_addon_update_halted'                        => 'Addon update halted',
    'command_update_getting_info_from_local_env'                => 'Getting upgrade information from your local environment',
    'command_update_getting_info_from_ee_com'                   => 'Getting upgrade information from ExpressionEngine.com',
    'command_update_updater_failed'                             => 'Updater failed',
    'command_update_updating_to_version'                        => 'Updating to version ',
    'command_update_failed_on_version'                          => 'Failed on version ',
    'command_update_error_updater_failed_missing_version'       => 'Updater failed because of missing version. Please update the UpgradeMap. Version: ',
    'command_update_missing_avatar_path_message'                => 'Your update process will fail without a set avatar path.',
    'command_update_enter_full_avatar_path'                     => 'Enter full avatar path',
    // update options
    'command_update_option_rollback'                            => 'Rollback last update',
    'command_update_option_verbose'                             => 'Verbose output',
    'command_update_option_microapp'                            => 'Run as microapp',
    'command_update_option_step'                                => 'Step in process (param required)',
    'command_update_option_no_bootstrap'                        => 'Runs with no bootstrap',
    'command_update_option_force_addon_upgrades'                => 'Automatically runs all addon updaters at end of update (advanced)',
    'command_update_option_y'                                   => 'Skip all confirmations. Don\'t do this.',

    // Lang entries for command update:prepare
    'command_update_prepare_description'                        => 'Prepare a site to be upgraded using these files',
    'command_update_prepare_summary'                            => 'This command copies all files necessary for upgrading into a different ExpressionEngine site and restructures it',
    'command_update_prepare_preparing_upgrade_for_site'         => 'Preparing the upgrade for a site.',
    'command_update_prepare_running_ee_upgrade'                 => 'Running EE upgrade',
    'command_update_prepare_process_complete'                   => 'Process complete!',
    'command_update_prepare_running_preflight_hooks'            => 'Running preflight hooks',
    'command_update_prepare_running_postflight_hooks'           => 'Running postflight hooks',
    'command_update_prepare_how_things_are_configured'          => 'Here\'s how things are configured:',
    'command_update_prepare_notify_moving_files_to_tmp'         => 'We are about to move X file to tmp/X and Y to system/Y',
    'command_update_prepare_make_sure_you_have_backups'         => 'Make sure you have backups!',
    'command_update_prepare_are_you_sure_you_want_to_proceed'   => 'Are you sure you want to proceed?',
    'command_update_prepare_upgrade_aborted'                    => 'Upgrade aborted',
    'command_update_prepare_notify_also_upgrade_ee_after'       => 'You also said you want to upgrade EE after moving these files around.',
    'command_update_prepare_what_is_path_to_upgrade_config'     => 'What is the path to your upgrade.config.php? (defaults to SYSPATH, currently ',
    'command_update_prepare_custom_config_not_found'            => 'Custom config not found.',
    'command_update_prepare_database_file_found_move_to_config' => 'We found a database file. Please move this information in to config.php',
    // update:prepare options
    'command_update_prepare_option_upgrade_ee'                  => 'Start the upgrade after moving files',
    'command_update_prepare_option_force_add_on_upgrade'        => 'After upgrading EE, runs addon upgrades',
    'command_update_prepare_option_old_base_path'               => 'Absolute path of old site',
    'command_update_prepare_option_new_base_path'               => 'Absolute path of new site',
    'command_update_prepare_option_old_public_path'             => 'Absolute path of old site public path',
    'command_update_prepare_option_new_public_path'             => 'Absolute path of new site public path',
    'command_update_prepare_option_no_config_file'              => 'Ignores the config file and doesn\'t check for it',
    'command_update_prepare_option_ee_version'                  => 'The current site ',
    'command_update_prepare_option_should_move_system_path'     => 'Whether the upgrade process should move the old system folder to the new site',
    'command_update_prepare_option_old_system_path'             => 'Absolute path of old site system folder',
    'command_update_prepare_option_new_system_path'             => 'Absolute path of new site system folder',
    'command_update_prepare_option_should_move_template_path'   => 'Whether the upgrade process should move the old template folder to the new site',
    'command_update_prepare_option_old_template_path'           => 'Absolute path of old site template folder',
    'command_update_prepare_option_new_template_path'           => 'Absolute path of new site template folder',
    'command_update_prepare_option_should_move_theme_path'      => 'Whether the upgrade process should move the old theme folder to the new site',
    'command_update_prepare_option_old_theme_path'              => 'Absolute path of old site user theme folder',
    'command_update_prepare_option_new_theme_path'              => 'Absolute path of new site user theme folder',
    'command_update_prepare_option_run_preflight_hooks'         => 'Whether the upgrade process should run defined preflight hooks',
    'command_update_prepare_option_run_postflight_hooks'        => 'Whether the upgrade process should run defined postflight hooks',
    'command_update_prepare_option_temp_directory'              => 'The directory we work magic in',

    // Lang entries for command update:run-hook
    'command_update_run_hook_description'                       => 'Runs update hooks from your upgrade.config.php file',
    'command_update_run_hook_summary'                           => 'This will run one of the preflight or postflight hooks as defined in the upgrade.config.php file. This can be a destructive action, so use with caution',
    'command_update_run_hook_running'                           => 'Running: ',
    'command_update_run_hook_hook_not_found'                    => 'Hook not found: ',
    'command_update_run_hook_success'                           => 'Success!',
    'command_update_run_hook_what_is_path_to_upgrade_config'    => 'What is the path to your upgrade.config.php? (defaults to SYSPATH)',
    'command_update_run_hook_custom_config_not_found'           => 'Custom config not found.',

);

// EOF
