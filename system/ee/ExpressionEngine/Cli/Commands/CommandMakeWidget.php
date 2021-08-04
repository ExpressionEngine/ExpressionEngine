<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Service\Generator\WidgetGenerator;

/**
 * Command to clear selected caches
 */
class CommandMakeWidget extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Widget Generator';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'make:widget';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php make:widget MyNewWidget --addon=my_existing_addon';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'addon,a:'       => 'command_make_widget_option_addon',
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
        $this->info('command_make_widget_lets_build_widget');

        // Gather alll the widget information
        $this->data['name'] =  $this->getFirstUnnamedArgument("command_make_widget_ask_widget_name", null, true);
        $this->data['addon'] = $this->getOptionOrAsk('--addon', "command_make_widget_ask_addon", null, true);

        $this->info('command_make_widget_lets_build');

        try {
            // Build the widget
            $service = ee('WidgetGenerator', $this->data);
            $service->build();
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->info('command_make_widget_created_successfully');
    }
}
