<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Jumps;

use CP_Controller;

/**
 * Member Create Controller
 */
class Publish extends Jumps
{
    public function __construct()
    {
        parent::__construct();
        if (!ee('Permission')->hasAny(['can_edit_other_entries', 'can_edit_self_entries'])) {
            $this->sendResponse([]);
        }
    }

    /**
     * Publish Jump Data
     */
    public function index()
    {
        // Should never be here without another segment.
        show_error(lang('unauthorized_access'), 403);
    }

    public function create()
    {
        $channels = $this->loadChannels(ee()->input->post('searchString'), true);

        $response = array();

        foreach ($channels as $channel) {
            $id = $channel->getId();
            $title = $channel->channel_title;

            $response['createEntryIn' . $channel->getId()] = array(
                'icon' => 'fa-plus',
                'command' => $channel->channel_title,
                'command_title' => $channel->channel_title,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('publish/create/' . $channel->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }

    public function view()
    {
        $channels = $this->loadChannels(ee()->input->post('searchString'));

        $response = array();

        foreach ($channels as $channel) {
            $id = $channel->getId();
            $title = $channel->channel_title;

            $response['viewEntriesIn' . $channel->getId()] = array(
                'icon' => 'fa-eye',
                'command' => $channel->channel_title,
                'command_title' => $channel->channel_title,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('publish/edit', array('filter_by_channel' => $channel->getId()))->compile()
            );
        }

        $this->sendResponse($response);
    }

    public function edit()
    {
        $entries = $this->loadEntries(ee()->input->post('searchString'));

        $response = array();

        foreach ($entries as $entry) {
            $id = $entry->getId();
            $title = $entry->title;

            $response['editEntry' . $entry->getId()] = array(
                'icon' => 'fa-pencil-alt',
                'command' => $entry->title,
                'command_title' => $entry->title,
                'command_context' => $entry->getChannel()->channel_title,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('publish/edit/entry/' . $entry->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }

    private function loadChannels($searchString = false, $can_create = false)
    {
        if (empty(ee()->functions->fetch_assigned_channels())) {
            return [];
        }

        $channels = ee('Model')->get('Channel')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('channel_id', 'IN', ee()->functions->fetch_assigned_channels());

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                $channels->filter('channel_title', 'LIKE', '%' . ee()->db->escape_like_str($keyword) . '%');
            }
        }

        $channels = $channels->order('channel_title', 'ASC')->limit(11)->all();

        if ($can_create) {
            foreach ($channels as $i => $channel) {
                if ($channel->maxEntriesLimitReached()) {
                    unset($channels[$i]);
                }
            }
        }

        return $channels;
    }

    private function loadEntries($searchString = false)
    {
        $channels = ee()->functions->fetch_assigned_channels();

        // Make sure we have channels before trying to load entries.
        if (empty($channels)) {
            return [];
        }

        $entries = ee('Model')->get('ChannelEntry')
            ->fields('entry_id', 'title')
            ->filter('channel_id', 'IN', $channels);

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                $entries->filter('title', 'LIKE', '%' . ee()->db->escape_like_str($keyword) . '%');
            }
        }

        return $entries->order('title', 'ASC')->limit(11)->all();
    }
}
