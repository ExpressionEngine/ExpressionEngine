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
 * Command to generate service files for addons
 */
class CommandMakeService extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Service Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:service';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:service MyService --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'    => 'command_make_service_option_addon',
        'singleton,s' => 'command_make_service_option_singleton',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_service_lets_build_service');

        // Gather all the service information
        $this->data['name'] = $this->getFirstUnnamedArgument("command_make_service_ask_service_name", null, true);
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_service_ask_addon");
        $this->data['is_singleton'] = (bool) $this->option('--singleton');

        $this->info('command_make_service_lets_build');

        try {
            // Build the service
            $service = ee('ServiceGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_make_service_created_successfully');
    }
}
