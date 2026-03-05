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
class CommandMakeTemplateGenerator extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Create Template Generator for Add-on';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:template-generator';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:template-generator --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_make_template_generator_option_addon',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_template_generator_lets_build_template_generator');

        // Gather all the template generator information
        $this->data['name'] = $this->getFirstUnnamedArgument("command_make_template_generator_ask_name", null, true);
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_template_generator_ask_addon");

        $this->info('command_make_template_generator_building_template_generator');

        try {
            // Build the action
            $service = ee('TemplateGeneratorGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_make_template_generator_created_successfully');
    }
}
