<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli;

use ExpressionEngine\Cli\CliFactory;
use ExpressionEngine\Cli\Help;
use ExpressionEngine\Cli\Status;
use ExpressionEngine\Cli\Context\OptionFactory;
use ExpressionEngine\Library\Filesystem\Filesystem;

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
     * Command arguments
     * @var [type]
     */
    public $arguments;

    /**
     * name of command
     * @var string
     */
    public $name;

    /**
     * signature of command
     * @var string
     */
    public $signature;

    /**
     * How to use command
     * @var string
     */
    public $usage;

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions;

    /**
     * Summary of the command
     * @var string
     */
    public $summary;

    /**
     * Description of the command
     * @var string
     */
    public $description;

    /**
     * Command options
     * @var object
     */
    public $options;

    public $filesystem;

    /**
     * list of commands available from EE
     * @var array
     */
    private $internalCommands = [
        // Addons
        'addons:install' => Commands\CommandAddonsInstall::class,
        'addons:list' => Commands\CommandAddonsList::class,
        'addons:uninstall' => Commands\CommandAddonsUninstall::class,
        'addons:update' => Commands\CommandAddonsUpdate::class,

        // Backup
        'backup:database' => Commands\CommandBackupDatabase::class,

        // Cache
        'cache:clear' => Commands\CommandClearCaches::class,

        // Config
        'config:config' => Commands\CommandConfigConfig::class,
        'config:env' => Commands\CommandConfigEnv::class,

        // List
        'list' => Commands\CommandListCommands::class,

        // Make
        'make:action' => Commands\CommandMakeAction::class,
        'make:addon' => Commands\CommandMakeAddon::class,
        'make:command' => Commands\CommandMakeCommand::class,
        'make:cp-route' => Commands\CommandMakeCpRoute::class,
        'make:extension-hook' => Commands\CommandMakeExtensionHook::class,
        'make:fieldtype' => Commands\CommandMakeFieldtype::class,
        'make:jump' => Commands\CommandMakeJump::class,
        'make:migration' => Commands\CommandMakeMigration::class,
        'make:model' => Commands\CommandMakeModel::class,
        'make:prolet' => Commands\CommandMakeProlet::class,
        'make:sidebar' => Commands\CommandMakeSidebar::class,
        'make:template-tag' => Commands\CommandMakeTemplateTag::class,
        'make:widget' => Commands\CommandMakeWidget::class,

        // Migrate
        'migrate' => Commands\CommandMigrate::class,
        'migrate:addon' => Commands\CommandMigrateAddon::class,
        'migrate:all' => Commands\CommandMigrateAll::class,
        'migrate:core' => Commands\CommandMigrateCore::class,
        'migrate:reset' => Commands\CommandMigrateReset::class,
        'migrate:rollback' => Commands\CommandMigrateRollback::class,

        // Sync
        'sync:conditional-fields' => Commands\CommandSyncConditionalFieldLogic::class,
        'sync:file-usage' => Commands\CommandSyncFileUsage::class,
        'sync:reindex' => Commands\CommandSyncReindex::class,
        'sync:upload-directory' => Commands\CommandSyncUploadDirectory::class,

        // Update
        'update' => Commands\CommandUpdate::class,
        'update:prepare' => Commands\CommandUpdatePrepare::class,
        'update:run-hook' => Commands\CommandUpdateRunHook::class,
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

    /**
     * config setting for if the CLI is enabled
     * @var string
     */
    protected $cliEnabled;

    public function __construct()
    {
        // Load the language helper and the DB
        ee()->load->helper('language_helper');
        ee()->lang->loadfile('cli');
        ee()->load->database();

        //  Is the CLI disabled in the settings?
        $this->cliEnabled = ee()->config->item('cli_enabled') != false ? bool_config_item('cli_enabled') : true;

        // Initialize the object
        $factory = new CliFactory();
        $this->filesystem = ee('Filesystem');

        $this->command = $factory->newContext($GLOBALS);
        $this->output = $factory->newStdio();
        $this->input = $factory->newStdio();
        $this->argv = $this->command->argv->get();

        // If the cli is enabled, we will fail here, before anything has really been done
        if (!$this->cliEnabled) {
            $this->fail('cli_error_cli_disabled');
        }

        if (!isset($this->argv[1])) {
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
        // -------------------------------------------
        // 'cli_boot' hook.
        //  - Runs on every CLI request
        //  - Intercept CLI call and make it do extra stuff
        //
        if (ee()->extensions->active_hook('cli_boot') === true) {
            ee()->extensions->call('cli_boot', $this);
            if (ee()->extensions->end_script === true) {
                $this->complete('');
            }
        }

        $this->availableCommands = $this->availableCommands();

        // Check if command exists
        // If not, return
        if (!$this->commandExists()) {
            return $this->fail('cli_error_command_not_found');
        }

        $commandClass = $this->getCommand($this->commandCalled);

        if (!class_exists($commandClass)) {
            return $this->fail('cli_error_command_not_found');
        }

        // Try and initialize command
        $command = new $commandClass();

        $command->loadOptions();

        if ($command->option('-h', false)) {
            return $command->help();
        }

        // -------------------------------------------
        // 'cli_before_handle' hook.
        //  - Runs on every CLI request
        //  - Intercept CLI call and make it do extra stuff
        //
        if (ee()->extensions->active_hook('cli_before_handle') === true) {
            $command = ee()->extensions->call('cli_before_handle', $this, $command, $commandClass);
            if (ee()->extensions->end_script === true) {
                $this->complete('');
            }
        }
        // -------------------------------------------

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

        // Echo out just the simple options for the command
        if($this->option('--options')) {
            $this->write($help->getHelpOptionsSimple());
            exit();
        }

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
            if (!is_array($messages)) {
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
     * Prints a text based table given the headers and data
     * @param  array $headers
     * @param  array $data
     * @return null
     */
    public function table(array $headers, array $data)
    {
        // We need headers in order to print a table
        if(empty($headers)) {
            return;
        }

        // Determine the width of each column based on the headers and data
        $widths = [];
        foreach ($headers as $header) {
            $widths[] = strlen($header);
        }

        // Loop through the data and determine the max width of each column
        foreach ($data as $row) {
            $count = 0;
            foreach ($row as $value) {
                $widths[$count] = max($widths[$count], strlen($value));
                $count++;
            }
        }

        // Create a format string for sprintf based on the widths
        $format = '';
        foreach ($widths as $k => $width) {
            $format .= '%-' . $width . 's | ';

            // if last row by key
            if($k === array_key_last($widths)) {
                $format = rtrim($format, '| ');
            }
        }

        // Output the headers
        $this->write(vsprintf($format, $headers));

        // Add a line of dashes under the headers with | between each column
        $dash_str = '';
        foreach ($widths as $k => $width) {
            $length = $width + 2;
            if($k === array_key_first($widths)) {
                $length -= 1;
            }

            $dash_str .= str_repeat('-', $length) . '|';

            // if last row by key
            if($k === array_key_last($widths)) {
                $dash_str = rtrim($dash_str, '|');
            }
        }
        $this->write($dash_str);

        // Output the data with the format string
        foreach ($data as $row) {
            $this->write(vsprintf($format, $row));
        }

        // if the data is empty, print no results
        if(empty($data)) {
            $this->write(lang('cli_table_no_results'));
        }
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

        $result = (string) $this->input->in();

        return $result ? addslashes($result) : $default;
    }

    public function getFirstUnnamedArgument($question = null, $default = null, $required = false)
    {
        $argument = isset($this->arguments[0]) ? $this->arguments[0] : null;

        // If the first argument is an option, we have nothing so return null
        if (is_string($argument) && substr($argument, 0, 1) === '-') {
            $argument = null;
        }

        if (empty(trim((string) $argument)) && !is_null($question)) {
            $argument = $this->ask($question, $default);
        }

        // Name is a required field
        if ($required && empty(trim((string) $argument))) {
            $this->fail(lang('cli_error_is_required'));
        }

        return $argument;
    }

    public function getOptionOrAsk($option, $askText, $default = '', $required = false)
    {
        // Get option if it was passed
        if ($this->option($option)) {
            return $this->option($option);
        }

        // Get the answer by asking
        $answer = (string) $this->ask($askText, $default);

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
        if (!is_bool($answer)) {
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
        if (empty($this->commandOptions)) {
            $this->commandOptions = [];
        }

        // This parses the command options through the lang file
        $this->parseCommandOptions();

        $commandOptions = array_merge(
            $this->commandOptions,
            [
                'help,h' => 'cli_option_help',
                'options' => 'cli_option_help_options'
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
        if (empty($this->options)) {
            return $default;
        }

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
        if (!empty($this->signature)) {
            $simplifiedSignature = str_replace([':', '-'], '_', $this->signature);
            $this->description = !empty($this->description) ? lang($this->description) : lang('command_' . $simplifiedSignature . '_description');
            $this->summary = !empty($this->summary) ? lang($this->summary) : lang('command_' . $simplifiedSignature . '_summary');
        }
    }

    public function getOptionOrAskAddon($option, $askText = null, $default = 'first', $required = true, $showAddons = 'all')
    {
        $addonList = array_keys($this->getAddonList($showAddons));

        if (empty($addonList)) {
            $this->fail('cli_no_addons');
        }

        // Get option if it was passed
        if ($this->option($option)) {
            $addon = $this->option($option);
            $this->validateAddonName($addon, $addonList);

            return $addon;
        }

        if (is_null($askText)) {
            $askText = 'Select addon';
        }

        // Get the answer by asking
        $answer = $this->askAddon(lang($askText), $addonList, $default);

        // If it was a required field and no answer was passed, fail
        if ($required && empty(trim($answer))) {
            $this->fail(lang('cli_error_is_required_field') . $option);
        }

        return $answer;
    }

    protected function askAddon($askText, $addonList, $default = '')
    {
        $askText = $askText . " \n - " . implode("\n - ", $addonList) . "\n: ";

        // If the default is "first", then return the first element in the array
        if ($default === 'first' && !empty($addonList)) {
            // Get the first array element
            $default = reset($addonList);
        }

        // Get the answer by asking
        $answer = $this->ask($askText, $default);

        $this->validateAddonName($answer, $addonList);

        return $answer;
    }

    protected function validateAddonName($addon, $addonList)
    {
        if (!in_array($addon, $addonList)) {
            $this->fail($addon . ' is not a valid addon');
        }

        return true;
    }

    protected function getAddonList($showAddons = 'all')
    {
        $list = [];
        $addons = ee('Addon')->all();

        foreach ($addons as $name => $info) {
            //if (strpos($info->getPath(), PATH_THIRD) !== 0) {
            if ($info->get('built_in')) {
                continue;
            }

            $addon = [
                'name' => $info->getName(),
                'shortname' => $name,
                'version' => $info->getVersion(),
                'installed' => $info->isInstalled() ? 'yes' : 'no',
            ];

            switch ($showAddons) {
                case 'installed':
                    if ($info->isInstalled()) {
                        $list[$name] = $addon;
                    }

                    break;
                case 'uninstalled':
                    if (!$info->isInstalled()) {
                        $list[$name] = $addon;
                    }

                    break;
                case 'update':
                    if ($info->hasUpdate()) {
                        $list[$name] = $addon;
                    }

                    break;
                case 'all':
                default:
                    $list[$name] = $addon;

                    break;
            }
        }

        return $list;
    }
}
