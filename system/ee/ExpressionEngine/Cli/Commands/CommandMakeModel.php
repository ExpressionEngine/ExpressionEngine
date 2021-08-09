<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;

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
        'addon,a:' => 'command_make_model_option_addon',
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

        $this->data['name'] =  $this->getFirstUnnamedArgument("command_make_model_ask_model_name");
        $this->data['addon'] = $this->getOptionOrAsk('--addon', "command_make_model_ask_addon");

        $this->info('command_make_model_lets_build');

        $service = ee('ModelGenerator', $this->data);

        $service->build();

        $this->info('command_make_model_created_successfully');
    }
}
