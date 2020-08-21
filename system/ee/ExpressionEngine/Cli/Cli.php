<?php

namespace ExpressionEngine\Cli;

use ExpressionEngine\Cli\CliFactory;
use ExpressionEngine\Cli\Help;
use ExpressionEngine\Cli\Status;
use ExpressionEngine\Cli\Context\OptionFactory;

class Cli {

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
	 * full list of commands available
	 * @var array
	 */
	public $availableCommands;

	/**
	 * first party add-on directory
	 * @var string
	 */
	protected $nativeAddonDirectory;

	/**
	 * third party add-on directory
	 * @var string
	 */
	protected $userAddonDirectory;

	/**
	 * command called on CLI
	 * @var string
	 */
	protected $commandCalled;

	public function __construct()
	{

		// Initialize the object
		$factory = new CliFactory;

		$this->command = $factory->newContext($GLOBALS);
		$this->output = $factory->newStdio();
		$this->input = $factory->newStdio();
		$this->argv = $this->command->argv->get();

		if( ! isset($this->argv[1]) ) {

			$this->fail('No command given');

		}

		// Get command called
		$this->commandCalled = $this->argv[1];
		$this->availableCommands = $this->availableCommands();
		$this->nativeAddonDirectory = SYSPATH . 'ee/ExpressionEngine/Addons';
		$this->userAddonDirectory = SYSPATH . 'user/addons';

	}

	public function process()
	{

		// Check if command exists
		// If not, return
		if( ! $this->commandExists() ) {

			return $this->fail('Command not found');

		}

		$commandClass = $this->getCommand($this->commandCalled);

		if( ! class_exists($commandClass) ) {
			return $this->fail('Command not found');
		}

		// Try and initialize command
		$command = new $commandClass;

		$command->loadOptions();

		if($command->option('-h', false)) {

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

		$help = new Help(new OptionFactory);

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
	public function fail($message = null)
	{

		if( $message ) {
			$this->output->errln("<<red>>{$message}<<reset>>");
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

		if( $message ) {
			$this->output->outln("<<green>>{$message}<<reset>>");
		}

		exit(Status::SUCCESS);

	}

	/**
	 * write info to the console
	 * @param  string
	 * @return null
	 */
	public function info($message = null)
	{

		$this->output->outln("<<green>>{$message}<<reset>>");

	}

	/**
	 * write error info to the console
	 * @param  string
	 * @return null
	 */
	public function error($message = null)
	{

		$this->output->outln("<<red>>{$message}<<reset>>");

	}

	/**
	 * Ask question and get input
	 * @param  string $question
	 * @return mixed
	 */
	public function ask($question)
	{

		$this->output->out($question . ' ');

		return $this->input->in();

	}

	/**
	 * Ask question and get boolean answer
	 * @param  string $question
	 * @return bool
	 */
	public function confirm($question, $default = false)
	{

		$choices = '(yes/no)';

		$defaultText = $default ? 'yes' : 'no';

		$defaultChoice = "<<white>>[<<yellow>>{$defaultText}<<white>>]<<reset>>";

		$this->output->outln("<<green>>{$question} {$choices} {$defaultChoice}");

		$answer = $this->input->in();

		if (is_bool($answer)) {
			return $answer;
		}

		$confirmationRegex = '/^y/i';

		$answerIsTrue = (bool) preg_match($confirmationRegex, $answer);

		if ($default === false) {

		    return $answer && $answerIsTrue;

		}

		return '' === $answer || $answerIsTrue;

	}

	/**
	 * check if command is defined among the addons
	 * @param  string $command [defaults to whatever command was called]
	 * @return bool
	 */
	protected function commandExists($command = null)
	{

		$commandToParse = $command ?? $this->commandCalled;

		return array_key_exists($commandToParse, $this->availableCommands);

	}

	/**
	 * get command class
	 * @param  string $command [defaults to whatever command was called]
	 * @return mixed
	 */
	protected function getCommand($command = null)
	{

		$commandToParse = $command ?? $this->commandCalled;

		return $this->availableCommands[$commandToParse];

	}

	/**
	 * gets all available commands as defined by addons
	 * @return array
	 */
	protected function availableCommands()
	{

		$commands = [];

		$providers = ee('App')->getProviders();

		foreach ($providers as $providerKey => $provider) {

			if($provider->get('commands')) {

				$commands = array_merge($commands, $provider->get('commands'));

			}

		}

		return $commands;

	}

	/**
	 * loads specific command options
	 * @return null
	 */
	protected function loadOptions()
	{

		if( ! isset($this->commandOptions) ) {
			return [];
		}

		$commandOptions = array_merge(
			$this->commandOptions,
			[
				'help,h'	=> 'See help'
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

}