<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Cli\Generator\Services\ModelGeneratorService;

/**
 * Command to clear selected caches
 */
class CommandMakeModel extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Model Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:model';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:model MyAwesomeModel --addon=my_existing_addon';

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
        $this->info('command_make_model_lets_build_model');

        $this->data['name'] = $this->getName();
        $this->data['addon'] = $this->getAddon();

        $this->info('command_make_model_lets_build');

        $this->build();

        $this->info('command_make_model_created_successfully');
    }

    protected function build()
    {
        $service = new ModelGeneratorService($this->data);

        return $service->build();
    }

    private function getName()
    {
        return isset($this->arguments[0]) ? $this->arguments[0] : $this->ask("command_make_model_ask_model_name");
    }

    private function getAddon()
    {
        return $this->option('--addon') ? $this->option('--addon') : $this->ask("command_make_model_ask_addon");
    }
}
