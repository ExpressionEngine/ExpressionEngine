<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli\Commands;

use ExpressionEngine\Cli\Cli;
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Cli\CliOptionsTrait;

/**
 * Command to list all upload directories
 */
class CommandUploadDirectoriesList extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'List Upload Directories';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'upload-directories:list';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php upload-directories:list [--site=<site_id>|all|first] [--format=<format>]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_upload_directories_list_option_site',
        'format,f:' => 'command_upload_directories_list_option_format',
    ];

    /**
     * Sets the tablemask for the list table
     * @var boolean
     */
    public $tableMask = "|%-8.8s |%-8.8s |%-30.30s |%-40.40s |%-40.40s |";

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        // do we need to ask for site?
        $site_id = $this->getMsmSiteId('all');

        $format = $this->option('--format', 'table');

        // Get all upload directories
        $query = ee('Model')->get('UploadDestination')->filter('module_id', ''); // exclude module upload dirs

        if (isset($site_id) && !is_null($site_id)) {
            $query->filter('site_id', 'IN', [0, $site_id]);
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
        $this->info('command_upload_directories_list_header');
        $this->write('');

        // Table header
        $this->write(sprintf($this->tableMask,
            lang('command_upload_directories_id'),
            lang('command_upload_directories_site_id'),
            lang('command_upload_directories_name'),
            lang('command_upload_directories_server_path'),
            lang('command_upload_directories_url')
        ));

        $this->write(str_repeat('-', 130));

        // Table rows
        foreach ($data as $row) {
            $this->write(sprintf($this->tableMask,
                $row->id,
                $row->site_id,
                $row->name,
                $row->server_path,
                $row->url
            ));
        }

        $this->write('');
        $this->complete(sprintf(lang('command_upload_directories_list_total'), $data->count()));
    }

    /**
     * Display upload directories in JSON format
     * @param Collection $data
     */
    private function displayJson($data)
    {
        $jsonData = [];
        foreach ($data as $row) {
            $jsonData[] = [
                'id' => $row->id,
                'site_id' => $row->site_id,
                'name' => $row->name,
                'server_path' => $row->server_path,
                'url' => $row->url,
            ];
        }

        $this->write(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    /**
     * Display upload directories in CSV format
     * @param Collection $data
     */
    private function displayCsv($data)
    {
        // CSV header
        $this->write('ID,Site ID,Name,Server Path,URL');

        // CSV rows
        foreach ($data as $row) {
            $fields = [
                $row->id,
                $row->site_id,
                '"' . str_replace('"', '""', $row->name) . '"',
                '"' . str_replace('"', '""', $row->server_path) . '"',
                '"' . str_replace('"', '""', $row->url) . '"',
            ];

            $this->write(implode(',', $fields));
        }
    }
}