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
 * Command to generate helper files for addons
 */
class CommandMakeHelper extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Helper Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:helper';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:helper --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:' => 'command_make_helper_option_addon',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_helper_lets_build_helper');

        $this->data['addon'] = $this->getOptionOrAskAddon('--addon', "command_make_helper_ask_addon");

        $this->info('command_make_helper_lets_build');

        try {
            // Build the helper
            $service = ee('HelperGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }

        $this->info('command_make_helper_created_successfully');
    }
}
