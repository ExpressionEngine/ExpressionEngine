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
 * Reindex content
 */
class CommandSyncReindex extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Reindex Content';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'sync:reindex';

    /**
     * description of command
     * @var string
     */
    public $description = 'command_reindex_description';

    /**
     * summary of command
     * @var string
     */
    public $summary = 'command_reindex_summary';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php sync:reindex';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site_id,s:'        => 'command_reindex_option_site_id',
    ];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        // Load the session library (necessary for some field types)
        ee()->load->library('session');

        ee()->lang->loadfile('utilities');

        $this->info('search_reindexed_started');
        $service = ee('Channel/Reindex');
        $service->site_id = $this->option('--site_id', 'all');
        $service->initialize();

        $steps = $service->getProgressSteps();
        for ($progressStep = 0; $progressStep < $steps; $progressStep++) {
            $service->process($progressStep);
            echo '.';
        }

        $this->write('');
        $this->info(sprintf(lang('search_reindexed_completed'), number_format($progressStep)));
    }
}
