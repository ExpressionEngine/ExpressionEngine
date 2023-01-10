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
 * Channel Entries Model
 */
class Channel_entries_model extends CI_Model
{
    /**
     *
     */
    public function get_entry_data(array $entries)
    {
        $entry_data = ee('Model')->get('ChannelEntry', $entries)
            ->with('Channel', 'Author', 'Categories')
            ->all()
            ->getModChannelResultsArray();

        if (! is_array($entry_data)) {
            $entry_data = array();
        }

        if (ee('LivePreview')->hasEntryData()) {
            $data = ee('LivePreview')->getEntryData();
            $found = false;

            foreach ($entry_data as $i => $datum) {
                if ($datum['entry_id'] == $data['entry_id']) {
                    $entry_data[$i] = $data;
                    $found = true;

                    break;
                }
            }

            if (! $found) {
                $entry_data[] = $data;
            }
        }

        return $entry_data;
    }

    /**
     * Get Entries
     *
     * Gets all entry ids for a channel.  Other fields and where can be specified optionally
     *
     * @access	public
     * @param	int
     * @param	mixed	// single field, or array of fields
     * @param	array	// associative array of where
     * @return	object
     */
    public function get_entries($channel_id, $additional_fields = array(), $additional_where = array())
    {
        if (! is_array($additional_fields)) {
            $additional_fields = array($additional_fields);
        }

        if (count($additional_fields) > 0) {
            $this->db->select(implode(',', $additional_fields));
        }

        // default just fecth entry id's
        $this->db->select('entry_id');
        $this->db->from('channel_titles');

        // which channel id's?
        if (is_array($channel_id)) {
            $this->db->where_in('channel_id', $channel_id);
        } else {
            $this->db->where('channel_id', $channel_id);
        }

        // add additional WHERE clauses
        foreach ($additional_where as $field => $value) {
            if (is_array($value)) {
                $this->db->where_in($field, $value);
            } else {
                $this->db->where($field, $value);
            }
        }

        return $this->db->get();
    }

    /**
     * Fetch the channel data for one entry
     *
     * @access	public
     * @return	mixed
     */
    public function get_entry($entry_id, $channel_id = '', $autosave_entry_id = false)
    {
        if ($channel_id != '') {
            if ($autosave_entry_id) {
                $this->db->from('channel_entries_autosave AS t');
                $this->db->where('t.entry_id', $autosave_entry_id);
            } else {
                $this->db->select('t.*, d.*');
                $this->db->from('channel_titles AS t, channel_data AS d');
                $this->db->where('t.entry_id', $entry_id);
                $this->db->where('t.entry_id = d.entry_id', null, false);
            }

            $this->db->where('t.channel_id', $channel_id);
        } else {
            if ($autosave_entry_id) {
                $from = 'channel_entries_autosave';
                $entry_id_selection = $autosave_entry_id;
            } else {
                $from = 'channel_titles';
                $entry_id_selection = $entry_id;
            }

            $this->db->from($from);
            $this->db->select('channel_id, entry_id, author_id');
            $this->db->where('entry_id', $entry_id_selection);
        }

        return $this->db->get();
    }

    /**
     * Get most recent entries
     *
     * Gets all recently posted entries
     *
     * @access	public
     * @param	int
     * @return	object
     */
    public function get_recent_entries($limit = '10')
    {
        $allowed_channels = $this->functions->fetch_assigned_channels();

        if (count($allowed_channels) == 0) {
            return false;
        }

        $this->db->select(
            '
						channel_titles.channel_id,
						channel_titles.author_id,
						channel_titles.entry_id,
						channel_titles.title,
						channel_titles.comment_total'
        );
        $this->db->from('channel_titles, channels');
        $this->db->where('channels.channel_id = ' . $this->db->dbprefix('channel_titles.channel_id'));
        $this->db->where('channel_titles.site_id', $this->config->item('site_id'));

        if (! ee('Permission')->can('view_other_entries') and
             ! ee('Permission')->can('edit_other_entries') and
             ! ee('Permission')->can('delete_all_entries')) {
            $this->db->where('channel_titles.author_id', $this->session->userdata('member_id'));
        }

        $allowed_channels = $this->functions->fetch_assigned_channels();

        $this->db->where_in('channel_titles.channel_id', $allowed_channels);

        $this->db->limit($limit);
        $this->db->order_by('entry_date', 'DESC');

        return $this->db->get();
    }

    /**
     * Get recent commented entries
     *
     * Gets all entries with recently posted comments
     *
     * @access	public
     * @param	int
     * @return	object
     */
    public function get_recent_commented($limit = '10')
    {
        $this->db->select(
            '
						channel_titles.channel_id,
						channel_titles.author_id,
						channel_titles.entry_id,
						channel_titles.title,
						channel_titles.recent_comment_date'
        );
        $this->db->from('channel_titles, channels');
        $this->db->where('channels.channel_id = ' . $this->db->dbprefix('channel_titles.channel_id'));
        $this->db->where('channel_titles.site_id', $this->config->item('site_id'));

        if (! ee('Permission')->can('view_other_comments') and
             ! ee('Permission')->can('moderate_comments') and
             ! ee('Permission')->can('delete_all_comments') and
             ! ee('Permission')->can('edit_all_comments')) {
            $this->db->where('channel_titles.author_id', $this->session->userdata('member_id'));
        }

        $allowed_channels = $this->functions->fetch_assigned_channels();

        if (count($allowed_channels) > 0) {
            $this->db->where_in('channel_titles.channel_id', $allowed_channels);
            $this->db->where("recent_comment_date != ''");

            $this->db->limit($limit);
            $this->db->order_by("recent_comment_date", "desc");

            return $this->db->get();
        }

        return false;
    }

    /**
     * Prune Revisions
     *
     * Removes all revisions of an entry except for the $max latest
     *
     * @access	public
     * @param	int
     * @return	int
     */
    public function prune_revisions($entry_id, $max)
    {
        $this->db->where('entry_id', $entry_id);
        $count = $this->db->count_all_results('entry_versioning');

        if ($count > $max) {
            $this->db->select('version_id');
            $this->db->where('entry_id', $entry_id);
            $this->db->order_by('version_id', 'DESC');
            $this->db->limit($max);

            $query = $this->db->get('entry_versioning');

            $ids = array();
            foreach ($query->result_array() as $row) {
                $ids[] = $row['version_id'];
            }

            $this->db->where('entry_id', $entry_id);
            $this->db->where_not_in('version_id', $ids);
            $this->db->delete('entry_versioning');
            unset($ids);
        }
    }
}
// END CLASS

// EOF
