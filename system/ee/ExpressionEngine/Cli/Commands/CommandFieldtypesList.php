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
        'short,s:' => 'command_fieldtypes_list_option_short',
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

        // Parse fieldtype shortname filters
        $shortFilter = $this->option('--short', '');
        $shortFilters = [];
        $shortFiltersLower = [];
        if (!empty($shortFilter)) {
            $shortFilters = array_filter(array_map('trim', explode(',', (string) $shortFilter)));
            $shortFiltersLower = array_map('strtolower', $shortFilters);
        }

        // Collect fieldtypes from all providers
        $providers = ee('App')->getProviders();
        $fieldtypes = [];

        // Preload channel field usage counts grouped by field_type
        $channelFieldUsage = $this->getChannelFieldUsageCounts();

        // Build installed fieldtypes details from DB
        $installedFieldtypeMap = [];
        $installedFieldtypeVersions = [];
        $installedFieldtypeHasGlobals = [];
        $installedFieldtypeSettings = [];
        $installedFieldtypes = ee('Model')->get('Fieldtype')->fields('name,version,has_global_settings,settings')->all();
        foreach ($installedFieldtypes as $installed) {
            $installedFieldtypeMap[$installed->name] = true;
            $installedFieldtypeVersions[$installed->name] = (string) $installed->version;
            // normalize has_global_settings to boolean
            $hasGlobals = is_string($installed->has_global_settings)
                ? (bool) get_bool_from_string($installed->has_global_settings)
                : (bool) $installed->has_global_settings;
            $installedFieldtypeHasGlobals[$installed->name] = $hasGlobals;
            // settings is typed as base64Serialized and should be array or null
            $installedFieldtypeSettings[$installed->name] = $installed->settings;
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
                // If fieldtype short filter is set, skip non-matching fieldtypes
                if (!empty($shortFiltersLower) && !in_array(strtolower($short), $shortFiltersLower, true)) {
                    continue;
                }

                // Determine installed status: DB record OR addon installed
                $installedForShort = isset($installedFieldtypeMap[$short]) || $addon->isInstalled();

                // If only installed requested, skip those not considered installed
                if ($onlyInstalled && !$installedForShort) {
                    continue;
                }

                // Determine version: DB version, or parse from fieldtype file, or fall back to add-on version
                $version = isset($installedFieldtypeVersions[$short]) ? $installedFieldtypeVersions[$short] : null;
                $filePath = $addon->getPath() . '/ft.' . $short . '.php';
                if (empty($version) && file_exists($filePath)) {
                    $contents = @file_get_contents($filePath);
                    if ($contents !== false && preg_match('/[\'\"]version[\'\"]\s*=>\s*[\'\"]([^\'\"]+)[\'\"]/i', $contents, $m)) {
                        $version = $m[1];
                    }
                }
                if (empty($version)) {
                    try {
                        $version = (string) $addon->getVersion();
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                // Determine settings: prefer global from DB; optionally fall back to defaults for specific fieldtypes
                $hasGlobalSettings = isset($installedFieldtypeHasGlobals[$short]) ? (bool) $installedFieldtypeHasGlobals[$short] : false;
                $settings = isset($installedFieldtypeSettings[$short]) ? $installedFieldtypeSettings[$short] : null;

                // If global settings are not present and a specific shortname was requested, optionally derive defaults
                if ((empty($settings) || !is_array($settings)) && !empty($shortFiltersLower) && in_array(strtolower($short), $shortFiltersLower, true)) {
                    // For Structure, use install() defaults which represent field settings
                    if (strtolower($short) === 'structure' && file_exists($filePath)) {
                        $defaults = $this->getFieldtypeDefaultSettings($filePath, ucfirst($short) . '_ft');
                        if (is_array($defaults) && !empty($defaults)) {
                            $settings = $defaults;
                        }
                    }
                }

                $fieldtypes[$short] = [
                    'short' => $short,
                    'name' => $display,
                    'addon' => $addon->getPrefix(),
                    'addon_name' => $addon->getName(),
                    'installed' => (bool) $installedForShort,
                    'version' => $version ?: null,
                    'class' => ucfirst($short) . '_ft',
                    'path' => file_exists($filePath) ? $filePath : null,
                    'has_global_settings' => $hasGlobalSettings,
                    'settings' => $settings,
                    'channel_field_count' => isset($channelFieldUsage[$short]) ? (int) $channelFieldUsage[$short] : 0,
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

    /**
     * Attempts to derive default field settings for a fieldtype by loading its class and calling install().
     * Restricted use to specific fieldtypes to avoid side effects.
     */
    private function getFieldtypeDefaultSettings($filePath, $className)
    {
        // Ensure base EE_Fieldtype is available
        if (!class_exists('EE_Fieldtype')) {
            @require_once SYSPATH . 'ee/legacy/fieldtypes/EE_Fieldtype.php';
        }

        if (!class_exists($className)) {
            @require_once $filePath;
        }

        if (class_exists($className)) {
            try {
                $obj = new $className();
                if (method_exists($obj, 'install')) {
                    $defaults = $obj->install();
                    if (is_array($defaults)) {
                        return $defaults;
                    }
                }
            } catch (\Throwable $e) {
                // ignore and fall through
            }
        }

        return null;
    }

    /**
     * Returns an associative array of channel field usage counts keyed by field_type shortname.
     */
    private function getChannelFieldUsageCounts()
    {
        $counts = [];
        try {
            $table = ee()->db->dbprefix . 'channel_fields';
            $query = ee()->db->query("SELECT field_type, COUNT(*) AS cnt FROM `{$table}` GROUP BY field_type");
            foreach ($query->result_array() as $row) {
                $counts[$row['field_type']] = (int) $row['cnt'];
            }
        } catch (\Throwable $e) {
            // ignore and return empty
        }
        return $counts;
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


