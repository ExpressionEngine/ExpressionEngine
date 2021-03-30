<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Cli\Generator\Services\CommandGeneratorService;

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
        'addon,a:' => 'Folder for third-party add-on you want to add model to',
    ];

    /**
     * Command can run without EE Core
     * @var boolean
     */
    public $standalone = true;

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

        $this->data['name'] = $this->getName();
        $this->data['addon'] = $this->getAddon();
        $this->data['description'] = $this->ask('command_make_command_ask_description');
        $this->data['signature'] = $this->ask('command_make_command_ask_signature');

        $this->info('command_make_command_lets_build');

        $this->build();

        $this->info('command_make_command_created_successfully');
    }

    protected function build()
    {
        $service = new CommandGeneratorService($this->data);

        return $service->build();
    }

    private function getName()
    {
        return isset($this->arguments[0]) ? $this->arguments[0] : $this->ask("command_make_command_ask_command_name");
    }

    private function getAddon()
    {
        return $this->option('--addon') ? $this->option('--addon') : $this->ask("command_make_command_ask_addon");
    }
}
