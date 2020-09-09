<?php

namespace EllisLab\ExpressionEngine\Cli\Commands;

use EllisLab\ExpressionEngine\Cli\Cli;

/**
 * List all availabe commands
 */
class CommandListCommands extends Cli
{

    /**
     * name of command
     * @var string
     */
    public $name = 'List Commands';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'list';

    /**
     * Public description of command
     * @var string
     */
    public $description = 'Lists all available commands';

    /**
     * Summary of command functionality
     * @var [type]
     */
    public $summary = 'This gives a full listing of all commands.';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli list';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [];

    /**
     * Command can run without EE Core
     * @var boolean
     */
    public $standalone = true;

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $available = $this->availableCommands();

        $this->info('<<bold>>Available Commands');
        $this->info('');

        $mask = "|%-20.20s |%-60.60s |\n";

        printf($mask, ' Command', ' Description');
        $this->info('-------------------------------------------------------------------------------------');

        foreach ($available as $availableCommand => $availableClass) {
            $availableHydratedClass = new $availableClass;

            printf($mask, " {$availableCommand} ", " {$availableHydratedClass->description}");
        }
    }
}
