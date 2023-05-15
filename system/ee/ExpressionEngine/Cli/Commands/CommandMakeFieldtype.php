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
 * Command to make a fieldtype
 */
class CommandMakeFieldtype extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Fieldtype Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:fieldtype';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:fieldtype MyNewFieldtype --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_make_fieldtype_option_addon',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_fieldtype_lets_build_fieldtype');

        // Gather all the fieldtype information
        $this->data['name'] = $this->getFirstUnnamedArgument("command_make_fieldtype_ask_fieldtype_name", null, false);
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_fieldtype_ask_addon");

        $this->info('command_make_fieldtype_building_fieldtype');

        try {
            // Build the fieldtype
            $service = ee('FieldtypeGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_make_fieldtype_created_successfully');
    }
}
