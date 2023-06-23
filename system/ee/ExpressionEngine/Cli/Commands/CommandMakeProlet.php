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
use ExpressionEngine\Service\Generator\ProletGenerator;

/**
 * Command to clear selected caches
 */
class CommandMakeProlet extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Prolet Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:prolet';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:prolet MyNewProlet --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_make_prolet_option_addon',
        'description,d:'  => 'command_make_prolet_option_description',
        'generate-icon,i' => 'command_make_prolet_option_generate_icon',
        'has-widget,w'    => 'command_make_prolet_option_has_widget',
        'widget-name,n:'  => 'command_make_prolet_option_widget_name',
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
        $this->info('command_make_prolet_lets_build_prolet');

        // Gather alll the prolet information
        $this->data['name'] = $this->getFirstUnnamedArgument("command_make_prolet_ask_prolet_name", null, true);
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_prolet_ask_addon");
        $this->data['description'] = $this->getOptionOrAsk('--description', "command_make_prolet_ask_description");

        // Add flag for generating a default icon file
        $this->data['generate-icon'] = $this->option('--generate-icon', false);

        // Has widget? We wont ask this one. If the flag isn't there, they can use the widget generator to generate a widget
        $this->data['has-widget'] = $this->option('--has-widget', false);

        // If it has a widget, we'll also collect widget options
        if ($this->data['has-widget']) {
            $widgetData['name'] = $this->getOptionOrAsk('--widget-name', "command_make_prolet_ask_widget_name");
            $widgetData['addon'] = $this->data['addon'];
        }

        $this->info('command_make_prolet_building_prolet');

        try {
            // Build the prolet
            $service = ee('ProletGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_make_prolet_created_successfully');

        // If it has a widget, lets generate it now
        if ($this->data['has-widget']) {
            $this->info('command_make_prolet_generating_widget');

            try {
                // Build the widget
                $service = ee('WidgetGenerator', $widgetData);
                $service->build();
            } catch (\Exception $e) {
                $this->fail(addslashes($e->getMessage()));
            }

            $this->info('command_make_prolet_widget_created_successfully');
        }
    }
}
