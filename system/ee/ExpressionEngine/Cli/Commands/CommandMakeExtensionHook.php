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
use ExpressionEngine\Service\Generator\ExtensionHookGenerator;

/**
 * Command to clear selected caches
 */
class CommandMakeExtensionHook extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Extension Hook Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:extension-hook';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:extension-hook sessions_start --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_make_extension_hook_option_addon',
        'install,i'   => 'command_make_extension_hook_option_install',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_extension_hook_lets_build_extension_hook');

        // Gather all the extension_hook information
        $this->data['name'] = $this->getFirstUnnamedArgument("command_make_extension_hook_ask_extension_hook_name", null, true);
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_extension_hook_ask_addon");

        $this->info('command_make_extension_hook_building_extension_hook');

        try {
            // Build the extension_hook
            $service = ee('ExtensionHookGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_make_extension_hook_created_successfully');

        // If install hook is set, lets install it now
        if ($this->option('--install')) {
            $this->info('command_make_extension_hook_installing_hook');

            $addon = ee('Addon')->get($this->data['addon']);

            if ($addon !== null && $addon->isInstalled()) {
                ee('Migration')->migrateAllByType($this->data['addon']);
                $this->info('command_make_extension_hook_installed_hook');
            } else {
                $this->fail('command_make_extension_hook_addon_must_be_installed_to_install_hook');
            }
        }
    }
}
