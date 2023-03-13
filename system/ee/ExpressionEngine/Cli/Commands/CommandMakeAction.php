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
 * Command to make action files for addons
 */
class CommandMakeAction extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Action Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:action';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:action MyNewAction --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_make_action_option_addon',
        'install,i'   => 'command_make_action_option_install',
        'csrf_exempt,c'   => 'command_make_action_option_csrf_exempt',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_action_lets_build_action');

        // Gather all the action information
        $this->data['name'] = $this->getFirstUnnamedArgument("command_make_action_ask_action_name", null, true);
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_action_ask_addon");
        $this->data['csrf_exempt'] = (bool) $this->option('--csrf_exempt');

        $this->info('command_make_action_building_action');

        try {
            // Build the action
            $service = ee('ActionGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_make_action_created_successfully');

        // If install action is set, lets install it now
        if ($this->option('--install')) {
            $this->info('command_make_action_installing_action');

            $addon = ee('Addon')->get($this->data['addon']);

            if ($addon !== null && $addon->isInstalled()) {
                ee('Migration')->migrateAllByType($this->data['addon']);
                $this->info('command_make_action_installed_action');
            } else {
                $this->fail('command_make_action_addon_must_be_installed_to_install_action');
            }
        }
    }
}
