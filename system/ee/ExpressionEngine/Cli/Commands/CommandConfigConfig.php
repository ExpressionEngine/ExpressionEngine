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
 * Command to update config values
 */
class CommandConfigConfig extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Update Config Values';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'config:config';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php config:config -c is_system_on -v n';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'config-variable,c:'    => 'command_config_config_option_config_variable',
        'value,v:'               => 'command_config_config_option_value',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        // Gather all the config variable information
        $this->data['config-variable'] = $this->getOptionOrAsk('--config-variable', 'command_config_config_ask_config_variable', '', true);
        $this->data['value'] = $this->getOptionOrAsk('--value', 'command_config_config_ask_config_value', '', true);

        $this->info('command_config_config_updating_config_variable');

        ee()->cache->save('cli/update-config-settings', true);

        // Set config item
        $config = ee('Config')->getFile();
        $config->set($this->data['config-variable'], $this->data['value'], true);

        $this->info('command_config_config_config_value_saved');
    }
}
