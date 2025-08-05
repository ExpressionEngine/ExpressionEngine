<?php
/**
 * 
 * # First-party addon
* php eecli.php make:helper "structure" --type="first-party"

* # Third-party addon
*php eecli.php make:helper "my_addon" --type="third-party"
 * 
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
 * Command to generate helper folder for third-party addons
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
    public $usage = 'php eecli.php make:helper "my_addon"';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'       => 'command_make_helper_option_addon',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_make_helper_lets_build_helper');

        $this->data['addon_name'] = $this->getAddonName();
       
        $this->info('command_make_helper_lets_build');

        $this->build();

        $this->info('command_make_helper_created_successfully');
    }

    private function build()
    {
        try {
            // Build the helper using the HelperGenerator
            $helper = ee('HelperGenerator', $this->data);
            $helper->build();
        } catch (\Exception $e) {
            $this->fail(addslashes($e->getMessage()));
        }
    }

    private function getAddonName()
    {
        // This is the addon name passed to the CLI
        $addon_name = $this->getFirstUnnamedArgument();

        // If no addon name was passed, ask for a name
        if (is_null($addon_name)) {
            $addon_name = $this->ask('command_make_helper_what_is_addon_name');
        }

        // Lets filter the name to only allow alphanumerics, "-", "_" and spaces
        $addon_name = preg_replace("/[^A-Za-z0-9 \-_]/", '', $addon_name);

        if (empty(trim($addon_name))) {
            $this->fail(lang('command_make_helper_addon_name_required'));
        }

        return $addon_name;
    }

} 