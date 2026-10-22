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
 * Command to list all categories
 */
class CommandCategoriesList extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'List Categories';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'categories:list';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php categories:list';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_category_groups_list_option_site',
        'category_group,g:' => 'command_categories_list_option_category_group',
        'format,f:' => 'command_channels_list_option_format',
    ];

    /**
     * Sets the tablemask for the list table
     * @var boolean
     */
    public $tableMask = "|%-8.8s |%-20.20s |%-65.65s |";

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $format = $this->option('--format', 'table');

        if ($this->option('--category_group', false) === false) {
            $site_id = $this->getMsmSiteId('all');
        }

        $category_groups = ee('Model')->get('CategoryGroup')->fields('group_id', 'group_name');
        if (isset($site_id) && !is_null($site_id)) {
            $category_groups->filter('site_id', $site_id);
        }
        $category_groups = $category_groups->all()->getDictionary('group_id', 'group_name');
        $groupId = $this->getOptionValue('category_group', [
            'type' => 'select',
            'desc' => 'command_categories_list_which_category_group',
            'choices' => $category_groups,
            'default' => null,
            'required' => false
        ]);

        $query = ee('Model')->get('Category')->with('CategoryGroup');
        if (isset($groupId) && !empty($groupId)) {
            $query->filter('group_id', $groupId);
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
        $this->info('command_categories_list_header');
        $this->write('');

        // Table header
        $this->write(sprintf($this->tableMask,
            lang('command_categories_id'),
            lang('command_categories_name'),
            lang('command_categories_url_title')
        ));

        $this->write(str_repeat('-', 100));

        // Table rows
        foreach ($data as $row) {

            $this->write(sprintf($this->tableMask,
                $row->cat_id,
                $row->cat_name,
                $row->cat_url_title
            ));
        }

        $this->write('');
        $this->complete(sprintf(lang('command_categories_list_total'), $data->count()));
    }

    /**
     * Display categories in JSON format
     * @param Collection $data
     */
    private function displayJson($data)
    {
        $jsonData = [];
        foreach ($data as $row) {
            $jsonData[] = [
                'cat_id' => $row->cat_id,
                'cat_name' => $row->cat_name,
                'cat_url_title' => $row->cat_url_title
            ];
        }

        $this->write(json_encode($jsonData, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    /**
     * Display categories in CSV format
     * @param Collection $data
     */
    private function displayCsv($data)
    {
        // CSV header
        $this->write('ID,Name,URL Title');

        // CSV rows
        foreach ($data as $row) {
            $row = [
                $row->cat_id,
                $row->cat_name,
                $row->cat_url_title
            ];

            $this->write(implode(',', $row));
        }
    }
}
