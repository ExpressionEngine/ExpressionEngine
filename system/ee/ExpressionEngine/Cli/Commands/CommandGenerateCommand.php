<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Cli\Generator\Services\CommandGeneratorService;

/**
 * Command to clear selected caches
 */
class CommandGenerateCommand extends Cli
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
    public $signature = 'generate:command';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Generates an EE command for an existing third-party addon';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary;

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli generate:command MakeMagic --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:' => 'Folder for third-party add-on you want to add model to',
    ];

    /**
     * list of available caches
     * @var array
     */
    public $summaryText = 'This interactively generates an EE command for an existing third-party addon';

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
        $this->info('Let\'s build your command!');

        $this->data['name'] = $this->getName();
        $this->data['addon'] = $this->getAddon();
        $this->data['description'] = $this->ask('Command description?');
        $this->data['signature'] = $this->ask('Command signature? (i.e. make:magic');

        $this->info('Let\'s build!');

        $this->build();

        $this->info('Your model has been created successfully!');
    }

    protected function build()
    {
        $service = new CommandGeneratorService($this->data);

        return $service->build();
    }

    private function getName()
    {
        return isset($this->arguments[0]) ? $this->arguments[0] : $this->ask("Model name?");
    }

    private function getAddon()
    {
        return $this->option('--addon') ? $this->option('--addon') : $this->ask("What addon do you want to add this to?");
    }
}
