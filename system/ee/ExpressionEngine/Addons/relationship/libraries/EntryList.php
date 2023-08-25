<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Relationship EntryList
 */
class EntryList
{
    // Cache variables
    protected $channels = array();
    protected $entries = array();
    protected $children = array();

    public function query($settings, $selected = array())
    {
        $channels = array();
        $limit_channels = $settings['channels'];
        $limit_categories = $settings['categories'];
        $limit_statuses = $settings['statuses'];
        $limit_authors = $settings['authors'];
        $limit = $settings['limit'];
        $show_expired = (bool) $settings['expired'];
        $show_future = (bool) $settings['future'];
        $order_field = $settings['order_field'];
        $order_dir = $settings['order_dir'];
        $entry_id = $settings['entry_id'];
        $search = isset($settings['search']) ? $settings['search'] : null;
        $channel_id = isset($settings['channel_id']) ? $settings['channel_id'] : null;
        $related = isset($settings['related']) ? $settings['related'] : null;
        $show_selected = isset($settings['selected']) ? $settings['selected'] : null;

        // Create a cache ID based on the query criteria for this field so fields
        // with similar entry listings can share data that's already been queried
        $cache_id = md5(serialize(compact(
            'limit_channels',
            'limit_categories',
            'limit_statuses',
            'limit_authors',
            'limit',
            'show_expired',
            'show_future',
            'order_field',
            'order_dir'
        )));

        // Bug 19321, old fields use date
        if ($order_field == 'date') {
            $order_field = 'entry_date';
        }

        $entries = ee('Model')->get('ChannelEntry')
            ->with('Channel')
            ->fields('Channel.channel_title', 'title', 'status')
            ->order($order_field, $order_dir);

        if ($related == 'related') {
            $entries->filter('entry_id', 'IN', $show_selected);
        } elseif ($related == 'unrelated') {
            $entries->filter('entry_id', 'NOT IN', $show_selected);
        }

        if (! empty($search)) {
            if (is_numeric($search) && strlen($search) < 3) {
                $entries->filter('entry_id', $search);
            } else {
                $entries->search(['title', 'url_title', 'entry_id'], $search);
            }
        }

        if (! empty($channel_id) && is_numeric($channel_id)) {
            $entries->filter('channel_id', $channel_id);
        }

        // -------------------------------------------
        // 'relationships_display_field_options' hook.
        //  - Allow developers to add additional filters to the entries that populate the field options
        //
        if (ee()->extensions->active_hook('relationships_display_field_options') === true) {
            ee()->extensions->call(
                'relationships_display_field_options',
                $entries,
                $settings['field_id'],
                $settings
            );
        }

        if (count($limit_channels)) {
            $entries->filter('channel_id', 'IN', $limit_channels);
        }

        if (count($limit_categories)) {
            $entries->with('Categories')
                ->filter('Categories.cat_id', 'IN', $limit_categories);
        }

        if (count($limit_statuses)) {
            $limit_statuses = str_replace(
                array('Open', 'Closed'),
                array('open', 'closed'),
                $limit_statuses
            );

            $entries->filter('status', 'IN', $limit_statuses);
        }

        if (count($limit_authors)) {
            $roles = array();
            $members = array();

            foreach ($limit_authors as $author) {
                switch ($author[0]) {
                    case 'g': $roles[] = substr($author, 2);

                        break;
                    case 'm': $members[] = substr($author, 2);

                        break;
                }
            }

            if (count($roles)) {
                foreach (ee('Model')->get('Role', $roles)->all(true) as $role) {
                    $members = array_merge($role->getAllMembersData('member_id'), $members);
                }
            }

            $entries->with('Author');

            if (count($members)) {
                $entries->filter('author_id', 'IN', $members);
            }
        }

        // Limit times
        $now = ee()->localize->now;

        if (! $show_future) {
            $entries->filter('entry_date', '<', $now);
        }

        if (! $show_expired) {
            $entries->filterGroup()
                ->filter('expiration_date', 0)
                ->orFilter('expiration_date', '>', $now)
                ->endFilterGroup();
        }

        if ($entry_id) {
            $entries->filter('entry_id', '!=', $entry_id);
        }

        if ($limit) {
            $entries->limit($limit);
        }

        // If we've got a limit and selected entries, we need to run the query
        // twice. Once without those entries and then separately with only those
        // entries.

        if (count($selected) && $limit) {
            $selected_entries = clone $entries;

            $entries = $entries->filter('entry_id', 'NOT IN', $selected)->all();

            $selected_entries->limit(count($selected))
                ->filter('entry_id', 'IN', $selected)
                ->all()
                ->map(function ($entry) use (&$entries) {
                    $entries[] = $entry;
                });

            $entries = $entries->sortBy($order_field);
        } else {
            // Don't query if we have this same query in the cache
            if (isset($this->entries[$cache_id])) {
                $entries = $this->entries[$cache_id];
            } else {
                $this->entries[$cache_id] = $entries = $entries->all();
            }
        }

        return $entries;
    }

    /**
     * Used to filter the entry choices in a relationship field
     */
    public function ajaxFilter()
    {
        $settings = ee('Encrypt')->decode(
            ee('Request')->get('settings'),
            ee()->config->item('session_crypt_key')
        );
        $settings = json_decode($settings, true);

        if (empty($settings)) {
            show_error(lang('unauthorized_access'), 403);
        }

        $settings['search'] = ee('Request')->isPost() ? ee('Request')->post('search') : ee('Request')->get('search');
        $settings['channel_id'] = ee('Request')->isPost() ? ee('Request')->post('channel_id') : ee('Request')->get('channel_id');
        $settings['related'] = ee('Request')->isPost() ? ee('Request')->post('related') : ee('Request')->get('related');
        $settings['selected'] = ee('Request')->isPost() ? ee('Request')->post('selected') : ee('Request')->get('selected');

        if (! AJAX_REQUEST or ! ee()->session->userdata('member_id')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $response = array();
        foreach ($this->query($settings) as $entry) {
            $response[] = [
                'value' => $entry->getId(),
                'label' => $entry->title,
                'instructions' => $entry->Channel->channel_title,
                'channel_id' => $entry->Channel->channel_id,
                'editable' => (ee('Permission')->isSuperAdmin() || array_key_exists($entry->Channel->getId(), ee()->session->userdata('assigned_channels'))),
                'status' => $entry->status
            ];
        }

        return $response;
    }
}

// EOF
