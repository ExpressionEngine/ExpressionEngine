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
 * Command to edit an entry
 */
class CommandEntriesEdit extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Edit Entry';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'entries:edit';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php entries:edit [--entry_id=<entry_id>]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_category_groups_list_option_site',
        'entry_id,e:' => 'command_entries_entry_id',
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

        $entries = [];

        if ($this->option('--entry_id', false) === false) {
            if ($this->option('--channel', false) !== false) {
                $channelId = $this->option('--channel');
            } else {
                $site_id = $this->getMsmSiteId('all');

                $channels = ee('Model')->get('Channel')->fields('channel_id', 'channel_title');
                if (isset($site_id) && !is_null($site_id)) {
                    $channels->filter('site_id', $site_id);
                }
                $channels = $channels->all()->getDictionary('channel_id', 'channel_title');
                $channelId = $this->getOptionValue('channel', [
                    'type' => 'select',
                    'desc' => 'command_entries_list_option_channel',
                    'choices' => $channels,
                    'default' => null,
                    'required' => false
                ]);
            }
            $entriesQuery = ee('Model')->get('ChannelEntry');
            if (isset($channelId) && !empty($channelId)) {
                $entriesQuery->filter('channel_id', $channelId);
            }
            foreach ($entriesQuery->all() as $entry) {
                $entries[$entry->entry_id] = $entry->title;
            }
        }

        $this->data['entry_id'] = $this->getOptionValue('entry_id', [
            'type' => 'select',
            'desc' => 'command_entries_edit_ask',
            'choices' => $entries,
            'default' => null,
            'required' => true
        ]);

        $entry = ee('Model')->get('ChannelEntry', $this->data['entry_id'])->first();
        if (empty($entry)) {
            $this->fail(lang('command_entries_not_found'));
        }

        $this->info(sprintf(lang('command_entries_editing'), $entry->title));

        $entry = $this->getFieldsForEntries($entry);

        // set categories
        if (isset($this->data['categories']) && !empty($this->data['categories'])) {
            $categories = ee('Model')->get('Category', $this->data['categories'])->all();
            unset($this->data['categories']);
            $entry->Categories = $categories;
        }

        $this->data['edit_date'] = ee()->localize->now;

        $entry->set($this->data);
        $this->validateModel($entry, lang('command_entries_not_saved'));

        $entry->save();
        $this->complete(lang('command_entries_saved'));
    }

}
