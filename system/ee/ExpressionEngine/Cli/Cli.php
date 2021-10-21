<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli;

use ExpressionEngine\Cli\CliFactory;
use ExpressionEngine\Cli\Help;
use ExpressionEngine\Cli\Status;
use ExpressionEngine\Cli\Context\OptionFactory;

class Cli
{
    /**
     * Primary CLI object
     * @var \ExpressionEngine\Cli\Context
     */
    public $command;

    /**
     * stdio for output
     * @var ExpressionEngine\Cli\Stdio
     */
    public $output;

    /**
     * stdio for Input
     * @var ExpressionEngine\Cli\Stdio
     */
    public $input;

    /**
     * command line argv
     * @var \ExpressionEngine\Cli\Context\Argv
     */
    public $argv;

    /**
     * [$arguments description]
     * @var [type]
     */
    public $arguments;

    /**
     * list of commands available from EE
     * @var array
     */
    private $internalCommands = [
        'list' => Commands\CommandListCommands::class,
        'update' => Commands\CommandUpdate::class,
        'update:prepare' => Commands\CommandUpdatePrepare::class,
        'update:run-hook' => Commands\CommandUpdateRunHook::class,
        'make:addon' => Commands\CommandMakeAddon::class,
        'make:command' => Commands\CommandMakeCommand::class,
        'make:migration' => Commands\CommandMakeMigration::class,
        'make:model' => Commands\CommandMakeModel::class,
        'make:prolet' => Commands\CommandMakeProlet::class,
        'make:widget' => Commands\CommandMakeWidget::class,
        'migrate' => Commands\CommandMigrate::class,
        'migrate:all' => Commands\CommandMigrateAll::class,
        'migrate:addon' => Commands\CommandMigrateAddon::class,
        'migrate:core' => Commands\CommandMigrateCore::class,
        'migrate:reset' => Commands\CommandMigrateReset::class,
        'migrate:rollback' => Commands\CommandMigrateRollback::class,
        'cache:clear' => Commands\CommandClearCaches::class,
    ];

    /**
     * full list of commands available
     * @var array
     */
    public $availableCommands;

    /**
     * command called on CLI
     * @var string
     */
    protected $commandCalled;

    public function __construct()
    {
        // Load the language helper and the DB
        ee()->load->helper('language_helper');
        ee()->lang->loadfile('cli');
        ee()->load->database();

        // Initialize the object
        $factory = new CliFactory();

        $this->command = $factory->newContext($GLOBALS);
        $this->output = $factory->newStdio();
        $this->input = $factory->newStdio();
        $this->argv = $this->command->argv->get();

        if (! isset($this->argv[1])) {
            $this->fail('cli_error_no_command_given');
        }

        $this->arguments = array_slice($this->command->argv->get(), 2);

        // Get command called
        $this->commandCalled = $this->argv[1];

        // This will set the desc and summary if theyre set in the lang file
        $this->setDescriptionAndSummaryFromLang();
    }

    /**
     * core CLI command processing
     * @return void
     */
    public function process()
    {
        $this->availableCommands = $this->availableCommands();

        // Check if command exists
        // If not, return
        if (! $this->commandExists()) {
            return $this->fail('cli_error_command_not_found');
        }

        $commandClass = $this->getCommand($this->commandCalled);

        if (! class_exists($commandClass)) {
            return $this->fail('cli_error_command_not_found');
        }

        // Try and initialize command
        $command = new $commandClass();

        $command->loadOptions();

        if ($command->option('-h', false)) {
            return $command->help();
        }

        // Run command
        $message = $command->handle();

        // Return output and end
        $this->complete($message);
    }

    /**
     * get command's help information
     * @return null
     */
    public function help()
    {
        $help = new Help(new OptionFactory());

        $help->setSummary($this->summary)
            ->setDescr($this->description)
            ->setUsage($this->usage)
            ->setOptions($this->commandOptions);

        $this->output->outln($help->getHelp($this->name));

        exit();
    }

    /**
     * fail the command and die
     * @param  string $message
     * @return null
     */
    public function fail($messages = null)
    {
        if ($messages) {
            if (! is_array($messages)) {
                $messages = [$messages];
            }

            foreach ($messages as $message) {
                $this->output->errln("<<red>>" . lang($message) . "<<reset>>");
            }
        }

        exit(Status::FAILURE);
    }

    /**
     * complete the command and die
     * @param  string $message
     * @return null
     */
    public function complete($message = null)
    {
        if ($message) {
            $this->output->outln("<<green>>" . lang($message) . "<<reset>>");
        }

        exit(Status::SUCCESS);
    }

    /**
     * write info to the console
     * @param  string
     * @return null
     */
    public function write($message = null)
    {
        $this->output->outln(lang($message));
    }

    /**
     * write info to the console
     * @param  string
     * @return null
     */
    public function info($message = null)
    {
        $this->output->outln("<<green>>" . lang($message) . "<<reset>>");
    }

    /**
     * write error info to the console
     * @param  string
     * @return null
     */
    public function error($message = null)
    {
        $this->output->outln("<<red>>" . lang($message) . "<<reset>>");
    }

    /**
     * Ask question and get input
     * @param  string $question
     * @return mixed
     */
    public function ask($question, $default = '')
    {
        $defaultChoice = !empty($default) ? "<<white>>[<<yellow>>{$default}<<white>>]<<reset>>" : '';

        $this->output->out(lang($question) . ' ' . $defaultChoice);

        $result = $this->input->in();

        return $result ? addslashes($result) : $default;
    }

    public function getFirstUnnamedArgument($question = null, $default = null, $required = false)
    {
        $argument = isset($this->arguments[0]) ? $this->arguments[0] : null;

        // If the first argument is an option, we have nothing so return null
        if (substr($argument, 0, 1) === '-') {
            $argument = null;
        }

        if (empty(trim($argument)) && !is_null($question)) {
            $argument = $this->ask($question, $default);
        }

        // Name is a required field
        if ($required && empty(trim($argument))) {
            $this->fail(lang('cli_error_is_required'));
        }

        return $argument;
    }

    public function getOptionOrAsk($option, $askText, $default=null, $required=false)
    {
        // Get option if it was passed
        if ($this->option($option)) {
            return $this->option($option);
        }

        // Get the answer by asking
        $answer = $this->ask($askText, $default);

        // If it was a required field and no answer was passed, fail
        if ($required && empty(trim($answer))) {
            $this->fail(lang('cli_error_is_required_field') . $option);
        }

        return $answer;
    }

    /**
     * Ask question and get boolean answer
     * @param  string $question
     * @param  bool $default
     * @param  array $required array('required' => <true/false>, 'error_message' => 'cli error message - accepts lang key')
     * @return bool
     */
    public function confirm($question, $default = false, array $required = ['required' => false, 'error_message' => 'cli_error_is_required'])
    {
        // Set required defaults if not passed
        $required = array_merge(['required' => false, 'error_message' => 'cli_error_is_required'], $required);

        $choices = '(yes/no)';

        $defaultText = $default ? 'yes' : 'no';

        $defaultChoice = "<<white>>[<<yellow>>{$defaultText}<<white>>]<<reset>>";

        $this->output->outln("<<green>>" . lang($question) . " {$choices} {$defaultChoice}");

        $answer = $this->input->in();

        // If they didnt answer, set answer to the default
        if (empty($answer)) {
            $answer = $default;
        }

        // If not bool, lets convert string to bool
        if (! is_bool($answer)) {
            $answer = get_bool_from_string($answer);
        }

        // If the string didnt convert to bool, it will be null so set to default value
        if (is_null($answer)) {
            $answer = $default;
        }

        // If the field is set to required and the answer is false, fail with message
        if ($required['required'] && !$answer) {
            $this->fail($required['error_message']);
        }

        return $answer;
    }

    /**
     * check if command is defined among the addons
     * @param  string $command [defaults to whatever command was called]
     * @return bool
     */
    protected function commandExists($command = null)
    {
        $commandToParse = $command ? $command : $this->commandCalled;

        if (EE_INSTALLED) {
            return array_key_exists($commandToParse, $this->availableCommands);
        } else {
            $this->fail('cli_error_ee_not_installed');
        }
    }

    /**
     * get command class
     * @param  string $command [defaults to whatever command was called]
     * @return mixed
     */
    protected function getCommand($command = null)
    {
        $commandToParse = $command ? $command : $this->commandCalled;

        return $this->availableCommands[$commandToParse];
    }

    /**
     * gets all available commands as defined by addons
     * @return array
     */
    protected function availableCommands()
    {
        $this->loadInternalCommands();

        $commands = $this->internalCommands;

        if (!isset(ee()->addons)) {
            ee()->load->library('addons');
            ee('App')->setupAddons(SYSPATH . 'ee/ExpressionEngine/Addons/');
        }

        $providers = ee('App')->getProviders();

        foreach ($providers as $providerKey => $provider) {
            if ($provider->get('commands')) {
                $commands = array_merge($commands, $provider->get('commands'));
            }
        }

        // Always return the commands in a sorted list
        asort($commands);

        return $commands;
    }

    /**
     * Parses command options to use lang files
     * @return null
     */
    protected function parseCommandOptions()
    {
        foreach ($this->commandOptions as $k => $commandOption) {
            $this->commandOptions[$k] = lang($commandOption);
        }
    }

    /**
     * loads specific command options
     * @return null
     */
    protected function loadOptions()
    {
        if (! isset($this->commandOptions)) {
            return [];
        }

        // This parses the command options through the lang file
        $this->parseCommandOptions();

        $commandOptions = array_merge(
            $this->commandOptions,
            [
                'help,h' => 'cli_option_help'
            ]
        );

        $this->options = $this->command->getopt(array_keys($commandOptions));

        if ($this->options->hasErrors()) {
            $errors = $this->options->getErrors();

            foreach ($errors as $error) {

                // print error messages to stderr using a Stdio object
                $this->error($error->getMessage());
            }

            $this->fail();
        };
    }

    /**
     * gets specific option with possibility for fallback
     * @param  string $name
     * @param  string $default
     * @return mixed
     */
    protected function option($name, $default = null)
    {
        return $this->options->get($name, $default);
    }

    /**
     * Loads EE Core commands
     * @return void
     */
    private function loadInternalCommands()
    {
        foreach ($this->internalCommands as $key => $value) {
            $obj = new $value();
        }
    }

    /**
     * Loads description and summary from Lang file
     * @return void
     */
    private function setDescriptionAndSummaryFromLang()
    {
        // Automatically load the description and signature from the lang file
        if (isset($this->signature)) {
            $simplifiedSignature = str_replace([':', '-'], '_', $this->signature);
            $this->description = isset($this->description) ? $this->description : lang('command_' . $simplifiedSignature . '_description');
            $this->summary = isset($this->summary) ? $this->summary : lang('command_' . $simplifiedSignature . '_description');
        }
    }
}
