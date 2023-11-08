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
use ExpressionEngine\Service\Generator\WidgetGenerator;

/**
 * Command to clear selected caches
 */
class CommandMakeWidget extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Widget Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:widget';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:widget MyNewWidget --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'       => 'command_make_widget_option_addon',
    ];

    protected $data = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_widget_lets_build_widget');

        // Gather alll the widget information
        $this->data['name'] = $this->getFirstUnnamedArgument("command_make_widget_ask_widget_name", null, true);
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_widget_ask_addon");

        $this->info('command_make_widget_building_widget');

        try {
            // Build the widget
            $service = ee('WidgetGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_make_widget_created_successfully');
    }
}
