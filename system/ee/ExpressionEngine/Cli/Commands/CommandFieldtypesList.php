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
 * Command to list all available fieldtypes
 */
class CommandFieldtypesList extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'List Fieldtypes';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'fieldtypes:list';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php fieldtypes:list';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'format,f:' => 'command_fieldtypes_list_option_format',
        'installed,i' => 'command_fieldtypes_list_option_installed',
        'addon,a:' => 'command_fieldtypes_list_option_addon',
    ];

    /**
     * Sets the tablemask for the list table
     * @var boolean
     */
    public $tableMask = "|%-20.20s |%-40.40s |%-20.20s |";

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $format = $this->option('--format', 'table');
        $onlyInstalled = (bool) $this->option('--installed', false);
        $addonFilter = $this->option('--addon', '');
        $addonFilters = [];
        $addonFiltersLower = [];
        if (!empty($addonFilter)) {
            $addonFilters = array_filter(array_map('trim', explode(',', (string) $addonFilter)));
            $addonFiltersLower = array_map('strtolower', $addonFilters);
        }

        // Collect fieldtypes from all providers
        $providers = ee('App')->getProviders();
        $fieldtypes = [];

        // Build installed fieldtypes map if filtering by installed
        $installedFieldtypeMap = [];
        if ($onlyInstalled) {
            $installedFieldtypes = ee('Model')->get('Fieldtype')->fields('name')->all();
            foreach ($installedFieldtypes as $installed) {
                $installedFieldtypeMap[$installed->name] = true;
            }
        }

        foreach ($providers as $providerKey => $provider) {
            $addon = ee('Addon')->get($provider->getPrefix());

            if (!$addon) {
                continue;
            }

            // If addon filter is set, skip non-matching providers
            if (!empty($addonFiltersLower) && !in_array(strtolower($addon->getPrefix()), $addonFiltersLower, true)) {
                continue;
            }

            $names = $addon->getFieldtypeNames(); // [shortname => displayName]

            foreach ($names as $short => $display) {
                if ($onlyInstalled && !isset($installedFieldtypeMap[$short])) {
                    continue;
                }
                $fieldtypes[$short] = [
                    'short' => $short,
                    'name' => $display,
                    'addon' => $addon->getPrefix(),
                ];
            }
        }

        ksort($fieldtypes);

        if (empty($fieldtypes)) {
            $this->info('command_fieldtypes_list_no_fieldtypes_found');
            return;
        }

        switch ($format) {
            case 'json':
                $this->displayJson($fieldtypes);
                break;
            case 'csv':
                $this->displayCsv($fieldtypes);
                break;
            case 'table':
            default:
                $this->displayTable($fieldtypes);
                break;
        }
    }

    private function displayTable(array $fieldtypes)
    {
        $this->info('command_fieldtypes_list_header');
        $this->write('');

        $this->write(sprintf($this->tableMask,
            lang('command_fieldtypes_list_shortname'),
            lang('command_fieldtypes_list_name'),
            lang('command_fieldtypes_list_addon')
        ));

        $this->write(str_repeat('-', 90));

        foreach ($fieldtypes as $ft) {
            $this->write(sprintf($this->tableMask,
                $ft['short'],
                $ft['name'],
                $ft['addon']
            ));
        }

        $this->write('');
        $this->info(sprintf(lang('command_fieldtypes_list_total'), count($fieldtypes)));
    }

    private function displayJson(array $fieldtypes)
    {
        $this->write(json_encode(array_values($fieldtypes), JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    private function displayCsv(array $fieldtypes)
    {
        $this->write('Shortname,Name,Addon');
        foreach ($fieldtypes as $ft) {
            $row = [
                $ft['short'],
                '"' . str_replace('"', '""', (string) $ft['name']) . '"',
                $ft['addon'],
            ];
            $this->write(implode(',', $row));
        }
    }
}


