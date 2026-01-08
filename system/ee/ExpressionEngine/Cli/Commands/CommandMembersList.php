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
 * Command to list all members
 */
class CommandMembersList extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'List Members';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'members:list';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php members:list';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'format,f:' => 'command_channels_list_option_format',
    ];

    /**
     * Sets the tablemask for the list table
     * @var boolean
     */
    public $tableMask = "|%-8.8s |%-25.25s |%-25.25s |%-30.30s |%-17.17s |%-17.17s |";

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $format = $this->option('--format', 'table');

        $query = ee('Model')->get('Member');
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
        $this->info(lang('command_members_list_header'));
        $this->write('');

        // Table header
        $this->write(sprintf(
            $this->tableMask,
            'ID',
            'Username',
            'Screen Name',
            'Email',
            'Joined',
            'Last Visit'
        ));

        $this->write(str_repeat('-', 130));

        // Table rows
        foreach ($data as $row) {
            $this->write(sprintf(
                $this->tableMask,
                $row->member_id,
                (string) $row->username,
                (string) $row->screen_name,
                (string) $row->email,
                $this->formatDate($row->join_date),
                $this->formatDate($row->last_visit)
            ));
        }

        $this->write('');
        $this->complete(sprintf(lang('command_members_list_total'), $data->count()));
    }

    /**
     * Display members in JSON format
     * @param Collection $data
     */
    private function displayJson($data)
    {
        $jsonData = [];
        foreach ($data as $row) {
            $jsonData[] = [
                'member_id' => $row->member_id,
                'username' => (string) $row->username,
                'screen_name' => (string) $row->screen_name,
                'email' => (string) $row->email,
                'joined' => $this->formatDate($row->join_date),
                'last_visit' => $this->formatDate($row->last_visit),
            ];
        }

        $this->write(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    /**
     * Display members in CSV format
     * @param Collection $data
     */
    private function displayCsv($data)
    {
        // CSV header
        $this->write('ID,Username,Screen Name,Email,Joined,Last Visit');

        // CSV rows
        foreach ($data as $row) {
            $line = [
                $row->member_id,
                (string) $row->username,
                (string) $row->screen_name,
                (string) $row->email,
                $this->formatDate($row->join_date),
                $this->formatDate($row->last_visit),
            ];

            $this->write(implode(',', $line));
        }
    }

    /**
     * Format timestamp to Y-m-d H:i, handle empty/zero values
     */
    private function formatDate($timestamp)
    {
        if (empty($timestamp)) {
            return '-';
        }

        return ee()->localize->format_date('%Y-%m-%d %H:%i', $timestamp);
    }
}