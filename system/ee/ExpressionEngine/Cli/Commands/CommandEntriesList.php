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
use ExpressionEngine\Cli\CliOptionsTrait;

/**
 * Command to list all entries
 */
class CommandEntriesList extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'List Entries';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'entries:list';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php entries:list';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_category_groups_list_option_site',
        'channel,c:' => 'command_entries_list_option_channel',
        'format,f:' => 'command_channels_list_option_format',
    ];

    /**
     * Sets the tablemask for the list table
     * @var boolean
     */
    public $tableMask = "|%-8.8s |%-40.40s |%-20.20s |%-17.17s |%-15.15s |%-8.8s |";

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee()->lang->load('content');

        $format = $this->option('--format', 'table');

        if ($this->option('--channel', false) === false) {
            $site_id = $this->getMsmSiteId('all');
        }

        $channels = ee('Model')->get('Channel')->fields('channel_id', 'channel_title');
        if (isset($site_id) && !is_null($site_id)) {
            $channels->filter('site_id', $site_id);
        }
        $channels = $channels->all()->getDictionary('channel_id', 'channel_title');
        $channelId = $this->getOptionValue('channel', [
            'type' => 'select',
            'desc' => 'command_entries_list_option_channel',
            'choices' => $channels,
            'default' => null,
            'required' => false
        ]);

        $query = ee('Model')->get('ChannelEntry')->with('Channel', 'Author');
        if (isset($channelId) && !empty($channelId)) {
            $query->filter('channel_id', $channelId);
        }

        $data = $query->all();

        switch ($format) {
            case 'json':
                $this->displayJson($data);
                break;
            case 'csv':
                $this->displayCsv($data);
                break;
            case 'table':
            default:
                $this->displayTable($data);
                break;
        }
    }

    /**
     * Display in table format
     * @param Collection $data
     */
    private function displayTable($data)
    {
        $this->info('command_entries_list_header');
        $this->write('');

        // Table header
        $this->write(sprintf($this->tableMask,
            lang('column_entry_id'),
            lang('column_title'),
            lang('channel'),
            lang('date'),
            lang('author'),
            lang('status')
        ));

        $this->write(str_repeat('-', 120));

        // Table rows
        foreach ($data as $row) {

            $this->write(sprintf($this->tableMask,
                $row->entry_id,
                $row->title,
                $row->Channel->channel_title,
                ee()->localize->format_date('%Y-%m-%d %H:%i', $row->entry_date),
                $row->Author->screen_name,
                lang($row->status)
            ));
        }

        $this->write('');
        $this->complete(sprintf(lang('command_entries_list_total'), $data->count()));
    }

    /**
     * Display categories in JSON format
     * @param Collection $data
     */
    private function displayJson($data)
    {
        $data = [];
        foreach ($data as $row) {
            $data[] = [
                'entry_id' => $row->entry_id,
                'title' => $row->title,
                'channel' => $row->Channel->channel_title,
                'date' => ee()->localize->format_date('%Y-%m-%d %H:%i', $row->entry_date),
                'author' => $row->Author->screen_name,
                'status' => lang($row->status)
            ];
        }

        $this->write(json_encode($data, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    /**
     * Display categories in CSV format
     * @param Collection $data
     */
    private function displayCsv($data)
    {
        // CSV header
        $this->write('ID,Title,Channel,Date,Author,Status');

        // CSV rows
        foreach ($data as $row) {
            $row = [
                $row->entry_id,
                $row->title,
                $row->Channel->channel_title,
                ee()->localize->format_date('%Y-%m-%d %H:%i', $row->entry_date),
                $row->Author->screen_name,
                lang($row->status)
            ];

            $this->write(implode(',', $row));
        }
    }
}
