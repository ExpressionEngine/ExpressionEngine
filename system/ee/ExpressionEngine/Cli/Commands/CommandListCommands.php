<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

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
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php list';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'simple,s'   => 'command_list_option_simple',
    ];

    /**
     * Sets the tablemask for the list table
     * @var boolean
     */
    public $tableMask = "|%-20.20s |%-60.60s |";

    /**
     * The number of characters for the Command name column
     * @var int
     */
    public $command_col_width = 20;

    /**
     * The number of characters for the Description column
     * @var int
     */
    public $desc_col_width = 60;

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $available = $this->availableCommands();

        // Simple flag just lists the commands without the description
        if($this->option('--simple')) {
            $this->write(implode("\n", array_keys($available)));
            return;
        }

        $this->generateMask($available);

        $total_dashes = $this->command_col_width + $this->desc_col_width + 5;
        $this->info('<<bold>>' . lang('command_list_all_available_commands'));
        $this->info('command_list_run_with_help');
        $this->info(str_repeat('-', $total_dashes));
        $this->write($this->fillTableLine(lang('command_list_command_header'), lang('command_list_description_header')));
        $this->info(str_repeat('-', $total_dashes));

        // Build a headers array as we list
        $headers = array();

        foreach ($available as $availableCommand => $availableClass) {
            if (!is_string($availableClass)) {
                continue;
            }
            $availableHydratedClass = new $availableClass();

            // Get the command header
            $commandSegments = explode(':', $availableCommand);
            $header = $commandSegments[0];

            // If this is a new header, we print a new line and then print the command header
            if (!in_array($header, $headers)) {
                $headers[] = $header;
                $this->write($this->fillTableLine());
                $this->printTableCommandHeader($header);
            }

            $this->printCommand($availableCommand, $availableHydratedClass->description);
        }

        $this->info(str_repeat('-', $total_dashes));
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

    public function changeColumnColor($line, $color, $column = 1)
    {
        $lineArray = explode('|', $line);
        $lineArray[$column] = "<<{$color}>>{$lineArray[$column]}<<reset>>";

        return implode('|', $lineArray);
    }

    public function fillTableLine($column1 = '', $column2 = '')
    {
        return sprintf($this->tableMask, " {$column1} ", " {$column2}");
    }

    protected function generateMask(array $available)
    {
        foreach ($available as $availableCommand => $availableClass) {
            $length = strlen($availableCommand);
            if ($length >= $this->command_col_width) {
                $this->command_col_width = $length;
            }

            if (!is_string($availableClass)) {
                continue;
            }
            $availableHydratedClass = new $availableClass();
            $length = strlen((string) $availableHydratedClass->description);
            if ($length >= $this->desc_col_width) {
                $this->desc_col_width = $length;
            }
        }

        $this->command_col_width += 3;
        $this->desc_col_width += 3;

        $this->command_col_width = ($this->command_col_width > 40 ? 40 : $this->command_col_width);
        $this->desc_col_width = ($this->desc_col_width > 100 ? 100 : $this->desc_col_width);

        $this->tableMask = "|%-" . $this->command_col_width . "." . $this->command_col_width . "s |%-" . $this->desc_col_width . "." . $this->desc_col_width . "s |";
    }
}
