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
 * Update or upgrade EE
 */
class CommandUpdateRunHook extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Run Update Hook';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'update:run-hook';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php run-update-hook functionName';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [];

    /**
     * sets the possible upgrade hooks
     * @var array
     */
    private $hooks;

    /**
     * The upgraded file confi
     * @var [type]
     */
    private $upgradeConfigFile;

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->getConfigFile();

        $this->setHooks();

        foreach ($this->arguments as $hook) {
            if (array_key_exists($hook, $this->hooks)) {
                $this->info(lang('command_update_run_hook_running') . $hook);

                call_user_func($this->hooks[$hook]);
            } else {
                $this->error(lang('command_update_run_hook_hook_not_found') . $hook);
            }
        }

        $this->complete('command_update_run_hook_success');
    }

    private function getConfigFile()
    {
        if ($this->option('no-config-file')) {
            return;
        }

        $path = $this->ask('command_update_run_hook_what_is_path_to_upgrade_config');

        if (! ($customConfig = $this->getConfigPath($path))) {
            $this->fail('command_update_run_hook_custom_config_not_found');
        }

        $this->upgradeConfigFile = include $customConfig;
    }

    private function setHooks()
    {
        $this->hooks = array_merge(
            $this->upgradeConfigFile['preflight_hooks'],
            $this->upgradeConfigFile['postflight_hooks']
        );
    }

    private function getConfigPath($path)
    {
        $customConfig = ($path ? rtrim($path, '/') : SYSPATH) . '/upgrade.config.php';

        if (! file_exists($customConfig)) {
            return false;
        }

        return $customConfig;
    }
}
