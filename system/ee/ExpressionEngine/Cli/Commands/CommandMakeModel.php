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
     * Public description of command
     * @var string
     */
    public $description = 'Generates an EE model for an existing third-party addon';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary;

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli make:model MyAwesomeModel --addon=my_existing_addon';

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
    public $summaryText = 'This interactively generates an EE model for an existing third-party addon';

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
        $this->info('Let\'s build your model!');

        $this->data['name'] = $this->getName();
        $this->data['addon'] = $this->getAddon();

        $this->info('Let\'s build!');

        $this->build();

        $this->info('Your model has been created successfully!');
    }

    protected function build()
    {
        $service = new ModelGeneratorService($this->data);

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
