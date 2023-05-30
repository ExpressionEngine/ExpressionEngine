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
use ExpressionEngine\Cli\Commands\Upgrade\UpgradeMap;
use ExpressionEngine\Library\Filesystem\Filesystem;

class CommandUpdatePrepare extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Prepare Update';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'update:prepare';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php update:prepare';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'upgrade-ee'                => 'command_update_prepare_option_upgrade_ee',
        'force-add-on-upgrade'      => 'command_update_prepare_option_force_add_on_upgrade',
        'old-base-path:'            => 'command_update_prepare_option_old_base_path',
        'new-base-path:'            => 'command_update_prepare_option_new_base_path',
        'old-public-path:'          => 'command_update_prepare_option_old_public_path',
        'new-public-path:'          => 'command_update_prepare_option_new_public_path',
        'no-config-file'            => 'command_update_prepare_option_no_config_file',
        'ee-version'                => 'command_update_prepare_option_ee_version',
        'should-move-system-path'   => 'command_update_prepare_option_should_move_system_path',
        'old-system-path:'          => 'command_update_prepare_option_old_system_path',
        'new-system-path:'          => 'command_update_prepare_option_new_system_path',
        'should-move-template-path' => 'command_update_prepare_option_should_move_template_path',
        'old-template-path:'        => 'command_update_prepare_option_old_template_path',
        'new-template-path:'        => 'command_update_prepare_option_new_template_path',
        'should-move-theme-path'    => 'command_update_prepare_option_should_move_theme_path',
        'old-theme-path:'           => 'command_update_prepare_option_old_theme_path',
        'new-theme-path:'           => 'command_update_prepare_option_new_theme_path',
        'run-preflight-hooks'       => 'command_update_prepare_option_run_preflight_hooks',
        'run-postflight-hooks'      => 'command_update_prepare_option_run_postflight_hooks',
        'temp-directory'            => 'command_update_prepare_option_temp_directory',
    ];

    protected $upgradeConfigFile = [];

    protected $upgradeConfig = [];

    protected $configKeys = [
        'upgrade_ee'                => 'bool',
        'ee_version'                => 'string',
        'old_base_path'             => 'string',
        'new_base_path'             => 'string',
        'should_move_system_path'   => 'bool',
        'old_system_path'           => 'string',
        'new_system_path'           => 'string',
        'should_move_template_path' => 'bool',
        'old_template_path'         => 'string',
        'new_template_path'         => 'string',
        'should_move_theme_path'    => 'bool',
        'old_theme_path'            => 'string',
        'new_theme_path'            => 'string',
        'run_preflight_hooks'       => 'bool',
        'run_postflight_hooks'      => 'bool',
        'preflight_hooks'           => 'array',
        'postflight_hooks'          => 'array',
        'old_public_path'           => 'string',
        'new_public_path'           => 'string',
        'temp_directory'            => 'string',
    ];

    protected $filemap;

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->confirm('command_update_prepare_are_you_sure_you_want_to_proceed', false, ['required' => true, 'error_message' => 'command_update_prepare_upgrade_aborted']);

        $this->info('command_update_prepare_preparing_upgrade_for_site');

        // Collect all the info we need before we can start
        // pull in the config
        $this->preloadConfig();

        $this->preChecks();

        $this->runPreflightHooks();

        $this->moveOriginalSiteToTmp();
        $this->copyNewEEFiles();
        $this->copyOriginalConfig();

        // Move templates
        $this->moveTemplates();

        // @TODO: Move addons
        $this->moveAddons();

        // Done preparing - if they wanted the upgrade to be run after, run it here
        if ($upgrade = ($this->option('upgrade-ee') || $this->upgradeConfig['upgrade_ee'])) {
            $this->info('command_update_prepare_running_ee_upgrade');
            $this->runUpgrade();
        }

        $this->runPostflightHooks();

        $this->complete('command_update_prepare_process_complete');
    }

    private function runPreflightHooks()
    {
        if (!$this->upgradeConfig['run_preflight_hooks']) {
            return;
        }

        $this->info('command_update_prepare_running_preflight_hooks');

        foreach ($this->upgradeConfig['preflight_hooks'] as $hook) {
            call_user_func_array($hook, func_get_args());
        }
    }

    private function runPostflightHooks()
    {
        if (!$this->upgradeConfig['run_postflight_hooks']) {
            return;
        }

        $this->info('command_update_prepare_running_postflight_hooks');

        foreach ($this->upgradeConfig['postflight_hooks'] as $hook) {
            call_user_func_array($hook, func_get_args());
        }
    }

    private function preChecks()
    {
        $this->info('command_update_prepare_how_things_are_configured');

        foreach ($this->upgradeConfig as $upgradeKey => $upgradeValue) {
            if (is_bool($upgradeValue)) {
                $upgradeValue = $upgradeValue ? 'true' : 'false';
            }

            if (! is_array($upgradeValue)) {
                $this->write("<<green>>{$upgradeKey}<<reset>>: {$upgradeValue}");
            }
        }

        $this->info('command_update_prepare_notify_moving_files_to_tmp');
        $this->info('command_update_prepare_make_sure_you_have_backups');

        $this->confirm('command_update_prepare_are_you_sure_you_want_to_proceed', false, ['required' => true, 'error_message' => 'command_update_prepare_upgrade_aborted']);

        // Check if upgrade too
        if ($this->option('upgrade-ee')) {
            $this->info('command_update_prepare_notify_also_upgrade_ee_after');

            $this->confirm('command_update_prepare_are_you_sure_you_want_to_proceed', false, ['required' => true, 'error_message' => 'command_update_prepare_upgrade_aborted']);
        }

        return true;
    }

    private function preloadConfig()
    {
        // First up, load config file
        $this->getConfigFile();
        $this->parseConfig();
    }

    private function getConfigFile()
    {
        if ($this->option('no-config-file')) {
            return;
        }

        $customConfig = $this->getConfigPath();

        if (! $customConfig) {
            $path = $this->ask(lang('command_update_prepare_what_is_path_to_upgrade_config') . SYSPATH . ')');

            if (! ($customConfig = $this->getConfigPath($path))) {
                $this->error('command_update_prepare_custom_config_not_found');

                return;
            }
        }

        $this->upgradeConfigFile = include $customConfig;
    }

    private function runUpgrade()
    {
        $command = "php {$this->upgradeConfig['new_base_path']}/eecli.php update -y";

        if ($this->option('--force-add-on-upgrade')) {
            $command .= ' --force-addon-upgrades';
        }

        exec($command);
    }

    private function parseConfig()
    {
        foreach ($this->configKeys as $key => $type) {
            $value = null;

            $flag = str_replace('_', '-', $key);

            // Check for the flag
            if ($flagData = $this->option($flag, false)) {
                $value = $flagData;
            }

            // If no flag, check the file
            if (!$value && isset($this->upgradeConfigFile[$key])) {
                $value = $this->upgradeConfigFile[$key];
            }

            // If neither of those, we'll ask
            if (!$value) {
                if ($type == 'array') {
                    $value = [];
                }

                if ($type == 'string') {
                    $value = $this->ask("Please set {$key}:");
                }

                if ($type == 'bool') {
                    $value = $this->confirm("{$key}?");
                }
            }

            $this->upgradeConfig[$key] = $value;
        }

        // Load upgrade file map
        if ($this->upgradeConfigFile['upgrade_map']) {
            $filemap = $this->upgradeConfigFile['upgrade_map'];
        } else {
            $version = $this->upgradeConfig['ee_version'];

            $filemap = UpgradeMap::get($version);
        }

        $this->filemap = UpgradeMap::prepare($filemap);
    }

    private function getConfigPath($path = null)
    {
        $customConfig = ($path ? rtrim($path, '/') : SYSPATH) . '/upgrade.config.php';

        if (! file_exists($customConfig)) {
            return false;
        }

        return $customConfig;
    }

    private function moveOriginalSiteToTmp()
    {
        // Need to copy system/, index.php, admin.php and themes folder

        $tmp_folder = $this->prepTmpDirectory();

        ee('Filesystem')->rename($this->upgradeConfig['old_public_path'] . $this->filemap['index_file_old'], $tmp_folder . $this->filemap['index_file_old']);
        ee('Filesystem')->rename($this->upgradeConfig['old_public_path'] . $this->filemap['admin_file_old'], $tmp_folder . $this->filemap['admin_file_old']);

        ee('Filesystem')->rename($this->upgradeConfig['old_system_path'], $tmp_folder . 'system');

        if ($this->upgradeConfig['should_move_theme_path']) {
            ee('Filesystem')->rename($this->upgradeConfig['old_theme_path'], $tmp_folder . 'themes');
        }
    }

    private function prepTmpDirectory()
    {
        $tmp_folder = rtrim($this->upgradeConfig['old_base_path'], '/') . '/' . $this->upgradeConfig['temp_directory'];

        if (ee('Filesystem')->isDir($tmp_folder)) {
            ee('Filesystem')->delete($tmp_folder);
        } else {
            ee('Filesystem')->mkdir($tmp_folder);
        }

        return rtrim($tmp_folder, '/') . '/';
    }

    private function copyNewEEFiles()
    {
        $filesystem->copy(
            SYSPATH,
            $this->upgradeConfig['new_system_path']
        );

        if ($this->upgradeConfig['should_move_theme_path']) {
            ee('Filesystem')->copy(
                FCPATH . 'themes',
                $this->upgradeConfig['new_theme_path']
            );
        }

        ee('Filesystem')->copy(
            FCPATH . $this->filemap['admin_file'],
            $this->upgradeConfig['new_public_path'] . $this->filemap['admin_file']
        );

        ee('Filesystem')->copy(
            FCPATH . $this->filemap['index_file'],
            $this->upgradeConfig['new_public_path'] . $this->filemap['index_file']
        );

        ee('Filesystem')->copy(
            FCPATH . 'eecli',
            $this->upgradeConfig['new_base_path'] . 'eecli'
        );
    }

    private function copyOriginalConfig()
    {
        $tmp_folder = rtrim($this->upgradeConfig['old_base_path'], '/') . '/' . $this->upgradeConfig['temp_directory'] . '/';

        // We should check if this is EE2 or EE3+
        if (ee('Filesystem')->exists($tmp_folder . '/system/expressionengine')) {
            // It's EE2!
            ee('Filesystem')->copy(
                $tmp_folder . rtrim($this->filemap['config_path'], '/') . '/' . $this->filemap['config_file'],
                rtrim($this->upgradeConfig['new_system_path'], '/') . '/user/config/' . ltrim($this->filemap['config_file'], '/')
            );

            if (isset($this->filemap['database_file']) && $this->filemap['database_file'] !== 'config.php') {
                ee('Filesystem')->copy(
                    $tmp_folder . rtrim($this->filemap['config_path'], '/') . '/' . $this->filemap['database_file'],
                    rtrim($this->upgradeConfig['new_system_path'], '/') . '/user/config/' . ltrim($this->filemap['database_file'], '/')
                );

                $this->info('command_update_prepare_database_file_found_move_to_config');
            }
        } else {
            // It's EE3+!
            ee('Filesystem')->copy(
                $tmp_folder . $this->filemap['config_path'] . $this->filemap['config_file'],
                rtrim($this->upgradeConfig['new_system_path'], '/') . '/user/config/' . ltrim($this->filemap['config_file'], '/')
            );

            ee('Filesystem')->copy(
                $tmp_folder . $this->filemap['config_path'] . $this->filemap['database_file'],
                rtrim($this->upgradeConfig['new_system_path'], '/') . '/user/config/' . ltrim($this->filemap['database_file'], '/')
            );
        }
    }

    private function moveTemplates()
    {
        if (! $this->upgradeConfig['should_move_template_path']) {
            return;
        }

        // $tmp_folder = rtrim($this->upgradeConfig['old_base_path'], '/') . '/' . $this->upgradeConfig['temp_directory'] . '/';

        ee('Filesystem')->rename(
            rtrim($this->upgradeConfig['old_template_path'], '/'),
            rtrim($this->upgradeConfig['new_template_path'], '/')
        );
    }

    private function moveAddons()
    {
    }

    private function checkIfPathAllowed($path)
    {
        // Check if path exists
        if (! file_exists($path)) {
            $this->fail("{$path} does not exist.");
        }

        // Check if it is a directory
        if (! is_dir($path)) {
            $this->fail("{$path} is not a directory.");
        }

        // Check if it writable
        if (! is_writable($path)) {
            $this->fail("{$path} is not writable.");
        }

        return true;
    }
}
