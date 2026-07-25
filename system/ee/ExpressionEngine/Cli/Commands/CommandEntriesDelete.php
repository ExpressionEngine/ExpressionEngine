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
 * Command to delete an entry
 */
class CommandEntriesDelete extends Cli
{
    use CliOptionsTrait;

    /**
     * name of command
     * @var string
     */
    public $name = 'Delete Entry';

    /**
     * signature of command
     * @var string
     */
    public $signature = 'entries:delete';

    /**
     * How to use command
     * @var string
     */
    public $usage = 'php eecli.php entries:delete --entry_id=<entry_id> [--force]';

    /**
     * options available for use in command
     * @var array
     */
    public $commandOptions = [
        'site,s:' => 'command_category_groups_list_option_site',
        'channel,c:' => 'command_entries_list_option_channel',
        'entry_id,i:' => 'command_entries_entry_id',
        'force,f' => 'command_sites_force_delete',
    ];

    protected $data = [];

    /**
     * Run the command
     * @return mixed
     */
    public function handle()
    {
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
            'desc' => 'command_entries_delete_ask',
            'choices' => $entries,
            'default' => null,
            'required' => true
        ]);

        if (!array_key_exists($this->data['entry_id'], $entries)) {
            $this->fail(lang('command_entries_not_found'));
        }

        $entry = ee('Model')->get('ChannelEntry', $this->data['entry_id'])->first();
        $confirmation = $this->option('--force', false);
        if (!$confirmation) {
            $confirmation = $this->confirm(sprintf(lang('command_entries_delete_confirm'), $entry->title), false);
        }
        if (!$confirmation) {
            $this->fail(lang('command_entries_not_deleted'));
        }

        $this->info(lang('command_entries_deleting_entry'));

        $entry->delete();

        $this->complete(lang('command_entries_deleted'));
    }
}
