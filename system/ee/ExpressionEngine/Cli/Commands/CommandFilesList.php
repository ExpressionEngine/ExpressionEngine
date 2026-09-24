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
 * Command to list all files
 */
class CommandFilesList extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'List Files';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'files:list';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php files:list [--upload_id=<upload_id>] [--format=<format>]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'upload_id,u:' => 'command_files_upload_id',
        'format,f:' => 'command_files_list_option_format',
    ];

    /**
     * Sets the tablemask for the list table
     * @var boolean
     */
    public $tableMask = "|%-8.8s |%-4.4s |%-54.54s |%-15.15s |%-20.20s |";

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $format = $this->option('--format', 'table');

        $siteId = $this->getMsmSiteId('all');

        // Get upload directories for selection
        $uploadDirectories = ee('Model')->get('UploadDestination')->filter('module_id', '');if (isset($siteId) && !is_null($siteId)) {
            $uploadDirectories->filter('site_id', 'IN', [0, $siteId]);
        }
        $uploadDirectories = ['all' => 'All Directories'] + $uploadDirectories->all()->getDictionary('id', 'name');

        $uploadId = $this->getOptionValue('upload_id',
            [
                'type' => 'select',
                'desc' => 'command_files_upload_id',
                'choices' => $uploadDirectories,
                'default' => 'all',
                'required' => true
            ]
        );

        $query = ee('Model')->get('File');

        if ($uploadId !== 'all' && $uploadId !== false) {
            $query->filter('upload_location_id', $uploadId);
        }

        if (isset($siteId) && !is_null($siteId)) {
            $query->filter('site_id', 'IN', [0, $siteId]);
        }

        $data = $query->order('file_name', 'asc')->all();

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
        $this->info('command_files_list_header');
        $this->write('');

        // Table header
        $this->write(sprintf($this->tableMask,
            lang('command_files_id'),
            lang('command_files_upload_id'),
            lang('command_files_name'),
            lang('command_files_size'),
            lang('command_files_uploaded')
        ));

        $this->write(str_repeat('-', 110));

        // Table rows
        foreach ($data as $row) {
            $size = $this->formatBytes($row->file_size);
            $uploaded = $row->upload_date ? date('Y-m-d H:i:s', $row->upload_date) : 'N/A';

            $this->write(sprintf($this->tableMask,
                $row->file_id,
                $row->upload_location_id,
                $row->file_name,
                $size,
                $uploaded
            ));
        }

        $this->write('');
        $this->complete(sprintf(lang('command_files_list_total'), $data->count()));
    }

    /**
     * Display files in JSON format
     * @param Collection $data
     */
    private function displayJson($data)
    {
        $jsonData = [];
        foreach ($data as $row) {
            $jsonData[] = [
                'file_id' => $row->file_id,
                'upload_location_id' => $row->upload_location_id,
                'file_name' => $row->file_name,
                'file_size' => $row->file_size,
                'upload_date' => $row->upload_date,
                'mime_type' => $row->mime_type,
            ];
        }

        $this->write(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    /**
     * Display files in CSV format
     * @param Collection $data
     */
    private function displayCsv($data)
    {
        // CSV header
        $this->write('File ID,Upload ID,File Name,File Size,MIME Type,Upload Date');

        // CSV rows
        foreach ($data as $row) {
            $fields = [
                $row->file_id,
                $row->upload_location_id,
                '"' . str_replace('"', '""', $row->file_name) . '"',
                $row->file_size,
                '"' . str_replace('"', '""', $row->mime_type) . '"',
                $row->upload_date,
            ];

            $this->write(implode(',', $fields));
        }
    }

    /**
     * Format bytes to human readable format
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}