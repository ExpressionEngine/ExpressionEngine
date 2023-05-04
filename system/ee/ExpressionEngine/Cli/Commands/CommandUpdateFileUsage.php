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
 * Update file usage
 */
class CommandUpdateFileUsage extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Update File Usage';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'update:file-usage';

    /**
     * description of command
     * @var string
     */
    public $description = 'command_update_file_usage_description';

    /**
     * summary of command
     * @var string
     */
    public $summary = 'command_update_file_usage_summary';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php update:file-usage';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $this->info('command_update_file_usage');
        $fileUsage = ee('FileUsage');

        foreach($fileUsage->getProgressSteps() as $progressStep) {
            $fileUsage->process($progressStep);
            echo '.';
        }

        $this->write('');
        $this->info('command_update_file_usage_done');
    }
}
