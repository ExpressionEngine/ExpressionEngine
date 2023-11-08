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
 * Command to make a new sidebar for an add-on
 */
class CommandMakeSidebar extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Add-on Sidebar Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:sidebar';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:sidebar --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_make_sidebar_option_addon',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_sidebar_lets_build_sidebar');

        // What add-on are we adding this to
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_sidebar_ask_addon");

        $this->info('command_make_sidebar_building_sidebar');

        try {
            // Build the Sidebar
            $service = ee('SidebarGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_make_sidebar_created_successfully');
    }
}
