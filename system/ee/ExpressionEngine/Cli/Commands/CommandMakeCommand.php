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
class CommandMakeCommand extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Command Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:command';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:command MakeMagic --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'       => 'command_make_command_option_addon',
        'description,d:' => 'command_make_command_option_description',
        'signature,s:'   => 'command_make_command_option_signature',
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
        $this->info('command_make_command_lets_build_command');

        // Gather all the command information
        $this->data['name'] = $this->getFirstUnnamedArgument("command_make_command_ask_command_name", null, true);
        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_command_ask_addon");
        $this->data['description'] = $this->getOptionOrAsk('--description', "command_make_command_ask_description");
        $this->data['signature'] = $this->getOptionOrAsk('--signature', "command_make_command_ask_signature", null, true);

        if (substr($this->data['signature'], 0, strlen($this->data['addon'] . ":")) == $this->data['addon'] . ":") {
            $this->data['signature'] = substr($this->data['signature'], strlen($this->data['addon'] . ":"));
        }
        // Lets prefix with the add-on name
        $this->data['signature'] = $this->data['addon'] . ":" . $this->data['signature'];

        $this->info('command_make_command_lets_build');

        try {
            // Build the command
            $service = ee('CommandGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_make_command_created_successfully');
    }
}
