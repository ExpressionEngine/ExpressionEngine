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
 * Command to list all channel fields
 */
class CommandFieldsList extends Cli
{
    /**
     * name of command
     * @var string
     */
    public $name = 'List Fields';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'fields:list';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php fields:list';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_fields_list_option_site',
        'format,f:' => 'command_fields_list_option_format',
        'type,t:' => 'command_fields_list_option_type',
        'group,g:' => 'command_fields_list_option_group',
        'channel_id,c:' => 'command_fields_list_option_channel_id',
        'field_id,i:' => 'command_fields_list_option_field_id',
    ];

    /**
     * Sets the tablemask for the list table
     * @var boolean
     */
    public $tableMask = "|%-8.8s |%-20.20s |%-30.30s |%-15.15s |%-10.10s |%-8.8s |%-8.8s |";

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        $site_id = $this->option('--site', null);
        $format = $this->option('--format', 'table');
        $type = $this->option('--type', '');
        $group = $this->option('--group', '');
        $channel_id = $this->option('--channel_id', '');
        $field_id = $this->option('--field_id', '');

        // Get all channel fields for the specified site
        $fields = ee('Model')->get('ChannelField')
            ->order('field_name')
            ->all();

        // Filter by field type if specified
        if (!empty($site_id)) {
          $fields = $fields->filter('site_id', $site_id);
        }

        // Filter by field group if specified
        if (!empty($group)) {
            $fields = $fields->filter('field_type', $group);
        }

        // Filter by field group if specified
        if (!empty($group)) {
            $filtered_fields = new Collection();
            foreach ($fields as $field) {
                foreach ($field->ChannelFieldGroups as $field_group) {
                    if ($field_group->group_name == $group || $field_group->short_name == $group) {
                        $filtered_fields[] = $field;
                        break;
                    }
                }
            }
            $fields = $filtered_fields;
        }

        // Filter by channel if specified
        if (!empty($channel_id)) {
            $filtered_fields = new Collection();
            foreach ($fields as $field) {
                foreach ($field->getAllChannels() as $channel) {
                    if ($channel->channel_id == $channel_id) {
                        $filtered_fields[] = $field;
                        break;
                    }
                }
            }
            $fields = $filtered_fields;
        }

        // Filter by field ID if specified
        if (!empty($field_id)) {
            $filtered_fields = new Collection();
            foreach ($fields as $field) {
                if ($field->field_id == $field_id) {
                    $filtered_fields[] = $field;
                    break;
                }
            }
            $fields = $filtered_fields;
        }

        if ($fields->count() == 0) {
            $this->info('command_fields_list_no_fields_found');
            return;
        }

        // Display fields based on format
        switch ($format) {
            case 'json':
                $this->displayJson($fields);
                break;
            case 'csv':
                $this->displayCsv($fields);
                break;
            case 'table':
            default:
                $this->displayTable($fields);
                break;
        }
    }

    /**
     * Display fields in table format
     * @param Collection $fields
     */
    private function displayTable($fields)
    {
        $this->info('command_fields_list_header');
        $this->write('');

        // Table header
        $this->write(sprintf($this->tableMask,
            lang('command_fields_list_id'),
            lang('command_fields_list_name'),
            lang('command_fields_list_label'),
            lang('command_fields_list_type'),
            lang('command_fields_list_required'),
            lang('command_fields_list_search'),
            lang('command_fields_list_hidden')
        ));

        $this->write(str_repeat('-', 120));

        // Table rows
        foreach ($fields as $field) {
            $this->write(sprintf($this->tableMask,
                $field->field_id,
                $field->field_name,
                $field->field_label,
                $field->field_type,
                $field->field_required == 'y' ? 'Yes' : 'No',
                $field->field_search == 'y' ? 'Yes' : 'No',
                $field->field_is_hidden == 'y' ? 'Yes' : 'No'
            ));
        }

        $this->write('');
        $this->info(sprintf(lang('command_fields_list_total'), $fields->count()));
    }

    /**
     * Display fields in JSON format
     * @param Collection $fields
     */
    private function displayJson($fields)
    {
        $data = [];
        foreach ($fields as $field) {
            $field_groups = [];
            foreach ($field->ChannelFieldGroups as $group) {
                $field_groups[] = [
                    'id' => $group->group_id,
                    'name' => $group->group_name,
                    'short_name' => $group->short_name
                ];
            }

            $channels = [];
            foreach ($field->getAllChannels() as $channel) {
                $channels[] = [
                    'id' => $channel->channel_id,
                    'name' => $channel->channel_name,
                    'title' => $channel->channel_title
                ];
            }

            $data[] = [
                'id' => $field->field_id,
                'name' => $field->field_name,
                'label' => $field->field_label,
                'instructions' => $field->field_instructions,
                'type' => $field->field_type,
                'required' => $field->field_required == 'y',
                'searchable' => $field->field_search == 'y',
                'hidden' => $field->field_is_hidden == 'y',
                'conditional' => $field->field_is_conditional == 'y',
                'max_length' => $field->field_maxl,
                'text_direction' => $field->field_text_direction,
                'order' => $field->field_order,
                'field_groups' => $field_groups,
                'channels' => $channels,
                'site_id' => $field->site_id
            ];
        }

        $this->write(json_encode($data, JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_APOS));
    }

    /**
     * Display fields in CSV format
     * @param Collection $fields
     */
    private function displayCsv($fields)
    {
        // CSV header
        $this->write('ID,Name,Label,Instructions,Type,Required,Searchable,Hidden,Conditional,Max Length,Text Direction,Order,Field Groups,Channels,Site ID');

        // CSV rows
        foreach ($fields as $field) {
            $field_groups = [];
            foreach ($field->ChannelFieldGroups as $group) {
                $field_groups[] = $group->group_name;
            }

            $channels = [];
            foreach ($field->getAllChannels() as $channel) {
                $channels[] = $channel->channel_name;
            }

            $row = [
                $field->field_id,
                $field->field_name,
                '"' . str_replace('"', '""', $field->field_label) . '"',
                '"' . str_replace('"', '""', $field->field_instructions) . '"',
                $field->field_type,
                $field->field_required == 'y' ? 'Yes' : 'No',
                $field->field_search == 'y' ? 'Yes' : 'No',
                $field->field_is_hidden == 'y' ? 'Yes' : 'No',
                $field->field_is_conditional == 'y' ? 'Yes' : 'No',
                $field->field_maxl,
                $field->field_text_direction,
                $field->field_order,
                '"' . implode('; ', $field_groups) . '"',
                '"' . implode('; ', $channels) . '"',
                $field->site_id
            ];

            $this->write(implode(',', $row));
        }
    }
}
