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
use ExpressionEngine\Service\Model\Collection;

/**
 * Command to list all channels
 */
class CommandChannelsList extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'List Channels';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'channels:list';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php channels:list';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_channels_list_option_site',
        'format,f:' => 'command_channels_list_option_format',
        'channel_id,c:' => 'command_channels_list_option_channel_id',
    ];

    /**
     * Sets the tablemask for the list table
     * @var boolean
     */
    public $tableMask = "|%-8.8s |%-20.20s |%-40.40s |%-10.10s |%-15.15s |";

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $site_id = $this->option('--site', '');
        $format = $this->option('--format', 'table');
        $channel_id = $this->option('--channel_id', '');

        // Get all channels, optionally filtered by site and channel_id
        $query = ee('Model')->get('Channel');

        if (!empty($site_id)) {
            $query->filter('site_id', $site_id);
        }

        if (!empty($channel_id)) {
            $query->filter('channel_id', $channel_id);
        }

        $channels = $query->order('channel_title')->all();

        if ($channels->count() == 0) {
            $this->info('command_channels_list_no_channels_found');
            return;
        }

        // Display channels based on format
        switch ($format) {
            case 'json':
                $this->displayJson($channels);
                break;
            case 'csv':
                $this->displayCsv($channels);
                break;
            case 'table':
            default:
                $this->displayTable($channels);
                break;
        }
    }

    /**
     * Display channels in table format
     * @param Collection $channels
     */
    private function displayTable($channels)
    {
        $this->info('command_channels_list_header');
        $this->write('');

        // Table header
        $this->write(sprintf($this->tableMask,
            lang('command_channels_list_id'),
            lang('command_channels_list_name'),
            lang('command_channels_list_title'),
            lang('command_channels_list_entries'),
            lang('command_channels_list_last_entry')
        ));

        $this->write(str_repeat('-', 100));

        // Table rows
        foreach ($channels as $channel) {
            $last_entry = $channel->last_entry_date ?
                ee()->localize->format_date('%Y-%m-%d', $channel->last_entry_date) :
                lang('command_channels_list_never');

            $this->write(sprintf($this->tableMask,
                $channel->channel_id,
                $channel->channel_name,
                $channel->channel_title,
                $channel->total_entries,
                $last_entry
            ));
        }

        $this->write('');
        $this->info(sprintf(lang('command_channels_list_total'), $channels->count()));
    }

    /**
     * Display channels in JSON format
     * @param Collection $channels
     */
    private function displayJson($channels)
    {
        $data = [];
        foreach ($channels as $channel) {
            $data[] = [
                'id' => $channel->channel_id,
                'name' => $channel->channel_name,
                'title' => $channel->channel_title,
                'description' => $channel->channel_description,
                'total_entries' => $channel->total_entries,
                'total_comments' => $channel->total_comments,
                'last_entry_date' => $channel->last_entry_date,
                'last_comment_date' => $channel->last_comment_date,
                'site_id' => $channel->site_id
            ];
        }

        $this->write(json_encode($data, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    /**
     * Display channels in CSV format
     * @param Collection $channels
     */
    private function displayCsv($channels)
    {
        // CSV header
        $this->write('ID,Name,Title,Description,Total Entries,Total Comments,Last Entry Date,Last Comment Date,Site ID');

        // CSV rows
        foreach ($channels as $channel) {
            $row = [
                $channel->channel_id,
                $channel->channel_name,
                $channel->channel_title,
                '"' . str_replace('"', '""', (string) $channel->channel_description) . '"',
                $channel->total_entries,
                $channel->total_comments,
                $channel->last_entry_date ? ee()->localize->format_date('%Y-%m-%d %H:%i:%s', $channel->last_entry_date) : '',
                $channel->last_comment_date ? ee()->localize->format_date('%Y-%m-%d %H:%i:%s', $channel->last_comment_date) : '',
                $channel->site_id
            ];

            $this->write(implode(',', $row));
        }
    }
}
