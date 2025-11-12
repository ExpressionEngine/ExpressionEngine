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
 * Command to update env values
 */
class CommandConfigEnv extends Cli
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
    public $signature = 'config:env';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php config:env -e IS_SYSTEM_ON -v y';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'env-variable,e:'   => 'command_config_env_option_config_variable',
        'value,v:'          => 'command_config_env_option_value',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        // Gather all the config variable information
        $this->data['env-variable'] = $this->getOptionOrAsk('--env-variable', 'command_config_env_ask_config_variable', '', true);
        $this->data['value'] = $this->getOptionOrAsk('--value', 'command_config_env_ask_config_value', '', true);

        $this->info('command_config_env_updating_config_variable');

        // Set Env item
        $this->setEnv($this->data['env-variable'], $this->data['value']);

        $this->info('command_config_env_config_value_saved');
    }

    // @TODO:
    // Move all of the below to an ENV service

    private function setEnv($key, $value)
    {
        if (! $this->envFileExists()) {
            $this->createEnvFile();
        }

        if ($this->keyIsInEnv($key)) {
            $this->overwriteExistingValue($key, $value);
        } else {
            $this->writeNewEnvValue($key, $value);
        }
    }

    private function getEnvFilePath()
    {
        return SYSPATH . '../.env.php';
    }

    private function envFileExists()
    {
        return ee('Filesystem')->exists($this->getEnvFilePath());
    }

    private function keyIsInEnv($key)
    {
        return (strpos($this->getEnvFileContents(), $key) !== false);
    }

    private function writeNewEnvValue($key, $value)
    {
        $envContents = $this->getEnvFileContents()
            . "\n" . $key . '=' . $value;

        $this->writeToEnv($envContents);
    }

    private function overwriteExistingValue($key, $value)
    {
        $envContents = str_replace(
            $key . '=' . $this->env($key),
            $key . '=' . $value,
            $this->getEnvFileContents()
        );

        $this->writeToEnv($envContents);
    }

    private function env($key)
    {
        return $_ENV[$key] ?? '';
    }

    private function createEnvFile()
    {
        ee('Filesystem')->write($this->getEnvFilePath(), '', true);
    }

    private function getEnvFileContents()
    {
        return ee('Filesystem')->read($this->getEnvFilePath());
    }

    private function writeToEnv($envContents)
    {
        return ee('Filesystem')->write($this->getEnvFilePath(), trim($envContents), true);
    }
}
