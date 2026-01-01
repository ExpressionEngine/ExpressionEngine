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
 * Command to list all category groups
 */
class CommandCategoryGroupList extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'List Category Groups';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'cgroups:list';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php cgroups:list';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_fields_list_option_site',
        'format,f:' => 'command_channels_list_option_format',
    ];

    /**
     * Sets the tablemask for the list table
     * @var boolean
     */
    public $tableMask = "|%-8.8s |%-8.8s |%-80.80s |";

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        // do we need to ask for site?
        $site_id = $this->getMsmSiteId('all');

        $format = $this->option('--format', 'table');

        // Get all sites
        $query = ee('Model')->get('CategoryGroup');

        if (isset($site_id) && !is_null($site_id)) {
            $query->filter('site_id', $site_id);
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
        $this->info('command_category_groups_list_header');
        $this->write('');

        // Table header
        $this->write(sprintf($this->tableMask,
            lang('command_category_groups_id'),
            lang('command_category_groups_site_id'),
            lang('command_category_groups_name')
        ));

        $this->write(str_repeat('-', 100));

        // Table rows
        foreach ($data as $row) {

            $this->write(sprintf($this->tableMask,
                $row->group_id,
                $row->site_id,
                $row->group_name
            ));
        }

        $this->write('');
        $this->complete(sprintf(lang('command_category_groups_list_total'), $data->count()));
    }

    /**
     * Display category groups in JSON format
     * @param Collection $data
     */
    private function displayJson($data)
    {
        $data = [];
        foreach ($data as $row) {
            $data[] = [
                'id' => $row->group_id,
                'site_id' => $row->site_id,
                'group_name' => $row->group_name
            ];
        }

        $this->write(json_encode($data, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    /**
     * Display sites in CSV format
     * @param Collection $sites
     */
    private function displayCsv($data)
    {
        // CSV header
        $this->write('ID,Site ID,Group Name');

        // CSV rows
        foreach ($data as $row) {
            $row = [
                $row->group_id,
                $row->site_id,
                $row->group_name
            ];

            $this->write(implode(',', $row));
        }
    }
}
