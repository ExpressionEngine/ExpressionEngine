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
use ExpressionEngine\Service\Generator\TagGenerator;

/**
 * Command to clear selected caches
 */
class CommandMakeTag extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Tag Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:tag';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:tag MyNewTag --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'        => 'command_make_tag_option_addon',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_tag_lets_build_tag');

        // Gather alll the tag information
        $this->data['name'] = $this->getFirstUnnamedArgument("command_make_tag_ask_tag_name", null, true);
        $this->data['addon'] = $this->getOptionOrAsk('--addon', "command_make_tag_ask_addon", null, true);

        $this->info('command_make_tag_building_tag');

        try {
            // Build the tag
            $service = ee('TagGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->info('command_make_tag_created_successfully');
    }
}
