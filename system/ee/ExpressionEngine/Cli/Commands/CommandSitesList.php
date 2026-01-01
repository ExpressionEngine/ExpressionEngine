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
 * Command to list all sites
 */
class CommandSitesList extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'List Sites';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'sites:list';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php sites:list';

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
    public $tableMask = "|%-8.8s |%-15.15s |%-20.20s |%-40.40s |%-10.10s |";

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        if (!bool_config_item('multiple_sites_enabled')) {
            $this->fail('command_sites_multiple_sites_disabled');
            return;
        }

        $format = $this->option('--format', 'table');

        // Get all sites
        $query = ee('Model')->get('Site');

        $sites = $query->order('site_label', 'asc')->all();

        $sitesOn = ee('Model')->get('Config')
            ->filter('key', 'is_site_on')
            ->all()
            ->getDictionary('site_id', 'value');

        // Display sites based on format
        switch ($format) {
            case 'json':
                $this->displayJson($sites, $sitesOn);
                break;
            case 'csv':
                $this->displayCsv($sites, $sitesOn);
                break;
            case 'table':
            default:
                $this->displayTable($sites, $sitesOn);
                break;
        }
    }

    /**
     * Display sites in table format
     * @param Collection $sites
     */
    private function displayTable($sites, $sitesOn)
    {
        $this->info('command_sites_list_header');
        $this->write('');

        // Table header
        $this->write(sprintf($this->tableMask,
            lang('command_sites_id'),
            lang('command_sites_name'),
            lang('command_sites_label'),
            lang('command_sites_desc'),
            lang('command_sites_status')
        ));

        $this->write(str_repeat('-', 100));

        // Table rows
        foreach ($sites as $site) {

            $this->write(sprintf($this->tableMask,
                $site->site_id,
                $site->site_name,
                $site->site_label,
                $site->site_description,
                isset($sitesOn[$site->site_id]) && $sitesOn[$site->site_id] == 'y' ? 'Online' : 'Offline'
            ));
        }

        $this->write('');
        $this->complete(sprintf(lang('command_sites_list_total'), $sites->count()));
    }

    /**
     * Display sites in JSON format
     * @param Collection $sites
     */
    private function displayJson($sites, $sitesOn)
    {
        $data = [];
        foreach ($sites as $site) {
            $data[] = [
                'id' => $site->site_id,
                'name' => $site->site_name,
                'label' => $site->site_label,
                'description' => $site->site_description,
                'status' => isset($sitesOn[$site->site_id]) && $sitesOn[$site->site_id] == 'y' ? 'Online' : 'Offline'
            ];
        }

        $this->write(json_encode($data, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    /**
     * Display sites in CSV format
     * @param Collection $sites
     */
    private function displayCsv($sites, $sitesOn)
    {
        // CSV header
        $this->write('ID,Name,Title,Description,Status');

        // CSV rows
        foreach ($sites as $site) {
            $row = [
                $site->site_id,
                $site->site_name,
                $site->site_label,
                $site->site_description,
                isset($sitesOn[$site->site_id]) && $sitesOn[$site->site_id] == 'y' ? 'Online' : 'Offline'
            ];

            $this->write(implode(',', $row));
        }
    }
}
