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
 * Channel Model
 */
class Channel_model extends CI_Model
{
    /**
     * Get Channels
     *
     * @access	public
     * @param	int
     * @return	mixed
     */
    public function get_channels($site_id = null, $fields = array(), $additional_where = array())
    {
        if (($site_id === null or ! is_numeric($site_id)) && $site_id != 'all') {
            $site_id = $this->config->item('site_id');
        } elseif ($site_id === 'all') {
            $this->db->order_by('site_id');
        }

        // If the user is restricted to specific channels, add that to the query
        if (! ee('Permission')->isSuperAdmin()) {
            $allowed_channels = $this->session->userdata('assigned_channels');

            if (! count($allowed_channels)) {
                return false;
            }

            $this->db->where_in('channel_title', $allowed_channels);
        }

        if (count($fields) > 0) {
            $this->db->select(implode(',', $fields));
        } else {
            $this->db->select('channel_title, channel_name, channel_id, cat_group');
        }

        foreach ($additional_where as $where) {
            foreach ($where as $field => $value) {
                if (is_array($value)) {
                    $this->db->where_in($field, $value);
                } else {
                    $this->db->where($field, $value);
                }
            }
        }

        if ($site_id != 'all') {
            $this->db->where('site_id', $site_id);
        }

        $this->db->order_by('channel_title');

        return $this->db->get('channels');
    }

    /**
     * Get Channel Menu
     *
     * @access	public
     * @param	int
     * @return	mixed
     */
    public function get_channel_menu($status_group, $cat_group, $field_group)
    {
        $this->db->select('channel_id, channel_title');
        $this->db->from('channels');
        $this->db->where('cat_group', $cat_group);
        $this->db->where('site_id', $this->config->item('site_id'));
        $this->db->order_by('channel_title');

        return $this->db->get();
    }

    /**
     * Get Channel Info
     *
     * Gets all metadata for a given Channel
     *
     * @access	public
     * @param	int
     * @return	mixed
     */
    public function get_channel_info($channel_id, $fields = array())
    {
        if (count($fields) > 0) {
            $this->db->select(implode(',', $fields));
        }

        $this->db->where('channel_id', $channel_id);

        return $this->db->get('channels');
    }

    /**
     * Get Channel Statuses
     *
     * Returns all information for a status
     *
     * @access	public
     * @param	int
     * @return	mixed
     */
    public function get_channel_statuses($status_group)
    {
        $this->db->order_by('status_order');

        return $this->db->get('statuses');
    }

    /**
     * Get Channel Fields
     *
     * Returns field information
     *
     * @access	public
     * @param	int
     * @return	mixed
     */
    public function get_channel_fields($field_group, $fields = array())
    {
        if (count($fields) > 0) {
            $this->db->select(implode(',', $fields));
        }

        $this->db->from('channel_fields');
        $this->db->where('group_id', $field_group);
        $this->db->order_by('field_order');

        return $this->db->get();
    }

    /**
     * Get Required Fields
     *
     * Returns required
     *
     * @access	public
     * @param	int
     * @return	mixed
     */
    public function get_required_fields($field_group)
    {
        $this->db->from('channel_fields');
        $this->db->where('group_id', $field_group);
        $this->db->where('field_required', 'y');
        $this->db->order_by('field_order');

        return $this->db->get();
    }

    /**
     * Get most recent entry/comment id
     *
     * @access	public
     * @param	string
     * @return	int
     */
    public function get_most_recent_id($type = 'entry')
    {
        if (! $allowed_channels = $this->session->userdata('assigned_channels')) {
            return false;
        }

        // By default we only grab the primary id
        $fields = array($type . '_id');
        $sort = $type . '_date';

        switch ($type) {
            case 'comment': $table = 'comments';

                break;
            case 'entry':
            default:
                $table = 'channel_titles';
                $fields[] = 'channel_id';
        }

        $this->db->select($fields)
            ->order_by($sort, 'DESC')
            ->where('site_id', $this->config->item('site_id'));

        if (! ee('Permission')->can('edit_other_entries')) {
            $this->db->where('author_id', $this->session->userdata('member_id'));
        }

        // Only return an entry from a channel the user has access to
        $entry = $this->db
            ->where_in('channel_id', array_keys($allowed_channels))
            ->get($table, 1);

        // Return the result if we found anything
        if ($entry->num_rows() > 0) {
            reset($fields);  // Needed due to a 5.2.1 bug (#40705)

            return (count($fields) > 1) ? $entry->row_array() : $entry->row(current($fields));
        }

        return false;
    }

    /**
     * Create Channel
     *
     * Inserts a new channel into the database
     *
     * @access	public
     * @param	array
     * @return	int
     */
    public function create_channel($data)
    {
        $this->db->insert('channels', $data);

        return $this->db->insert_id();
    }

    /**
     * Update Channel
     *
     * Updated an existing channel in the database
     *
     * @access	public
     * @param	array
     * @return	int
     */
    public function update_channel($data, $channel_id)
    {
        $this->db->where('channel_id', $channel_id);
        $this->db->update('channels', $data);

        return $this->db->affected_rows();
    }

    /**
     * Delete Channel
     *
     * Deletes a Channel and associated data
     *
     * @access	public
     * @param	int		// channel id
     * @param	array	// affected entry id's
     * @param	array	// affected member id's
     * @return	void
     */
    public function delete_channel($channel_id, $entries = array(), $authors = array())
    {
        $comments = false;

        $this->db->delete('channel_data', array('channel_id' => $channel_id));
        $this->db->delete('channel_titles', array('channel_id' => $channel_id));
        $this->db->delete('channels', array('channel_id' => $channel_id));

        if ($this->db->table_exists('comments')) {
            $comments = true;
            $this->db->delete('comments', array('channel_id' => $channel_id));
        }

        // delete Pages URIs for this Channel
        if (count($entries) > 0 && $this->config->item('site_pages') !== false) {
            $pages = $this->config->item('site_pages');

            if (count($pages[$this->config->item('site_id')]) > 0) {
                foreach ($entries as $entry_id) {
                    unset($pages[$this->config->item('site_id')]['uris'][$entry_id]);
                    unset($pages[$this->config->item('site_id')]['templates'][$entry_id]);
                }

                $this->config->set_item('site_pages', $pages);

                $this->db->where('site_id', $this->config->item('site_id'));
                $this->db->update('sites', array('site_pages' => base64_encode(serialize($pages))));
            }
        }

        // Just like a gossipy so-and-so, we will now destroy relationships! Category is also toast.
        if (count($entries) > 0) {
            // delete leftovers in category_posts
            $this->db->where_in('entry_id', $entries);
            $this->db->delete('category_posts');

            // delete parents
            $this->db->where_in('parent_id', $entries);
            $this->db->delete('relationships');

            // are there children?
            $this->db->select('relationship_id');
            $this->db->where_in('child_id', $entries);
            $child_results = $this->db->get('relationships');

            if ($child_results->num_rows() > 0) {
                // gather related fields
                $this->db->select('field_id');
                $this->db->where('field_type', 'rel');
                $fquery = $this->db->get('channel_fields');

                // We have children, so we need to do a bit of housekeeping
                // so parent entries don't continue to try to reference them
                $cids = array();

                foreach ($child_results->result_array() as $row) {
                    $cids[] = $row['relationship_id'];
                }

                foreach ($fquery->result_array() as $row) {
                    $this->db->where_in('field_id_' . $row['field_id'], $cids);
                    $this->db->update('channel_data', array('field_id_' . $row['field_id'] => 0));
                }
            }

            // aaaand delete
            $this->db->where_in('child_id', $entries);
            $this->db->delete('relationships');
        }

        // update author stats
        foreach ($authors as $author_id) {
            $this->db->where('author_id', $author_id);
            $total_entries = $this->db->count_all_results('channel_titles');
            $total_comments = 0;

            if ($comments) {
                $this->db->where('author_id', $author_id);
                $total_comments = $this->db->count_all_results('comments');
            }

            $this->db->where('member_id', $author_id);
            $this->db->update('members', array('total_entries' => $total_entries,'total_comments' => $total_comments));
        }

        // I can count my stats, 1 2 3
        $this->stats->update_channel_stats();
        $this->stats->update_comment_stats('', '', true);
    }

    /**
     * Update Comment Expiration
     *
     * Updates comment expiration for all entries for a channel
     *
     * @access	public
     * @param	int		channel id
     * @param	int		comment expiration
     * @return	int		affected rows
     */
    public function update_comment_expiration($channel_id, $comment_expiration, $reset_to_zero = false)
    {
        $this->db->where('channel_id', $channel_id);

        if ($reset_to_zero) {
            $this->db->set('comment_expiration_date', 0);
        } else {
            $this->db->set('comment_expiration_date', "(`entry_date` + {$comment_expiration})", false);
        }

        $this->db->update('channel_titles');

        return $this->db->affected_rows();
    }

    /**
     * Update Allowed Comments
     *
     * Updates allowed comments setting for all entries for a channel
     *
     * @access	public
     * @param	int		channel id
     * @param	int		comments allowed
     * @return	int		affected rows
     */
    public function update_comments_allowed($channel_id, $allow_comments)
    {
        $this->db->where('channel_id', $channel_id);

        if ($allow_comments == 'y') {
            $this->db->set('allow_comments', 'y');
        } else {
            $this->db->set('allow_comments', 'n');
        }

        $this->db->update('channel_titles');

        return $this->db->affected_rows();
    }

    /**
     * Clear Versioning Data
     *
     * @access	public
     * @param	int
     * @return	int
     */
    public function clear_versioning_data($channel_id)
    {
        $this->db->where('channel_id', $channel_id);
        $this->db->delete('entry_versioning');

        return $this->db->affected_rows();
    }

    /**
     * Generates SQL for a field search
     *
     * @param	string	Search terms from search parameter
     * @param	string	Database column name to search
     * @param	int		Site ID
     * @return	string	SQL to include in an existing query's WHERE clause
     */
    public function field_search_sql($terms, $col_name, $site_id = false)
    {
        $search_method = '_field_search';

        if (strncmp($terms, '=', 1) == 0) {
            // Remove the '=' sign that specified exact match.
            $terms = substr($terms, 1);

            $search_method = '_exact_field_search';
        } elseif (strncmp($terms, '<', 1) == 0 ||
                strncmp($terms, '>', 1) == 0) {
            $search_method = '_numeric_comparison_search';
        }

        return $this->$search_method($terms, $col_name, $site_id);
    }

    /**
     * Generate the SQL for a numeric comparison search
     * <, >, <=, >= operators
     *
     * search:field='>=20'
     * search:field='>3|<5'
     */
    private function _numeric_comparison_search($terms, $col_name, $site_id)
    {
        preg_match_all('/([<>]=?)\s?(\d+)/', $terms, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return $this->_field_search($terms, $col_name, $site_id);
        }

        $terms = array();

        foreach ($matches as $match) {
            // col_name >= 20
            $terms[] = "{$col_name} {$match[1]} {$match[2]}";
        }

        $site_id = ($site_id !== false) ? "( wd.site_id = {$site_id} AND " : '(';

        return $site_id . implode(' AND ', $terms) . ')';
    }

    /**
     * Generate the SQL for an exact query in field search.
     *
     * search:field="=words|other words"
     */
    private function _exact_field_search($terms, $col_name, $site_id = false)
    {
        // Did this because I don't like repeatedly checking
        // the beginning of the string with strncmp for that
        // 'not', much prefer to do it once and then set a
        // boolean.  But.. [cont:1]
        $not = false;
        $site_id = ($site_id !== false) ? 'wd.site_id=' . $site_id . ' AND ' : '';

        if (strncasecmp($terms, 'not ', 4) == 0) {
            $not = true;
            $terms = substr($terms, 4);
        }

        // Trivial case, we don't have special IS_EMPTY handling.
        if (strpos($terms, 'IS_EMPTY') === false) {
            $no_is_empty = substr(ee()->functions->sql_andor_string(($not ? 'not ' . $terms : $terms), $col_name), 3) . ' ';

            if ($not) {
                $no_is_empty = '(' . $no_is_empty . ' OR (' . $site_id . $col_name . ' IS NULL)) ';
            }

            return $no_is_empty;
        }

        if (strpos($terms, '|') !== false) {
            $terms = str_replace('IS_EMPTY|', '', $terms);
        } else {
            $terms = str_replace('IS_EMPTY', '', $terms);
        }

        $add_search = '';
        $conj = '';

        // If we have search terms, then we need to build the search.
        if (! empty($terms)) {
            // [cont:1]...it makes this a little hacky.  Gonna leave it for the moment,
            // but may come back to it.
            $add_search = ee()->functions->sql_andor_string(($not ? 'not ' . $terms : $terms), $col_name);
            // remove the first AND output by ee()->functions->sql_andor_string() so we can parenthesize this clause
            $add_search = '(' . $site_id . substr($add_search, 3) . ')';

            $conj = ($add_search != '' && ! $not) ? 'OR' : 'AND';
        }

        // If we reach here, we have an IS_EMPTY in addition to possible search terms.
        // Add the empty check condition.
        if ($not) {
            return $add_search . ' ' . $conj . ' ((' . $site_id . $col_name . ' != "") AND (' . $site_id . $col_name . ' IS NOT NULL))';
        }

        return $add_search . ' ' . $conj . ' ((' . $site_id . $col_name . ' = "") OR (' . $site_id . $col_name . ' IS NULL))';
    }

    /**
     * Generate the SQL for a LIKE query in field search.
     *
     * 		search:field="words|other words|IS_EMPTY"
     */
    private function _field_search($terms, $col_name, $site_id = false)
    {
        $not = '';
        if (strncasecmp($terms, 'not ', 4) == 0) {
            $terms = substr($terms, 4);
            $not = 'NOT';
        }

        if (strpos($terms, '&&') !== false) {
            $terms = explode('&&', $terms);
            $andor = $not == 'NOT' ? 'OR' : 'AND';
        } else {
            $terms = explode('|', $terms);
            $andor = $not == 'NOT' ? 'AND' : 'OR';
        }

        $site_id = ($site_id !== false) ? 'wd.site_id=' . $site_id . ' AND ' : '';

        $search_sql = '';
        $col_name = $site_id . $col_name;
        $empty = false;
        foreach ($terms as $term) {
            if ($search_sql !== '') {
                $search_sql .= $andor;
            }
            if ($term == 'IS_EMPTY') {
                $empty = true;
                // Empty string
                $search_sql .= ' (' . $col_name . ($not ? '!' : '') . '=""';
                // IS (NOT) NULL
                $search_sql .= $not ? ' AND ' : ' OR ';
                $search_sql .= $col_name . ' IS ' . ($not ?: '') . ' NULL) ';
            } elseif (strpos($term, '\W') !== false) { // full word only, no partial matches
                // Note: MySQL's nutty POSIX regex word boundary is [[:>:]]
                $term = '([[:<:]]|^)' . preg_quote(str_replace('\W', '', $term)) . '([[:>:]]|$)';

                $search_sql .= ' (' . $col_name . ' ' . $not . ' REGEXP "' . ee()->db->escape_str($term) . '") ';
            } else {
                $search_sql .= ' (' . $col_name . ' ' . $not . ' LIKE "%' . ee()->db->escape_like_str($term) . '%") ';
            }
        }

        if ($not && ! $empty) {
            $search_sql = '(' . $search_sql . ') OR (' . $col_name . ' IS NULL) ';
        }

        return $search_sql;
    }
}
// END CLASS

// EOF
