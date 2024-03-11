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
 * Run migrations
 */
class CommandSyncConditionalFieldLogic extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'Sync Conditional Field Logic';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'sync:conditional-fields';

    /**
     * description of command
     * @var string
     */
    public $description = 'command_sync_conditional_fields_description';

    /**
     * summary of command
     * @var string
     */
    public $summary = 'command_sync_conditional_fields_summary';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php sync:conditional-fields';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'channel_id,c:' => 'command_sync_conditional_fields_option_channel_id',
        'verbose,v'     => 'command_sync_conditional_fields_option_verbose',
        'clear,x'       => 'command_sync_conditional_fields_option_clear',
    ];

    // Channel ID used for syncing
    private $channel_id;

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        if ($this->option('--clear')) {
            ee()->db->delete('channel_entry_hidden_fields', ['entry_id != ' => 0]);
            $this->info('command_sync_conditional_fields_cleared_all_hidden_fields');
            exit;
        }

        // Load the session
        ee()->load->library('session');

        $this->info('command_sync_conditional_fields_sync_utility');

        $this->channel_id = $this->option('--channel_id');
        $verbose = $this->option('--verbose');

        // This will ask for a channel entry before syncing
        // $this->channel_id = $this->getOptionOrAsk('--channel_id', "Channel ID to sync", '', false);

        $entries = ee('Model')->get('ChannelEntry');

        if (!empty($this->channel_id)) {
            $entries = $entries->filter('channel_id', $this->channel_id);
        }

        $entry_count = $entries->count();

        unset($entries);

        $this->info(sprintf(lang('command_sync_conditional_fields_syncing'), $entry_count));

        // Start timer
        $starttime = microtime(true);

        $loopData = $this->getLoopData($entry_count, 50);

        $syncCount = 0;
        foreach ($loopData as $data) {
            $entries = $this->getEntries($data);

            foreach ($entries as $entry) {
                if ($verbose) {
                    $this->info(sprintf(lang('command_sync_conditional_fields_current_entry'), $entry->getId()));
                }

                // Evaluate the conditions and save
                $entry->evaluateConditionalFields();
                $entry->HiddenFields->save();

                unset($entry);

                $timediff = number_format(round(microtime(true) - $starttime, 2), 2);
                if (++$syncCount % 50 == 0) {
                    $this->info(sprintf(
                        lang('command_sync_conditional_fields_entries_processed'),
                        $syncCount,
                        '(' . $timediff . ' s)',
                        '(' . $this->getMemoryUsage() . ')'
                    ));
                }
            }

            unset($entries);
        }

        // clear caches
        if (ee()->config->item('new_posts_clear_caches') == 'y') {
            ee()->functions->clear_caching('all');
        } else {
            ee()->functions->clear_caching('sql');
        }


        // End timer
        $endtime = microtime(true);
        $timediff = number_format(round($endtime - $starttime, 2), 2);

        $this->info(sprintf(
            lang('command_sync_conditional_fields_sync_complete'),
            $syncCount,
            '(' . $timediff . ' s)',
            '(' . $this->getMemoryUsage() . ')'
        ));

        $this->info(vsprintf(
            lang('command_sync_conditional_fields_database_info'),
            [
                ee('Database')->getLog()->getQueryCount(),
                number_format(ee('Database')->currentExecutionTime(), 4)
            ]
        ));
    }

    private function getEntries($data)
    {
        $entries = ee('Model')->get('ChannelEntry')->with(['Channel' => ['CustomFields' => ['FieldConditionSets' => 'FieldConditions']]]);

        if (!empty($this->channel_id)) {
            $entries = $entries->filter('channel_id', $this->channel_id);
        }

        $entries = $entries->limit($data['limit']);
        $entries = $entries->offset($data['offset']);

        return $entries->all();
    }

    private function getLoopData($entry_count, $chunkSize = 50)
    {
        $loopData = array();
        $counter = 0;
        $remaining = $entry_count;
        $step = $chunkSize;

        while ($remaining > 0) {
            $step = $step > $remaining ? $remaining : $chunkSize;

            $loopData[] = [
                'limit'  => $step,
                'offset' => $counter,
            ];

            $counter += $step;
            $remaining = $entry_count - $counter;
        }

        return $loopData;
    }

    private function getMemoryUsage()
    {
        $size = memory_get_usage();
        $unit = array('b','kb','mb','gb','tb','pb');

        return number_format(@round($size / pow(1024, ($i = floor(log($size, 1024)))), 2), 2) . ' ' . $unit[$i];
    }
}
