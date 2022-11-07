<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Service\Generator\ActionGenerator;

/**
 * Command to clear selected caches
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
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_action_lets_build_action');

        // Gather alll the action information
        $this->data['name'] = $this->getFirstUnnamedArgument("command_make_action_ask_action_name", null, true);
        $this->data['addon'] = $this->getOptionOrAsk('--addon', "command_make_action_ask_addon", null, true);

        $this->info('command_make_action_building_action');

        try {
            // Build the action
            $service = ee('ActionGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->info('command_make_action_created_successfully');
    }
}
