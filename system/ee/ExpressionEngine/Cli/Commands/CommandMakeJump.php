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
 * Command to clear selected caches
 */
class CommandMakeJump extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Add Jumps File Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:jump';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:jump --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_make_jump_file_addon',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee('CP/JumpMenu')->clearAllCaches();
        $this->info('command_make_cp_jumps');

        // Gather all the mcp information
        // $this->data['name'] = $this->getFirstUnnamedArgument("command_make_cp_route_ask_route_name", null, true);
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_cp_jumps_ask_addon");

        $this->info('command_make_cp_jumps_building_jumps');

        try {
            // Build the mcp
            $service = ee('JumpsGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_make_cp_jumps_created_successfully');
    }
}
