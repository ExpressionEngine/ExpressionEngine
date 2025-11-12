<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Advisor;

class EntryAdvisor
{

    /**
     * Get entries that do not have certain related record
     *
     * @return void
     */
    public function getEntriesMissingData($what = 'channel')
    {
        switch ($what) {
            case 'channel':
                $table = 'channels';
                $column = 'channel_id';
                $listQuery = ee()->db->select($column)->where('site_id', ee()->config->item('site_id'))->get($table);
                break;
            case 'status':
                $table = 'statuses';
                $column = 'status_id';
                $listQuery = ee()->db->select($column)->get($table);
                break;
        }
        $listIds = array_column($listQuery->result_array(), $column);
        $entriesQuery = ee()->db->select('entry_id, title, ' . $column)->where('site_id', ee()->config->item('site_id'))->where($column, 'NOT IN', $listIds)->get('channel_titles');
        $data = [];
        foreach ($entriesQuery->result_array() as $entry) {
            $data[$entry['entry_id']] = $entry['title'];
        }

        return $data;
    }

    /**
     * Get entries that do not have author record
     *
     * @return void
     */
    public function getEntriesMissingAuthor()
    {
        $entriesQuery = ee()->db->query('SELECT entry_id, title, author_id FROM exp_channel_titles WHERE site_id=' . ee()->config->item('site_id') . ' AND NOT EXISTS (SELECT member_id FROM exp_members WHERE exp_members.member_id=exp_channel_titles.author_id)');
        $data = [];
        foreach ($entriesQuery->result_array() as $entry) {
            $data[$entry['entry_id']] = $entry['title'];
        }

        return $data;
    }
}

// EOF
