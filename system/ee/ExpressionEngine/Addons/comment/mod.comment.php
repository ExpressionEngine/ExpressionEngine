<?php

namespace ExpressionEngine\Addons\Comment;

use ExpressionEngine\Addons\Comment\Service\Notifications;
use ExpressionEngine\Addons\Comment\Service\Variables\Comment as CommentVars;

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Comment Module
 */
class Comment
{
    // Maximum number of comments.  This is a safety valve
    // in case the user doesn't specify a maximum
    public $limit = 100;

    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct()
    {
        $fields = array('name', 'email', 'url', 'location', 'comment');

        foreach ($fields as $val) {
            if (isset($_POST[$val])) {
                $_POST[$val] = ee()->functions->encode_ee_tags($_POST[$val], true);

                if ($val == 'comment') {
                    $_POST[$val] = ee('Security/XSS')->clean($_POST[$val]);
                }
            }
        }
    }

    /**
     * Retrieve the disable parameter from the template and parse it
     * out into the $enabled_features array.  Return that array. The
     * $enabled_features array is an array of boolean values keyed to
     * certain features of tag that can be disabled.  In the case of
     * the comment module, only pagination may currently be disabled.
     *
     * NOTE: This code is virtually identical to Channel::_fetch_disable_param()
     * it should be commonized somehow, but our current program structure
     * does not make this easy and putting it in a random cache of common
     * functions does not make sense.
     *
     * FIXME Commonlize this code in a logical way.
     *
     * @return array An array of enabled features of the form feature_key=>boolean
     */
    private function _fetch_disable_param()
    {
        $enabled_features = array('pagination' => true);

        // Get the disable parameter from the template.
        if ($disabled = ee()->TMPL->fetch_param('disable')) {
            // If we have more than one value, then
            // we need to break them out.
            if (strpos($disabled, '|') !== false) {
                foreach (explode('|', $disabled) as $feature) {
                    if (isset($enabled_features[$feature])) {
                        $enabled_features[$feature] = false;
                    }
                }
            }
            // Otherwise there's just one value and just
            // disable the one.
            elseif (isset($enabled_features[$disabled])) {
                $enabled_features[$disabled] = false;
            }
        }

        // Return our array.
        return $enabled_features;
    }

    /**
     * Get the time in seconds since the epoc at which a comment may
     * no longer be editted. If current time is past this value then
     * do not allow the user to edit it.
     *
     * @return int The time at which comment editing permission expires in seconds since the epoc.
     */
    private function _comment_edit_time_limit()
    {
        if (ee()->config->item('comment_edit_time_limit') == 0) {
            return 0;
        }

        $time_limit_sec = 60 * ee()->config->item('comment_edit_time_limit');

        return ee()->localize->now - $time_limit_sec;
    }

    /**
     * Comment Entries
     *
     * @access	public
     * @return	string
     */
    public function entries()
    {
        $return = '';
        $qstring = ee()->uri->query_string;
        $uristr = ee()->uri->uri_string;
        $switch = array();
        $search_link = '';
        $enabled = $this->_fetch_disable_param();

        if ($enabled['pagination']) {
            ee()->load->library('pagination');
            $pagination = ee()->pagination->create(__CLASS__);
        }

        $dynamic = get_bool_from_string(ee()->TMPL->fetch_param('dynamic', true));

        $force_entry = false;
        if (ee()->TMPL->fetch_param('author_id') !== false
            or ee()->TMPL->fetch_param('entry_id') !== false
            or ee()->TMPL->fetch_param('url_title') !== false
            or ee()->TMPL->fetch_param('comment_id') !== false) {
            $force_entry = true;
        }

        // A note on returns!
        // If dynamic is off - ANY no results triggers no_result template
        // If dynamic is on- we do not want to trigger no_results on a non-single entry page
        // so only trigger if no comment ids- not for no valid entry ids existing
        // Do not vary by force_entry setting

        /** ----------------------------------------------
        /**  Do we allow dynamic POST variables to set parameters?
        /** ----------------------------------------------*/
        if (ee()->TMPL->fetch_param('dynamic_parameters') !== false and isset($_POST) and count($_POST) > 0) {
            foreach (explode('|', ee()->TMPL->fetch_param('dynamic_parameters')) as $var) {
                if (isset($_POST[$var]) and in_array($var, array('channel', 'limit', 'sort', 'orderby'))) {
                    ee()->TMPL->tagparams[$var] = $_POST[$var];
                }
            }
        }

        /** --------------------------------------
        /**  Parse page number
        /** --------------------------------------*/

        // We need to strip the page number from the URL for two reasons:
        // 1. So we can create pagination links
        // 2. So it won't confuse the query with an improper proper ID

        if (! $dynamic) {
            if (preg_match("#(^|/)N(\d+)(/|$)#i", $qstring, $match)) {
                if ($enabled['pagination']) {
                    $pagination->current_page = $match['2'];
                }
                $uristr = trim(reduce_double_slashes(str_replace($match['0'], '/', $uristr)), '/');
            }
        } else {
            if (preg_match("#(^|/)P(\d+)(/|$)#", $qstring, $match)) {
                if ($enabled['pagination']) {
                    $pagination->current_page = $match['2'];
                }
                $uristr = reduce_double_slashes(str_replace($match['0'], '/', $uristr));
                $qstring = trim(reduce_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
            }
        }

        // Fetch channel_ids if appropriate
        $channel_ids = array();
        if ($channel = ee()->TMPL->fetch_param('channel') or ee()->TMPL->fetch_param('site')) {
            ee()->db->select('channel_id');
            ee()->db->where_in('site_id', ee()->TMPL->site_ids);
            if ($channel !== false) {
                ee()->functions->ar_andor_string($channel, 'channel_name');
            }

            $channels = ee()->db->get('channels');
            if ($channels->num_rows() == 0) {
                if (! $dynamic) {
                    return ee()->TMPL->no_results();
                }

                return false;
            } else {
                foreach ($channels->result_array() as $row) {
                    $channel_ids[] = $row['channel_id'];
                }
            }
        }

        // Fetch entry ids- we'll use them to make sure comments are to open, etc. entries
        $comment_id_param = false;
        if ($dynamic == true or $force_entry == true) {
            if ($force_entry == true) {
                // Check if an entry_id, url_title or comment_id was specified
                if ($entry_id = ee()->TMPL->fetch_param('entry_id')) {
                    ee()->functions->ar_andor_string($entry_id, 'entry_id');
                } elseif ($url_title = ee()->TMPL->fetch_param('url_title')) {
                    ee()->functions->ar_andor_string($url_title, 'url_title');
                } elseif ($comment_id_param = ee()->TMPL->fetch_param('comment_id')) {
                    $force_entry_ids = $this->fetch_comment_ids_param($comment_id_param);
                    if (count($force_entry_ids) == 0) {
                        // No entry ids for the comment ids?  How'd they manage that
                        if (! $dynamic) {
                            return ee()->TMPL->no_results();
                        }

                        return false;
                    }
                    ee()->db->where_in('entry_id', $force_entry_ids);
                }
            } else {
                // If there is a slash in the entry ID we'll kill everything after it.
                $entry_id = trim($qstring);
                $entry_id = preg_replace("#/.+#", "", $entry_id);

                // Have to choose between id or url title
                if (! is_numeric($entry_id)) {
                    ee()->db->where('url_title', $entry_id);
                } else {
                    ee()->db->where('entry_id', $entry_id);
                }
            }

            //  Do we have a valid entry ID number?
            $timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

            ee()->db->select('entry_id, channel_id');
            //ee()->db->where('channel_titles.channel_id = '.ee()->db->dbprefix('channels').'.channel_id');
            ee()->db->where_in('channel_titles.site_id', ee()->TMPL->site_ids);

            if (ee()->TMPL->fetch_param('show_expired') !== 'yes') {
                $date_where = "(" . ee()->db->protect_identifiers('expiration_date') . " = 0 OR "
                . ee()->db->protect_identifiers('expiration_date') . " > {$timestamp})";
                ee()->db->where($date_where);
            }

            if ($e_status = ee()->TMPL->fetch_param('entry_status')) {
                $e_status = str_replace('Open', 'open', $e_status);
                $e_status = str_replace('Closed', 'closed', $e_status);

                // If they don't specify closed, it defaults to it
                if (! in_array('closed', explode('|', $e_status))) {
                    ee()->db->where('status !=', 'closed');
                }

                ee()->functions->ar_andor_string($e_status, 'status');
            } else {
                ee()->db->where('status !=', 'closed');
            }

            //  Limit to/exclude specific channels
            if (count($channel_ids) == 1) {
                ee()->db->where('channel_titles.channel_id', $channel_ids['0']);
            } elseif (count($channel_ids) > 1) {
                ee()->db->where_in('channel_titles.channel_id', $channel_ids);
            }
            ee()->db->from('channel_titles');
            $query = ee()->db->get();

            // Bad ID?  See ya!
            if ($query->num_rows() == 0) {
                if (! $dynamic) {
                    return ee()->TMPL->no_results();
                }

                return false;
            }

            // We'll reassign the entry IDs so they're the true numeric ID
            foreach ($query->result_array() as $row) {
                $entry_ids[] = $row['entry_id'];
            }
        }

        if ($enabled['pagination']) {
            $pagination->per_page = ee()->TMPL->fetch_param('limit', $this->limit);
        } else {
            $limit = ee()->TMPL->fetch_param('limit', $this->limit);
        }

        //  Set sorting and limiting
        $sort = (! $dynamic)
            ? ee()->TMPL->fetch_param('sort', 'desc')
            : ee()->TMPL->fetch_param('sort', 'asc');

        $allowed_sorts = array('date', 'email', 'location', 'name', 'url');

        if ($enabled['pagination']) {
            // Capture the pagination template
            ee()->TMPL->tagdata = $pagination->prepare(ee()->TMPL->tagdata);
        }

        /** ----------------------------------------
        /**  Fetch comment ID numbers
        /** ----------------------------------------*/
        $temp = array();
        $i = 0;

        // Left this here for backward compatibility
        // We need to deprecate the "order_by" parameter

        if (ee()->TMPL->fetch_param('orderby') != '') {
            $order_by = ee()->TMPL->fetch_param('orderby');
        } else {
            $order_by = ee()->TMPL->fetch_param('order_by');
        }

        $random = ($order_by == 'random') ? true : false;
        $order_by = ($order_by == 'date' or ! in_array($order_by, $allowed_sorts)) ? 'comment_date' : $order_by;

        // We cache the query in case we need to do a count for dynamic off pagination
        ee()->db->start_cache();
        ee()->db->select('comment_date, comment_id');
        ee()->db->from('comments c');

        if ($status = ee()->TMPL->fetch_param('status')) {
            $status = strtolower($status);
            $status = str_replace('open', 'o', $status);
            $status = str_replace('closed', 'c', $status);
            $status = str_replace('pending', 'p', $status);

            ee()->functions->ar_andor_string($status, 'c.status');

            // No custom status for comments, so we can be leaner in check for 'c'
            if (stristr($status, "c") === false) {
                ee()->db->where('c.status !=', 'c');
            }
        } else {
            ee()->db->where('c.status', 'o');
        }

        if ($author_id = ee()->TMPL->fetch_param('author_id')) {
            ee()->db->where('c.author_id', $author_id);
        }

        // Note if it's not dynamic and the entry isn't forced?  We don't check on the entry criteria,
        // so this point, dynamic and forced entry will have 'valid' entry ids, dynamic off may not

        if (! $dynamic && ! $force_entry) {
            //  Limit to/exclude specific channels
            if (count($channel_ids) == 1) {
                ee()->db->where('c.channel_id', $channel_ids['0'], false);
            } elseif (count($channel_ids) > 1) {
                ee()->db->where_in('c.channel_id', $channel_ids);
            }

            ee()->db->join('channel_titles ct', 'ct.entry_id = c.entry_id');

            if ($e_status = ee()->TMPL->fetch_param('entry_status')) {
                $e_status = str_replace('Open', 'open', $e_status);
                $e_status = str_replace('Closed', 'closed', $e_status);

                // If they don't specify closed, it defaults to it
                if (! in_array('closed', explode('|', $e_status))) {
                    ee()->db->where('ct.status !=', 'closed');
                }

                ee()->functions->ar_andor_string($e_status, 'ct.status');
            } else {
                ee()->db->where('ct.status !=', 'closed');
            }

            // seems redundant given channels
            ee()->db->where_in('c.site_id', ee()->TMPL->site_ids, false);

            if (ee()->TMPL->fetch_param('show_expired') !== 'yes') {
                $timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

                $date_where = "(" . ee()->db->protect_identifiers('ct.expiration_date') . " = 0 OR "
                . ee()->db->protect_identifiers('ct.expiration_date') . " > {$timestamp})";
                ee()->db->where($date_where);
            }
        } else {
            // Force entry may result in multiple entry ids
            if (isset($entry_ids) && count($entry_ids) > 0) {
                ee()->db->where_in('c.entry_id', $entry_ids);
            } else {
                ee()->db->where('c.entry_id', $entry_id);
            }

            if ($comment_id_param) {
                ee()->functions->ar_andor_string($comment_id_param, 'comment_id');
            }
        }

        // -------------------------------------------
        // 'comment_entries_comment_ids_query' hook.
        //  - Manipulate the database object performing the query to gather IDs of comments to display
        //  - Added 3.1.0
        //
        if (ee()->extensions->active_hook('comment_entries_comment_ids_query') === true) {
            ee()->extensions->call('comment_entries_comment_ids_query', ee()->db);
            if (ee()->extensions->end_script === true) {
                return ee()->TMPL->tagdata;
            }
        }
        //
        // -------------------------------------------

        if ($enabled['pagination']) {
            if ($pagination->paginate === true) {
                // When we are only showing comments and it is not based on an
                // entry id or url title in the URL, we can make the query much
                // more efficient and save some work.
                $pagination->total_items = ee()->db->count_all_results();
            }

            // Determine the offset from the query string
            $pagination->prefix = (! $dynamic) ? 'N' : 'P';
        }

        $this_sort = ($random) ? 'random' : strtolower($sort);
        ee()->db->order_by($order_by, $this_sort);

        if ($enabled['pagination']) {
            $pagination->build($pagination->total_items, $pagination->per_page);

            ee()->db->limit($pagination->per_page, $pagination->offset);
        } else {
            ee()->db->limit($limit, 0);
        }

        ee()->db->stop_cache();
        $query = ee()->db->get();
        ee()->db->flush_cache();
        $result_ids = array();

        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $result_ids[] = $row->comment_id;
            }
        }

        //  No results?  No reason to continue...
        if (count($result_ids) == 0) {
            return ee()->TMPL->no_results();
        }

        // time to build a comments
        $comment_models = ee('Model')->get('Comment', $result_ids)
            ->with('Author', 'Channel', 'Entry')
            ->order($order_by, $this_sort)
            ->all();

        $comments = [];
        if (! empty($comment_models)) {
            foreach ($comment_models as $comment_model) {
                $comment_vars = new CommentVars(
                    $comment_model,
                    $this->getMemberFields(),
                    $this->getFieldsInTemplate()
                );
                $comments[] = $comment_vars->getTemplateVariables();
            }

            if (ee()->extensions->active_hook('comment_entries_query_result') === true) {
                $comments = ee()->extensions->call('comment_entries_query_result', $comments);
                if (ee()->extensions->end_script === true) {
                    return ee()->TMPL->tagdata;
                }
            }
        }

        ee()->TMPL->set_data($comments);

        /** ----------------------------------------
        /**  Parse It!
        /** ----------------------------------------*/
        $count = 0;
        $relative_count = 0;
        $total_results = count($comments);

        if ($enabled['pagination']) {
            $absolute_count = ($pagination->current_page == '') ? 0 : ($pagination->current_page - 1) * (int) $pagination->per_page;
            $total_displayed = $pagination->total_items;
        } else {
            $absolute_count = 0;
            $total_displayed = $total_results;
        }

        $vars = [];
        foreach ($comments as $comment) {
            ++$count;

            $absoluteCount = (isset($pagination))
                                ? $pagination->offset + $count
                                : $count;

            $vars[] = array_merge(
                [
                    'absolute_count' => $absoluteCount,
                    'absolute_results' => $total_results,
                    'absolute_reverse_count' => $total_results - $count + 1,
                    'count' => $count,
                    'reverse_count' => $total_results - $count + 1,
                    'total_comments' => $total_displayed,
                    'total_results' => $total_results,
                ],
                $comment
            );
        }
        
        // We could do this in one fell, performant swoop with:
        //
        // $tagdata = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
        //
        // But we have a legacy extension hook here that fires on EVERY row's tagdata...
        // So we need to loop it for now, deprecate it, and change/remove it in v5
        $return = '';

        // Custom parse {switch=} until we can use parse_variables()
        if (preg_match_all("/".LD."(switch\s*=.+?)".RD."/i", ee()->TMPL->tagdata, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $sparam = ee('Variables/Parser')->parseTagParameters($match[1]);
                if (isset($sparam['switch'])) {
                    $sopt = explode("|", $sparam['switch']);
                    $switch[$match[1]] = $sopt;
                }
            }
        }

        $count = 0;
        foreach ($vars as $variables) {
            $tagdata = ee()->TMPL->tagdata;

            // -------------------------------------------
            // 'comment_entries_tagdata' hook.
            //  - Modify and play with the tagdata before everyone else
            //
            if (ee()->extensions->active_hook('comment_entries_tagdata') === TRUE) {
                $tagdata = ee()->extensions->call('comment_entries_tagdata', $tagdata, $variables);
                if (ee()->extensions->end_script === TRUE) return $tagdata;
            }
            //
            // -------------------------------------------

            $count++;
            foreach ($switch as $key => $val) {
                $variables[$key] = $switch[$key][($count + count($val) -1) % count($val)];
            }
            $return .= ee()->TMPL->parse_variables_row($tagdata, $variables);
        }

        if ($enabled['pagination']) {
            return $pagination->render($return);
        } else {
            return $return;
        }
    }

    /**
     * Get and cache member field models, but only if the fields are present in the template
     * @return array Collection of MemberField models
     */
    private function getMemberFields()
    {
        $field_names = ee()->session->cache(__CLASS__, 'member_field_names');
        if ($field_names === false) {
            $field_names = ee('Model')->get('MemberField')
                ->fields('field_id', 'm_field_name', 'field_fmt')
                ->all()
                ->indexBy('field_name');

            ee()->session->set_cache(__CLASS__, 'member_field_names', $field_names);
        }

        // may not have any member fields, in which case we can skip the rest of this processing
        if (empty($field_names)) {
            return [];
        }

        // progressively cache member field models
        $member_fields = ee()->session->cache(__CLASS__, 'member_fields') ?: [];

        // Get fields present in the template, and not yet cached
        $member_field_ids = [];
        foreach ($this->getFieldsInTemplate() as $name => $fields) {
            if (isset($field_names[$name]) && ! isset($member_fields[$name])) {
                $member_field_ids[] = $field_names[$name]->getId();
            }
        }

        // fetch the missing member field models
        if (! empty($member_field_ids)) {
            $member_fields = $member_fields + ee('Model')->get('MemberField', $member_field_ids)
                ->all()
                ->indexBy('field_name');
        }

        ee()->session->set_cache(__CLASS__, 'member_fields', $member_fields);

        return $member_fields;
    }

    private function getFieldsInTemplate()
    {
        // contextual safety, ACT requests, etc.
        if (empty(ee()->TMPL)) {
            return [];
        }

        $cache_key = 'fields_in_use:' . md5(ee()->TMPL->tagdata);
        $fields = ee()->session->cache(__CLASS__, $cache_key) ?: [];

        if (! empty($fields)) {
            return $fields;
        }

        foreach (array_keys(ee()->TMPL->var_single) as $var) {
            $field = ee('Variables/Parser')->parseVariableProperties($var);

            // indexed by var name, but stored as an array since multiple modifiers or parameters may be in play
            $fields[$field['field_name']][] = $field;
        }

        ee()->session->set_cache(__CLASS__, $cache_key, $fields);

        return $fields;
    }

    /**
     * Fetch comment ids associated entry ids
     *
     * @access	public
     * @return	string
     */
    public function fetch_comment_ids_param($comment_id_param)
    {
        $entry_ids = array();

        ee()->db->distinct();
        ee()->db->select('entry_id');
        ee()->functions->ar_andor_string($comment_id_param, 'comment_id');
        $query = ee()->db->get('comments');

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $entry_ids[] = $row['entry_id'];
            }
        }

        return $entry_ids;
    }

    /**
     * Comment Submission Form
     *
     * @access	public
     * @return	string
     */
    public function form($return_form = false, $captcha = '')
    {
        $qstring = ee()->uri->query_string;
        $entry_where = array();
        $halt_processing = false;

        /** --------------------------------------
        /**  Remove page number
        /** --------------------------------------*/
        if (preg_match("#(^|/)P(\d+)(/|$)#", $qstring, $match)) {
            $qstring = trim(reduce_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
        }

        // Figure out the right entry ID
        // Order of precedence: POST, entry_id=, url_title=, $qstring
        if (isset($_POST['entry_id'])) {
            $entry_where = array('entry_id' => $_POST['entry_id']);
        } elseif ($entry_id = ee()->TMPL->fetch_param('entry_id')) {
            $entry_where = array('entry_id' => $entry_id);
        } elseif ($url_title = ee()->TMPL->fetch_param('url_title')) {
            $entry_where = array('url_title' => $url_title);
        } else {
            // If there is a slash in the entry ID we'll kill everything after it.
            $entry_id = trim($qstring);
            $entry_id = preg_replace("#/.+#", "", $entry_id);

            if (! is_numeric($entry_id)) {
                $entry_where = array('url_title' => $entry_id);
            } else {
                $entry_where = array('entry_id' => $entry_id);
            }
        }

        /** ----------------------------------------
        /**  Are comments allowed?
        /** ----------------------------------------*/
        if ($channel = ee()->TMPL->fetch_param('channel')) {
            ee()->db->select('channel_id');
            ee()->functions->ar_andor_string($channel, 'channel_name');
            ee()->db->where_in('site_id', ee()->TMPL->site_ids);
            $query = ee()->db->get('channels');

            if ($query->num_rows() == 0) {
                return false;
            }

            if ($query->num_rows() == 1) {
                ee()->db->where('channel_titles.channel_id', $query->row('channel_id'));
            } else {
                $ids = array();

                foreach ($query->result_array() as $row) {
                    $ids[] = $row['channel_id'];
                }

                ee()->db->where_in('channel_titles.channel_id', $ids);
            }
        }

        // The where clauses above will affect this query - it's below the conditional
        // because AR cannot keep track of two queries at once

        ee()->db->select('channel_titles.entry_id, channel_titles.entry_date, channel_titles.comment_expiration_date, channel_titles.allow_comments, channels.comment_system_enabled, channels.comment_expiration');
        ee()->db->from(array('channel_titles', 'channels'));

        ee()->db->where_in('channel_titles.site_id', ee()->TMPL->site_ids);
        ee()->db->where('channel_titles.channel_id = ' . ee()->db->dbprefix('channels') . '.channel_id');

        if ($e_status = ee()->TMPL->fetch_param('entry_status')) {
            $e_status = str_replace('Open', 'open', $e_status);
            $e_status = str_replace('Closed', 'closed', $e_status);

            ee()->functions->ar_andor_string($e_status, 'status');

            if (stristr($sql, "'closed'") === false) {
                ee()->db->where('status !=', 'closed');
            }
        } else {
            ee()->db->where('status !=', 'closed');
        }

        ee()->db->where($entry_where);

        $query = ee()->db->get();

        if ($query->num_rows() == 0) {
            return false;
        }

        if ($query->row('allow_comments') == 'n'
            or $query->row('comment_system_enabled') == 'n'
            or ee()->config->item('enable_comments') != 'y'
            or !ee('Permission')->can('post_comments')) {
            $halt_processing = 'disabled';
        }

        /** ----------------------------------------
        /**  Smart Notifications? Mark comments as read.
        /** ----------------------------------------*/
        if (ee()->session->userdata('smart_notifications') == 'y') {
            ee()->load->library('subscription');
            ee()->subscription->init('comment', array('entry_id' => $query->row('entry_id')), true);
            ee()->subscription->mark_as_read();
        }

        /** ----------------------------------------
        /**  Return the "no cache" version of the form
        /** ----------------------------------------*/
        if ($return_form == false) {
            if (! ee('Captcha')->shouldRequireCaptcha()) {
                ee()->TMPL->tagdata = str_replace(LD . 'captcha' . RD, '', ee()->TMPL->tagdata);
            }

            $nc = '';

            if (is_array(ee()->TMPL->tagparams) and count(ee()->TMPL->tagparams) > 0) {
                foreach (ee()->TMPL->tagparams as $key => $val) {
                    switch ($key) {
                        case 'form_class':
                            $nc .= 'class="' . $val . '" ';

                            break;
                        case 'form_id':
                            $nc .= 'id="' . $val . '" ';

                            break;
                        default:
                            $nc .= ' ' . $key . '="' . $val . '" ';
                    }
                }
            }

            return '{NOCACHE_COMMENT_FORM="' . $nc . '"}' . ee()->TMPL->tagdata . '{/NOCACHE_FORM}';
        }

        /** ----------------------------------------
        /**  Has commenting expired?
        /** ----------------------------------------*/

        //  First check whether expiration is overriden
        if (ee()->config->item('comment_moderation_override') !== 'y') {
            if ($query->row('comment_expiration_date') > 0) {
                if (ee()->localize->now > $query->row('comment_expiration_date')) {
                    $halt_processing = 'expired';
                }
            }
        }

        $tagdata = ee()->TMPL->tagdata;

        if ($halt_processing != false) {
            foreach (ee()->TMPL->var_cond as $key => $val) {
                if ($halt_processing == 'expired') {
                    if (isset($val['3']) && $val['3'] == 'comments_expired') {
                        return $val['2'];
                    }
                } elseif ($halt_processing == 'disabled') {
                    if (isset($val['3']) && $val['3'] == 'comments_disabled') {
                        return $val['2'];
                    }
                }
            }

            // If there is no conditional- just return the message
            ee()->lang->loadfile('comment');

            return ee()->lang->line('cmt_commenting_has_expired');
        }

        // -------------------------------------------
        // 'comment_form_tagdata' hook.
        //  - Modify, add, etc. something to the comment form
        //
        if (ee()->extensions->active_hook('comment_form_tagdata') === true) {
            $tagdata = ee()->extensions->call('comment_form_tagdata', $tagdata);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }
        //
        // -------------------------------------------

        /** ----------------------------------------
        /**  Conditionals
        /** ----------------------------------------*/
        $cond = array();
        $cond['logged_in'] = (ee()->session->userdata('member_id') == 0) ? false : true;
        $cond['logged_out'] = (ee()->session->userdata('member_id') != 0) ? false : true;

        $cond['captcha'] = ee('Captcha')->shouldRequireCaptcha();

        if ($cond['captcha'] && ee()->config->item('use_recaptcha') == 'y') {
            $tagdata = preg_replace("/{if captcha}.+?{\/if}/s", ee('Captcha')->create(), $tagdata);
        }

        $tagdata = ee()->functions->prep_conditionals($tagdata, $cond);

        /** ----------------------------------------
        /**  Single Variables
        /** ----------------------------------------*/

        // Load the form helper
        ee()->load->helper('form');

        foreach (ee()->TMPL->var_single as $key => $val) {
            /** ----------------------------------------
            /**  parse {name}
            /** ----------------------------------------*/
            if ($key == 'name') {
                $name = (ee()->session->userdata['screen_name'] != '') ? ee()->session->userdata['screen_name'] : ee()->session->userdata['username'];

                $name = (! isset($_POST['name'])) ? $name : $_POST['name'];

                $name = ee()->functions->encode_ee_tags($name, true);

                $tagdata = ee()->TMPL->swap_var_single($key, form_prep($name), $tagdata);
            }

            /** ----------------------------------------
            /**  parse {email}
            /** ----------------------------------------*/
            if ($key == 'email') {
                $email = (! isset($_POST['email'])) ? ee()->session->userdata['email'] : $_POST['email'];

                $email = ee()->functions->encode_ee_tags($email, true);

                $tagdata = ee()->TMPL->swap_var_single($key, form_prep($email), $tagdata);
            }

            /** ----------------------------------------
            /**  parse {url}
            /** ----------------------------------------*/
            if ($key == 'url') {
                $url = (! isset($_POST['url'])) ? ee()->session->userdata['url'] : $_POST['url'];

                $url = ee()->functions->encode_ee_tags($url, true);

                if ($url == '') {
                    $url = 'http://';
                }

                $tagdata = ee()->TMPL->swap_var_single($key, form_prep($url), $tagdata);
            }

            /** ----------------------------------------
            /**  parse {location}
            /** ----------------------------------------*/
            if ($key == 'location') {
                $location = (! isset($_POST['location'])) ? ee()->session->userdata['location'] : $_POST['location'];

                $location = ee()->functions->encode_ee_tags($location, true);

                $tagdata = ee()->TMPL->swap_var_single($key, form_prep($location), $tagdata);
            }

            /** ----------------------------------------
            /**  parse {comment}
            /** ----------------------------------------*/
            if ($key == 'comment') {
                $comment = (! isset($_POST['comment'])) ? '' : $_POST['comment'];

                $tagdata = ee()->TMPL->swap_var_single($key, $comment, $tagdata);
            }

            /** ----------------------------------------
            /**  parse {captcha_word}
            /** ----------------------------------------*/
            if ($key == 'captcha_word') {
                $tagdata = ee()->TMPL->swap_var_single($key, '', $tagdata);
            }

            /** ----------------------------------------
            /**  parse {save_info}
            /** ----------------------------------------*/
            if ($key == 'save_info') {
                $save_info = (! isset($_POST['save_info'])) ? '' : $_POST['save_info'];

                $notify = (! isset(ee()->session->userdata['notify_by_default'])) ? ee()->input->cookie('save_info') : ee()->session->userdata['notify_by_default'];

                $checked = (! isset($_POST['PRV'])) ? $notify : $save_info;

                $tagdata = ee()->TMPL->swap_var_single($key, ($checked == 'yes') ? "checked=\"checked\"" : '', $tagdata);
            }

            /** ----------------------------------------
            /**  parse {notify_me}
            /** ----------------------------------------*/
            if ($key == 'notify_me') {
                $checked = '';

                if (! isset($_POST['PRV'])) {
                    if (ee()->input->cookie('notify_me')) {
                        $checked = ee()->input->cookie('notify_me');
                    }

                    if (isset(ee()->session->userdata['notify_by_default'])) {
                        $checked = (ee()->session->userdata['notify_by_default'] == 'y') ? 'yes' : '';
                    }
                }

                if (isset($_POST['notify_me'])) {
                    $checked = $_POST['notify_me'];
                }

                $tagdata = ee()->TMPL->swap_var_single($key, ($checked == 'yes') ? "checked=\"checked\"" : '', $tagdata);
            }
        }

        /** ----------------------------------------
        /**  Create form
        /** ----------------------------------------*/
        $RET = ee('Encrypt')->encode(
            ee()->functions->fetch_current_uri(),
            ee()->config->item('session_crypt_key')
        );

        if (isset($_POST['RET'])) {
            // previews / post should already be encoded
            $RET = ee()->input->post('RET');
        } elseif (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") {
            $RET = ee('Encrypt')->encode(
                ee()->TMPL->fetch_param('return'),
                ee()->config->item('session_crypt_key')
            );
        }

        $PRV = (isset($_POST['PRV'])) ? $_POST['PRV'] : ee()->TMPL->fetch_param('preview');

        $hidden_fields = array(
            'ACT' => ee()->functions->fetch_action_id('Comment', 'insert_new_comment'),
            'RET' => $RET,
            'URI' => (ee()->uri->uri_string == '') ? 'index' : ee()->uri->uri_string,
            'PRV' => $PRV,
            'entry_id' => $query->row('entry_id')
        );

        if (ee('Captcha')->shouldRequireCaptcha()) {
            if (preg_match("/({captcha})/", $tagdata)) {
                $tagdata = preg_replace("/{captcha}/", ee('Captcha')->create(), $tagdata);
            }
        }

        // -------------------------------------------
        // 'comment_form_hidden_fields' hook.
        //  - Add/Remove Hidden Fields for Comment Form
        //
        if (ee()->extensions->active_hook('comment_form_hidden_fields') === true) {
            $hidden_fields = ee()->extensions->call('comment_form_hidden_fields', $hidden_fields);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }
        //
        // -------------------------------------------

        $uri_string = (ee()->uri->uri_string == '') ? 'index' : ee()->uri->uri_string;
        $url = ee()->functions->fetch_site_index(true) . $uri_string;

        $data = array(
            'action' => reduce_double_slashes($url),
            'hidden_fields' => $hidden_fields,
            'id' => (! isset(ee()->TMPL->tagparams['id'])) ? 'comment_form' : ee()->TMPL->tagparams['id'],
            'class' => (! isset(ee()->TMPL->tagparams['class'])) ? null : ee()->TMPL->tagparams['class']
        );

        if (ee()->TMPL->fetch_param('name') !== false &&
            preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('name'), $match)) {
            $data['name'] = ee()->TMPL->fetch_param('name');
        }

        $res = ee()->functions->form_declaration($data);

        $res .= stripslashes($tagdata);
        $res .= "</form>";

        // -------------------------------------------
        // 'comment_form_end' hook.
        //  - Modify, add, etc. something to the comment form at end of processing
        //
        if (ee()->extensions->active_hook('comment_form_end') === true) {
            $res = ee()->extensions->call('comment_form_end', $res);
            if (ee()->extensions->end_script === true) {
                return $res;
            }
        }
        //
        // -------------------------------------------

        return $res;
    }

    /**
     * Preview
     *
     * @access	public
     * @return	void
     */
    public function preview()
    {
        $entry_id = (isset($_POST['entry_id'])) ? $_POST['entry_id'] : ee()->uri->query_string;

        if (! is_numeric($entry_id) or empty($_POST['comment'])) {
            return false;
        }

        /** ----------------------------------------
        /**  Instantiate Typography class
        /** ----------------------------------------*/
        ee()->load->library('typography');
        ee()->typography->initialize(
            array(
                'parse_images' => false,
                'allow_headings' => false,
                'encode_email' => false,
                'word_censor' => (ee()->config->item('comment_word_censoring') == 'y') ? true : false)
        );

        ee()->db->select('channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls, channels.comment_max_chars');
        ee()->db->where('channel_titles.channel_id = ' . ee()->db->dbprefix('channels') . '.channel_id');
        ee()->db->where('channel_titles.entry_id', $entry_id);
        ee()->db->from(array('channels', 'channel_titles'));

        $query = ee()->db->get();

        if ($query->num_rows() == 0) {
            return '';
        }

        /** -------------------------------------
        /**  Check size of comment
        /** -------------------------------------*/
        if ($query->row('comment_max_chars') != '' and $query->row('comment_max_chars') != 0) {
            if (strlen($_POST['comment']) > $query->row('comment_max_chars')) {
                $str = str_replace("%n", strlen($_POST['comment']), ee()->lang->line('cmt_too_large'));

                $str = str_replace("%x", $query->row('comment_max_chars'), $str);

                return ee()->output->show_user_error('submission', $str);
            }
        }

        $formatting = 'none';

        if ($query->num_rows()) {
            $formatting = $query->row('comment_text_formatting') ;
        }

        $tagdata = ee()->TMPL->tagdata;

        // -------------------------------------------
        // 'comment_preview_tagdata' hook.
        //  - Play with the tagdata contents of the comment preview
        //
        if (ee()->extensions->active_hook('comment_preview_tagdata') === true) {
            $tagdata = ee()->extensions->call('comment_preview_tagdata', $tagdata);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }
        //
        // -------------------------------------------

        /** ----------------------------------------
        /**  Set defaults based on member data as needed
        /** ----------------------------------------*/
        $name = ee()->input->post('name', true);
        $email = ee()->input->post('email', true); // this is just for preview, actual submission will validate the email address
        $url = ee()->input->post('url', true);
        $location = ee()->input->post('location', true);

        if (ee()->session->userdata('member_id') != 0) {
            $name = ee()->session->userdata('screen_name') ? ee()->session->userdata('screen_name') : ee()->session->userdata('username');
            $email = ee()->session->userdata('email');
        }

        /** ----------------------------------------
        /**  Conditionals
        /** ----------------------------------------*/
        $cond = $_POST; // Sanitized on input and also in prep_conditionals, so no real worries here
        $cond['logged_in'] = (ee()->session->userdata('member_id') == 0) ? false : true;
        $cond['logged_out'] = (ee()->session->userdata('member_id') != 0) ? false : true;
        $cond['name'] = $name;
        $cond['email'] = $email;
        $cond['url'] = ($url == 'http://') ? '' : $url;
        $cond['location'] = $location;

        $tagdata = ee()->functions->prep_conditionals($tagdata, $cond);

        // Prep the URL

        if ($url != '') {
            ee()->load->helper('url');
            $url = ee('Format')->make('Text', $url)->url();
        }

        /** ----------------------------------------
        /**  Single Variables
        /** ----------------------------------------*/
        foreach (ee()->TMPL->var_single as $key => $val) {
            // Start with the simple ones
            if (in_array($key, array('name', 'email', 'url', 'location'))) {
                $tagdata = ee()->TMPL->swap_var_single($key, $$key, $tagdata);
            }

            //  {url_or_email}
            elseif ($key == "url_or_email") {
                $temp = $url;

                if ($temp == '' and $email != '') {
                    $temp = ee()->typography->encode_email($email, '', 0);
                }

                $tagdata = ee()->TMPL->swap_var_single($val, $temp, $tagdata);
            }

            //  {url_or_email_as_author}
            elseif ($key == "url_or_email_as_author") {
                if ($url != '') {
                    $tagdata = ee()->TMPL->swap_var_single($val, "<a href=\"" . $url . "\">" . $name . "</a>", $tagdata);
                } else {
                    if ($email != '') {
                        $tagdata = ee()->TMPL->swap_var_single($val, ee()->typography->encode_email($email, $name), $tagdata);
                    } else {
                        $tagdata = ee()->TMPL->swap_var_single($val, $name, $tagdata);
                    }
                }
            }

            //  {url_or_email_as_link}
            elseif ($key == "url_or_email_as_link") {
                if ($url != '') {
                    $tagdata = ee()->TMPL->swap_var_single($val, "<a href=\"" . $url . "\">" . $url . "</a>", $tagdata);
                } else {
                    if ($email != '') {
                        $tagdata = ee()->TMPL->swap_var_single($val, ee()->typography->encode_email($email), $tagdata);
                    } else {
                        $tagdata = ee()->TMPL->swap_var_single($val, $name, $tagdata);
                    }
                }
            }

            //  {url_as_author}

            elseif ($key == 'url_as_author') {
                if ($url != '') {
                    $tagdata = ee()->TMPL->swap_var_single($val, '<a href="' . $url . '">' . $name . '</a>', $tagdata);
                } else {
                    $tagdata = ee()->TMPL->swap_var_single($val, $name, $tagdata);
                }
            }

            /** ----------------------------------------
            /**  parse comment field
            /** ----------------------------------------*/
            elseif ($key == 'comment') {
                // -------------------------------------------
                // 'comment_preview_comment_format' hook.
                //  - Play with the tagdata contents of the comment preview
                //
                if (ee()->extensions->active_hook('comment_preview_comment_format') === true) {
                    $data = ee()->extensions->call('comment_preview_comment_format', $query->row());
                    if (ee()->extensions->end_script === true) {
                        return;
                    }
                } else {
                    $data = ee()->typography->parse_type(
                        ee()->input->post('comment'),
                        array(
                            'text_format' => $query->row('comment_text_formatting'),
                            'html_format' => $query->row('comment_html_formatting'),
                            'auto_links' => $query->row('comment_auto_link_urls'),
                            'allow_img_url' => $query->row('comment_allow_img_urls')
                        )
                    );
                }

                // -------------------------------------------

                $tagdata = ee()->TMPL->swap_var_single($key, $data, $tagdata);
            }

            /** ----------------------------------------
            /**  parse comment date
            /** ----------------------------------------*/
            $tagdata = ee()->TMPL->parse_date_variables($tagdata, array('comment_date' => ee()->localize->now));
        }

        return $tagdata;
    }

    /**
     * Preview Handler
     *
     * @access	public
     * @return	void
     */
    public function preview_handler()
    {
        if (ee()->input->post('PRV') == '') {
            $error[] = ee()->lang->line('cmt_no_preview_template_specified');

            return ee()->output->show_user_error('general', $error);
        }

        if (! isset($_POST['PRV']) or $_POST['PRV'] == '') {
            exit('Preview template not specified in your comment form tag');
        }
        // Clean return value- segments only
        $clean_return = str_replace(ee()->functions->fetch_site_index(), '', $_POST['RET']);

        $_POST['PRV'] = trim_slashes(ee('Security/XSS')->clean($_POST['PRV']));

        ee()->functions->clear_caching('all', $_POST['PRV']);
        ee()->functions->clear_caching('all', $clean_return);

        $preview = (! ee()->input->post('PRV')) ? '' : ee()->input->get_post('PRV');

        if (strpos($preview, '/') === false) {
            $preview = '';
        } else {
            $ex = explode("/", $preview);

            if (count($ex) != 2) {
                $preview = '';
            }
        }

        $group = ($preview = '') ? 'channel' : $ex[0];
        $templ = ($preview = '') ? 'preview' : $ex[1];

        // this makes sure the query string is seen correctly by tags on the template
        ee()->load->library('template', null, 'TMPL');
        ee()->TMPL->parse_template_uri();
        ee()->TMPL->run_template_engine($group, $templ);
    }

    /**
     * Insert New Comment
     *
     * @access public
     * @return void
     */
    public function insert_new_comment()
    {
        $default = array('name', 'email', 'url', 'comment', 'location', 'entry_id');

        foreach ($default as $val) {
            if (! isset($_POST[$val])) {
                $_POST[$val] = '';
            }
        }

        // No entry ID?  What the heck are they doing?
        if (! is_numeric($_POST['entry_id'])) {
            return false;
        }

        /** ----------------------------------------
        /**  Fetch the comment language pack
        /** ----------------------------------------*/
        ee()->lang->loadfile('comment');

        //  No comment- let's end it here
        if (trim($_POST['comment']) == '') {
            $error = ee()->lang->line('cmt_missing_comment');

            return ee()->output->show_user_error('submission', $error);
        }

        /** ----------------------------------------
        /**  Is the user banned?
        /** ----------------------------------------*/
        if (ee()->session->userdata['is_banned'] == true) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('not_authorized')));
        }

        /** ----------------------------------------
        /**  Is the IP address and User Agent required?
        /** ----------------------------------------*/
        if (ee()->config->item('require_ip_for_posting') == 'y') {
            if (ee()->input->ip_address() == '0.0.0.0' or ee()->session->userdata['user_agent'] == "") {
                return ee()->output->show_user_error('general', array(ee()->lang->line('not_authorized')));
            }
        }

        /** ----------------------------------------
        /**  Is the nation of the user banend?
        /** ----------------------------------------*/
        ee()->session->nation_ban_check();

        /** ----------------------------------------
        /**  Can the user post comments?
        /** ----------------------------------------*/
        if (! ee('Permission')->can('post_comments')) {
            $error[] = ee()->lang->line('cmt_no_authorized_for_comments');

            return ee()->output->show_user_error('general', $error);
        }

        /** ----------------------------------------
        /**  Blocked/Allowed List Check
        /** ----------------------------------------*/
        if (ee()->blockedlist->blocked == 'y' && ee()->blockedlist->allowed == 'n') {
            return ee()->output->show_user_error('general', array(ee()->lang->line('not_authorized')));
        }

        /** ----------------------------------------
        /**  Is this a preview request?
        /** ----------------------------------------*/
        if (isset($_POST['preview'])) {
            return $this->preview_handler();
        }

        // -------------------------------------------
        // 'insert_comment_start' hook.
        //  - Allows complete rewrite of comment submission routine.
        //  - Or could be used to modify the POST data before processing
        //
        ee()->extensions->call('insert_comment_start');
        if (ee()->extensions->end_script === true) {
            return;
        }
        //
        // -------------------------------------------

        /** ----------------------------------------
        /**  Fetch channel preferences
        /** ----------------------------------------*/

        // Bummer, saw the hook after converting the query
        /*
                ee()->db->select('channel_titles.title, channel_titles.url_title, channel_titles.channel_id, channel_titles.author_id,
                                channel_titles.comment_total, channel_titles.allow_comments, channel_titles.entry_date, channel_titles.comment_expiration_date,
                                channels.channel_title, channels.comment_system_enabled, channels.comment_max_chars,
                                channels.comment_timelock, channels.comment_require_membership, channels.comment_moderate, channels.comment_require_email,
                                channels.comment_notify, channels.comment_notify_authors, channels.comment_notify_emails, channels.comment_expiration'
                );

                ee()->db->from(array('channel_titles', 'channels'));
                ee()->db->where('channel_titles.channel_id = channels.channel_id');
                ee()->db->where('channel_titles.entry_id', $_POST['entry_id']);
                ee()->db->where('channel_titles.status', 'closed');
        */
        $sql = "SELECT exp_channel_titles.title,
                exp_channel_titles.url_title,
                exp_channel_titles.entry_id,
                exp_channel_titles.channel_id,
                exp_channel_titles.author_id,
                exp_channel_titles.allow_comments,
                exp_channel_titles.entry_date,
                exp_channel_titles.comment_expiration_date,
                exp_channels.channel_title,
                exp_channels.comment_system_enabled,
                exp_channels.comment_max_chars,
                exp_channels.comment_timelock,
                exp_channels.comment_require_membership,
                exp_channels.comment_moderate,
                exp_channels.comment_require_email,
                exp_channels.comment_notify,
                exp_channels.comment_notify_authors,
                exp_channels.comment_notify_emails,
                exp_channels.comment_expiration,
                exp_channels.channel_url,
                exp_channels.comment_url,
                exp_channels.site_id
            FROM	exp_channel_titles, exp_channels
            WHERE	exp_channel_titles.channel_id = exp_channels.channel_id
            AND	exp_channel_titles.entry_id = '" . ee()->db->escape_str($_POST['entry_id']) . "'";

        //  Added entry_status param, so it is possible to post to closed title
        //AND	exp_channel_titles.status != 'closed' ";

        // -------------------------------------------
        // 'insert_comment_preferences_sql' hook.
        //  - Rewrite or add to the comment preference sql query
        //  - Could be handy for comment/channel restrictions
        //
        if (ee()->extensions->active_hook('insert_comment_preferences_sql') === true) {
            $sql = ee()->extensions->call('insert_comment_preferences_sql', $sql);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }
        //
        // -------------------------------------------

        $query = ee()->db->query($sql);

        unset($sql);

        if ($query->num_rows() == 0) {
            return false;
        }

        /** ----------------------------------------
        /**  Are comments allowed?
        /** ----------------------------------------*/
        if ($query->row('allow_comments') == 'n' or $query->row('comment_system_enabled') == 'n') {
            return ee()->output->show_user_error('submission', ee()->lang->line('cmt_comments_not_allowed'));
        }

        /** ----------------------------------------
        /**  Has commenting expired?
        /** ----------------------------------------*/
        $force_moderation = $query->row('comment_moderate');

        if ($query->row('comment_expiration_date') > 0) {
            if (ee()->localize->now > $query->row('comment_expiration_date')) {
                if (ee()->config->item('comment_moderation_override') == 'y') {
                    $force_moderation = 'y';
                } else {
                    return ee()->output->show_user_error('submission', ee()->lang->line('cmt_commenting_has_expired'));
                }
            }
        }

        /** ----------------------------------------
        /**  Is there a comment timelock?
        /** ----------------------------------------*/
        if ($query->row('comment_timelock') != '' and $query->row('comment_timelock') > 0) {
            if (! ee('Permission')->isSuperAdmin()) {
                $time = ee()->localize->now - $query->row('comment_timelock') ;

                ee()->db->where('comment_date >', $time);
                ee()->db->where('ip_address', ee()->input->ip_address());

                $result = ee()->db->count_all_results('comments');

                if ($result > 0) {
                    return ee()->output->show_user_error('submission', str_replace("%s", $query->row('comment_timelock'), ee()->lang->line('cmt_comments_timelock')));
                }
            }
        }

        /** ----------------------------------------
        /**  Do we allow duplicate data?
        /** ----------------------------------------*/
        if (ee()->config->item('deny_duplicate_data') == 'y') {
            if (! ee('Permission')->isSuperAdmin()) {
                ee()->db->where('comment', $_POST['comment']);
                $result = ee()->db->count_all_results('comments');

                if ($result > 0) {
                    return ee()->output->show_user_error('submission', ee()->lang->line('cmt_duplicate_comment_warning'));
                }
            }
        }

        /** ----------------------------------------
        /**  Assign data
        /** ----------------------------------------*/
        $channel_id = $query->row('channel_id') ;
        $require_membership = $query->row('comment_require_membership');
        $comment_moderate = ee('Permission')->isSuperAdmin() ? 'n' : $force_moderation;
        if ($comment_moderate == 'y' && ee()->session->userdata('member_id') != 0) {
            // do we need to skip moderation based on ANY of member roles?
            $roleIds = ee()->session->getMember()->getAllRoles()->pluck('role_id');
            $excludeFromModeration = ee('Model')
                ->get('RoleSetting')
                ->filter('role_id', 'IN', $roleIds)
                ->filter('exclude_from_moderation', 'y')
                ->filter('site_id', ee()->config->item('site_id'))
                ->count();
            if ($excludeFromModeration > 0) {
                $comment_moderate = 'n';
            }
        }
        $entry_id = $query->row('entry_id');
        $comment_site_id = $query->row('site_id');

        $comment_string = ee('Security/XSS')->clean($_POST['comment']);

        // This may be verbose (it could be a simple ternary) but it reads better:
        // Super Admins are exempt from Spam checking.
        if (ee('Permission')->isSuperAdmin()) {
            $is_spam = false;
        } else {
            $is_spam = ee('Spam')->isSpam($comment_string);
        }

        if ($is_spam === true) {
            $comment_moderate = 'y';
        }

        /** ----------------------------------------
        /**  Start error trapping
        /** ----------------------------------------*/
        $error = array();

        if (ee()->session->userdata('member_id') != 0) {
            // If the user is logged in we'll reassign the POST variables with the user data

            $_POST['name'] = (ee()->session->userdata['screen_name'] != '') ? ee()->session->userdata['screen_name'] : ee()->session->userdata['username'];
            $_POST['email'] = ee()->session->userdata['email'];
            $_POST['url'] = (is_null(ee()->session->userdata['url'])) ? '' : ee()->session->userdata['url'];
            $_POST['location'] = (is_null(ee()->session->userdata['location'])) ? '' : ee()->session->userdata['location'];
        }

        /** ----------------------------------------
        /**  Is membership is required to post...
        /** ----------------------------------------*/
        if ($require_membership == 'y') {
            // Not logged in

            if (ee()->session->userdata('member_id') == 0) {
                return ee()->output->show_user_error('submission', ee()->lang->line('cmt_must_be_member'));
            }

            // Membership is pending

            if (ee()->session->getMember()->isPending()) {
                return ee()->output->show_user_error('general', ee()->lang->line('cmt_account_not_active'));
            }
        } else {
            /** ----------------------------------------
            /**  Missing name?
            /** ----------------------------------------*/
            if (trim($_POST['name']) == '') {
                $error[] = ee()->lang->line('cmt_missing_name');
            }

            /** -------------------------------------
            /**  Is name banned?
            /** -------------------------------------*/
            if (ee()->session->ban_check('screen_name', $_POST['name'])) {
                $error[] = ee()->lang->line('cmt_name_not_allowed');
            }

            // Let's make sure they aren't putting in funky html to bork our screens
            $_POST['name'] = str_replace(array('<', '>'), array('&lt;', '&gt;'), $_POST['name']);

            /** ----------------------------------------
            /**  Missing or invalid email address
            /** ----------------------------------------*/
            if ($query->row('comment_require_email') == 'y') {
                ee()->load->helper('email');

                if ($_POST['email'] == '') {
                    $error[] = ee()->lang->line('cmt_missing_email');
                } elseif (! valid_email($_POST['email'])) {
                    $error[] = ee()->lang->line('cmt_invalid_email');
                }
            }
        }

        /** -------------------------------------
        /**  Is email banned?
        /** -------------------------------------*/
        if ($_POST['email'] != '') {
            if (ee()->session->ban_check('email', $_POST['email'])) {
                $error[] = ee()->lang->line('cmt_banned_email');
            }
        }

        /** ----------------------------------------
        /**  Is comment too big?
        /** ----------------------------------------*/
        if ($query->row('comment_max_chars') != '' and $query->row('comment_max_chars') != 0) {
            if (strlen($_POST['comment']) > $query->row('comment_max_chars')) {
                $str = str_replace("%n", strlen($_POST['comment']), ee()->lang->line('cmt_too_large'));

                $str = str_replace("%x", $query->row('comment_max_chars'), $str);

                $error[] = $str;
            }
        }

        /** ----------------------------------------
        /**  Do we have errors to display?
        /** ----------------------------------------*/
        if (count($error) > 0) {
            return ee()->output->show_user_error('submission', $error);
        }

        /** ----------------------------------------
        /**  Do we require CAPTCHA?
        /** ----------------------------------------*/
        if (ee('Captcha')->shouldRequireCaptcha()) {
            if (! isset($_POST['captcha']) or $_POST['captcha'] == '') {
                $captcha_error = ee()->config->item('use_recaptcha') == 'y' ? ee()->lang->line('recaptcha_required') : ee()->lang->line('captcha_required');
                return ee()->output->show_user_error('submission', $captcha_error);
            } else {
                $captcha_error = ee()->config->item('use_recaptcha') == 'y' ? ee()->lang->line('recaptcha_required') : ee()->lang->line('captcha_incorrect');
                ee()->db->where('word', $_POST['captcha']);
                ee()->db->where('ip_address', ee()->input->ip_address());
                ee()->db->where('date > UNIX_TIMESTAMP()-7200', null, false);

                $result = ee()->db->count_all_results('captcha');

                if ($result == 0) {
                    return ee()->output->show_user_error('submission', $captcha_error);
                }

                // @TODO: AR
                ee()->db->query("DELETE FROM exp_captcha WHERE (word='" . ee()->db->escape_str($_POST['captcha']) . "' AND ip_address = '" . ee()->input->ip_address() . "') OR date < UNIX_TIMESTAMP()-7200");
            }
        }

        /** ----------------------------------------
        /**  Build the data array
        /** ----------------------------------------*/
        $notify = (ee()->input->post('notify_me')) ? 'y' : 'n';

        $cmtr_name = ee()->input->post('name', true);
        $cmtr_email = ee()->input->post('email');
        $cmtr_loc = ee()->input->post('location', true);
        $cmtr_url = ee()->input->post('url', true);
        $cmtr_url = (string) filter_var(ee('Format')->make('Text', $cmtr_url)->url(), FILTER_VALIDATE_URL);

        $data = array(
            'channel_id' => $channel_id,
            'entry_id' => $entry_id,
            'author_id' => ee()->session->userdata('member_id'),
            'name' => $cmtr_name,
            'email' => $cmtr_email,
            'url' => $cmtr_url,
            'location' => $cmtr_loc,
            'comment' => $comment_string,
            'comment_date' => ee()->localize->now,
            'ip_address' => ee()->input->ip_address(),
            'status' => ($comment_moderate == 'y') ? 'p' : 'o',
            'site_id' => $comment_site_id
        );

        if ($is_spam === true) {
            $data['status'] = 's';
        }

        // -------------------------------------------
        // 'insert_comment_insert_array' hook.
        //  - Modify any of the soon to be inserted values
        //
        if (ee()->extensions->active_hook('insert_comment_insert_array') === true) {
            $data = ee()->extensions->call('insert_comment_insert_array', $data);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }
        //
        // -------------------------------------------

        $RET = ee('Encrypt')->decode($_POST['RET'], ee()->config->item('session_crypt_key'));
        $return_link = (! stristr($RET, 'http://') && ! stristr($RET, 'https://')) ? ee()->functions->create_url($RET) : $RET;

        //  Insert data
        $comment = ee('Model')->make('Comment', $data)->save();
        $comment_id = $comment->getId();

        if ($is_spam == true) {
            ee('Spam')->moderate('comment', $comment, $comment->comment, $_POST['URI']);
        }

        // update their subscription
        if ($notify == 'y') {
            ee()->load->library('subscription');
            ee()->subscription->init('comment', array('entry_id' => $entry_id), true);

            if ($cmtr_id = ee()->session->userdata('member_id')) {
                ee()->subscription->subscribe($cmtr_id);
            } else {
                ee()->subscription->subscribe($cmtr_email);
            }
        }

        // send notifications
        if (! $is_spam) {
            $notification = new Notifications($comment, $_POST['URI']);
            $notification->sendAdminNotifications();

            if ($comment_moderate == 'n') {
                $notification->sendUserNotifications();
            }
        }

        /** ----------------------------------------
        /**  Set cookies
        /** ----------------------------------------*/
        if ($notify == 'y') {
            ee()->input->set_cookie('notify_me', 'yes', 31104000);
        } else {
            ee()->input->set_cookie('notify_me', 'no', 31104000);
        }

        if (ee()->input->post('save_info')) {
            ee()->input->set_cookie('save_info', 'yes', 31104000);
            ee('Cookie')->setSignedCookie('my_name', $_POST['name'], 31104000);
            ee('Cookie')->setSignedCookie('my_email', $_POST['email'], 31104000);
            ee('Cookie')->setSignedCookie('my_url', $_POST['url'], 31104000);
            ee('Cookie')->setSignedCookie('my_location', $_POST['location'], 31104000);
        } else {
            ee()->input->set_cookie('save_info', 'no', 31104000);
            ee()->input->delete_cookie('my_name');
            ee()->input->delete_cookie('my_email');
            ee()->input->delete_cookie('my_url');
            ee()->input->delete_cookie('my_location');
        }

        // -------------------------------------------
        // 'insert_comment_end' hook.
        //  - More emails, more processing, different redirect
        //  - $comment_id added in 1.6.1
        //
        ee()->extensions->call('insert_comment_end', $data, $comment_moderate, $comment_id);
        if (ee()->extensions->end_script === true) {
            return;
        }
        //
        // -------------------------------------------

        /** -------------------------------------------
        /**  Bounce user back to the comment page
        /** -------------------------------------------*/
        if ($comment_moderate == 'y') {
            $data = array(
                'title' => ee()->lang->line('cmt_comment_accepted'),
                'heading' => ee()->lang->line('thank_you'),
                'content' => ee()->lang->line('cmt_will_be_reviewed'),
                'redirect' => $return_link,
                'link' => array($return_link, ee()->lang->line('cmt_return_to_comments')),
                'rate' => 3
            );

            ee()->output->show_message($data);
        } else {
            ee()->functions->redirect($return_link);
        }
    }

    /**
     * Comment subscription tag
     *
     *
     * @access	public
     * @return	string
     */
    public function notification_links()
    {
        // Membership is required
        if (ee()->session->userdata('member_id') == 0) {
            return;
        }

        $entry_id = $this->_divine_entry_id();

        // entry_id is required
        if (! $entry_id) {
            return;
        }

        ee()->load->library('subscription');
        ee()->subscription->init('comment', array('entry_id' => $entry_id), true);
        $subscribed = ee()->subscription->is_subscribed(false);

        $action_id = ee()->functions->fetch_action_id('Comment', 'comment_subscribe');

        // Bleh- really need a conditional for if they are subscribed

        $sub_link = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id . '&entry_id=' . $entry_id . '&ret=' . ee()->uri->uri_string() .'&csrf_token=' . CSRF_TOKEN;
        $unsub_link = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id . '&entry_id=' . $entry_id . '&type=unsubscribe' . '&ret=' . ee()->uri->uri_string() . '&csrf_token=' . CSRF_TOKEN;

        $data[] = array('subscribe_link' => $sub_link, 'unsubscribe_link' => $unsub_link, 'subscribed' => $subscribed);

        $tagdata = ee()->TMPL->tagdata;

        return ee()->TMPL->parse_variables($tagdata, $data);
    }

    /**
     * List of subscribers to an entry
     *
     *
     * @access	public
     * @return	string
     */
    public function subscriber_list()
    {
        $entry_id = $this->_divine_entry_id();

        // entry is required, and this is not the same as "no results for a valid entry"
        // so return nada
        if (! $entry_id) {
            return;
        }

        $anonymous = true;
        if (ee()->TMPL->fetch_param('exclude_guests') == 'yes') {
            $anonymous = false;
        }

        ee()->load->library('subscription');
        ee()->subscription->init('comment', array('entry_id' => $entry_id), $anonymous);
        $subscribed = ee()->subscription->get_subscriptions(false, true);

        if (empty($subscribed)) {
            return ee()->TMPL->no_results();
        }

        // non-member comments will expose email addresses, so make sure the visitor should
        // be able to see this data before including it
        $expose_emails = (ee('Permission')->isSuperAdmin()) ? true : false;

        $vars = array();
        $total_results = count($subscribed);
        $count = 0;
        $guest_total = 0;
        $member_total = 0;

        foreach ($subscribed as $subscription_id => $subscription) {
            $vars[] = array(
                'subscriber_member_id' => $subscription['member_id'],
                'subscriber_screen_name' => $subscription['screen_name'],
                'subscriber_email' => (($expose_emails && $anonymous) ? $subscription['email'] : ''),
                'subscriber_is_member' => (($subscription['member_id'] == 0) ? false : true),
                'subscriber_count' => ++$count,
                'subscriber_total_results' => $total_results
            );

            if ($subscription['member_id'] == 0) {
                $guest_total++;
            } else {
                $member_total++;
            }
        }

        // loop through again to add the final guest/subscribed tallies
        foreach ($vars as $key => $val) {
            $vars[$key]['subscriber_guest_total'] = $guest_total;
            $vars[$key]['subscriber_member_total'] = $member_total;
        }

        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
    }

    /**
     * Comment subscription w/out commenting
     *
     *
     * @access public
     * @return void
     */
    public function comment_subscribe()
    {
        if (!bool_config_item('disable_csrf_protection') && ee()->input->get('csrf_token')!==CSRF_TOKEN) {
            show_error(lang('unauthorized_access'));
        }
        ee()->lang->loadfile('comment');

        $id = ee()->input->get('entry_id');
        $hash = ee()->input->get('hash');
        $type = (ee()->input->get('type')) ? 'unsubscribe' : 'subscribe';
        $ret = ee()->input->get('ret');

        // Membership is required unless hash is set
        if (ee()->session->userdata('member_id') == 0) {
            if ($type == 'subscribe') {
                return ee()->output->show_user_error('submission', ee()->lang->line('cmt_must_be_logged_in'));
            } elseif ($type == 'unsubscribe' && ! $hash) {
                return ee()->output->show_user_error('submission', ee()->lang->line('cmt_must_be_logged_in'));
            }
        }

        if (! $id) {
            return ee()->output->show_user_error('submission', 'invalid_subscription');
        }

        // Does entry exist?
        ee()->db->select('title');
        $query = ee()->db->get_where('channel_titles', array('entry_id' => $id));

        if ($query->num_rows() != 1) {
            return ee()->output->show_user_error('submission', 'invalid_subscription');
        }

        $row = $query->row();
        $entry_title = $row->title;

        // Are they currently subscribed

        ee()->load->library('subscription');
        ee()->subscription->init('comment', array('entry_id' => $id), true);
        $subscribed = ee()->subscription->is_subscribed(false);

        if ($type == 'subscribe' && $subscribed == true) {
            return ee()->output->show_user_error('submission', ee()->lang->line('already_subscribed'));
        }

        if ($type == 'unsubscribe' && $subscribed == false) {
            return ee()->output->show_user_error('submission', ee()->lang->line('not_currently_subscribed'));
        }

        // They check out- let them through
        if ($type == 'unsubscribe') {
            ee()->subscription->$type(false, $hash);
            $title = 'cmt_unsubscribe';
            $content = 'you_have_been_unsubscribed';
        } else {
            ee()->subscription->$type();
            $title = 'cmt_subscribe';
            $content = 'you_have_been_subscribed';
        }

        // Show success message
        ee()->lang->loadfile('comment');

        $redirect = ee()->functions->create_url($ret);

        if (! $ret) {
            $return_link = array($redirect,
                stripslashes(ee()->config->item('site_name')));
        } else {
            $return_link = array($redirect,
                ee()->lang->line('cmt_return_to_comments'));
        }

        $data = array(
            'title' => ee()->lang->line($title),
            'heading' => ee()->lang->line('thank_you'),
            'content' => ee()->lang->line($content) . ' ' . $entry_title,
            'redirect' => $redirect,
            'link' => $return_link,
            'rate' => 3
        );

        ee()->output->show_message($data);
    }

    /**
     * Frontend comment editing
     *
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function edit_comment($ajax_request = true)
    {
        /*
        This check is needed because otherwise, links could be created to CSRF and edit comments.
        */
        if (!bool_config_item('disable_csrf_protection') && ee()->input->get('csrf_token')!==CSRF_TOKEN) {
            show_error(lang('unauthorized_access'));
        }

        @header("Content-type: text/html; charset=UTF-8");
        $unauthorized = ee()->lang->line('not_authorized');

        if (ee()->input->get_post('comment_id') === false or ((ee()->input->get_post('comment') === false or ee()->input->get_post('comment') == '') && ee()->input->get_post('status') != 'close')) {
            ee()->output->send_ajax_response(['error' => $unauthorized]);
        }

        // Not logged in member- eject
        if (ee()->session->userdata['member_id'] == '0') {
            ee()->output->send_ajax_response(['error' => $unauthorized]);
        }

        $edited_status = (ee()->input->get_post('status') == 'close');
        $edited_comment = ee()->input->get_post('comment');
        $can_edit = false;
        $can_moderate = ee('Permission')->can('moderate_comments');

        $comment = ee('Model')->get('Comment', ee()->input->get_post('comment_id'))
            ->with('Author', 'Channel', 'Entry')
            ->first();

        if (! $comment) {
            ee()->output->send_ajax_response(['error' => $unauthorized]);
        }

        $comment_vars = new CommentVars(
            $comment,
            $this->getMemberFields(),
            $this->getFieldsInTemplate()
        );

        if ($edited_status && $comment_vars->getVariable('can_moderate_comment')) {
            $comment->status = 'c';
        }

        if ($edited_comment && $comment_vars->getVariable('editable')) {
            $comment->comment = $edited_comment;
        }

        // save if we changed something
        if ($comment->isDirty()) {
            $comment->edit_date = ee()->localize->now;
            $result = $comment->validate();

            // shouldn't happen since we fully control the modifications above, but if we make an error above
            // in the future, let's make debugging easier and reflect the model errors in the XHR
            if (! $result->isValid()) {
                ee()->output->send_ajax_response(['error' => $result->getAllErrors()]);
            }

            $comment->save();

            if ($edited_status) {
                // Send back the updated comment
                ee()->output->send_ajax_response(array('moderated' => ee()->lang->line('closed')));
            }

            // send back the new parsed comment
            $comment_vars = new CommentVars(
                $comment,
                $this->getMemberFields(),
                $this->getFieldsInTemplate()
            );

            // Send back the updated comment
            return ee()->output->send_ajax_response(['comment' => $comment_vars->getVariable('comment')]);
        }

        // d-fence
        return ee()->output->send_ajax_response(['error' => $unauthorized]);
    }

    /**
     * Edit Comment Script
     *
     * Outputs a script tag with an ACT request to the edit comment JavaScript
     *
     * @access	public
     * @return	type
     */
    public function edit_comment_script()
    {
        $src = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=comment_editor';

        return '<script type="text/javascript" charset="utf-8" src="' . $src . '"></script>';
    }

    /**
     * Comment Editor
     *
     * Outputs the JavaScript to edit comments on the front end, with JS headers.
     * Called via an action request by the exp:comment:edit_comment_script tag
     *
     * @access	public
     * @return	string
     */
    public function comment_editor()
    {
        $ajax_url = $this->ajax_edit_url();

        $script = <<<CMT_EDIT_SCR
$.fn.CommentEditor = function(options) {

    var OPT;

    OPT = $.extend({
        url: "{$ajax_url}",
        comment_body: '.comment_body',
        showEditor: '.edit_link',
        hideEditor: '.cancel_edit',
        saveComment: '.submit_edit',
        closeComment: '.mod_link'
    }, options);

    var view_elements = [OPT.comment_body, OPT.showEditor, OPT.closeComment].join(','),
        edit_elements = '.editCommentBox',
        csrf_token = '{csrf_token}';

    return this.each(function() {
        var id = this.id.replace('comment_', ''),
        parent = $(this);

        parent.find(OPT.showEditor).click(function(e) { e.preventDefault(); showEditor(id); });
        parent.find(OPT.hideEditor).click(function(e) { e.preventDefault(); hideEditor(id); });
        parent.find(OPT.saveComment).click(function(e) { e.preventDefault(); saveComment(id); });
        parent.find(OPT.closeComment).click(function(e) { e.preventDefault(); closeComment(id); });
    });

    function showEditor(id) {
        $("#comment_"+id)
            .find(view_elements).hide().end()
            .find(edit_elements).show().end();
    }

    function hideEditor(id) {
        $("#comment_"+id)
            .find(view_elements).show().end()
            .find(edit_elements).hide();
    }

    function closeComment(id) {
        var data = {status: "close", comment_id: id, csrf_token: csrf_token};

        $.post(OPT.url, data, function (res) {
            if (res.error) {
                return $.error('Could not moderate comment.');
            }

            $('#comment_' + id).hide();
       });
    }

    function saveComment(id) {
        var content = $("#comment_"+id).find('.editCommentBox'+' textarea').val(),
            data = {comment: content, comment_id: id, csrf_token: csrf_token};

        $.post(OPT.url, data, function (res) {
            if (res.error) {
                hideEditor(id);
                return $.error('Could not save comment.');
            }

            $("#comment_"+id).find('.comment_body').html(res.comment);
            hideEditor(id);
        });
    }
};


$(function() { $('.comment').CommentEditor(); });
CMT_EDIT_SCR;

        $script = ee()->functions->add_form_security_hash($script);
        $script = ee()->functions->insert_action_ids($script);

        ee()->output->enable_profiler(false);
        ee()->output->set_header("Content-Type: text/javascript");

        if (ee()->config->item('send_headers') == 'y') {
            ee()->output->send_cache_headers(strtotime(APP_BUILD));
            ee()->output->set_header('Content-Length: ' . strlen($script));
        }

        exit($script);
    }

    /**
     * AJAX Edit URL
     *
     * @access	public
     * @return	string
     */
    public function ajax_edit_url()
    {
        $url = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Comment', 'edit_comment') . '&csrf_token=' . CSRF_TOKEN;

        return $url;
    }

    /**
     * Discover the entry ID for the current entry
     *
     *
     * @access	private
     * @return	int
     */
    private function _divine_entry_id()
    {
        $entry_id = false;
        $qstring = ee()->uri->query_string;
        $qstring_hash = md5($qstring);

        if (isset(ee()->session->cache['comment']['entry_id'][$qstring_hash])) {
            return ee()->session->cache['comment']['entry_id'][$qstring_hash];
        }

        if (ee()->TMPL->fetch_param('entry_id')) {
            return ee()->TMPL->fetch_param('entry_id');
        } elseif (ee()->TMPL->fetch_param('url_title')) {
            $entry_seg = ee()->TMPL->fetch_param('url_title');
        } else {
            if (preg_match("#(^|/)P(\d+)(/|$)#", $qstring, $match)) {
                $qstring = trim(ee()->functions->remove_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
            }

            // Figure out the right entry ID
            // If there is a slash in the entry ID we'll kill everything after it.
            $entry_seg = trim($qstring);
            $entry_seg = preg_replace("#/.+#", "", $entry_seg);
        }

        if (is_numeric($entry_seg)) {
            $entry_id = $entry_seg;
        } else {
            ee()->db->select('entry_id');
            $query = ee()->db->get_where('channel_titles', array('url_title' => $entry_seg));

            if ($query->num_rows() == 1) {
                $row = $query->row();
                $entry_id = $row->entry_id;
            }
        }

        return ee()->session->cache['comment']['entry_id'][$qstring_hash] = $entry_id;
    }
}
// END CLASS

// EOF
