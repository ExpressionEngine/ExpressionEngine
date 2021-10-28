<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Search Module
 */
class Search
{
    public $min_length = 3;			// Minimum length of search keywords
    public $max_length = 60;			// Maximum length of search keywords (logged to varchar(60))...
    public $cache_expire = 2;			// How many hours should we keep search caches?
    public $keywords = "";
    public $terms = [];
    public $text_format = 'xhtml';		// Excerpt text formatting
    public $html_format = 'all';		// Excerpt html formatting
    public $auto_links = 'y';			// Excerpt auto-linking: y/n
    public $allow_img_url = 'n';			// Excerpt - allow images:  y/n
    public $channel_array = array();
    public $cat_array = array();
    public $fields = array();
    public $num_rows = 0;
    public $hash = "";

    protected $_meta = array();
    protected $custom_fields = [];

    /**
     * Do Search
     */
    public function do_search()
    {
        ee()->lang->loadfile('search');

        // Get hidden meta vars
        if (isset($_POST['meta'])) {
            $this->_get_meta_vars();
        }

        /** ----------------------------------------
        /**  Profile Exception
        /** ----------------------------------------*/

        // This is an exception to the normal search routine.
        // It permits us to search for all posts by a particular user's screen name
        // We look for the "mbr" $_GET variable.  If it exsists it will
        // trigger our exception

        if (ee()->input->get_post('mbr')) {
            $this->_meta['result_page'] = (ee()->input->get_post('result_path') != '') ? ee()->input->get_post('result_path') : 'search/results';
            $_POST['keywords'] = '';
            $_POST['exact_match'] = 'y';
            $_POST['exact_keyword'] = 'n';
            $this->_meta['site_ids'] = array(ee()->config->item('site_id'));
        }

        // RP can be used in a query string,
        // so we need to clean it a bit

        $this->_meta['result_page'] = str_replace(array('=', '&'), '', $this->_meta['result_page']);

        /** ----------------------------------------
        /**  Pulldown Addition - Any, All, Exact
        /** ----------------------------------------*/
        if (isset($this->_meta['where']) && $this->_meta['where'] == 'exact') {
            $_POST['exact_keyword'] = 'y';
        }

        /** ----------------------------------------
        /**  Do we have a search results page?
        /** ----------------------------------------*/

        // The search results template is specified as a parameter in the search form tag.
        // If the parameter is missing we'll issue an error since we don't know where to
        // show the results

        if (! isset($this->_meta['result_page']) or $this->_meta['result_page'] == '') {
            return ee()->output->show_user_error('general', array(lang('search_path_error')));
        }

        /** ----------------------------------------
        /**  Is the current user allowed to search?
        /** ----------------------------------------*/
        if (! ee('Permission')->can('search') and ! ee('Permission')->isSuperAdmin()) {
            return ee()->output->show_user_error('general', array(lang('search_not_allowed')));
        }

        /** ----------------------------------------
        /**  Flood control
        /** ----------------------------------------*/
        if (ee()->session->userdata['search_flood_control'] > 0 and ! ee('Permission')->isSuperAdmin()) {
            $cutoff = time() - ee()->session->userdata['search_flood_control'];

            // Only checking current site searches
            $sql = "SELECT search_id FROM exp_search WHERE site_id = '" . ee()->db->escape_str(ee()->config->item('site_id')) . "' AND search_date > '{$cutoff}' AND ";

            if (ee()->session->userdata['member_id'] != 0) {
                $sql .= "(member_id='" . ee()->db->escape_str(ee()->session->userdata('member_id')) . "' OR ip_address='" . ee()->db->escape_str(ee()->input->ip_address()) . "')";
            } else {
                $sql .= "ip_address='" . ee()->db->escape_str(ee()->input->ip_address()) . "'";
            }

            $query = ee()->db->query($sql);

            $text = str_replace("%x", ee()->session->userdata['search_flood_control'], lang('search_time_not_expired'));

            if ($query->num_rows() > 0) {
                return ee()->output->show_user_error('general', array($text));
            }
        }

        /** ----------------------------------------
        /**  Did the user submit any keywords?
        /** ----------------------------------------*/

        // We only require a keyword if the member name field is blank

        if (! isset($_GET['mbr']) or ! is_numeric($_GET['mbr'])) {
            if (! isset($_POST['member_name']) or $_POST['member_name'] == '') {
                if (! isset($_POST['keywords']) or $_POST['keywords'] == "") {
                    return ee()->output->show_user_error('general', array(lang('search_no_keywords')));
                }
            }
        }

        /** ----------------------------------------
        /**  Strip extraneous junk from keywords
        /** ----------------------------------------*/
        if ($_POST['keywords'] != "") {
            // Load the search helper so we can filter the keywords
            ee()->load->helper('search');

            // If the search terms are too long to log we'll toss an error. We do this
            // before sanitizing because with a long enough input that process can take
            // enough time to be a DDoS attack point. :sigh:
            if (strlen($_POST['keywords']) > $this->max_length) {
                $text = lang('search_max_length');

                $text = str_replace("%x", $this->max_length, $text);

                return ee()->output->show_user_error('general', array($text));
            }

            $this->keywords = sanitize_search_terms($_POST['keywords']);

            /** ----------------------------------------
            /**  Is the search term long enough?
            /** ----------------------------------------*/
            if (strlen($this->keywords) < $this->min_length) {
                $text = lang('search_min_length');

                $text = str_replace("%x", $this->min_length, $text);

                return ee()->output->show_user_error('general', array($text));
            }

            // Load the text helper
            ee()->load->helper('text');

            $this->keywords = (ee()->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($this->keywords) : $this->keywords;

            /** ----------------------------------------
            /**  Remove "ignored" words
            /** ----------------------------------------*/
            $ignore = ee()->config->loadFile('stopwords');

            if ((! isset($_POST['exact_keyword']) or $_POST['exact_keyword'] != 'y')) {
                $parts = explode('"', $this->keywords);

                $this->keywords = '';

                foreach ($parts as $num => $part) {
                    // The odd breaks contain quoted strings.
                    if ($num % 2 == 0) {
                        foreach ($ignore as $badword) {
                            $part = preg_replace("/\b" . preg_quote($badword, '/') . "\b/iu", "", $part);
                        }
                    }

                    $this->keywords .= ($num != 0) ? '"' . $part : $part;
                }

                if (trim($this->keywords) == '') {
                    return ee()->output->show_user_error('general', array(lang('search_no_stopwords')));
                }
            }

            /** ----------------------------------------
            /**  Log Search Terms
            /** ----------------------------------------*/
            ee()->functions->log_search_terms($this->keywords);
        }

        if (isset($_POST['member_name']) and $_POST['member_name'] != "") {
            $_POST['member_name'] = ee('Security/XSS')->clean($_POST['member_name']);
        }

        /** ----------------------------------------
        /**  Build and run query
        /** ----------------------------------------*/
        $original_keywords = $this->keywords;
        $mbr = (! isset($_GET['mbr'])) ? '' : $_GET['mbr'];

        $this->hash = ee()->functions->random('md5');

        $query_parts = $this->getAllQueryParts();

        /** ----------------------------------------
        /**  No query results?
        /** ----------------------------------------*/
        if ($query_parts == false) {
            if (isset($this->_meta['no_results_page']) and $this->_meta['no_results_page'] != '') {
                $data = array(
                    'search_id' => $this->hash,
                    'search_date' => time(),
                    'member_id' => ee()->session->userdata('member_id'),
                    'keywords' => ($original_keywords != '') ? $original_keywords : $mbr,
                    'ip_address' => ee()->input->ip_address(),
                    'total_results' => 0,
                    'per_page' => 0,
                    'query' => '',
                    'custom_fields' => '',
                    'result_page' => '',
                    'site_id' => ee()->config->item('site_id')
                );

                ee()->db->query(ee()->db->insert_string('exp_search', $data));

                return ee()->functions->redirect(ee()->functions->create_url(ee()->functions->extract_path("='" . $this->_meta['no_results_page'] . "'")) . '/' . $this->hash . '/');
            } else {
                return ee()->output->show_user_error('off', array(lang('search_no_result')), lang('search_result_heading'));
            }
        }

        /** ----------------------------------------
        /**  If we have a result, cache it
        /** ----------------------------------------*/
        $data = array(
            'search_id' => $this->hash,
            'search_date' => time(),
            'member_id' => ee()->session->userdata('member_id'),
            'keywords' => ($original_keywords != '') ? $original_keywords : $mbr,
            'ip_address' => ee()->input->ip_address(),
            'total_results' => $this->num_rows,
            'per_page' => (isset($_POST['RES']) and is_numeric($_POST['RES']) and $_POST['RES'] < 999) ? $_POST['RES'] : 50,
            'query' => serialize($query_parts),
            'custom_fields' => addslashes(serialize($this->fields)),
            'result_page' => $this->_meta['result_page'],
            'site_id' => ee()->config->item('site_id')  // site search was made from
        );

        ee()->db->query(ee()->db->insert_string('exp_search', $data));

        /** ----------------------------------------
        /**  Redirect to search results page
        /** ----------------------------------------*/
        $path = reduce_double_slashes(
            ee()->functions->create_url(
                trim_slashes($this->_meta['result_page'])
            ) . '/' . $this->hash . '/'
        );

        return ee()->functions->redirect($path);
    }

    /**
     * Build Meta Array
     *
     * This builds the array of parameters that are stored in a secure hash in a hidden input
     * on the search forms.
     */
    protected function _build_meta_array()
    {
        $site_ids = (ee()->TMPL->fetch_param('site')) ? ee()->TMPL->site_ids : array(ee()->config->item('site_id'));

        $meta = array(
            'status' => ee()->TMPL->fetch_param('status', ''),
            'channel' => ee()->TMPL->fetch_param('channel', ''),
            'category' => ee()->TMPL->fetch_param('category', ''),
            'search_in' => ee()->TMPL->fetch_param('search_in', ''),
            'where' => ee()->TMPL->fetch_param('where', ''),
            'show_expired' => ee()->TMPL->fetch_param('show_expired', ''),
            'show_future_entries' => ee()->TMPL->fetch_param('show_future_entries'),
            'result_page' => ee()->TMPL->fetch_param('result_page', 'search/results'),
            'no_results_page' => ee()->TMPL->fetch_param('no_result_page', ''),
            'site_ids' => $site_ids
        );

        $meta = serialize($meta);

        return ee('Encrypt')->encode($meta, ee()->config->item('session_crypt_key'));
    }

    /**
     * get Meta vars
     *
     * Get the meta variables on the POSTed form.
     *
     */
    protected function _get_meta_vars()
    {
        // Get data from the meta input

        $meta_array = ee('Encrypt')->decode($_POST['meta'], ee()->config->item('session_crypt_key'));

        $this->_meta = unserialize($meta_array);

        // Check for Advanced Form Inputs
        $valid_inputs = array('search_in', 'where');
        foreach ($valid_inputs as $current_input) {
            if (
                (! isset($this->_meta[$current_input]) or $this->_meta[$current_input] === '') &&
                ee()->input->post($current_input)
            ) {
                $this->_meta[$current_input] = ee()->input->post($current_input);
            }
        }

        // Default 'where' to 'all' if it hasn't been specified
        if (! isset($this->_meta['where']) or $this->_meta['where'] === '') {
            $this->_meta['where'] = 'all';
        }
    }

    /** ---------------------------------------
    /**  Create the search query
    /** ---------------------------------------*/
    public function build_standard_query()
    {
        ee()->load->model('addons_model');

        $channel_array = array();

        /** ---------------------------------------
        /**  Fetch the channel_id numbers
        /** ---------------------------------------*/

        // If $_POST['channel_id'] exists we know the request is coming from the
        // advanced search form. We set those values to the $channel_id_array

        if (isset($_POST['channel_id']) and is_array($_POST['channel_id'])) {
            $channel_id_array = $_POST['channel_id'];
        }

        // Since both the simple and advanced search form have
        // $_POST['channel'], then we can safely find all of the
        // channels available for searching

        // By doing this for the advanced search form, we can discover
        // Which channels we are or are not supposed to search for, when
        // "Any Channel" is chosen

        ee()->db->select('channel_id');
        if (isset($this->_meta['channel']) and $this->_meta['channel'] != '') {
            ee()->functions->ar_andor_string($this->_meta['channel'], 'channel_name');
        }
        ee()->db->where_in('site_id', $this->_meta['site_ids']);
        $query = ee()->db->get('channels');

        // If channel's are specified and there NO valid channels returned?  There can be no results!
        if ($query->num_rows() == 0) {
            return false;
        }

        foreach ($query->result_array() as $row) {
            $channel_array[] = $row['channel_id'];
        }

        /** ------------------------------------------------------
        /**  Find the Common Channel IDs for Advanced Search Form
        /** ------------------------------------------------------*/
        if (isset($channel_id_array) && $channel_id_array['0'] != 'null') {
            $channel_array = array_intersect($channel_id_array, $channel_array);
        }

        /** ----------------------------------------------
        /**  Fetch the channel_id numbers (from Advanced search)
        /** ----------------------------------------------*/

        // We do this up-front since we use this same sub-query in two places

        $id_query = '';

        if (count($channel_array) > 0) {
            foreach ($channel_array as $val) {
                if ($val != 'null' and $val != '') {
                    $id_query .= " exp_channel_titles.channel_id = '" . ee()->db->escape_str($val) . "' OR";
                }
            }

            if ($id_query != '') {
                $id_query = substr($id_query, 0, -2);
                $id_query = ' AND (' . $id_query . ') ';
            }
        }

        /** ----------------------------------------------
        /**  Limit to a specific member? We do this now
        /**  as there's a potential for this to bring the
        /**  search to an end if it's not a valid member
        /** ----------------------------------------------*/
        $member_array = array();
        $member_ids = '';

        if (isset($_GET['mbr']) and is_numeric($_GET['mbr'])) {
            $query = ee()->db->select('member_id')->get_where('members', array(
                'member_id' => $_GET['mbr']
            ));

            if ($query->num_rows() != 1) {
                return false;
            } else {
                $member_array[] = $query->row('member_id');
            }
        } else {
            if (ee()->input->post('member_name') != '') {
                ee()->db->select('member_id');

                if (ee()->input->post('exact_match') == 'y') {
                    ee()->db->where('screen_name', ee()->input->post('member_name'));
                } else {
                    ee()->db->like('screen_name', ee()->input->post('member_name'));
                }

                $query = ee()->db->get('members');

                if ($query->num_rows() == 0) {
                    return false;
                } else {
                    foreach ($query->result_array() as $row) {
                        $member_array[] = $row['member_id'];
                    }
                }
            }
        }

        // and turn it into a string now so we only implode once
        if (count($member_array) > 0) {
            $member_ids = ' IN (' . implode(',', $member_array) . ') ';
        }

        unset($member_array);

        /** ---------------------------------------
        /**  Fetch the searchable field names
        /** ---------------------------------------*/
        $fields = array();
        $legacy_fields = array();
        $joins = '';

        // no need to do this unless there are keywords to search
        if (trim($this->keywords) != '') {
            $channels = ee('Model')->get('Channel')
                ->filter('site_id', 'IN', $this->_meta['site_ids'])
                ->all();

            if ($channels) {
                if (empty($this->custom_fields)) {
                    $custom_fields = array();
                    foreach ($channels as $channel) {
                        $custom_fields = array_merge($custom_fields, $channel->getAllCustomFields()->asArray());
                    }
                    $this->custom_fields = array_chunk($custom_fields, 50);
                }

                foreach (array_shift($this->custom_fields) as $field) {
                    if ($field->field_search) {
                        if (! isset($fields[$field->field_id])) {
                            $fields[$field->field_id] = $field->field_id;
                            $legacy_fields[$field->field_id] = $field->legacy_field_data;
                            if (! $field->legacy_field_data) {
                                $joins .= "\nLEFT JOIN exp_channel_data_field_{$field->field_id} ON exp_channel_data_field_{$field->field_id}.entry_id = exp_channel_titles.entry_id ";
                            }
                        }
                    }

                    $this->fields[$field->field_name] = array($field->field_id, $field->field_search);
                }
            }
        }

        /** ---------------------------------------
        /**  Build the main query
        /** ---------------------------------------*/
        $sql = "SELECT
			DISTINCT(exp_channel_titles.entry_id), exp_channel_titles.channel_id
			FROM exp_channel_titles
			LEFT JOIN exp_channels ON exp_channel_titles.channel_id = exp_channels.channel_id
			LEFT JOIN exp_channel_data ON exp_channel_titles.entry_id = exp_channel_data.entry_id ";

        $sql .= $joins;

        // is the comment module installed?
        if (ee()->addons_model->module_installed('comment')) {
            $sql .= "LEFT JOIN exp_comments ON exp_channel_titles.entry_id = exp_comments.entry_id ";
        }

        $sql .= "LEFT JOIN exp_category_posts ON exp_channel_titles.entry_id = exp_category_posts.entry_id
			LEFT JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id
			WHERE exp_channels.site_id IN ('" . implode("','", $this->_meta['site_ids']) . "') ";

        /** ----------------------------------------------
        /**  We only select entries that have not expired
        /** ----------------------------------------------*/
        if (! isset($this->_meta['show_future_entries']) or $this->_meta['show_future_entries'] != 'yes') {
            $sql .= "\nAND exp_channel_titles.entry_date < " . ee()->localize->now . " ";
        }

        if (! isset($this->_meta['show_expired']) or $this->_meta['show_expired'] != 'yes') {
            $sql .= "\nAND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > " . ee()->localize->now . ") ";
        }

        /** ----------------------------------------------
        /**  Add status declaration to the query
        /** ----------------------------------------------*/
        if (isset($this->_meta['status']) and ($status = $this->_meta['status']) != '') {
            $status = str_replace('Open', 'open', $status);
            $status = str_replace('Closed', 'closed', $status);

            $sql .= ee()->functions->sql_andor_string($status, 'exp_channel_titles.status');

            // add exclusion for closed unless it was explicitly used
            if (strncasecmp($status, 'not ', 4) == 0) {
                $status = trim(substr($status, 3));
            }

            $stati = explode('|', $status);

            if (! in_array('closed', $stati)) {
                $sql .= "\nAND exp_channel_titles.status != 'closed' ";
            }
        } else {
            $sql .= "AND exp_channel_titles.status = 'open' ";
        }

        /** ----------------------------------------------
        /**  Set Date filtering
        /** ----------------------------------------------*/
        if (isset($_POST['date']) and $_POST['date'] != 0) {
            $cutoff = ee()->localize->now - (60 * 60 * 24 * $_POST['date']);

            if (isset($_POST['date_order']) and $_POST['date_order'] == 'older') {
                $sql .= "AND exp_channel_titles.entry_date < " . $cutoff . " ";
            } else {
                $sql .= "AND exp_channel_titles.entry_date > " . $cutoff . " ";
            }
        }

        /** ----------------------------------------------
        /**  Add keyword to the query
        /** ----------------------------------------------*/
        if (trim($this->keywords) != '' || ! empty($this->terms)) {
            // So it begins
            $sql .= "\nAND (";

            /** -----------------------------------------
            /**  Process our Keywords into Search Terms
            /** -----------------------------------------*/
            $this->keywords = stripslashes($this->keywords);
            $criteria = (isset($this->_meta['where']) && $this->_meta['where'] == 'all') ? 'AND' : 'OR';

            if (preg_match_all("/\-*\"(.*?)\"/", $this->keywords, $matches)) {
                for ($m = 0; $m < count($matches['1']); $m++) {
                    $this->terms[] = trim(str_replace('"', '', $matches['0'][$m]));
                    $this->keywords = str_replace($matches['0'][$m], '', $this->keywords);
                }
            }

            if (trim($this->keywords) != '') {
                $this->terms = array_merge($this->terms, preg_split("/\s+/", trim($this->keywords)));
            }

            $not_and = (count($this->terms) > 2) ? ') AND (' : 'AND';
            rsort($this->terms);
            $terms_like = ee()->db->escape_like_str($this->terms);
            $this->terms = ee()->db->escape_str($this->terms);

            /** ----------------------------------
            /**  Search in Title Field
            /** ----------------------------------*/
            if (count($this->terms) == 1 && isset($this->_meta['where']) && $this->_meta['where'] == 'word') { // Exact word match
                $sql .= "((exp_channel_titles.title = '" . $this->terms['0'] . "' OR exp_channel_titles.title LIKE '" . $terms_like['0'] . " %' OR exp_channel_titles.title LIKE '% " . $terms_like['0'] . " %') ";

                // and close up the member clause
                if ($member_ids != '') {
                    $sql .= " AND (exp_channel_titles.author_id {$member_ids})) \n";
                } else {
                    $sql .= ") \n";
                }
            } elseif (! isset($_POST['exact_keyword'])) {  // Any terms, all terms
                $mysql_function = (substr($this->terms['0'], 0, 1) == '-') ? 'NOT LIKE' : 'LIKE';
                $search_term = (substr($this->terms['0'], 0, 1) == '-') ? substr($terms_like['0'], 1) : $terms_like['0'];

                // We have three parentheses in the beginning in case
                // there are any NOT LIKE's being used and to allow for a member clause
                $sql .= "\n(((exp_channel_titles.title $mysql_function '%" . $search_term . "%' ";

                for ($i = 1; $i < count($this->terms); $i++) {
                    $mysql_criteria = ($mysql_function == 'NOT LIKE' or substr($this->terms[$i], 0, 1) == '-') ? $not_and : $criteria;
                    $mysql_function = (substr($this->terms[$i], 0, 1) == '-') ? 'NOT LIKE' : 'LIKE';
                    $search_term = (substr($this->terms[$i], 0, 1) == '-') ? substr($terms_like[$i], 1) : $terms_like[$i];

                    $sql .= "$mysql_criteria exp_channel_titles.title $mysql_function '%" . $search_term . "%' ";
                }

                $sql .= ")) ";

                // and close up the member clause
                if ($member_ids != '') {
                    $sql .= " AND (exp_channel_titles.author_id {$member_ids})) \n";
                } else {
                    $sql .= ") \n";
                }
            } else { // exact phrase match
                $search_term = (count($this->terms) == 1) ? $terms_like[0] : ee()->db->escape_str($this->keywords);
                $sql .= "(exp_channel_titles.title LIKE '%" . $search_term . "%' ";

                // and close up the member clause
                if ($member_ids != '') {
                    $sql .= " AND (exp_channel_titles.author_id {$member_ids})) \n";
                } else {
                    $sql .= ") \n";
                }
            }

            /** ----------------------------------
            /**  Search in Searchable Fields
            /** ----------------------------------*/
            if (isset($this->_meta['search_in']) and ($this->_meta['search_in'] == 'entries' or $this->_meta['search_in'] == 'everywhere')) {
                if (count($this->terms) > 1 && isset($this->_meta['where']) && $this->_meta['where'] == 'all' && ! isset($_POST['exact_keyword']) && count($fields) > 0) {
                    $concat_tables = [];
                    foreach ($fields as $val) {
                        $table = ($legacy_fields[$val]) ? "exp_channel_data" : "exp_channel_data_field_{$val}";
                        $concat_tables[] = $table . '.field_id_' . $val;
                    }
                    $concat_fields = "CAST(CONCAT_WS(' ', " . implode(', ', $concat_tables) . ") AS CHAR)";

                    $mysql_function = (substr($this->terms['0'], 0, 1) == '-') ? 'NOT LIKE' : 'LIKE';
                    $search_term = (substr($this->terms['0'], 0, 1) == '-') ? substr($this->terms['0'], 1) : $this->terms['0'];

                    // Since Title is always required in a search we use OR
                    // And then three parentheses just like above in case
                    // there are any NOT LIKE's being used and to allow for a member clause
                    $sql .= "\nOR ((($concat_fields $mysql_function '%" . $search_term . "%' ";

                    for ($i = 1; $i < count($this->terms); $i++) {
                        $mysql_criteria = ($mysql_function == 'NOT LIKE' or substr($this->terms[$i], 0, 1) == '-') ? $not_and : $criteria;
                        $mysql_function = (substr($this->terms[$i], 0, 1) == '-') ? 'NOT LIKE' : 'LIKE';
                        $search_term = (substr($this->terms[$i], 0, 1) == '-') ? substr($terms_like[$i], 1) : $terms_like[$i];

                        $sql .= "$mysql_criteria $concat_fields $mysql_function '%" . $search_term . "%' ";
                    }

                    $sql .= ")) ";

                    // and close up the member clause
                    if ($member_ids != '') {
                        $sql .= " AND (exp_channel_titles.author_id {$member_ids})) \n";
                    } else {
                        $sql .= ") \n";
                    }
                } else {
                    foreach ($fields as $val) {
                        $table = ($legacy_fields[$val]) ? "exp_channel_data" : "exp_channel_data_field_{$val}";

                        if (count($this->terms) == 1 && isset($this->_meta['where']) && $this->_meta['where'] == 'word') {
                            $sql .= "\nOR (({$table}.field_id_" . $val . " LIKE '" . $terms_like['0'] . " %' OR {$table}.field_id_" . $val . " LIKE '% " . $terms_like['0'] . " %' OR {$table}.field_id_" . $val . " LIKE '% " . $terms_like['0'] . "' OR {$table}.field_id_" . $val . " = '" . $this->terms['0'] . "') ";

                            // and close up the member clause
                            if ($member_ids != '') {
                                $sql .= " AND (exp_channel_titles.author_id {$member_ids})) ";
                            } else {
                                $sql .= ") ";
                            }
                        } elseif (! isset($_POST['exact_keyword'])) {
                            $mysql_function = (substr($this->terms['0'], 0, 1) == '-') ? 'NOT LIKE' : 'LIKE';
                            $search_term = (substr($this->terms['0'], 0, 1) == '-') ? substr($terms_like['0'], 1) : $terms_like['0'];

                            // Since Title is always required in a search we use OR
                            // And then three parentheses just like above in case
                            // there are any NOT LIKE's being used and to allow for a member clause
                            $sql .= "\nOR ((({$table}.field_id_" . $val . " $mysql_function '%" . $search_term . "%' ";

                            for ($i = 1; $i < count($this->terms); $i++) {
                                $mysql_criteria = ($mysql_function == 'NOT LIKE' or substr($this->terms[$i], 0, 1) == '-') ? $not_and : $criteria;
                                $mysql_function = (substr($this->terms[$i], 0, 1) == '-') ? 'NOT LIKE' : 'LIKE';
                                $search_term = (substr($this->terms[$i], 0, 1) == '-') ? substr($terms_like[$i], 1) : $terms_like[$i];

                                $sql .= "$mysql_criteria {$table}.field_id_" . $val . " $mysql_function '%" . $search_term . "%' ";
                            }

                            $sql .= ")) ";

                            // and close up the member clause
                            if ($member_ids != '') {
                                $sql .= " AND (exp_channel_titles.author_id {$member_ids})) \n";
                            } else {
                                // close up the extra parenthesis
                                $sql .= ") \n";
                            }
                        } else {
                            $search_term = (count($this->terms) == 1) ? $terms_like[0] : ee()->db->escape_str($this->keywords);
                            $sql .= "\nOR ({$table}.field_id_" . $val . " LIKE '%" . $search_term . "%' ";

                            // and close up the member clause
                            if ($member_ids != '') {
                                $sql .= " AND (exp_channel_titles.author_id {$member_ids})) \n";
                            } else {
                                // close up the extra parenthesis
                                $sql .= ") \n";
                            }
                        }
                    }
                }
            }

            /** ----------------------------------
            /**  Search in Comments
            /** ----------------------------------*/
            if (isset($this->_meta['search_in']) and $this->_meta['search_in'] == 'everywhere' and ee()->addons_model->module_installed('comment')) {
                if (count($this->terms) == 1 && isset($this->_meta['where']) && $this->_meta['where'] == 'word') {
                    $sql .= " OR (exp_comments.comment LIKE '% " . $terms_like['0'] . " %' ";

                    // and close up the member clause
                    if ($member_ids != '') {
                        $sql .= " AND (exp_comments.author_id {$member_ids})) \n";
                    } else {
                        // close up the extra parenthesis
                        $sql .= ") \n";
                    }
                } elseif (! isset($_POST['exact_keyword'])) {
                    $mysql_function = (substr($this->terms['0'], 0, 1) == '-') ? 'NOT LIKE' : 'LIKE';
                    $search_term = (substr($this->terms['0'], 0, 1) == '-') ? substr($terms_like['0'], 1) : $terms_like['0'];

                    // We have three parentheses in the beginning in case
                    // there are any NOT LIKE's being used and to allow a member clause
                    $sql .= "\nOR (((exp_comments.comment $mysql_function '%" . $search_term . "%' ";

                    for ($i = 1; $i < count($this->terms); $i++) {
                        $mysql_criteria = ($mysql_function == 'NOT LIKE' or substr($this->terms[$i], 0, 1) == '-') ? $not_and : $criteria;
                        $mysql_function = (substr($this->terms[$i], 0, 1) == '-') ? 'NOT LIKE' : 'LIKE';
                        $search_term = (substr($this->terms[$i], 0, 1) == '-') ? substr($terms_like[$i], 1) : $terms_like[$i];

                        $sql .= "$mysql_criteria exp_comments.comment $mysql_function '%" . $search_term . "%' ";
                    }

                    $sql .= ")) ";

                    // and close up the member clause
                    if ($member_ids != '') {
                        $sql .= " AND (exp_comments.author_id {$member_ids})) \n";
                    } else {
                        // close up the extra parenthesis
                        $sql .= ") \n";
                    }
                } else {
                    $search_term = (count($this->terms) == 1) ? $terms_like[0] : ee()->db->escape_str($this->keywords);
                    $sql .= " OR ((exp_comments.comment LIKE '%" . $search_term . "%') ";

                    // and close up the member clause
                    if ($member_ids != '') {
                        $sql .= " AND (exp_comments.author_id {$member_ids})) \n";
                    } else {
                        // close up the extra parenthesis
                        $sql .= ") \n";
                    }
                }
            }

            // So it ends
            $sql .= ") \n";
        } else {
            // there are no keywords at all.  Do we still need a member search?
            if ($member_ids != '') {
                $sql .= "AND (exp_channel_titles.author_id {$member_ids} ";

                // searching comments too?
                if (isset($this->_meta['search_in']) and $this->_meta['search_in'] == 'everywhere' and ee()->addons_model->module_installed('comment')) {
                    $sql .= " OR exp_comments.author_id {$member_ids}";
                }

                $sql .= ")";
            }
        }

        /** ----------------------------------------------
        /**  Limit query to a specific channel
        /** ----------------------------------------------*/
        if (count($channel_array) > 0) {
            $sql .= $id_query;
        }

        /** ----------------------------------------------
        /**  Limit query to a specific category
        /** ----------------------------------------------*/

        // Check for different sets of category IDs, checking the parameters
        // first, then the $_POST
        if (isset($this->_meta['category']) and $this->_meta['category'] != '' and ! is_array($this->_meta['category'])) {
            $this->_meta['category'] = explode('|', $this->_meta['category']);
        } elseif (
            (! isset($this->_meta['category']) or $this->_meta['category'] == '') and
            (isset($_POST['cat_id']) and is_array($_POST['cat_id']))
        ) {
            $this->_meta['category'] = $_POST['cat_id'];
        }

        if (isset($this->_meta['category']) and is_array($this->_meta['category'])) {
            $temp = '';

            foreach ($this->_meta['category'] as $val) {
                if ($val != 'all' and $val != '') {
                    $temp .= " exp_categories.cat_id = '" . ee()->db->escape_str($val) . "' OR";
                }
            }

            if ($temp != '') {
                $temp = substr($temp, 0, -2);

                $sql .= ' AND (' . $temp . ') ';
            }
        }

        // -------------------------------------------
        // 'channel_search_modify_search_query' hook.
        //  - Take the whole query string, do what you wish
        //  - added 2.8
        //
        if (ee()->extensions->active_hook('channel_search_modify_search_query') === true) {
            $modified_sql = ee()->extensions->call('channel_search_modify_search_query', $sql, $this->hash);

            // Make sure its valid
            if (is_string($modified_sql) && $modified_sql != '') {
                $sql = $modified_sql;
            }

            // This will save the custom query and the total results to exp_search
            if (ee()->extensions->end_script === true) {
                $query = ee()->db->query($sql);

                if ($query->num_rows() == 0) {
                    return false;
                }

                $this->num_rows = $query->num_rows();

                $return = array(
                    'entries' => array(),
                    'channel_ids' => array(),
                    'end' => ''
                );

                foreach ($query->result_array() as $row) {
                    $return['entries'][] = $row['entry_id'];
                    $return['channel_ids'][] = $row['channel_id'];
                }

                $return['channel_ids'] = array_unique($return['channel_ids']);

                if (stripos($sql, ' ORDER BY ') !== false) {
                    list($before, $end) = explode(' ORDER BY ', $sql);
                    $return['end'] = ' ORDER BY ' . $end;
                }

                return $return;
            }
        }
        //
        // -------------------------------------------

        /** ----------------------------------------------
        /**  Are there results?
        /** ----------------------------------------------*/
        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) {
            return false;
        }

        $return = array(
            'entries' => array(),
            'channel_ids' => array(),
            'end' => ''
        );

        foreach ($query->result_array() as $row) {
            $return['entries'][] = $row['entry_id'];
            $return['channel_ids'][] = $row['channel_id'];
        }

        $return['channel_ids'] = array_unique($return['channel_ids']);

        /** ----------------------------------------------
        /**  Set sort order
        /** ----------------------------------------------*/
        $order_by = (! isset($_POST['order_by'])) ? 'date' : $_POST['order_by'];
        $orderby = (! isset($_POST['orderby'])) ? $order_by : $_POST['orderby'];

        $end = '';

        switch ($orderby) {
            case 'most_comments':
                $end .= " ORDER BY comment_total ";

                break;
            case 'recent_comment':
                $end .= " ORDER BY recent_comment_date ";

                break;
            case 'title':
                $end .= " ORDER BY title ";

                break;
            default:
                $end .= " ORDER BY entry_date ";

                break;
        }

        $order = (! isset($_POST['sort_order'])) ? 'desc' : $_POST['sort_order'];

        if ($order != 'asc' and $order != 'desc') {
            $order = 'desc';
        }

        $end .= " " . $order;

        $return['end'] = $end;

        return $return;
    }

    protected function getAllQueryParts()
    {
        $query_parts = $this->build_standard_query();

        if (! empty($this->custom_fields)) {
            foreach (array_keys($this->custom_fields) as $i) {
                $qp = $this->build_standard_query();

                if ($query_parts === false) {
                    $query_parts = $qp;
                } else {
                    if ($qp) {
                        $query_parts['entries'] = array_merge($query_parts['entries'], $qp['entries']);
                        $query_parts['channel_ids'] = array_merge($query_parts['channel_ids'], $qp['channel_ids']);
                    }
                }
            }
        }

        // Set absolute count
        $this->num_rows = $query_parts ? count(array_unique($query_parts['entries'])) : 0;

        return $query_parts;
    }

    /** ----------------------------------------
    /**  Total search results
    /** ----------------------------------------*/
    public function total_results()
    {
        $search_id = $this->_get_search_id();

        if (! $search_id) {
            return '';
        }

        /** ----------------------------------------
        /**  Fetch the cached search query
        /** ----------------------------------------*/
        $query = ee()->db->query("SELECT total_results FROM exp_search WHERE search_id = '" . ee()->db->escape_str($search_id) . "'");

        if ($query->num_rows() == 1) {
            return $query->row('total_results') ;
        } else {
            return 0;
        }
    }

    /** ----------------------------------------
    /**  Search keywords
    /** ----------------------------------------*/
    public function keywords()
    {
        $search_id = $this->_get_search_id();

        if (! $search_id) {
            return '';
        }

        /** ----------------------------------------
        /**  Fetch the cached search query
        /** ----------------------------------------*/
        $query = ee()->db->query("SELECT keywords FROM exp_search WHERE search_id = '" . ee()->db->escape_str($search_id) . "'");

        if ($query->num_rows() == 1) {
            // Load the XML Helper
            ee()->load->helper('xml');

            return ee()->functions->encode_ee_tags(xml_convert($query->row('keywords')));
        } else {
            return '';
        }
    }

    /**
     * Returns a validated search id, checking first for a parameter and second in the query string
     *
     * @access	private
     * @return	mixed 	The validated search id or FALSE
     */
    private function _get_search_id()
    {
        $search_id = ee()->TMPL->fetch_param('search_id');

        // Retrieve the search_id
        if (! $search_id) {
            $qstring = explode('/', ee()->uri->query_string);
            $search_id = trim($qstring[0]);
        }

        // Check search ID number
        if (strlen($search_id) < 32) {
            return false;
        }

        return $search_id;
    }

    /** ----------------------------------------
    /**  Show search results
    /** ----------------------------------------*/
    public function search_results()
    {
        // Fetch the search language file
        ee()->lang->loadfile('search');

        // Load Pagination Object
        ee()->load->library('pagination');
        $pagination = ee()->pagination->create();
        ee()->TMPL->tagdata = $pagination->prepare(ee()->TMPL->tagdata);

        $search_id = $this->_get_search_id();

        if (! $search_id) {
            return ee()->output->show_user_error(
                'off',
                array(lang('search_no_result'))
            );
        }

        // Clear old search results
        ee()->db->delete(
            'search',
            array(
                'site_id' => ee()->config->item('site_id'), // Current site
                'search_date <' => ee()->localize->now - ($this->cache_expire * 3600)
            )
        );

        // Fetch the cached search query
        $query = ee()->db->get_where('search', array('search_id' => $search_id));

        if ($query->num_rows() == 0 or $query->row('total_results') == 0) {
            // This should be impossible as we already know there are results
            return ee()->output->show_user_error(
                'general',
                array(lang('invalid_action'))
            );
        }

        $fields = ($query->row('custom_fields') == '') ? array() : unserialize(stripslashes($query->row('custom_fields')));
        $query_parts = unserialize($query->row('query'));

        $this->num_rows = (int) $query->row('total_results');
        $pagination->per_page = (int) $query->row('per_page');
        $res_page = $query->row('result_page');

        if (! class_exists('Channel')) {
            require PATH_ADDONS . 'channel/mod.channel.php';
        }

        $channel = new Channel();

        $channel->fetch_custom_channel_fields();
        $channel->fetch_custom_member_fields();

        ee()->session->cache['channel']['entry_ids'] = $query_parts['entries'];

        $sql = 'SELECT DISTINCT(t.entry_id), w.search_results_url, w.search_excerpt, ';
        $sql .= $channel->generateSQLForEntries($query_parts['entries'], $query_parts['channel_ids']);
        $sql .= $query_parts['end'];

        // -------------------------------------------
        // 'channel_search_modify_result_query' hook.
        //  - Take the whole query string, do what you wish
        //  - added 2.8
        //
        if (ee()->extensions->active_hook('channel_search_modify_result_query') === true) {
            $modified_sql = ee()->extensions->call('channel_search_modify_result_query', $sql, $search_id);

            // Make sure its valid
            if (is_string($modified_sql) && $modified_sql != '') {
                $sql = $modified_sql;
            }
        }
        //
        // -------------------------------------------

        // Run the search query
        $query = ee()->db->query(preg_replace("/SELECT(.*?)\s+FROM\s+/is", 'SELECT COUNT(*) AS count FROM ', $sql));

        if ($query->row('count') == 0) {
            // This should also be impossible
            return ee()->output->show_user_error(
                'general',
                array(lang('invalid_action'))
            );
        }

        // Calculate total number of pages and add total rows
        $pagination->total_items = $query->row('count');

        // Build pagination if enabled
        // If we're paginating limit the query and do it again
        if ($pagination->paginate === true) {
            $pagination->build($pagination->total_items, $pagination->per_page);
            $sql .= " LIMIT " . $pagination->offset . ", " . $pagination->per_page;
        } else {
            $sql .= " LIMIT 0, 100";
        }

        $query = ee()->db->query($sql);

        $output = '';

        unset(ee()->TMPL->var_single['auto_path']);
        unset(ee()->TMPL->var_single['excerpt']);
        unset(ee()->TMPL->var_single['id_auto_path']);
        unset(ee()->TMPL->var_single['full_text']);
        unset(ee()->TMPL->var_single['switch']);

        foreach (ee()->TMPL->var_single as $key => $value) {
            if (substr($key, 0, strlen('member_path')) == 'member_path') {
                unset(ee()->TMPL->var_single[$key]);
            }
        }

        $switch = ee()->TMPL->fetch_param('switch');
        if (! empty($switch) && strpos(ee()->TMPL->tagdata, '{switch}') !== false) {
            ee()->TMPL->tagdata = str_replace("{switch}", "{switch='{$switch}'}", ee()->TMPL->tagdata);
            ee()->load->library('logger');
            ee()->logger->developer('The search module\'s {switch} variable has been deprecated, use standard {switch=} tags in your search results template.', true, 604800);
        }

        // This allows the channel {absolute_count} variable to work
        $channel->pagination->offset = ($pagination->per_page * $pagination->current_page) - $pagination->per_page;

        $channel->query = $query;
        $channel->absolute_results = $this->num_rows;

        if ($channel->query->num_rows() == 0) {
            return ee()->TMPL->no_results();
        }

        ee()->load->library('typography');
        ee()->typography->initialize(array(
            'convert_curly' => false,
            'encode_email' => false
        ));

        $channel->fetch_categories();
        $channel->parse_channel_entries(array($this, 'callback_search_result_row'));

        // Add new pagination
        ee()->TMPL->tagdata = $pagination->render($channel->return_data);

        // Parse lang variables
        $swap = array(
            'lang:total_search_results' => lang('search_total_results'),
            'lang:search_engine' => lang('search_engine'),
            'lang:search_results' => lang('search_results'),
            'lang:search' => lang('search'),
            'lang:title' => lang('search_title'),
            'lang:channel' => lang('search_channel'),
            'lang:excerpt' => lang('search_excerpt'),
            'lang:author' => lang('search_author'),
            'lang:date' => lang('search_date'),
            'lang:total_comments' => lang('search_total_comments'),
            'lang:recent_comments' => lang('search_recent_comment_date'),
            'lang:keywords' => lang('search_keywords')
        );
        ee()->TMPL->template = ee()->functions->var_swap(ee()->TMPL->template, $swap);

        return ee()->TMPL->tagdata;
    }

    /**
     * Callback called by Channel Entries parser so we can parse search results
     * tags
     * @param  String $tagdata Individual tagdata variable for a given row
     * @param  Array  $row     Data associated with current row
     * @return String          Parsed tagdata
     */
    public function callback_search_result_row($tagdata, $row)
    {
        $overrides = ee()->config->get_cached_site_prefs($row['entry_site_id']);
        $row['channel_url'] = parse_config_variables($row['channel_url'], $overrides);
        $row['comment_url'] = parse_config_variables($row['comment_url'], $overrides);
        $row['search_results_url'] = parse_config_variables($row['search_results_url'], $overrides);

        if (isset($row['field_id_' . $row['search_excerpt']]) and $row['field_id_' . $row['search_excerpt']]) {
            $format = (! isset($row['field_ft_' . $row['search_excerpt']])) ? 'xhtml' : $row['field_ft_' . $row['search_excerpt']];

            $full_text = ee()->typography->parse_type(
                // Replace block HTML tags with spaces so words don't run together in case
                // they're saved with no spaces in between the markup
                strip_tags(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        preg_replace('/<[\/?][p|br|div|h1|h2]*>/', ' ', $row['field_id_' . $row['search_excerpt']])
                    )
                ),
                array(
                    'text_format' => $format,
                    'html_format' => 'safe',
                    'auto_links' => 'y',
                    'allow_img_url' => 'n'
                )
            );

            $excerpt = trim(strip_tags($full_text));

            if (strpos($excerpt, "\r") !== false or strpos($excerpt, "\n") !== false) {
                $excerpt = str_replace(array("\r\n", "\r", "\n"), " ", $excerpt);
            }

            $excerpt = ee()->functions->word_limiter($excerpt, 50);
        } else {
            $excerpt = '';
            $full_text = '';
        }

        // Parse permalink path
        $url = ($row['search_results_url'] != '') ? $row['search_results_url'] : $row['channel_url'];

        $path = reduce_double_slashes(ee()->functions->prep_query_string($url) . '/' . $row['url_title']);
        $idpath = reduce_double_slashes(ee()->functions->prep_query_string($url) . '/' . $row['entry_id']);

        $tagdata = preg_replace(
            "/" . LD . 'auto_path' . RD . "/",
            $path,
            $tagdata,
            $this->tag_count('auto_path', $tagdata)
        );
        $tagdata = preg_replace(
            "/" . LD . 'id_auto_path' . RD . "/",
            $idpath,
            $tagdata,
            $this->tag_count('id_auto_path', $tagdata)
        );
        $tagdata = preg_replace(
            "/" . LD . 'excerpt' . RD . "/",
            $this->_escape_replacement_pattern($excerpt),
            $tagdata,
            $this->tag_count('excerpt', $tagdata)
        );
        $tagdata = preg_replace(
            "/" . LD . 'full_text' . RD . "/",
            $this->_escape_replacement_pattern($full_text),
            $tagdata,
            $this->tag_count('full_text', $tagdata)
        );

        $m_paths = $this->get_member_path_tags($tagdata);

        // Parse member_path
        if (count($m_paths) > 0) {
            foreach ($m_paths as $val) {
                $tagdata = preg_replace(
                    "/" . preg_quote($val['0'], '/') . "/",
                    ee()->functions->create_url($val['1'] . '/' . $row['member_id']),
                    $tagdata,
                    1
                );
            }
        }

        return $tagdata;
    }

    /**
     * Retrieve the Member Path tags for a set of tagdata
     *
     * @param String $tagdata The tagdata to get member_path tags from
     * @return array Nested array containing tag and resulting paths for member
     *               path tags (e.g. {member_path="member/index"})
     */
    private function get_member_path_tags($tagdata = null)
    {
        if (isset($this->m_paths)) {
            return $this->m_paths;
        }

        $tagdata = ($tagdata) ?: ee()->TMPL->tagdata;

        // Fetch member path variable
        // We do it here in case it's used in multiple places.
        $this->m_paths = array();

        if (preg_match_all("/" . LD . "member_path(\s*=.*?)" . RD . "/s", ee()->TMPL->tagdata, $matches)) {
            for ($j = 0; $j < count($matches['0']); $j++) {
                $this->m_paths[] = array($matches['0'][$j], ee()->functions->extract_path($matches['1'][$j]));
            }
        }

        return $this->m_paths;
    }

    /**
     * Get the number of tags in a given tagdata
     * @param  String $tag_name The name of the tag to look for
     * @param  String $tagdata  The tagdata to get member_path tags from
     * @return int              The number of tags found
     */
    private function tag_count($tag_name, $tagdata = null)
    {
        $tagdata = ($tagdata) ?: ee()->TMPL->tagdata;

        return substr_count($tagdata, LD . $tag_name . RD);
    }

    /**
     * For when preg_quote is too much, we just need to escape replacement patterns
     * @param  string	String to escape
     * @return string	Escaped string
     */
    private function _escape_replacement_pattern($string)
    {
        return strtr($string, array('\\' => '\\\\', '$' => '\$'));
    }

    /**
     * Simple Search Form
     *
     * Generate the simple search form
     */
    public function simple_form()
    {
        $meta = $this->_build_meta_array();

        $data['hidden_fields'] = array(
            'ACT' => ee()->functions->fetch_action_id('Search', 'do_search'),
            'RES' => ee()->TMPL->fetch_param('results'),
            'meta' => $meta
        );

        if (ee()->TMPL->fetch_param('name') !== false &&
            preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('name'))) {
            $data['name'] = ee()->TMPL->fetch_param('name');
        }

        if (ee()->TMPL->fetch_param('id') !== false &&
            preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('id'))) {
            $data['id'] = ee()->TMPL->fetch_param('id');
            ee()->TMPL->log_item('Simple Search Form:  The \'id\' parameter has been deprecated.  Please use form_id');
        } else {
            $data['id'] = ee()->TMPL->form_id;
        }

        $data['class'] = ee()->TMPL->form_class;

        $res = ee()->functions->form_declaration($data);

        $res .= stripslashes(ee()->TMPL->tagdata);

        $res .= "</form>";

        return $res;
    }

    /** ----------------------------------------
    /**  Advanced Search Form
    /** ----------------------------------------*/
    public function advanced_form()
    {
        ee()->lang->loadfile('search');
        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_categories');

        /** ----------------------------------------
        /**  Fetch channels and categories
        /** ----------------------------------------*/

        // First we need to grab the name/ID number of all channels and categories

        $sql = "SELECT channel_title, channel_id, cat_group FROM exp_channels WHERE ";

        $sql .= "site_id = '" . ee()->db->escape_str(ee()->config->item('site_id')) . "' ";

        if ($channel = ee()->TMPL->fetch_param('channel')) {
            $xql = "SELECT channel_id FROM exp_channels WHERE site_id = '" . ee()->db->escape_str(ee()->config->item('site_id')) . "' ";

            $xql .= ee()->functions->sql_andor_string($channel, 'channel_name');

            $query = ee()->db->query($xql);

            if ($query->num_rows() > 0) {
                if ($query->num_rows() == 1) {
                    $sql .= "AND channel_id = '" . $query->row('channel_id') . "' ";
                } else {
                    $sql .= "AND (";

                    foreach ($query->result_array() as $row) {
                        $sql .= "channel_id = '" . $row['channel_id'] . "' OR ";
                    }

                    $sql = substr($sql, 0, - 3);

                    $sql .= ") ";
                }
            }
        }

        $sql .= " ORDER BY channel_title";

        $query = ee()->db->query($sql);

        foreach ($query->result_array() as $row) {
            $this->channel_array[$row['channel_id']] = array($row['channel_title'], $row['cat_group']);
        }

        $nested = (ee()->TMPL->fetch_param('cat_style') !== false && ee()->TMPL->fetch_param('cat_style') == 'nested') ? 'y' : 'n';

        /** ----------------------------------------
        /**  Build select list
        /** ----------------------------------------*/
        $channel_names = "<option value=\"null\" selected=\"selected\">" . lang('search_any_channel') . "</option>\n";

        // Load the form helper
        ee()->load->helper('form');

        foreach ($this->channel_array as $key => $val) {
            $channel_names .= "<option value=\"" . $key . "\">" . form_prep($val['0']) . "</option>\n";
        }

        $tagdata = ee()->TMPL->tagdata;

        /** ----------------------------------------
        /**  Parse variables
        /** ----------------------------------------*/
        $swap = array(
            'lang:search_engine' => lang('search_engine'),
            'lang:search' => lang('search'),
            'lang:search_by_keyword' => lang('search_by_keyword'),
            'lang:search_in_titles' => lang('search_in_titles'),
            'lang:search_in_entries' => lang('search_entries'),
            'lang:search_everywhere' => lang('search_everywhere'),
            'lang:search_by_member_name' => lang('search_by_member_name'),
            'lang:exact_name_match' => lang('search_exact_name_match'),
            'lang:exact_phrase_match' => lang('search_exact_phrase_match'),
            'lang:also_search_comments' => lang('search_also_search_comments'),
            'lang:any_date' => lang('search_any_date'),
            'lang:today_and' => lang('search_today_and'),
            'lang:this_week_and' => lang('search_this_week_and'),
            'lang:one_month_ago_and' => lang('search_one_month_ago_and'),
            'lang:three_months_ago_and' => lang('search_three_months_ago_and'),
            'lang:six_months_ago_and' => lang('search_six_months_ago_and'),
            'lang:one_year_ago_and' => lang('search_one_year_ago_and'),
            'lang:channels' => lang('search_channels'),
            'lang:weblogs' => lang('search_channels'),
            'lang:categories' => lang('search_categories'),
            'lang:newer' => lang('search_newer'),
            'lang:older' => lang('search_older'),
            'lang:sort_results_by' => lang('search_sort_results_by'),
            'lang:date' => lang('search_date'),
            'lang:title' => lang('search_title'),
            'lang:most_comments' => lang('search_most_comments'),
            'lang:recent_comment' => lang('search_recent_comment'),
            'lang:descending' => lang('search_descending'),
            'lang:ascending' => lang('search_ascending'),
            'lang:search_entries_from' => lang('search_entries_from'),
            'lang:any_category' => lang('search_any_category'),
            'lang:search_any_words' => lang('search_any_words'),
            'lang:search_all_words' => lang('search_all_words'),
            'lang:search_exact_word' => lang('search_exact_word'),
            'channel_names' => $channel_names
        );

        $tagdata = ee()->functions->var_swap($tagdata, $swap);

        ee()->TMPL->template = ee()->functions->var_swap(ee()->TMPL->template, $swap);

        /** ----------------------------------------
        /**  Create form
        /** ----------------------------------------*/
        $meta = $this->_build_meta_array();

        $data['class'] = ee()->TMPL->form_class;
        $data['hidden_fields'] = array(
            'ACT' => ee()->functions->fetch_action_id('Search', 'do_search'),
            'RES' => ee()->TMPL->fetch_param('results'),
            'meta' => $meta
        );

        if (ee()->TMPL->fetch_param('name') !== false &&
            preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('name'))) {
            $data['name'] = ee()->TMPL->fetch_param('name');
        }

        if (ee()->TMPL->fetch_param('id') !== false &&
            preg_match("#^[a-zA-Z0-9_\-]+$#i", ee()->TMPL->fetch_param('id'))) {
            $data['id'] = ee()->TMPL->fetch_param('id');
            ee()->TMPL->log_item('Advanced Search Form:  The \'id\' parameter has been deprecated.  Please use form_id');
        } elseif (ee()->TMPL->form_id != '') {
            $data['id'] = ee()->TMPL->form_id;
        } else {
            $data['id'] = 'searchform';
        }

        $res = ee()->functions->form_declaration($data);

        $res .= $this->search_js_switcher($nested, $data['id']);

        $res .= stripslashes($tagdata);

        $res .= "</form>";

        return $res;
    }

    /**
     * JavaScript channel/category switch code
     */
    public function search_js_switcher($nested = 'n', $id = 'searchform')
    {
        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_categories');

        $cat_array = ee()->api_channel_categories->category_form_tree(
            $nested,
            ee()->TMPL->fetch_param('category')
        );

        ob_start(); ?>
<script type="text/javascript">
//<![CDATA[

var firstcategory = 1;
var firststatus = 1;

function changemenu(index)
{
	var categories = new Array();

	var i = firstcategory;
	var j = firststatus;

	var theSearchForm = false

	if (document.searchform)
	{
		theSearchForm = document.searchform;
	}
	else if (document.getElementById('<?php echo $id; ?>'))
	{
		theSearchForm = document.getElementById('<?php echo $id; ?>');
	}

	if (theSearchForm.elements['channel_id'])
	{
		var channel_obj = theSearchForm.elements['channel_id'];
	}
	else
	{
		var channel_obj = theSearchForm.elements['channel_id[]'];
	}

	var channels = channel_obj.options[index].value;

	var reset = 0;

	for (var g = 0; g < channel_obj.options.length; g++)
	{
		if (channel_obj.options[g].value != 'null' &&
			channel_obj.options[g].selected == true)
		{
			reset++;
		}
	}

	with (theSearchForm.elements['cat_id[]'])
	{	<?php

        foreach ($this->channel_array as $key => $val) {
            ?>

		if (channels == "<?php echo $key ?>")
		{	<?php echo "\n";
            if (count($cat_array) > 0) {
                $last_group = 0;

                foreach ($cat_array as $k => $v) {
                    if (in_array($v['0'], explode('|', $val['1']))) {
                        if ($last_group == 0 or $last_group != $v['0']) {?>
			categories[i] = new Option("-------", ""); i++; <?php echo "\n";
                            $last_group = $v['0'];
                        }

                        // Note: this kludgy indentation is so that the JavaScript will look nice when it's renedered on the page?>
			categories[i] = new Option("<?php echo addslashes($v['2']); ?>", "<?php echo $v['1']; ?>"); i++; <?php echo "\n";
                    }
                }
            } ?>

		} // END if channels

		<?php
        } // END OUTER FOREACH?>

		if (reset > 1)
		{
			 categories = new Array();
		}

		spaceString = eval("/!-!/g");

		with (theSearchForm.elements['cat_id[]'])
		{
			for (i = length-1; i >= firstcategory; i--)
				options[i] = null;

			for (i = firstcategory; i < categories.length; i++)
			{
				options[i] = categories[i];
				options[i].text = options[i].text.replace(spaceString, String.fromCharCode(160));
			}

			options[0].selected = true;
		}

	}
}

//]]>
</script>

		<?php

        $buffer = ob_get_contents();

        ob_end_clean();

        return $buffer;
    }
}
// END CLASS

// EOF
