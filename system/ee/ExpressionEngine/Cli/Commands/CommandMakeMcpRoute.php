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

/**
 * Command to clear selected caches
 */
class CommandMakeMcpRoute extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Mcp Route Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:mcp-route';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:mcp-route MyNewRoute --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_make_mcp_route_option_addon',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_mcp_route_lets_build_mcp_route');

        // Gather all the mcp information
        $this->data['name'] = $this->getFirstUnnamedArgument("command_make_mcp_route_ask_route_name", null, true);
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_mcp_route_ask_addon");

        $this->info('command_make_mcp_route_building_mcp_route');

        try {
            // Build the mcp
            $service = ee('McpRouteGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->info('command_make_mcp_route_created_successfully');
    }
}
