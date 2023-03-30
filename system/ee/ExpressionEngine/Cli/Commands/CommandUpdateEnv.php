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
class CommandUpdateEnv extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Update Env Values';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'update:env';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php update:env -e IS_SYSTEM_ON -v y';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'env-variable,e:'   => 'command_update_env_option_config_variable',
        'value,v:'          => 'command_update_env_option_value',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        // Gather all the config variable information
        $this->data['env-variable'] = $this->getOptionOrAsk('--env-variable', 'command_update_env_ask_config_variable', '', true);
        $this->data['value'] = $this->getOptionOrAsk('--value', 'command_update_env_ask_config_variable', '', true);

        $this->info('command_update_env_updating_config_variable');

        // Set Env item
        $this->setEnv($this->data['env-variable'], $this->data['value']);

        $this->info('command_update_env_config_value_saved');
    }

}
