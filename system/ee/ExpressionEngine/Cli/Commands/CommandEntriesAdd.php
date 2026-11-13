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
use ExpressionEngine\Cli\CliOptionsTrait;

/**
 * Command to add a new entry
 */
class CommandEntriesAdd extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Add Entry';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'entries:add';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php entries:add [--channel=<channel_id>] [--title="entry_title"]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_category_groups_list_option_site',
        'channel,c:' => 'command_entries_list_channel',
        'fields,f:' => 'command_sites_fields',
    ];

    /**
    * whether command options are dynamic
    * @var bool
    */
    public $dynamicCommandOptions = true;

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
        ee()->lang->load('admin_content');
        ee()->lang->load('channel');
        ee()->lang->load('content');

        if ($this->option('--channel', false) !== false) {
            $this->data['channel_id'] = $this->option('--channel');
        } else {
            $site_id = $this->getMsmSiteId('all');

            $channels = ee('Model')->get('Channel')->fields('channel_id', 'channel_title');
            if (isset($site_id) && !is_null($site_id)) {
                $channels->filter('site_id', $site_id);
            }
            $channels = $channels->all()->getDictionary('channel_id', 'channel_title');
            $this->data['channel_id'] = $this->getOptionValue('channel', [
                'type' => 'select',
                'desc' => 'command_entries_list_option_channel',
                'choices' => $channels,
                'default' => null,
                'required' => false
            ]);
            if (empty($this->data['channel_id']) || !array_key_exists($this->data['channel_id'], $channels)) {
                $this->fail(lang('command_channels_not_found'));
            }
        }

        $this->data['site_id'] = ee('Model')->get('Channel', $this->data['channel_id'])->first()->site_id;
        $this->info(lang('command_entries_adding'));

        $entry = $this->getFieldsForEntries();

        // set categories
        if (isset($this->data['categories']) && !empty($this->data['categories'])) {
            $categories = ee('Model')->get('Category', $this->data['categories'])->all();
            unset($this->data['categories']);
            $entry->Categories = $categories;
        }

        $this->data['entry_date'] = ee()->localize->now;

        $entry->set($this->data);
        $this->validateModel($entry, lang('command_entries_not_added'));

        $entry->save();
        $this->complete(lang('command_entries_added'));
    }

}
