<?php

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;

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
    public $usage = 'php eecli.php list';

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
     * Sets the tablemask for the list table
     * @var boolean
     */
    public $tableMask = "|%-20.20s |%-60.60s |";

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $available = $this->availableCommands();

        $this->info('<<bold>>All Available Commands:');
        $this->info('Run a command with --help for more information');
        $this->info('-------------------------------------------------------------------------------------');
        $this->write($this->fillTableLine('Command', 'Description'));
        $this->info('-------------------------------------------------------------------------------------');

        // Build a headers array as we list
        $headers = array();

        foreach ($available as $availableCommand => $availableClass) {
            $availableHydratedClass = new $availableClass();

            // Get the command header
            $header = (explode(':', $availableCommand))[0];

            // If this is a new header, we print a new line and then print the command header
            if (! in_array($header, $headers)) {
                $headers[] = $header;
                $this->write($this->fillTableLine());
                $this->printTableCommandHeader($header);
            }

            $this->printCommand($availableCommand, $availableHydratedClass->description);
        }

        $this->info('-------------------------------------------------------------------------------------');
    }

    public function printTableCommandHeader($header)
    {
        $headerLine = $this->fillTableLine($header);
        $headerLine = $this->changeColumnColor($headerLine, "green", 1);
        $this->write($headerLine);
    }

    public function printCommand($command, $description)
    {
        $this->write($this->fillTableLine($command, $description));
    }

    public function changeColumnColor($line, $color, $column=1)
    {
        $lineArray = explode('|', $line);
        $lineArray[$column] = "<<{$color}>>{$lineArray[$column]}<<reset>>";

        return implode('|', $lineArray);
    }

    public function fillTableLine($column1='', $column2='')
    {
        return sprintf($this->tableMask, " {$column1} ", " {$column2}");
    }
}
