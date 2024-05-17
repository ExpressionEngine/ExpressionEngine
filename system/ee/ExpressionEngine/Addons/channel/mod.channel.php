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
 * Channel Module
 */
class Channel
{
    public $limit = '100';  // Default maximum query results if not specified.

    // These variable are all set dynamically

    public $query;
    public $TYPE;
    public $entry_id = '';
    public $uri = '';
    public $uristr = '';
    public $return_data = '';       // Final data
    public $hit_tracking_id = false;
    public $sql = false;
    public $cfields = array(); // Custom fields
    public $dfields = array(); // Date fields
    public $rfields = array(); // Relationship fields
    public $gfields = array(); // Grid fields
    public $msfields = array(); // Member select fields
    public $mfields = array(); // Custom member fields
    public $mpfields = array(); // Member pair fields
    public $pfields = array(); // Pair custom fields
    public $ffields = array(); // Fluid fields
    public $tfields = array(); // Toggle fields
    public $categories = array();
    public $catfields = array();
    public $channel_name = array();
    public $channels_array = array();
    public $reserved_cat_segment = '';
    public $use_category_names = false;
    public $cat_request = false;
    public $enable = array();   // modified by various tags with disable= parameter
    public $absolute_results = null;        // absolute total results returned by the tag, useful when paginating
    public $display_by = '';
    public $hidden_fields = []; //conditionally hidden fields for given entries
    protected $cat_field_models = array();

    // These are used with the nested category trees

    public $category_list = array();
    public $cat_full_array = array();
    public $cat_array = array();
    public $temp_array = array();
    public $category_count = 0;

    public $pagination;
    public $pager_sql = '';

    protected $chunks = array();

    protected $preview_conditions = array();

    // SQL cache key prefix
    protected $_sql_cache_prefix = 'sql_cache';

    // Misc. - Class variable usable by extensions
    public $misc = false;

    // Array of parameters allowed to be set dynamically
    private $_dynamic_parameters = array();

    public $query_string = '';

    /**
      * Constructor
      */
    public function __construct()
    {
        ee()->load->library('pagination');
        $this->pagination = ee()->pagination->create();

        $this->query_string = (ee()->uri->page_query_string != '') ? ee()->uri->page_query_string : ee()->uri->query_string;

        if (ee()->config->item("use_category_name") == 'y' && ee()->config->item("reserved_category_word") != '') {
            $this->use_category_names = ee()->config->item("use_category_name");
            $this->reserved_cat_segment = ee()->config->item("reserved_category_word");
        }

        // a number of tags utilize the disable= parameter, set it here
        if (isset(ee()->TMPL) && is_object(ee()->TMPL)) {
            $this->_fetch_disable_param();
        }

        $this->_dynamic_parameters = array('channel', 'entry_id', 'category', 'orderby',
            'sort', 'sticky', 'show_future_entries', 'show_expired', 'entry_id_from',
            'entry_id_to', 'not_entry_id', 'start_on', 'stop_before', 'year', 'month',
            'day', 'display_by', 'limit', 'username', 'status', 'group_id', 'primary_role_id', 'cat_limit',
            'month_limit', 'offset', 'author_id', 'url_title');
    }

    /**
      *  Initialize values
      */
    public function initialize()
    {
        $this->sql = '';
        $this->return_data = '';
    }

    /**
      *  Fetch Cache
      */
    public function fetch_cache($identifier = '')
    {
        $tag = ($identifier == '') ? ee()->TMPL->tagproper : ee()->TMPL->tagproper . $identifier;

        $tag .= $this->fetch_dynamic_params();

        return ee()->cache->get('/' . $this->_sql_cache_prefix . '/' . md5($tag . ee()->uri->uri_string()));
    }

    /**
      *  Save Cache
      */
    public function save_cache($sql, $identifier = '')
    {
        $tag = ($identifier == '') ? ee()->TMPL->tagproper : ee()->TMPL->tagproper . $identifier;

        return ee()->cache->save(
            '/' . $this->_sql_cache_prefix . '/' . md5($tag . ee()->uri->uri_string()),
            $sql,
            0   // No TTL, cache lives on till cleared
        );
    }

    /**
      *  Channel entries
      */
    public function entries()
    {
        // If the "related_categories" mode is enabled
        // we'll call the "related_categories" function
        // and bail out.

        if (ee()->TMPL->fetch_param('related_categories_mode') == 'yes') {
            return $this->related_category_entries();
        }
        // Onward...

        $this->initialize();

        $this->uri = ($this->query_string != '') ? $this->query_string : 'index.php';

        if ($this->enable['custom_fields'] == true) {
            $this->fetch_custom_channel_fields();
        }

        if ($this->enable['member_data'] == true) {
            $this->fetch_custom_member_fields();
        }

        if ($this->enable['pagination'] == true) {
            ee()->TMPL->tagdata = $this->pagination->prepare(ee()->TMPL->tagdata);
        }

        $save_cache = false;

        if (ee()->config->item('enable_sql_caching') == 'y' && ee()->TMPL->fetch_param('author_id') != 'CURRENT_USER') {
            if (false == ($this->sql = $this->fetch_cache())) {
                $save_cache = true;
            } else {
                if (ee()->TMPL->fetch_param('dynamic') != 'no') {
                    if (preg_match("#(^|\/)C(\d+)#", $this->query_string, $match) or in_array($this->reserved_cat_segment, explode("/", $this->query_string))) {
                        $this->cat_request = true;
                    }
                }
            }

            $this->chunks = $this->fetch_cache('chunks');

            if (($cache = $this->fetch_cache('pagination_count')) !== false) {
                // We need to establish the per_page limits if we're using
                // cached SQL because limits are normally created when building
                // the SQL query

                // Check to see if we can actually deal with cat_limit. Has
                // to have dynamic != 'no' and channel set with a category
                // in the uri_string somewhere
                $cat_limit = false;
                if (
                    (
                        in_array(ee()->config->item("reserved_category_word"), explode("/", ee()->uri->uri_string))
                        or preg_match("#(^|\/)C(\d+)#", ee()->uri->uri_string, $match)
                    )
                    and ee()->TMPL->fetch_param('dynamic') != 'no'
                    and ee()->TMPL->fetch_param('channel')
                ) {
                    $cat_limit = true;
                }

                if ($cat_limit and is_numeric(ee()->TMPL->fetch_param('cat_limit'))) {
                    $per_page = ee()->TMPL->fetch_param('cat_limit');
                } else {
                    $per_page = (! is_numeric(ee()->TMPL->fetch_param('limit'))) ? '100' : ee()->TMPL->fetch_param('limit');
                }

                if ($this->pagination->build(trim($cache), $per_page) == false) {
                    $this->sql = '';
                }
            }
        }

        if ($this->sql == '') {
            $this->build_sql_query();
        }

        if (! $this->isLivePreviewEntry() && $this->sql == '') {
            return ee()->TMPL->no_results();
        }

        if ($this->sql) {
            if ($save_cache == true) {
                $this->save_cache($this->sql);
                if (! empty($this->chunks)) {
                    $this->save_cache($this->chunks, 'chunks');
                }
            }

            $this->query = ee()->db->query($this->sql);

            // Spanning an entry pagination needs the query result
            if ($this->pagination->field_pagination == true) {
                $this->pagination->cfields = $this->cfields;
                $this->pagination->field_pagination_query = ($this->query->num_rows() == 1) ? $this->query : null;
                $this->pagination->build(1, 1);
            }

            // -------------------------------------
            //  "Relaxed" View Tracking
            //
            //  Some people have tags that are used to mimic a single-entry
            //  page without it being dynamic. This allows Entry View Tracking
            //  to work for ANY combination that results in only one entry
            //  being returned by the tag, including channel query caching.
            //
            //  Hidden Configuration Variable
            //  - relaxed_track_views => Allow view tracking on non-dynamic
            //      single entries (y/n)
            // -------------------------------------
            if (ee()->config->item('relaxed_track_views') === 'y' && $this->query->num_rows() == 1) {
                $this->hit_tracking_id = $this->query->row('entry_id') ;
            }
        }

        //only fetch catgories if those are enabled and called in template
        if ($this->enable['categories'] == true && (empty(ee()->TMPL->tagdata) || strpos(ee()->TMPL->tagdata, 'categories') !== false)) {
            $this->fetch_categories();
        }

        $this->parse_channel_entries();

        $this->track_views();

        if ($this->enable['pagination'] == true) {
            $this->return_data = $this->pagination->render($this->return_data);
        }

        return $this->return_data;
    }

    /**
      *  Track Views
      */
    public function track_views()
    {
        if (ee()->config->item('enable_entry_view_tracking') == 'n') {
            return;
        }

        if (! ee()->TMPL->fetch_param('track_views') or $this->hit_tracking_id === false) {
            return;
        }

        if ($this->pagination->field_pagination == true and $this->pagination->offset > 0) {
            return;
        }

        foreach (explode('|', ee()->TMPL->fetch_param('track_views')) as $view) {
            if (! in_array(strtolower($view), array("one", "two", "three", "four"))) {
                continue;
            }

            $sql = "UPDATE exp_channel_titles SET view_count_{$view} = (view_count_{$view} + 1) WHERE ";
            $sql .= (is_numeric($this->hit_tracking_id)) ? "entry_id = {$this->hit_tracking_id}" : "url_title = '" . ee()->db->escape_str($this->hit_tracking_id) . "'";

            ee()->db->query($sql);
        }
    }

    /**
      *  Fetch custom channel field IDs
      */
    public function fetch_custom_channel_fields()
    {
        if (
            isset(ee()->session->cache['channel']['custom_channel_fields']) &&
            isset(ee()->session->cache['channel']['date_fields']) &&
            isset(ee()->session->cache['channel']['relationship_fields']) &&
            isset(ee()->session->cache['channel']['grid_fields']) &&
            isset(ee()->session->cache['channel']['members_fields']) &&
            isset(ee()->session->cache['channel']['pair_custom_fields']) &&
            isset(ee()->session->cache['channel']['fluid_field_fields']) &&
            isset(ee()->session->cache['channel']['toggle_fields'])
        ) {
            $this->cfields = ee()->session->cache['channel']['custom_channel_fields'];
            $this->dfields = ee()->session->cache['channel']['date_fields'];
            $this->rfields = ee()->session->cache['channel']['relationship_fields'];
            $this->gfields = ee()->session->cache['channel']['grid_fields'];
            $this->msfields = ee()->session->cache['channel']['members_fields'];
            $this->pfields = ee()->session->cache['channel']['pair_custom_fields'];
            $this->ffields = ee()->session->cache['channel']['fluid_field_fields'];
            $this->tfields = ee()->session->cache['channel']['toggle_fields'];

            return;
        }

        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');

        $fields = ee()->api_channel_fields->fetch_custom_channel_fields();

        $this->cfields = $fields['custom_channel_fields'];
        $this->dfields = $fields['date_fields'];
        $this->rfields = $fields['relationship_fields'];
        $this->msfields = $fields['members_fields'];
        $this->gfields = $fields['grid_fields'];
        $this->pfields = $fields['pair_custom_fields'];
        $this->ffields = $fields['fluid_field_fields'];
        $this->tfields = $fields['toggle_fields'];

        // If there are install-wide fields, make them available to each site
        if (isset($this->cfields[0])) {
            $sites = ee('Model')->get('Site')
                ->fields('site_id', 'site_name')
                ->all(true);
            $site_ids = $sites->getIds();

            foreach (['cfields', 'dfields', 'rfields', 'gfields', 'msfields', 'pfields', 'ffields', 'tfields'] as $custom_fields) {
                $tmp = $this->$custom_fields;

                if (! isset($tmp[0])) {
                    continue;
                }

                foreach ($site_ids as $site_id) {
                    if (! isset($tmp[$site_id])) {
                        $tmp[$site_id] = $tmp[0];
                    } else {
                        $tmp[$site_id] = $tmp[0] + $tmp[$site_id];
                    }
                }

                $this->$custom_fields = $tmp;
            }
        }

        ee()->session->cache['channel']['custom_channel_fields'] = $this->cfields;
        ee()->session->cache['channel']['date_fields'] = $this->dfields;
        ee()->session->cache['channel']['relationship_fields'] = $this->rfields;
        ee()->session->cache['channel']['members_fields'] = $this->msfields;
        ee()->session->cache['channel']['grid_fields'] = $this->gfields;
        ee()->session->cache['channel']['pair_custom_fields'] = $this->pfields;
        ee()->session->cache['channel']['fluid_field_fields'] = $this->ffields;
        ee()->session->cache['channel']['toggle_fields'] = $this->tfields;
    }

    /**
      *  Fetch custom member field IDs
      */
    public function fetch_custom_member_fields()
    {
        if (
            isset(ee()->session->cache['channel']['custom_member_fields']) &&
            isset(ee()->session->cache['channel']['custom_member_field_pairs'])
        ) {
            $this->mfields = ee()->session->cache['channel']['custom_member_fields'];
            $this->mpfields = ee()->session->cache['channel']['custom_member_field_pairs'];
            return;
        }
        
        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');

        $this->mfields = ee()->api_channel_fields->fetch_custom_member_fields();
        $this->mpfields = ee()->api_channel_fields->custom_member_field_pairs;

        ee()->session->cache['channel']['custom_member_fields'] = $this->mfields;
        ee()->session->cache['channel']['custom_member_fields'] = $this->mpfields;
    }

    /**
      *  Fetch categories
      */
    public function fetch_categories()
    {
        if (! is_null($this->query)) {
            list($field_sqla, $field_sqlb) = $this->generateCategoryFieldSQL();

            $sql = "SELECT c.cat_name, c.cat_url_title, c.cat_id, c.cat_image, c.cat_description, c.parent_id,
                            p.cat_id, p.entry_id, c.group_id {$field_sqla}
                    FROM    (exp_categories AS c, exp_category_posts AS p)
                    {$field_sqlb}
                    WHERE   c.cat_id = p.cat_id
                    AND     p.entry_id IN (";

            $categories = array();

            foreach ($this->query->result_array() as $row) {
                $categories[] = $row['entry_id'];
            }

            if (empty($categories) && ! ee('LivePreview')->hasEntryData()) {
                return;
            }

            $sql .= implode(',', array_unique(array_filter($categories))) . ')';

            $sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order";

            $query = ee()->db->query($sql);

            if ($query->num_rows() == 0 && ! ee('LivePreview')->hasEntryData()) {
                return;
            }

            foreach ($categories as $val) {
                $this->temp_array = array();
                $this->cat_array = array();
                $parents = array();

                foreach ($query->result_array() as $row) {
                    if ($val == $row['entry_id']) {
                        $this->temp_array[$row['cat_id']] = array($row['cat_id'], $row['parent_id'], $row['cat_name'], $row['cat_image'], $row['cat_description'], $row['group_id'], $row['cat_url_title']);

                        foreach ($row as $k => $v) {
                            if (strpos($k, 'field') !== false) {
                                $this->temp_array[$row['cat_id']][$k] = $v;
                            }
                        }

                        if ($row['parent_id'] > 0 && ! isset($this->temp_array[$row['parent_id']])) {
                            $parents[$row['parent_id']] = '';
                        }
                        unset($parents[$row['cat_id']]);
                    }
                }

                if (count($this->temp_array) == 0) {
                    $temp = false;
                } else {
                    foreach ($this->temp_array as $k => $v) {
                        if (isset($parents[$v[1]])) {
                            $v[1] = 0;
                        }

                        if (0 == $v[1]) {
                            $this->cat_array[] = $this->temp_array[$k];
                            $this->process_subcategories($k);
                        }
                    }
                }

                $this->categories[$val] = $this->cat_array;
            }

            unset($this->temp_array);
            unset($this->cat_array);
        }

        if (ee('LivePreview')->hasEntryData()) {
            $data = ee('LivePreview')->getEntryData();
            unset($this->categories[$data['entry_id']]);

            $cats = [];

            if (isset($data['categories']) && is_array($data['categories'])) {
                foreach ($data['categories'] as $cat_group) {
                    foreach ($cat_group as $cat) {
                        $cats[] = $cat;
                    }
                }
            }

            $this->temp_array = array();
            $this->cat_array = array();
            $parents = array();

            $categories = ee('Model')->get('Category', $cats)->all();
            foreach ($categories as $cat) {
                $this->temp_array[$cat->cat_id] = array($cat->cat_id, $cat->parent_id, $cat->cat_name, $cat->cat_image, $cat->cat_description, $cat->group_id, $cat->cat_url_title);
                if ($cat->parent_id > 0 && ! isset($this->temp_array[$cat->parent_id])) {
                    $parents[$cat->parent_id] = '';
                }
                unset($parents[$cat->cat_id]);
            }

            foreach ($this->temp_array as $k => $v) {
                if (isset($parents[$v[1]])) {
                    $v[1] = 0;
                }

                if (0 == $v[1]) {
                    $this->cat_array[] = $this->temp_array[$k];
                    $this->process_subcategories($k);
                }
            }

            $this->categories[$data['entry_id']] = $this->cat_array;
        }
    }

    /**
     * Fetch dynamic parameters
     *
     * Processes dynamic parameters, setting values based on $_GET/$_POST
     * Returns a string formatted for use in a tag and sets adds them to
     * TMPL->tagparams
     *
     * @return  string  The dynamic parameters formatted for use in a tag
     */
    public function fetch_dynamic_params()
    {
        $tag = '';

        if (ee()->TMPL->fetch_param('dynamic_parameters') === false or (empty($_POST) && empty($_GET))) {
            return $tag;
        }

        // Swap out a placeholder for [&] and [|]
        $placeholders = array('[*PIPE*]', '[*AMP*]');
        $dynamic_params = explode('|', str_replace(array('[|]', '[&]'), $placeholders, ee()->TMPL->fetch_param('dynamic_parameters')));

        foreach ($dynamic_params as $var) {
            // We default to pipes for joining arrays
            $modifier = '|';

            // Do we have a pipe or ampersand?
            if (strpos($var, '[*') !== false) {
                if (substr($var, -8) == '[*PIPE*]') {
                    $var = substr($var, 0, -8);
                    $modifier = '|';
                } elseif (substr($var, -7) == '[*AMP*]') {
                    $var = substr($var, 0, -7);
                    $modifier = '&';
                }
            }

            if (ee()->input->get_post($var)) {
                if (in_array($var, $this->_dynamic_parameters)) {
                    // Allow arrays
                    $param_value = ee()->input->get_post($var);

                    if (is_array($param_value)) {
                        // Drop empty, leave 0
                        $param_value = array_filter($param_value, 'strlen');
                        $param_value = rtrim(implode($modifier, $param_value), $modifier);
                    }

                    $tag .= $var . '="' . $param_value . '"';
                    ee()->TMPL->tagparams[$var] = $param_value;
                } elseif (strncmp($var, 'search:', 7) == 0) {
                    // Search uses double ampersands
                    $modifier = ($modifier == '&') ? '&&' : '|';

                    // Allow arrays
                    $param_value = ee()->input->get_post($var);

                    if (is_array($param_value)) {
                        $param_value = array_filter($param_value, 'strlen');
                        $param_value = rtrim(implode($modifier, $param_value), $modifier);
                    }

                    $tag .= substr($var, 7) . '="' . $param_value . '"';
                    ee()->TMPL->search_fields[substr($var, 7)] = $param_value;
                }
            }
        }

        return $tag;
    }

    /****************************************************************
    * Field Searching
    *
    *   Generate the sql for the where clause to implement field
    *  searching.  Implements cross site field searching with a
    *  sloppy search, IE if there are any fields with the same name
    *  in any of the sites specified in the [ site="" ] parameter then
    *  all of those fields will be searched.
    *
    *****************************************************************/

    /**
     * Generate the SQL where condition to handle the {exp:channel:entries}
     * field search parameter -- search:field="".  There are two primary
     * syntax possibilities:
     *  search:field="words|other words"
     *
     * and
     *  search:field="=words|other words"
     * The first performs a LIKE "%words%" OR LIKE "%other words%".  The second
     * one performs an ="words" OR ="other words".  Other possibilities are
     * prepending "not" to negate the search:
     *
     *  search:field="not words|other words"
     * And using IS_EMPTY to indicate an empty field.
     *  search:field ="IS_EMPTY"
     *  search:field="not IS_EMPTY"
     *  search:field="=IS_EMPTY"
     *  search:field="=not IS_EMPTY"
     * All of these may be combined:
     *
     *  search:field="not IS_EMPTY|words"
     */
    private function _generate_field_search_sql($search_fields, $legacy_fields, $site_ids)
    {
        $sql = '';

        ee()->load->model('channel_model');

        foreach ($search_fields as $field_name => $search_terms) {
            // Log empty terms to notify the user.
            if ($search_terms == '' || $search_terms === '=') {
                ee()->TMPL->log_item('WARNING: Field search parameter for field "' . $field_name . '" was empty.  If you wish to search for an empty field, use IS_EMPTY.');

                continue;
            }

            $fields_sql = '';
            $search_terms = trim($search_terms);

            // Note- if a 'contains' search goes through with an empty string
            // the resulting sql looks like: LIKE "%%"
            // While it doesn't throw an error, there's no point in adding the overhead.
            if ($search_terms == '' or $search_terms == '=') {
                continue;
            }

            $sites = ($site_ids ? $site_ids : array(ee()->config->item('site_id')));
            foreach ($sites as $site_name => $site_id) {
                // We're goign to repeat the search on each site
                // so store the terms in a temp.  FIXME Necessary?
                $terms = $search_terms;
                if (in_array($field_name, ['title', 'url_title'])) {
                    $table = 't';
                    $search_column_name = $table . '.' . $field_name;
                } elseif (! isset($this->cfields[$site_id][$field_name])) {
                    continue;
                }

                // If fields_sql isn't empty then this isn't a first
                // loop and we have terms that need to be ored together.
                if ($fields_sql !== '') {
                    $fields_sql .= ' OR ';
                }

                if (!isset($search_column_name)) {
                    $field_id = $this->cfields[$site_id][$field_name];
                    $table = (isset($legacy_fields[$field_id])) ? "wd" : "exp_channel_data_field_{$field_id}";
                    $search_column_name = $table . '.field_id_' . $this->cfields[$site_id][$field_name];
                    if (ee()->config->item('show_profiler') === 'y') {
                        if (isset($this->rfields[$site_id][$field_name])) {
                            ee()->TMPL->log_item('WARNING: Using Relationship fields in `search` parameter is not supported.');
                        } elseif (isset($this->gfields[$site_id][$field_name])) {
                            ee()->TMPL->log_item('NOTE: Using Grid fields in `search` parameter requires the field to be marked as searchable.');
                        } elseif (isset($this->ffields[$site_id][$field_name])) {
                            ee()->TMPL->log_item('NOTE: Using Fluid fields in `search` parameter requires the field to be marked as searchable.');
                        }
                    }
                }

                $fields_sql .= ee()->channel_model->field_search_sql($terms, $search_column_name, $site_id);
                unset($search_column_name);
            } // foreach($sites as $site_id)
            if (! empty($fields_sql)) {
                $sql .= ' AND (' . $fields_sql . ')';
            }
        }

        return $sql;
    }

    /**
      *  Build SQL query
      */
    public function build_sql_query($qstring = '')
    {
        $entry_id = '';
        $year = '';
        $month = '';
        $day = '';
        $qtitle = '';
        $cat_id = '';
        $corder = array();
        $offset = 0;
        $page_marker = false;
        $dynamic = true;

        // Is dynamic='off' set?
        // If so, we'll override all dynamically set variables
        if (ee()->TMPL->fetch_param('dynamic') == 'no') {
            $dynamic = false;
        }

        /**------
        /**  Do we allow dynamic POST variables to set parameters?
        /**------*/

        $this->fetch_dynamic_params();

        /**------
        /**  Parse the URL query string
        /**------*/

        $this->uristr = ee()->uri->uri_string;

        if ($qstring == '') {
            $qstring = $this->query_string;
        }

        if ($qstring == '') {
            if (ee()->TMPL->fetch_param('require_entry') == 'yes') {
                return '';
            }
        } else {
            /** --------------------------------------
            /**  Do we have a pure ID number?
            /** --------------------------------------*/
            if ($dynamic && is_numeric($qstring)) {
                $entry_id = $qstring;
            } else {
                $uri_has_digit = preg_match('/[0-9]/', $qstring);

                /** --------------------------------------
                /**  Parse day
                /** --------------------------------------*/
                if ($dynamic && $uri_has_digit && preg_match("#(^|\/)(\d{4}/\d{2}/\d{2})#", $qstring, $match)) {
                    $ex = explode('/', $match[2]);

                    $year = $ex[0];
                    $month = $ex[1];
                    $day = $ex[2];

                    $qstring = trim_slashes(str_replace($match[0], '', $qstring));
                }

                /** --------------------------------------
                /**  Parse /year/month/
                /** --------------------------------------*/

                // added (^|\/) to make sure this doesn't trigger with url titles like big_party_2006
                if ($dynamic && $uri_has_digit && preg_match("#(^|\/)(\d{4}/\d{2})(\/|$)#", $qstring, $match)) {
                    $ex = explode('/', $match[2]);

                    $year = $ex[0];
                    $month = $ex[1];

                    $qstring = trim_slashes(str_replace($match[2], '', $qstring));
                }

                /** --------------------------------------
                /**  Parse ID indicator
                /** --------------------------------------*/
                if ($dynamic && $uri_has_digit && preg_match("#^(\d+)(.*)#", $qstring, $match)) {
                    $seg = (! isset($match[2])) ? '' : $match[2];

                    if (substr($seg, 0, 1) == "/" or $seg == '') {
                        $entry_id = $match[1];
                        $qstring = trim_slashes(preg_replace("#^" . $match[1] . "#", '', $qstring));
                    }
                }

                /** --------------------------------------
                /**  Parse page number
                /** --------------------------------------*/
                if (($dynamic or ee()->TMPL->fetch_param('paginate')) && $uri_has_digit && preg_match("#^P(\d+)|/P(\d+)#", $qstring, $match)) {
                    $this->uristr = reduce_double_slashes(str_replace($match[0], '', $this->uristr));
                    $qstring = trim_slashes(str_replace($match[0], '', $qstring));
                    $page_marker = true;
                }

                /** --------------------------------------
                /**  Parse category indicator
                /** --------------------------------------*/

                // Text version of the category

                if ($qstring != '' and $this->reserved_cat_segment != '' and in_array($this->reserved_cat_segment, explode("/", $qstring)) and $dynamic and ee()->TMPL->fetch_param('channel')) {
                    $qstring = preg_replace("/(.*?)\/" . preg_quote($this->reserved_cat_segment) . "\//i", '', '/' . $qstring);

                    $sql = "SELECT exp_channel_category_groups.channel_id, exp_channel_category_groups.group_id FROM exp_channel_category_groups LEFT JOIN exp_channels ON exp_channel_category_groups.channel_id=exp_channels.channel_id WHERE site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') AND ";

                    $xsql = ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');

                    if (substr($xsql, 0, 3) == 'AND') {
                        $xsql = substr($xsql, 3);
                    }

                    $sql .= ' ' . $xsql . ' ORDER BY exp_channel_category_groups.channel_id';

                    $query = ee()->db->query($sql);

                    if ($query->num_rows() > 0) {
                        $valid_cats = [];

                        if (ee()->TMPL->fetch_param('relaxed_categories') == 'yes') {
                            foreach ($query->result_array() as $row) {
                                $valid_cats[] = $row['group_id'];
                            }
                        } else {
                            $channel_cat_groups = [];
                            foreach ($query->result_array() as $row) {
                                if (!isset($channel_cat_groups[$row['channel_id']])) {
                                    $channel_cat_groups[$row['channel_id']] = [];
                                }
                                $channel_cat_groups[$row['channel_id']][] = $row['group_id'];
                            }
                            if (count($channel_cat_groups) == 1) {
                                $valid_cats = $channel_cat_groups[array_keys($channel_cat_groups)[0]];
                            } else {
                                $valid_cats = call_user_func_array('array_intersect', $channel_cat_groups);
                            }
                        }

                        $valid_cats = array_unique($valid_cats);

                        if (count($valid_cats) == 0) {
                            return '';
                        }

                        // the category URL title should be the first segment left at this point in $qstring,
                        // but because prior to this feature being added, category names were used in URLs,
                        // and '/' is a valid character for category names.  If they have not updated their
                        // category url titles since updating to 1.6, their category URL title could still
                        // contain a '/'.  So we'll try to get the category the correct way first, and if
                        // it fails, we'll try the whole $qstring

                        // do this as separate commands to work around a PHP 5.0.x bug
                        $arr = explode('/', $qstring);
                        $cut_qstring = array_shift($arr);
                        unset($arr);

                        $result = ee()->db->query("SELECT cat_id FROM exp_categories
                            WHERE cat_url_title='" . ee()->db->escape_str($cut_qstring) . "'
                            AND group_id IN ('" . implode("','", $valid_cats) . "')");

                        if ($result->num_rows() == 1) {
                            $qstring = str_replace($cut_qstring, 'C' . $result->row('cat_id'), $qstring);
                            $cat_id = $result->row('cat_id');
                        } else {
                            // give it one more try using the whole $qstring
                            $result = ee()->db->query("SELECT cat_id FROM exp_categories
                                WHERE cat_url_title='" . ee()->db->escape_str($qstring) . "'
                                AND group_id IN ('" . implode("','", $valid_cats) . "')");

                            if ($result->num_rows() == 1) {
                                $qstring = 'C' . $result->row('cat_id') ;
                                $cat_id = $result->row('cat_id');
                            } else {
                                return '';
                            }
                        }
                    }
                }

                // If we got here, category may be numeric
                if (empty($cat_id)) {
                    ee()->load->helper('segment');
                    $cat_id = parse_category($this->query_string);
                }

                // If we were able to get a numeric category ID
                if (is_numeric($cat_id) and $cat_id !== false) {
                    $this->cat_request = true;
                } else {
                    // parse_category did not return a numberic ID, blow away $cat_id
                    $cat_id = false;
                }

                /** --------------------------------------
                /**  Remove "N"
                /** --------------------------------------*/

                // The recent comments feature uses "N" as the URL indicator
                // It needs to be removed if presenst

                if ($uri_has_digit && preg_match("#^N(\d+)|/N(\d+)#", $qstring, $match)) {
                    $this->uristr = reduce_double_slashes(str_replace($match[0], '', $this->uristr));

                    $qstring = trim_slashes(str_replace($match[0], '', $qstring));
                }

                /** --------------------------------------
                /**  Parse URL title
                /** --------------------------------------*/
                if (($cat_id == '' and $year == '') or ee()->TMPL->fetch_param('require_entry') == 'yes') {
                    if (strpos($qstring, '/') !== false) {
                        $xe = explode('/', $qstring);
                        $qstring = current($xe);
                    }

                    if ($dynamic == true) {
                        $sql = "SELECT count(*) AS count
                                FROM  exp_channel_titles
                                WHERE exp_channel_titles.site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";

                        if ($entry_id != '') {
                            $sql .= " AND exp_channel_titles.entry_id = '" . ee()->db->escape_str($entry_id) . "'";
                        } else {
                            $sql .= " AND exp_channel_titles.url_title = '" . ee()->db->escape_str($qstring) . "'";
                        }

                        $query = ee()->db->query($sql);

                        if ($query->row('count') == 0) {
                            if (ee()->TMPL->fetch_param('require_entry') == 'yes') {
                                return '';
                            }
                        } elseif ($entry_id == '') {
                            $qtitle = $qstring;
                        }
                    }
                }
            }
        }

        /**------
        /**  Entry ID number
        /**------*/

        // If the "entry ID" was hard-coded, use it instead of
        // using the dynamically set one above

        if (ee()->TMPL->fetch_param('entry_id')) {
            $entry_id = ee()->TMPL->fetch_param('entry_id');
        }

        /**------
        /**  Only Entries with Pages
        /**------*/

        if (ee()->TMPL->fetch_param('show_pages') !== false && in_array(ee()->TMPL->fetch_param('show_pages'), array('only', 'no'))) {
            $pages_uris = array();

            foreach (ee()->TMPL->site_ids as $site_id) {
                if ($site_id != ee()->config->item('site_id')) {
                    $pages = ee()->config->site_pages($site_id);
                } else {
                    $pages = ee()->config->item('site_pages');
                }

                if (empty($pages)) {
                    continue;
                }

                foreach ($pages as $data) {
                    $pages_uris += $data['uris'];
                }
            }

            if (count($pages_uris) > 0 or ee()->TMPL->fetch_param('show_pages') == 'only') {
                $pages_uri_ids = array_keys($pages_uris);

                // consider entry_id
                if (ee()->TMPL->fetch_param('entry_id') !== false) {
                    $not = false;

                    if (strncmp($entry_id, 'not', 3) == 0) {
                        $not = true;
                        $entry_id = trim(substr($entry_id, 3));
                    }

                    $ids = explode('|', $entry_id);

                    if (ee()->TMPL->fetch_param('show_pages') == 'only') {
                        if ($not === true) {
                            $entry_id = implode('|', array_diff(array_flip($pages_uris), $ids));
                        } else {
                            $entry_id = implode('|', array_diff($ids, array_diff($ids, $pages_uri_ids)));
                        }
                    } else {
                        if ($not === true) {
                            $entry_id = "not {$entry_id}|" . implode('|', $pages_uri_ids);
                        } else {
                            $entry_id = implode('|', array_diff($ids, $pages_uri_ids));
                        }
                    }
                } else {
                    $entry_id = ((ee()->TMPL->fetch_param('show_pages') == 'no') ? 'not ' : '') . implode('|', $pages_uri_ids);
                }

                //  No pages and show_pages only
                if ($entry_id == '' && ee()->TMPL->fetch_param('show_pages') == 'only') {
                    $this->sql = '';

                    return;
                }
            }
        }

        /**------
        /**  Passing the order variables
        /**------*/

        $order = ee()->TMPL->fetch_param('orderby');
        $sort = ee()->TMPL->fetch_param('sort');
        $sticky = ee()->TMPL->fetch_param('sticky');

        /** -------------------------------------
        /**  Multiple Orders and Sorts...
        /** -------------------------------------*/
        if ($order !== false && stristr($order, '|')) {
            $order_array = explode('|', $order);

            if ($order_array[0] == 'random') {
                $order_array = array('random');
            }
        } else {
            $order_array = array($order);
        }

        if ($sort !== false && stristr($sort, '|')) {
            $sort_array = explode('|', $sort);
        } else {
            $sort_array = array($sort);
        }

        /** -------------------------------------
        /**  Validate Results for Later Processing
        /** -------------------------------------*/
        $base_orders = array('status', 'random', 'entry_id', 'date', 'entry_date', 'title', 'url_title', 'edit_date', 'comment_total', 'username', 'screen_name', 'most_recent_comment', 'expiration_date',
            'view_count_one', 'view_count_two', 'view_count_three', 'view_count_four');

        foreach ($order_array as $key => $order) {
            if (! in_array($order, $base_orders)) {
                if (false !== $order) {
                    $set = 'n';

                    /** -------------------------------------
                    /**  Site Namespace is Being Used, Parse Out
                    /** -------------------------------------*/
                    if (strpos($order, ':') !== false) {
                        $order_parts = explode(':', $order, 2);

                        if (isset(ee()->TMPL->site_ids[$order_parts[0]]) && isset($this->cfields[ee()->TMPL->site_ids[$order_parts[0]]][$order_parts[1]])) {
                            $corder[$key] = $this->cfields[ee()->TMPL->site_ids[$order_parts[0]]][$order_parts[1]];
                            $order_array[$key] = 'custom_field';
                            $set = 'y';
                        }
                    }

                    /** -------------------------------------
                    /**  Find the Custom Field, Cycle Through All Sites for Tag
                    /**  - If multiple sites have the same short_name for a field, we do a CONCAT ORDERBY in query
                    /** -------------------------------------*/
                    if ($set == 'n') {
                        foreach ($this->cfields as $site_id => $cfields) {
                            // Only those sites specified
                            if (! in_array($site_id, ee()->TMPL->site_ids)) {
                                continue;
                            }

                            if (isset($cfields[$order])) {
                                if ($set == 'y') {
                                    $corder[$key] .= '|' . $cfields[$order];
                                } else {
                                    $corder[$key] = $cfields[$order];
                                    $order_array[$key] = 'custom_field';
                                    $set = 'y';
                                }
                            }
                        }
                    }

                    if ($set == 'n') {
                        $order_array[$key] = false;
                    }
                }
            }

            if (! isset($sort_array[$key])) {
                $sort_array[$key] = 'desc';
            }
        }

        foreach ($sort_array as $key => $sort) {
            if ($sort == false or ($sort != 'asc' and $sort != 'desc')) {
                $sort_array[$key] = "desc";
            }
        }

        // fixed entry id ordering
        if (($fixed_order = ee()->TMPL->fetch_param('fixed_order')) === false or preg_match('/[^0-9\|]/', $fixed_order)) {
            $fixed_order = false;
        } else {
            // MySQL will not order the entries correctly unless the results are constrained
            // to matching rows only, so we force the entry_id as well
            $entry_id = $fixed_order;
            $fixed_order = preg_split('/\|/', $fixed_order, -1, PREG_SPLIT_NO_EMPTY);

            // some peeps might want to be able to 'flip' it
            // the default sort order is 'desc' but in this context 'desc' has a stronger "reversing"
            // connotation, so we look not at the sort array, but the tag parameter itself, to see the user's intent
            if ($sort == 'desc') {
                $fixed_order = array_reverse($fixed_order);
            }
        }

        /**------
        /**  Build the master SQL query
        /**------*/

        $sql_a = "SELECT ";

        $sql_b = (ee()->TMPL->fetch_param('category') or ee()->TMPL->fetch_param('category_group') or $cat_id != '' or $order_array[0] == 'random') ? "DISTINCT t.entry_id " : "t.entry_id ";

        $sql_b .= ", exp_channels.channel_id ";

        $sql = "FROM exp_channel_titles AS t
                LEFT JOIN exp_channels ON t.channel_id = exp_channels.channel_id ";

        if (in_array('custom_field', $order_array)) {
            $sql .= "LEFT JOIN exp_channel_data AS wd ON t.entry_id = wd.entry_id ";
        } elseif (! empty(ee()->TMPL->search_fields)) {
            $sql .= "LEFT JOIN exp_channel_data AS wd ON wd.entry_id = t.entry_id ";
        }

        $join_member_table = false;
        $member_join = "LEFT JOIN exp_members AS m ON m.member_id = t.author_id ";

        if (ee()->TMPL->fetch_param('category') or ee()->TMPL->fetch_param('category_group') or ($cat_id != '' && $dynamic == true)) {
            /* --------------------------------
            /*  We use LEFT JOIN when there is a 'not' so that we get
            /*  entries that are not assigned to a category.
            /* --------------------------------*/

            if ((substr(ee()->TMPL->fetch_param('category_group'), 0, 3) == 'not' or substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not') && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no') {
                $sql .= "LEFT JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
                         LEFT JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ";
            } else {
                $sql .= "INNER JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
                         INNER JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ";
            }
        }

        $sql .= "WHERE t.entry_id != '' AND t.site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";

        /**------
        /**  We only select entries that have not expired
        /**------*/

        $timestamp = ee()->localize->now;

        if (ee()->TMPL->fetch_param('show_future_entries') != 'yes') {
            $sql .= " AND t.entry_date <= " . $timestamp . " ";
        }

        if (ee()->TMPL->fetch_param('show_expired') == 'only') {
            $sql .= " AND (t.expiration_date != 0 AND t.expiration_date <= " . $timestamp . ") ";
        } elseif (ee()->TMPL->fetch_param('show_expired') != 'yes') {
            $sql .= " AND (t.expiration_date = 0 OR t.expiration_date > " . $timestamp . ") ";
        }

        // Only Sticky Entries
        if (ee()->TMPL->fetch_param('sticky') == 'only') {
            $sql .= " AND t.sticky = 'y' ";
        } elseif (ee()->TMPL->fetch_param('sticky') == 'none') {
            $sql .= " AND t.sticky != 'y' ";
        }

        /**------
        /**  Limit query by post ID for individual entries
        /**------*/

        if ($entry_id != '') {
            $sql .= ee()->functions->sql_andor_string($entry_id, 't.entry_id') . ' ';
        }

        /**------
        /**  Limit query by post url_title for individual entries
        /**------*/

        if ($url_title = ee()->TMPL->fetch_param('url_title')) {
            $sql .= ee()->functions->sql_andor_string($url_title, 't.url_title') . ' ';
        }

        /**------
        /**  Limit query by entry_id range
        /**------*/

        if ($entry_id_from = ee()->TMPL->fetch_param('entry_id_from')) {
            $sql .= "AND t.entry_id >= '$entry_id_from' ";
        }

        if ($entry_id_to = ee()->TMPL->fetch_param('entry_id_to')) {
            $sql .= "AND t.entry_id <= '$entry_id_to' ";
        }

        /**------
        /**  Exclude an individual entry
        /**------*/
        if ($not_entry_id = ee()->TMPL->fetch_param('not_entry_id')) {
            $sql .= (! is_numeric($not_entry_id))
                    ? "AND t.url_title != '{$not_entry_id}' "
                    : "AND t.entry_id  != '{$not_entry_id}' ";
        }

        /**------
        /**  Limit to/exclude specific channels
        /**------*/

        if ($channel = ee()->TMPL->fetch_param('channel')) {
            $channels = ee('Model')->get('Channel')->fields('channel_id', 'channel_name')->all(true);
            $channelInOperator = 'IN';
            if (strpos($channel, 'not ') === 0) {
                $channelInOperator = 'NOT IN';
                $channel = substr($channel, 4);
            }
            if (strpos($channel, '|') !== false) {
                $options = preg_split('/\|/', $channel, -1, PREG_SPLIT_NO_EMPTY);
                $options = array_map('trim', $options);
            } elseif (! empty($channel)) {
                $options = [$channel];
            }
            $channel_ids = array();
            foreach ($options as $option) {
                foreach ($channels as $channelModel) {
                    if (strtolower($option) == strtolower($channelModel->channel_name)) {
                        $channel_ids[] = $channelModel->channel_id;
                    }
                }
            }

            if (empty($channel_ids)) {
                if ($channelInOperator == 'IN') {
                    return '';
                }
            } else {
                $sql .= "AND t.channel_id " . $channelInOperator . " (" . implode(',', $channel_ids) . ") ";
            }
        }

        /**------------
        /**  Limit query by date range given in tag parameters
        /**------------*/
        if (ee()->TMPL->fetch_param('start_on')) {
            $sql .= "AND t.entry_date >= '" . ee()->localize->string_to_timestamp(ee()->TMPL->fetch_param('start_on')) . "' ";
        }

        if (ee()->TMPL->fetch_param('stop_before')) {
            $sql .= "AND t.entry_date < '" . ee()->localize->string_to_timestamp(ee()->TMPL->fetch_param('stop_before')) . "' ";
        }

        /**-------------
        /**  Limit query by date contained in tag parameters
        /**-------------*/

        ee()->load->helper('date');

        if (ee()->TMPL->fetch_param('year') or ee()->TMPL->fetch_param('month') or ee()->TMPL->fetch_param('day')) {
            $year = (! is_numeric(ee()->TMPL->fetch_param('year'))) ? date('Y') : ee()->TMPL->fetch_param('year');
            $smonth = (! is_numeric(ee()->TMPL->fetch_param('month'))) ? '01' : ee()->TMPL->fetch_param('month');
            $emonth = (! is_numeric(ee()->TMPL->fetch_param('month'))) ? '12' : ee()->TMPL->fetch_param('month');
            $day = (! is_numeric(ee()->TMPL->fetch_param('day'))) ? '' : ee()->TMPL->fetch_param('day');

            if ($day != '' and ! is_numeric(ee()->TMPL->fetch_param('month'))) {
                $smonth = date('m');
                $emonth = date('m');
            }

            if (strlen($smonth) == 1) {
                $smonth = '0' . $smonth;
            }

            if (strlen($emonth) == 1) {
                $emonth = '0' . $emonth;
            }

            if ($day == '') {
                $sday = 1;
                $eday = days_in_month($emonth, $year);
            } else {
                $sday = $day;
                $eday = $day;
            }

            $stime = ee()->localize->string_to_timestamp($year . '-' . $smonth . '-' . $sday . ' 00:00');
            $etime = ee()->localize->string_to_timestamp($year . '-' . $emonth . '-' . $eday . ' 23:59');

            if ($stime && $etime) {
                $sql .= " AND t.entry_date >= " . $stime . " AND t.entry_date <= " . $etime . " ";
            } else {
                // Log invalid date to notify the user.
                ee()->TMPL->log_item('WARNING: Invalid date parameter, limiting by year/month/day skipped.');
            }
        } else {
            /**--------
            /**  Limit query by date in URI: /2003/12/14/
            /**---------*/

            if ($year != '' and $month != '' and $dynamic == true) {
                if ($day == '') {
                    $sday = 1;
                    $eday = days_in_month($month, $year);
                } else {
                    $sday = $day;
                    $eday = $day;
                }

                $stime = ee()->localize->string_to_timestamp($year . '-' . $month . '-' . $sday . ' 00:00:00');
                $etime = ee()->localize->string_to_timestamp($year . '-' . $month . '-' . $eday . ' 23:59:59');

                if ($stime && $etime) {
                    $sql .= " AND t.entry_date >= " . $stime . " AND t.entry_date <= " . $etime . " ";
                } else {
                    // Log invalid date to notify the user.
                    ee()->TMPL->log_item('WARNING: Invalid date URI, limiting by year/month/day skipped.');
                }
            } else {
                $this->display_by = ee()->TMPL->fetch_param('display_by');

                $lim = (! is_numeric(ee()->TMPL->fetch_param('limit'))) ? '1' : ee()->TMPL->fetch_param('limit');

                /**---
                /**  If display_by = "month"
                /**---*/

                if ($this->display_by == 'month') {
                    // We need to run a query and fetch the distinct months in which there are entries

                    $dql = "SELECT t.year, t.month " . $sql;

                    /**------
                    /**  Add status declaration
                    /**------*/

                    if ($status = ee()->TMPL->fetch_param('status')) {
                        $status = str_replace('Open', 'open', $status);
                        $status = str_replace('Closed', 'closed', $status);

                        $sstr = ee()->functions->sql_andor_string($status, 't.status');

                        if (stristr($sstr, "'closed'") === false) {
                            $sstr .= " AND t.status != 'closed' ";
                        }

                        $dql .= $sstr;
                    } else {
                        $dql .= "AND t.status = 'open' ";
                    }

                    $query = ee()->db->query($dql);

                    $distinct = array();

                    if ($query->num_rows() > 0) {
                        foreach ($query->result_array() as $row) {
                            $distinct[] = $row['year'] . $row['month'];
                        }

                        $distinct = array_unique($distinct);

                        sort($distinct);

                        if ($sort_array[0] == 'desc') {
                            $distinct = array_reverse($distinct);
                        }

                        $this->pagination->total_items = count($distinct);

                        $cur = ($this->pagination->offset == '') ? 0 : $this->pagination->offset;

                        $distinct = array_slice($distinct, $cur, $lim);

                        if ($distinct != false) {
                            $sql .= "AND (";

                            foreach ($distinct as $val) {
                                $sql .= "(t.year  = '" . substr($val, 0, 4) . "' AND t.month = '" . substr($val, 4, 2) . "') OR";
                            }

                            $sql = substr($sql, 0, -2) . ')';
                        }
                    }
                } elseif ($this->display_by == 'day') {
                    /**---
                    /**  If display_by = "day"
                    /**---*/

                    // We need to run a query and fetch the distinct days in which there are entries

                    $dql = "SELECT t.year, t.month, t.day " . $sql;

                    /**------
                    /**  Add status declaration
                    /**------*/

                    if ($status = ee()->TMPL->fetch_param('status')) {
                        $status = str_replace('Open', 'open', $status);
                        $status = str_replace('Closed', 'closed', $status);

                        $sstr = ee()->functions->sql_andor_string($status, 't.status');

                        if (stristr($sstr, "'closed'") === false) {
                            $sstr .= " AND t.status != 'closed' ";
                        }

                        $dql .= $sstr;
                    } else {
                        $dql .= "AND t.status = 'open' ";
                    }

                    $query = ee()->db->query($dql);

                    $distinct = array();

                    if ($query->num_rows() > 0) {
                        foreach ($query->result_array() as $row) {
                            $distinct[] = $row['year'] . $row['month'] . $row['day'];
                        }

                        $distinct = array_unique($distinct);
                        sort($distinct);

                        if ($sort_array[0] == 'desc') {
                            $distinct = array_reverse($distinct);
                        }

                        $this->pagination->total_items = count($distinct);

                        $cur = ($this->pagination->offset == '') ? 0 : $this->pagination->offset;

                        $distinct = array_slice($distinct, $cur, $lim);

                        if ($distinct != false) {
                            $sql .= "AND (";

                            foreach ($distinct as $val) {
                                $sql .= "(t.year  = '" . substr($val, 0, 4) . "' AND t.month = '" . substr($val, 4, 2) . "' AND t.day   = '" . substr($val, 6) . "' ) OR";
                            }

                            $sql = substr($sql, 0, -2) . ')';
                        }
                    }
                } elseif ($this->display_by == 'week') {
                    /**---
                    /**  If display_by = "week"
                    /**---*/

                    /** ---------------------------------
                    /*   Run a Query to get a combined Year and Week value.  There is a downside
                    /*   to this approach and that is the lack of localization and use of DST for
                    /*   dates.  Unfortunately, without making a complex and ultimately fubar'ed
                    /*  PHP script this is the best approach possible.
                    /*  ---------------------------------*/
                    $loc_offset = $this->_get_timezone_offset();

                    if (ee()->TMPL->fetch_param('start_day') === 'Monday') {
                        $yearweek = "DATE_FORMAT(FROM_UNIXTIME(entry_date + {$loc_offset}), '%x%v') AS yearweek ";
                        $dql = 'SELECT ' . $yearweek . $sql;
                    } else {
                        $yearweek = "DATE_FORMAT(FROM_UNIXTIME(entry_date + {$loc_offset}), '%X%V') AS yearweek ";
                        $dql = 'SELECT ' . $yearweek . $sql;
                    }

                    /**------
                    /**  Add status declaration
                    /**------*/

                    if ($status = ee()->TMPL->fetch_param('status')) {
                        $status = str_replace('Open', 'open', $status);
                        $status = str_replace('Closed', 'closed', $status);

                        $sstr = ee()->functions->sql_andor_string($status, 't.status');

                        if (stristr($sstr, "'closed'") === false) {
                            $sstr .= " AND t.status != 'closed' ";
                        }

                        $dql .= $sstr;
                    } else {
                        $dql .= "AND t.status = 'open' ";
                    }

                    $query = ee()->db->query($dql);

                    $distinct = array();

                    if ($query->num_rows() > 0) {
                        /** ---------------------------------
                        /*   Sort Default is ASC for Display By Week so that entries are displayed
                        /*  oldest to newest in the week, which is how you would expect.
                        /*  ---------------------------------*/
                        if (ee()->TMPL->fetch_param('sort') === false) {
                            $sort_array[0] = 'asc';
                        }

                        foreach ($query->result_array() as $row) {
                            $distinct[] = $row['yearweek'];
                        }

                        $distinct = array_unique($distinct);
                        rsort($distinct);

                        $this->pagination->total_items = count($distinct);
                        $cur = ($this->pagination->offset == '') ? 0 : $this->pagination->offset;

                        /** ---------------------------------
                        /*   If no pagination, then the Current Week is shown by default with
                        /*   all pagination correctly set and ready to roll, if used.
                        /*  ---------------------------------*/
                        if (ee()->TMPL->fetch_param('show_current_week') === 'yes' && $this->pagination->offset == '') {
                            if (ee()->TMPL->fetch_param('start_day') === 'Monday') {
                                $query = ee()->db->query("SELECT DATE_FORMAT(CURDATE(), '%x%v') AS thisWeek");
                            } else {
                                $query = ee()->db->query("SELECT DATE_FORMAT(CURDATE(), '%X%V') AS thisWeek");
                            }

                            foreach ($distinct as $key => $week) {
                                if ($week == $query->row('thisWeek')) {
                                    $cur = $key;
                                    $this->pagination->offset = $key;

                                    break;
                                }
                            }
                        }

                        $distinct = array_slice($distinct, $cur, $lim);

                        /** ---------------------------------
                        /*   Finally, we add the display by week SQL to the query
                        /*  ---------------------------------*/
                        if ($distinct != false) {
                            $sql .= "AND (";

                            foreach ($distinct as $val) {
                                $sql_offset = $this->_get_timezone_offset();

                                if (ee()->TMPL->fetch_param('start_day') === 'Monday') {
                                    $sql .= " DATE_FORMAT(FROM_UNIXTIME(entry_date + {$sql_offset}), '%x%v') = '" . $val . "' OR";
                                } else {
                                    $sql .= " DATE_FORMAT(FROM_UNIXTIME(entry_date + {$sql_offset}), '%X%V') = '" . $val . "' OR";
                                }
                            }

                            $sql = substr($sql, 0, -2) . ')';
                        }
                    }
                }
            }
        }

        /**------
        /**  Limit query "URL title"
        /**------*/

        if ($qtitle != '' and $dynamic) {
            $sql .= "AND t.url_title = '" . ee()->db->escape_str($qtitle) . "' ";

            // We use this with hit tracking....

            $this->hit_tracking_id = $qtitle;
        }

        // We set a
        if ($entry_id != '' and $this->entry_id !== false) {
            $this->hit_tracking_id = $entry_id;
        }

        /**------
        /**  Limit query by category
        /**------*/

        if (ee()->TMPL->fetch_param('category')) {
            if (stristr(ee()->TMPL->fetch_param('category'), '&')) {
                /** --------------------------------------
                /**  First, we find all entries with these categories
                /** --------------------------------------*/
                $for_sql = (substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not') ? trim(substr(ee()->TMPL->fetch_param('category'), 3)) : ee()->TMPL->fetch_param('category');

                $csql = "SELECT exp_category_posts.entry_id, exp_category_posts.cat_id " .
                        $sql .
                        ee()->functions->sql_andor_string(str_replace('&', '|', $for_sql), 'exp_categories.cat_id');

                //exit($csql);

                $results = ee()->db->query($csql);

                if ($results->num_rows() == 0) {
                    return;
                }

                $type = 'IN';
                $categories = explode('&', ee()->TMPL->fetch_param('category'));
                $entry_array = array();

                if (substr($categories[0], 0, 3) == 'not') {
                    $type = 'NOT IN';

                    $categories[0] = trim(substr($categories[0], 3));
                }

                foreach ($results->result_array() as $row) {
                    $entry_array[$row['cat_id']][] = $row['entry_id'];
                }

                if (count($entry_array) < 2 or count(array_diff($categories, array_keys($entry_array))) > 0) {
                    return;
                }

                $chosen = call_user_func_array('array_intersect', $entry_array);

                if (count($chosen) == 0) {
                    return;
                }

                $sql .= "AND t.entry_id " . $type . " ('" . implode("','", $chosen) . "') ";
            } else {
                if (substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not' && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no') {
                    $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category'), 'exp_categories.cat_id', '', true) . " ";
                } else {
                    $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category'), 'exp_categories.cat_id') . " ";
                }
            }
        }

        if (ee()->TMPL->fetch_param('category_group')) {
            if (substr(ee()->TMPL->fetch_param('category_group'), 0, 3) == 'not' && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no') {
                $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category_group'), 'exp_categories.group_id', '', true) . " ";
            } else {
                $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category_group'), 'exp_categories.group_id') . " ";
            }
        }

        if (ee()->TMPL->fetch_param('category') === false && ee()->TMPL->fetch_param('category_group') === false) {
            if ($cat_id != '' and $dynamic) {
                $sql .= " AND exp_categories.cat_id = '" . ee()->db->escape_str($cat_id) . "' ";
            }
        }

        /**------
        /**  Limit to (or exclude) specific users
        /**------*/

        if ($username = ee()->TMPL->fetch_param('username')) {
            // Shows entries ONLY for currently logged in user
            $join_member_table = true;

            if ($username == 'CURRENT_USER') {
                $sql .= "AND m.member_id = '" . ee()->session->userdata('member_id') . "' ";
            } elseif ($username == 'NOT_CURRENT_USER') {
                $sql .= "AND m.member_id != '" . ee()->session->userdata('member_id') . "' ";
            } else {
                $sql .= ee()->functions->sql_andor_string($username, 'm.username');
            }
        }

        /**------
        /**  Limit to (or exclude) specific author id(s)
        /**------*/

        if ($author_id = ee()->TMPL->fetch_param('author_id')) {
            $join_member_table = true;
            // Shows entries ONLY for currently logged in user

            if ($author_id == 'CURRENT_USER') {
                $sql .= "AND m.member_id = '" . ee()->session->userdata('member_id') . "' ";
            } elseif ($author_id == 'NOT_CURRENT_USER') {
                $sql .= "AND m.member_id != '" . ee()->session->userdata('member_id') . "' ";
            } else {
                $sql .= ee()->functions->sql_andor_string($author_id, 'm.member_id');
            }
        }

        /**------
        /**  Add status declaration
        /**------*/

        if ($status = ee()->TMPL->fetch_param('status')) {
            $status = str_replace('Open', 'open', $status);
            $status = str_replace('Closed', 'closed', $status);

            $sstr = ee()->functions->sql_andor_string($status, 't.status');

            if (stristr($sstr, "'closed'") === false) {
                $sstr .= " AND t.status != 'closed' ";
            }

            $sql .= $sstr;
        } else {
            $sql .= "AND t.status = 'open' ";
        }

        /**------
        /**  Add Group ID clause
        /**------*/

        $group_id = ee()->TMPL->fetch_param('primary_role_id') ?: ee()->TMPL->fetch_param('group_id');
        if ($group_id) {
            $join_member_table = true;
            $sql .= ee()->functions->sql_andor_string($group_id, 'm.role_id');
        }

        /** ---------------------------------------
        /**  Field searching
        /** ---------------------------------------*/
        if (! empty(ee()->TMPL->search_fields)) {
            $joins = '';
            $legacy_fields = array();
            $joined_fields = [];
            foreach (array_keys(ee()->TMPL->search_fields) as $field_name) {
                $sites = (ee()->TMPL->site_ids ? ee()->TMPL->site_ids : array(ee()->config->item('site_id')));
                foreach ($sites as $site_name => $site_id) {
                    if (isset($this->cfields[$site_id][$field_name])) {
                        $field_id = $this->cfields[$site_id][$field_name];

                        if (isset($joined_fields[$field_id])) {
                            continue;
                        }

                        $field = ee('Model')->get('ChannelField', $field_id)
                            ->fields('legacy_field_data')
                            ->first();

                        if (! $field->legacy_field_data) {
                            $joins .= "LEFT JOIN exp_channel_data_field_{$field_id} ON exp_channel_data_field_{$field_id}.entry_id = t.entry_id ";

                            $joined_fields[$field_id] = true;
                        } else {
                            $legacy_fields[$field_id] = $field_name;
                        }
                    }
                }
            }

            if (! empty($joins)) {
                $sql = str_replace('WHERE ', $joins . 'WHERE ', $sql);
            }

            $sql .= $this->_generate_field_search_sql(ee()->TMPL->search_fields, $legacy_fields, ee()->TMPL->site_ids);
        }

        /**----------
        /**  Build sorting clause
        /**----------*/

        // We'll assign this to a different variable since we
        // need to use this in two places

        $end = 'ORDER BY ';

        // If selecting distinctly, gather the columns we'll be ordering by to make
        // sure they're included in the SELECT to prevent errors in MySQL 5.7
        $distinct_select = '';

        if ($fixed_order !== false && ! empty($fixed_order)) {
            $end .= 'FIELD(t.entry_id, ' . implode(',', $fixed_order) . ') ';
        } else {
            // Used to eliminate sort issues with duplicated fields below
            $entry_id_sort = $sort_array[0];

            if (false === $order_array[0]) {
                if ($sticky == 'no') {
                    $end .= "t.entry_date";
                    $distinct_select .= ', t.entry_date ';
                } else {
                    $end .= "t.sticky desc, t.entry_date";
                    $distinct_select .= ', t.entry_date, t.sticky ';
                }

                if ($sort_array[0] == 'asc' or $sort_array[0] == 'desc') {
                    $end .= " " . $sort_array[0];
                }
            } else {
                if ($sticky != 'no') {
                    $end .= "t.sticky desc, ";
                    $distinct_select .= ', t.sticky ';
                }

                foreach ($order_array as $key => $order) {
                    if (in_array($order, array('view_count_one', 'view_count_two', 'view_count_three', 'view_count_four'))) {
                        $view_ct = substr($order, 10);
                        $order = "view_count";
                    }

                    if ($key > 0) {
                        $end .= ", ";
                    }

                    switch ($order) {
                        case 'entry_id':
                            $end .= "t.entry_id";
                            break;

                        case 'date':
                            $end .= "t.entry_date";
                            $distinct_select .= ', t.entry_date ';
                            break;

                        case 'edit_date':
                            $end .= "t.edit_date";
                            $distinct_select .= ', t.edit_date ';
                            break;

                        case 'expiration_date':
                            $end .= "t.expiration_date";
                            $distinct_select .= ', t.expiration_date ';
                            break;

                        case 'status':
                            $end .= "t.status";
                            $distinct_select .= ', t.status ';
                            break;

                        case 'title':
                            $end .= "t.title";
                            $distinct_select .= ', t.title ';
                            break;

                        case 'url_title':
                            $end .= "t.url_title";
                            $distinct_select .= ', t.url_title ';
                            break;

                        case 'view_count':
                            $vc = $order . $view_ct;
                            $end .= " t.{$vc} " . $sort_array[$key];
                            $distinct_select .= ",  t.{$vc} ";

                            if (count($order_array) - 1 == $key) {
                                $end .= ", t.entry_date " . $sort_array[$key];
                                $distinct_select .= ', t.entry_date ';
                            }
                            $sort_array[$key] = false;
                            break;

                        case 'comment_total':
                            $end .= "t.comment_total " . $sort_array[$key];
                            $distinct_select .= ', t.comment_total ';
                            if (count($order_array) - 1 == $key) {
                                $end .= ", t.entry_date " . $sort_array[$key];
                                $distinct_select .= ', t.entry_date ';
                            }
                            $sort_array[$key] = false;
                            break;

                        case 'most_recent_comment':
                            $end .= "t.recent_comment_date " . $sort_array[$key];
                            $distinct_select .= ', t.recent_comment_date ';
                            if (count($order_array) - 1 == $key) {
                                $end .= ", t.entry_date " . $sort_array[$key];
                                $distinct_select .= ', t.entry_date ';
                            }
                            $sort_array[$key] = false;
                            break;

                        case 'username':
                            $join_member_table = true;
                            $end .= "m.username";
                            $distinct_select .= ', m.username ';
                            break;

                        case 'screen_name':
                            $join_member_table = true;
                            $end .= "m.screen_name";
                            $distinct_select .= ', m.screen_name ';
                            break;

                        case 'custom_field':
                            if (strpos($corder[$key], '|') !== false) {
                                $field_list = [];

                                foreach (explode('|', $corder[$key]) as $field_id) {
                                    $field = ee('Model')->get('ChannelField', $field_id)->first();

                                    if ($field->legacy_field_data) {
                                        $field_list[] = "wd.field_id_{$field_id}";
                                    } else {
                                        if (strpos($sql, "exp_channel_data_field_{$field_id}") === false) {
                                            $join = "LEFT JOIN exp_channel_data_field_{$field_id} ON exp_channel_data_field_{$field_id}.entry_id = t.entry_id ";
                                            $sql = str_replace('WHERE ', $join . 'WHERE ', $sql);
                                        }

                                        $field_list[] = "exp_channel_data_field_{$field_id}.field_id_{$field_id}";
                                    }
                                }

                                $field_list = implode(', ', $field_list);

                                $end .= "CONCAT(" . $field_list . ")";
                                $distinct_select .= ', ' . $field_list . ' ';
                            } else {
                                $field_id = $corder[$key];

                                $field = ee('Model')->get('ChannelField', $field_id)->first();

                                if ($field->legacy_field_data) {
                                    $end .= "wd.field_id_{$field_id}";
                                    $distinct_select .= ", wd.field_id_{$field_id} ";
                                } else {
                                    if (strpos($sql, "exp_channel_data_field_{$field_id}") === false) {
                                        $join = "LEFT JOIN exp_channel_data_field_{$field_id} ON exp_channel_data_field_{$field_id}.entry_id = t.entry_id ";
                                        $sql = str_replace('WHERE ', $join . 'WHERE ', $sql);
                                    }

                                    $end .= "exp_channel_data_field_{$field_id}.field_id_{$field_id}";
                                    $distinct_select .= ", exp_channel_data_field_{$field_id}.field_id_{$field_id} ";
                                }
                            }
                            break;

                        case 'random':
                            $random_seed = ($this->pagination->paginate === true) ? (int) ee()->session->userdata('last_visit') : '';
                            $end = "ORDER BY rand({$random_seed})";
                            $sort_array[$key] = false;
                            break;

                        default:
                            $end .= "t.entry_date";
                            $distinct_select .= ', t.entry_date ';
                            break;
                    }

                    if ($sort_array[$key] == 'asc' or $sort_array[$key] == 'desc') {
                        // keep entries with the same timestamp in the correct order
                        $end .= " {$sort_array[$key]}";
                    }
                }
            }

            // In the event of a sorted field containing identical information as another
            // entry (title, entry_date, etc), they will sort on the order they were entered
            // into ExpressionEngine, with the first "sort" parameter taking precedence.
            // If no sort parameter is set, entries will descend by entry id.
            if (! in_array('entry_id', $order_array)) {
                $end .= ", t.entry_id " . $entry_id_sort;
            }

            // If we're selecting distinctly, add all ORDER BY fields to the SELECT statement
            // to prevent errors in MySQL 5.7
            if (strpos($sql_b, 'DISTINCT') !== false) {
                $sql_b .= $distinct_select;
            }
        }

        //  Determine the row limits
        // Even thouth we don't use the LIMIT clause until the end,
        // we need it to help create our pagination links so we'll
        // set it here

        if ($cat_id != '' and is_numeric(ee()->TMPL->fetch_param('cat_limit'))) {
            $this->pagination->per_page = ee()->TMPL->fetch_param('cat_limit');
        } elseif ($month != '' and is_numeric(ee()->TMPL->fetch_param('month_limit'))) {
            $this->pagination->per_page = ee()->TMPL->fetch_param('month_limit');
        } else {
            $this->pagination->per_page = (! is_numeric(ee()->TMPL->fetch_param('limit'))) ? $this->limit : ee()->TMPL->fetch_param('limit');
        }

        if ($join_member_table) {
            $sql = str_replace(' WHERE ', ' ' . $member_join . ' WHERE ', $sql);
        }

        /**------
        /**  Is there an offset?
        /**------*/
        // We do this hear so we can use the offset into next, then later one as well
        $offset = (! ee()->TMPL->fetch_param('offset') or ! is_numeric(ee()->TMPL->fetch_param('offset'))) ? '0' : ee()->TMPL->fetch_param('offset');

        // Do we need entry pagination?
        // We'll run the query to find out
        if ($this->pagination->paginate == true) {
            $this->pager_sql = '';

            if ($this->pagination->field_pagination == false) {
                $this->pager_sql = $sql_a . $sql_b . $sql;
                $query = ee()->db->query($this->pager_sql);
                $total = $query->num_rows();
                $this->absolute_results = $total;

                // Adjust for offset
                if ($total >= $offset) {
                    $total = $total - $offset;
                }

                // do a little dance to remove the seed if we have random order
                // and only one page of results. Random order should only be
                // sticky across pages.
                if (isset($random_seed) && $total <= $this->pagination->per_page) {
                    $end = str_replace($random_seed, '', $end);
                }

                $this->pagination->build($total, $this->pagination->per_page);

                if (ee()->config->item('enable_sql_caching') == 'y') {
                    $this->save_cache($total, 'pagination_count');
                }
            }
        }

        /**------
        /**  Add Limits to query
        /**------*/

        if (isset(ee()->session) && ee('LivePreview')->hasEntryData()) {
            $parts = explode(' WHERE ', $sql);
            $this->preview_conditions = explode(' AND ', $parts[1]);
        }

        $sql .= $end;

        if ($this->pagination->paginate == false) {
            $this->pagination->offset = 0;
        }

        // Adjust for offset
        $this->pagination->offset += $offset;

        if ($this->display_by == '') {
            if (($page_marker == false and $this->pagination->per_page != '') or ($page_marker == true and $this->pagination->field_pagination != true)) {
                $sql .= ($this->pagination->offset == '') ? " LIMIT " . $offset . ', ' . $this->pagination->per_page : " LIMIT " . $this->pagination->offset . ', ' . $this->pagination->per_page;
            } elseif ($entry_id == '' and $qtitle == '') {
                $sql .= ($this->pagination->offset == '') ? " LIMIT " . $this->limit : " LIMIT " . $this->pagination->offset . ', ' . $this->limit;
            }
        } else {
            if ($offset != 0) {
                $sql .= ($this->pagination->offset == '') ? " LIMIT " . $offset . ', ' . $this->pagination->per_page : " LIMIT " . $this->pagination->offset . ', ' . $this->pagination->per_page;
            }
        }

        /**------
        /**  Fetch the entry_id numbers
        /**------*/

        $query = ee()->db->query($sql_a . $sql_b . $sql);

        if ($query->num_rows() == 0) {
            $this->sql = '';

            return;
        }

        /**------
        /**  Build the full SQL query
        /**------*/

        $this->sql = "SELECT ";

        if (ee()->TMPL->fetch_param('category') or ee()->TMPL->fetch_param('category_group') or $cat_id != '') {
            // Using DISTINCT like this is bogus but since
            // FULL OUTER JOINs are not supported in older versions
            // of MySQL it's our only choice

            $this->sql .= " DISTINCT(t.entry_id), ";
        }

        if ($this->display_by == 'week' && isset($yearweek)) {
            $this->sql .= $yearweek . ', ';
        }

        $entries = array();
        $channel_ids = array();

        foreach ($query->result_array() as $row) {
            $entries[] = $row['entry_id'];
            $channel_ids[] = $row['channel_id'];
        }

        $entries = array_unique($entries);
        $channel_ids = array_unique($channel_ids);

        // find out which fields should be conditionally hidden
        $hiddenFieldsQuery = ee('db')->select('entry_id, field_id')->from('channel_entry_hidden_fields')->where_in('entry_id', $entries)->get();
        if ($hiddenFieldsQuery->num_rows() > 0) {
            foreach ($hiddenFieldsQuery->result_array() as $hiddenFieldsRow) {
                if (!isset($this->hidden_fields[$hiddenFieldsRow['entry_id']])) {
                    $this->hidden_fields[$hiddenFieldsRow['entry_id']] = [];
                }
                $this->hidden_fields[$hiddenFieldsRow['entry_id']][] = $hiddenFieldsRow['field_id'];
            }
        }

        $this->sql .= $this->generateSQLForEntries($entries, $channel_ids);

        //cache the entry_id
        if (isset(ee()->session)) {
            ee()->session->cache['channel']['entry_ids'] = $entries;
            ee()->session->cache['channel']['channel_ids'] = $channel_ids;
        }

        $end = "ORDER BY FIELD(t.entry_id, " . implode(',', $entries) . ")";

        // modify the ORDER BY if displaying by week
        if ($this->display_by == 'week' && isset($yearweek)) {
            $weeksort = (ee()->TMPL->fetch_param('week_sort') == 'desc') ? 'DESC' : 'ASC';
            $end = str_replace('ORDER BY ', 'ORDER BY yearweek ' . $weeksort . ', ', $end);
        }

        $this->sql .= $end;
    }

    public function generateSQLForEntries(array $entries, array $channel_ids)
    {
        $sql = " t.entry_id, t.channel_id, t.forum_topic_id, t.author_id, t.ip_address, t.title, t.url_title, t.status, t.view_count_one, t.view_count_two, t.view_count_three, t.view_count_four, t.allow_comments, t.comment_expiration_date, t.sticky, t.entry_date, t.year, t.month, t.day, t.edit_date, t.expiration_date, t.recent_comment_date, t.comment_total, t.site_id as entry_site_id,
                        w.channel_title, w.channel_name, w.channel_url, w.comment_url, w.comment_moderate, w.channel_html_formatting, w.channel_allow_img_urls, w.channel_auto_link_urls, w.comment_system_enabled,
                        m.username, m.email, m.screen_name, m.signature, m.sig_img_filename, m.sig_img_width, m.sig_img_height, m.avatar_filename, m.avatar_width, m.avatar_height, m.photo_filename, m.photo_width, m.photo_height, m.role_id, m.member_id";

        // check if we have param for needed fields only.. default is no
        if (! $needed_fields_only = ee()->TMPL->fetch_param('needed_fields_only')) {
            // string is weird on this one.. but it keeps the query
            // well formatted for viewing when fully rendered
            $sql .= ", 
                        wd.*";
        }

        $from = " FROM exp_channel_titles       AS t
                LEFT JOIN exp_channels      AS w  ON t.channel_id = w.channel_id
                LEFT JOIN exp_channel_data  AS wd ON t.entry_id = wd.entry_id
                LEFT JOIN exp_members       AS m  ON m.member_id = t.author_id ";

        $mfieldCount = 0;
        if ($this->enable['member_data'] && ! empty($this->mfields)) {
            $sql .= ", md.* ";
            $from .= "LEFT JOIN exp_member_data AS md ON md.member_id = m.member_id ";

            foreach ($this->mfields as $mfield) {
                // Only join non-legacy field tables
                if ($mfield[2] == 'n') {
                    $mfieldCount++;
                    $field_id = $mfield[0];
                    $table = "exp_member_data_field_{$field_id}";
                    $sql .= ", {$table}.*";
                    $from .= "LEFT JOIN {$table} ON m.member_id = {$table}.member_id ";
                }
            }
        }

        $fields = array();

        if ($this->enable['custom_fields']) {
            $cache_key = "mod.channel/Channels/" . implode(',', $channel_ids);

            if (($channels = ee()->session->cache(__CLASS__, $cache_key, false)) === false) {
                $channels = ee('Model')->get('Channel', $channel_ids)
                    ->with('FieldGroups', 'CustomFields')
                    ->all();

                ee()->session->set_cache(__CLASS__, $cache_key, $channels);
            }

            // Get the fields for the channels passed in
            foreach ($channels as $channel) {
                foreach ($channel->getAllCustomFields() as $field) {
                    // assign fields in new storage format to array
                    if (! $field->legacy_field_data) {
                        $fields[$field->field_id] = $field;
                    }

                    // if we're including only the needed legacy fields, assign them to the array
                    if ($needed_fields_only && $field->legacy_field_data) {
                        $legacy_fields[] = $field;
                    }
                }
            }
        }

        //Build string for legacy fields to be added in.
        if ($needed_fields_only) {
            // add the fields that are needed for joins etc.
            $sql .= ", wd.entry_id, wd.site_id, wd.channel_id";

            if (!empty($legacy_fields)) {
                foreach ($legacy_fields as $lField) {
                    $sql .= ', wd.field_ft_' . $lField->field_id;
                    $sql .= ', wd.field_id_' . $lField->field_id;
                }
            }
        }


        //MySQL has limit of 61 joins, so we need to make sure to not hit it
        $join_limit = 61 - 7 - $mfieldCount;
        $chunks = array_chunk($fields, $join_limit);

        $chunk = (array_shift($chunks)) ?: array();

        if (! empty($chunks)) {
            $this->chunks = $chunks;
        }

        if (is_array($chunk)) {
            foreach ($chunk as $field) {
                $field_id = $field->getId();
                $table = "exp_channel_data_field_{$field_id}";

                foreach ($field->getColumnNames() as $column) {
                    $sql .= ", {$table}.{$column}";
                }

                $from .= "LEFT JOIN {$table} ON t.entry_id = {$table}.entry_id ";
            }
        }

        $sql .= $from;

        $sql .= "WHERE t.entry_id IN (" . implode(',', $entries) . ")";

        return $sql;
    }

    /**
     * Gets timezone offset for use in SQL queries for the display_by parameter
     *
     * @return int
     */
    private function _get_timezone_offset()
    {
        ee()->load->helper('date');

        $offset = 0;
        $timezones = timezones();
        $timezone = ee()->config->item('default_site_timezone');

        // Check legacy timezone formats
        if (isset($timezones[$timezone])) {
            $offset = $timezones[$timezone] * 3600;
        } else {
            // Otherwise, get the offset from DateTime
            $dt = new DateTime('now', new DateTimeZone($timezone));

            if ($dt) {
                $offset = $dt->getOffset();
            }
        }

        return $offset;
    }

    private function isLivePreviewEntry()
    {
        $return = false;

        if (!isset(ee()->session)) {
            return $return;
        }

        if (ee('LivePreview')->hasEntryData()) {
            $data = ee('LivePreview')->getEntryData();
            if (in_array($this->query_string, [$data['entry_id'], $data['url_title']])) {
                $return = true;

                if ($channels = ee()->TMPL->fetch_param('channel')) {
                    if (
                        strpos($channels, $data['channel_name']) === false
                        || strpos($channels, 'not ' . $data['channel_name']) !== false
                    ) {
                        $return = false;
                    }
                }
            }
        }

        return $return;
    }

    private function overrideWithPreviewData($result_array)
    {
        if (!isset(ee()->session)) {
            return $result_array;
        }

        if (ee('LivePreview')->hasEntryData()) {
            $found = false;
            $show_closed = false;
            $show_expired = (ee()->TMPL->fetch_param('show_expired') == 'yes');

            if (($status = ee()->TMPL->fetch_param('status')) !== false) {
                $status = strtolower($status);
                $parts = preg_split('/\|/', $status, -1, PREG_SPLIT_NO_EMPTY);
                $parts = array_map('trim', $parts);
                $show_closed = in_array('closed', $parts);
            }

            $data = ee('LivePreview')->getEntryData();
            $this->hidden_fields[$data['entry_id']] = [];
            foreach ($data as $field => $fieldValue) {
                if (strpos($field, 'field_hide_') === 0) {
                    $this->hidden_fields[$data['entry_id']][] = substr($field, 11);
                }
            }

            foreach ($result_array as $i => $row) {
                if ($row['entry_id'] == $data['entry_id']) {
                    if (
                        (! $show_closed && $data['status'] == 'closed')
                        || (! $show_expired && $data['expiration_date'] && $data['expiration_date'] < ee()->localize->now)
                    ) {
                        unset($result_array[$i]);
                    } else {
                        $result_array[$i] = $data;
                    }

                    $found = true;

                    break;
                }
            }

            // One of the things we will not find are new entries that are
            // being previewed. They will not be in the database and thus will
            // not be returned.
            if (! $found) {
                $add = false;

                if (in_array($this->query_string, [$data['entry_id'], $data['url_title']])) {
                    $add = true;
                }

                foreach ($this->preview_conditions as $condition) {
                    if (strpos('OR', $condition) === false) {
                        $valid = $this->previewDataPassesCondition($condition, $data);
                    } else {
                        $valid = false;

                        $condition = trim($condition, '()');
                        $conditions = explode(' OR ', $condition);
                        foreach ($conditions as $sub_condition) {
                            $valid = $this->previewDataPassesCondition($sub_condition, $data);
                            if ($valid) {
                                break;
                            }
                        }
                    }

                    if ($valid) {
                        $add = true;
                    } else {
                        $add = false;

                        break;
                    }
                }

                if ($add) {
                    array_unshift($result_array, $data);
                }
            }
        }

        return $result_array;
    }

    private function previewDataPassesCondition($condition, $data)
    {
        list($column, $comparison, $value) = explode(' ', trim($condition, '() '));
        list($table, $key) = explode('.', $column);

        $datum = $data[$key];
        $value = trim($value, "'");

        $passes = false;

        switch ($comparison) {
            case '=':
                if (is_array($datum)) {
                    $passes = in_array($value, $datum);
                } else {
                    $passes = ($datum == $value);
                }

                break;

            case '!=':
                if (is_array($datum)) {
                    $passes = ! in_array($value, $datum);
                } else {
                    $passes = ($datum != $value);
                }

                break;

            case '>':
                $passes = ($datum > $value);

                break;

            case '<':
                $passes = ($datum < $value);

                break;

            case '>=':
                $passes = ($datum >= $value);

                break;

            case '<=':
                $passes = ($datum <= $value);

                break;

            case 'IN':
                $value = trim($value, '()');
                $value = explode(',', str_replace("'", '', $value));

                if (is_array($datum)) {
                    $passes = array_intersect($datum, $value);
                    $passes = ! empty($passes);
                } else {
                    $passes = in_array($datum, $value);
                }

                break;
        }

        return $passes;
    }

    /**
      *  Parse channel entries
      *  @param Callable $per_row_callback A callable to send each row's tagdata
      *                                    and row data to. Your callable should
      *                                    have the following method signature:
      *                                    function($tagdata, $row)
      */
    public function parse_channel_entries($per_row_callback = null)
    {
        // For our hook to work, we need to grab the result array
        $query_result = ($this->query) ? $this->query->result_array() : [];

        if (! empty($this->chunks)) {
            $query_result = $this->getExtraData($query_result);
        }

        if (!empty($this->hidden_fields)) {
            foreach ($query_result as $i => $row) {
                if (isset($this->hidden_fields[$row['entry_id']])) {
                    foreach ($this->hidden_fields[$row['entry_id']] as $hiddenFieldId) {
                        $row['field_hide_' . $hiddenFieldId] = 'y';
                    }
                    $query_result[$i] = $row;
                    ee()->TMPL->log_item("Conditionally hidden fields for entry ID " . $row['entry_id'] . ": " . implode(", ", $this->hidden_fields[$row['entry_id']]));
                }
            }
        }

        $query_result = $this->overrideWithPreviewData($query_result);

        // Ditch everything else
        if ($this->query) {
            $this->query->free_result();
            unset($this->query);
        }

        // -------------------------------------------
        // 'channel_entries_query_result' hook.
        //  - Take the whole query result array, do what you wish
        //  - added 1.6.7
        //
        if (ee()->extensions->active_hook('channel_entries_query_result') === true) {
            $query_result = ee()->extensions->call('channel_entries_query_result', $this, $query_result);
            if (ee()->extensions->end_script === true) {
                return ee()->TMPL->tagdata;
            }
        }
        //
        // -------------------------------------------

        if (empty($query_result)) {
            $this->enable['pagination'] = false;
            $this->return_data = ee()->TMPL->no_results();

            return;
        }

        // just before we pass the data to hook, let Pro do its thing
        ee()->TMPL->tagdata = ee('pro:FrontEdit')->prepareTemplate(ee()->TMPL->tagdata);

        ee()->load->library('channel_entries_parser');
        $parser = ee()->channel_entries_parser->create(ee()->TMPL->tagdata/*, $prefix=''*/);

        $disable = array();

        foreach ($this->enable as $k => $v) {
            if ($v === false) {
                $disable[] = $k;
            }
        }

        // Relate entry_ids to their entries for quick lookup and then parse
        $entries = array();

        foreach ($query_result as $i => $row) {
            unset($query_result[$i]);
            $entries[$row['entry_id']] = $row;
        }

        $data = array(
            'entries' => $entries,
            'categories' => $this->categories,
            'absolute_results' => $this->absolute_results,
            'absolute_offset' => $this->pagination->offset
        );

        ee()->TMPL->set_data($data);
        $tagdata_loop_end = array($this, 'callback_tagdata_loop_end');

        $config = array(
            'callbacks' => array(
                'entry_row_data' => array($this, 'callback_entry_row_data'),
                'tagdata_loop_start' => array($this, 'callback_tagdata_loop_start'),
                'tagdata_loop_end' => function ($tagdata, $row) use ($per_row_callback, $tagdata_loop_end) {
                    $tagdata = call_user_func($tagdata_loop_end, $tagdata, $row);

                    if (is_callable($per_row_callback)) {
                        $tagdata = call_user_func($per_row_callback, $tagdata, $row);
                    }

                    return $tagdata;
                }
            ),
            'disable' => $disable
        );

        $this->cacheCategoryFieldModels();

        if (isset(ee()->session)) {
            ee()->session->set_cache('mod_channel', 'active', $this);
        }
        $this->return_data = $parser->parse($this, $data, $config);

        unset($parser, $entries, $data);

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        // Kill multi_field variable
        if (strpos($this->return_data, 'multi_field=') !== false) {
            $this->return_data = preg_replace("/" . LD . "multi_field\=[\"'](.+?)[\"']" . RD . "/s", "", $this->return_data);
        }

        // Do we have backspacing?
        if ($back = ee()->TMPL->fetch_param('backspace')) {
            if (is_numeric($back)) {
                $this->return_data = substr($this->return_data, 0, - $back);
            }
        }
    }

    private function getExtraData($query_result)
    {
        $where = "WHERE t.entry_id IN (" . implode(',', ee()->session->cache['channel']['entry_ids']) . ")";

        foreach ($this->chunks as $chunk) {
            $sql = "SELECT t.entry_id";
            $from = " FROM exp_channel_titles AS t ";

            foreach ($chunk as $field) {
                $field_id = $field->getId();
                $table = "exp_channel_data_field_{$field_id}";

                foreach ($field->getColumnNames() as $column) {
                    $sql .= ", {$table}.{$column}";
                }

                $from .= "LEFT JOIN {$table} ON t.entry_id = {$table}.entry_id ";
            }

            $query = ee()->db->query($sql . $from . $where);

            foreach ($query->result_array() as $row) {
                array_walk($query_result, function (&$data, $key, $field_data) {
                    if ($data['entry_id'] == $field_data['entry_id']) {
                        $data = array_merge($data, $field_data);
                    }
                }, $row);
            }

            $query->free_result();
        }

        return $query_result;
    }

    public function callback_entry_row_data($tagdata, $row)
    {
        // -------------------------------------------
        // 'channel_entries_row' hook.
        //  - Take the entry data, do what you wish
        //  - added 1.6.7
        //
        if (ee()->extensions->active_hook('channel_entries_row') === true) {
            $row = ee()->extensions->call('channel_entries_row', $this, $row);
            //if (ee()->extensions->end_script === TRUE) return $tagdata;
        }
        //
        // -------------------------------------------

        return $row;
    }

    public function callback_tagdata_loop_start($tagdata, $row)
    {
        // -------------------------------------------
        // 'channel_entries_tagdata' hook.
        //  - Take the entry data and tag data, do what you wish
        //
        if (ee()->extensions->active_hook('channel_entries_tagdata') === true) {
            $tagdata = ee()->extensions->call('channel_entries_tagdata', $tagdata, $row, $this);
            //  if (ee()->extensions->end_script === TRUE) return $tagdata;
        }
        //
        // -------------------------------------------

        return $tagdata;
    }

    public function callback_tagdata_loop_end($tagdata, $row)
    {
        // -------------------------------------------
        // 'channel_entries_tagdata_end' hook.
        //  - Take the final results of an entry's parsing and do what you wish
        //
        if (ee()->extensions->active_hook('channel_entries_tagdata_end') === true) {
            $tagdata = ee()->extensions->call('channel_entries_tagdata_end', $tagdata, $row, $this);
            //  if (ee()->extensions->end_script === TRUE) return $tagdata;
        }
        //
        // -------------------------------------------

        return $tagdata;
    }

    /**
      *  Channel Info Tag
      */
    public function info()
    {
        if (! $channel_name = ee()->TMPL->fetch_param('channel')) {
            return '';
        }

        if (count(ee()->TMPL->var_single) == 0) {
            return '';
        }

        $params = array(
            'channel_title',
            'channel_url',
            'channel_description',
            'channel_lang'
        );

        $q = '';
        $tags = false;
        $charset = ee()->config->item('charset');

        foreach (ee()->TMPL->var_single as $val) {
            if (in_array($val, $params)) {
                $tags = true;
                $q .= $val . ',';
            } elseif ($val == 'channel_encoding') {
                $tags = true;
            }
        }

        $q = substr($q, 0, -1);

        if ($tags == false) {
            return '';
        }

        $sql = "SELECT " . $q . " FROM exp_channels ";

        $sql .= " WHERE site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";

        if ($channel_name != '') {
            $sql .= " AND channel_name = '" . ee()->db->escape_str($channel_name) . "'";
        }

        $query = ee()->db->query($sql);

        if ($query->num_rows() != 1) {
            return '';
        }

        // We add in the channel_encoding
        $cond_vars = array_merge($query->row_array(), array('channel_encoding' => $charset));

        ee()->TMPL->tagdata = ee()->functions->prep_conditionals(ee()->TMPL->tagdata, $cond_vars);

        foreach ($query->row_array() as $key => $val) {
            ee()->TMPL->tagdata = str_replace(LD . $key . RD, $val, ee()->TMPL->tagdata);
        }

        ee()->TMPL->tagdata = str_replace(LD . 'channel_encoding' . RD, $charset, ee()->TMPL->tagdata);

        return ee()->TMPL->tagdata;
    }

    /**
      *  Channel Name
      */
    public function channel_name()
    {
        $channel_name = ee()->TMPL->fetch_param('channel');

        if (isset($this->channel_name[$channel_name])) {
            return $this->channel_name[$channel_name];
        }

        $sql = "SELECT channel_title FROM exp_channels ";

        $sql .= " WHERE site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";

        if ($channel_name != '') {
            $sql .= " AND channel_name = '" . ee()->db->escape_str($channel_name) . "'";
        }

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 1) {
            $this->channel_name[$channel_name] = $query->row('channel_title') ;

            return $query->row('channel_title') ;
        } else {
            return '';
        }
    }

    /**
      *  Channel Categories
      */
    public function categories()
    {
        // -------------------------------------------
        // 'channel_module_categories_start' hook.
        //  - Rewrite the displaying of categories, if you dare!
        //
        if (ee()->extensions->active_hook('channel_module_categories_start') === true) {
            return ee()->extensions->call('channel_module_categories_start');
        }
        //
        // -------------------------------------------

        $site_ids_str = implode("','", ee()->TMPL->site_ids);
        $sql = "SELECT exp_channel_category_groups.channel_id, exp_channel_category_groups.group_id FROM exp_channel_category_groups LEFT JOIN exp_channels ON exp_channel_category_groups.channel_id=exp_channels.channel_id WHERE site_id IN ('" . $site_ids_str . "') ";

        if ($channel = ee()->TMPL->fetch_param('channel')) {
            $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');
        }

        $cat_groups = ee()->db->query($sql);

        if ($cat_groups->num_rows() == 0) {
            return ee()->TMPL->no_results();
        }

        $channel_ids = array();
        $group_ids = array();
        foreach ($cat_groups->result_array() as $group) {
            $channel_ids[] = $group['channel_id'];
            $group_ids[] = $group['group_id'];
        }

        $channel_ids = array_unique($channel_ids);
        $group_ids = array_unique($group_ids);
        $group_ids_str = implode("','", $group_ids);

        if ($category_group = ee()->TMPL->fetch_param('category_group')) {
            if (substr($category_group, 0, 4) == 'not ') {
                $x = explode('|', substr($category_group, 4));

                $group_ids = array_diff($group_ids, $x);
            } else {
                $x = explode('|', $category_group);

                $group_ids = array_intersect($group_ids, $x);
            }

            if (count($group_ids) == 0) {
                return ee()->TMPL->no_results();
            }

            $group_ids = array_filter($group_ids, 'is_numeric');
            $group_ids_str = implode("','", $group_ids);
        }

        $parent_only = (ee()->TMPL->fetch_param('parent_only') == 'yes') ? true : false;

        $path = array();

        if (preg_match_all("#" . LD . "path(=.+?)" . RD . "#", ee()->TMPL->tagdata, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                if (! isset($path[$matches[0][$i]])) {
                    $path[$matches[0][$i]] = ee()->functions->create_url(ee()->functions->extract_path($matches[1][$i]));
                }
            }
        }

        $str = '';
        $strict_empty = (ee()->TMPL->fetch_param('restrict_channel') == 'no') ? 'no' : 'yes';
        $all = [];

        if (ee()->TMPL->fetch_param('style') == '' or ee()->TMPL->fetch_param('style') == 'nested') {
            $this->category_tree(array(
                'group_id' => implode('|', $group_ids),
                'channel_ids' => $channel_ids,
                'template' => ee()->TMPL->tagdata,
                'path' => $path,
                'channel_array' => '',
                'parent_only' => $parent_only,
                'show_empty' => ee()->TMPL->fetch_param('show_empty'),
                'strict_empty' => $strict_empty
            ));

            if (count($this->category_list) > 0) {
                $i = 0;

                $id_name = (! ee()->TMPL->fetch_param('id')) ? 'nav_categories' : ee()->TMPL->fetch_param('id');
                $class_name = (! ee()->TMPL->fetch_param('class')) ? 'nav_categories' : ee()->TMPL->fetch_param('class');

                $this->category_list[0] = '<ul id="' . $id_name . '" class="' . $class_name . '">' . "\n";

                foreach ($this->category_list as $val) {
                    $str .= $val;
                }
            }
        } else {
            // fetch category field names and id's

            list($field_sqla, $field_sqlb) = $this->generateCategoryFieldSQL($group_ids);

            $show_empty = ee()->TMPL->fetch_param('show_empty');

            if ($show_empty == 'no') {
                // First we'll grab all category ID numbers

                $query = ee()->db->query("SELECT cat_id, parent_id
                                     FROM exp_categories
                                     WHERE group_id IN ('" . $group_ids_str . "')
                                     ORDER BY group_id, parent_id, cat_order");

                $all = array();

                // No categories exist?  Let's go home..
                if ($query->num_rows() == 0) {
                    return ee()->TMPL->no_results();
                }

                foreach ($query->result_array() as $row) {
                    $all[$row['cat_id']] = $row['parent_id'];
                }

                // Next we'l grab only the assigned categories

                $sql = "SELECT DISTINCT(exp_categories.cat_id), exp_categories.group_id, exp_categories.parent_id, exp_categories.cat_order FROM exp_categories
                        LEFT JOIN exp_category_posts ON exp_categories.cat_id = exp_category_posts.cat_id
                        LEFT JOIN exp_channel_titles ON exp_category_posts.entry_id = exp_channel_titles.entry_id
                        WHERE group_id IN ('" . $group_ids_str . "') ";

                $sql .= "AND exp_category_posts.cat_id IS NOT NULL ";

                if ($strict_empty == 'yes') {
                    $sql .= "AND exp_channel_titles.channel_id IN ('" . implode("','", $channel_ids) . "') ";
                } else {
                    $sql .= "AND exp_channel_titles.site_id IN ('" . $site_ids_str . "') ";
                }

                if (($status = ee()->TMPL->fetch_param('status')) !== false) {
                    $status = str_replace(array('Open', 'Closed'), array('open', 'closed'), $status);
                    $sql .= ee()->functions->sql_andor_string($status, 'exp_channel_titles.status');
                } else {
                    $sql .= "AND exp_channel_titles.status != 'closed' ";
                }

                /**------
                /**  We only select entries that have not expired
                /**------*/

                $timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

                if (ee()->TMPL->fetch_param('show_future_entries') != 'yes') {
                    $sql .= " AND exp_channel_titles.entry_date < " . $timestamp . " ";
                }

                if (ee()->TMPL->fetch_param('show_expired') != 'yes') {
                    $sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > " . $timestamp . ") ";
                }

                if ($parent_only === true) {
                    $sql .= " AND parent_id = 0";
                }

                $sql .= " ORDER BY group_id, parent_id, cat_order";

                $query = ee()->db->query($sql);

                if ($query->num_rows() == 0) {
                    return ee()->TMPL->no_results();
                }

                // All the magic happens here, baby!!

                foreach ($query->result_array() as $row) {
                    if ($row['parent_id'] != 0) {
                        $this->find_parent($row['parent_id'], $all);
                    }

                    $this->cat_full_array[] = $row['cat_id'];
                }

                $this->cat_full_array = array_unique($this->cat_full_array);

                $sql = "SELECT c.cat_id, c.parent_id, c.cat_name, c.cat_url_title, c.cat_image, c.cat_description {$field_sqla}
                FROM exp_categories AS c
                {$field_sqlb}
                WHERE c.cat_id IN (";

                foreach ($this->cat_full_array as $val) {
                    $sql .= $val . ',';
                }

                $sql = substr($sql, 0, -1) . ')';

                $sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order, c.cat_id";

                $query = ee()->db->query($sql);

                if ($query->num_rows() == 0) {
                    return ee()->TMPL->no_results();
                }
            } else {
                $sql = "SELECT c.cat_name, c.cat_url_title, c.cat_image, c.cat_description, c.cat_id, c.parent_id {$field_sqla}
                        FROM exp_categories AS c
                        {$field_sqlb}
                        WHERE c.group_id IN ('" . $group_ids_str . "') ";

                if ($parent_only === true) {
                    $sql .= " AND c.parent_id = 0";
                }

                $sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order, c.cat_id";

                $query = ee()->db->query($sql);

                if ($query->num_rows() == 0) {
                    return ee()->TMPL->no_results();
                }
            }

            // Here we check the show parameter to see if we have any
            // categories we should be ignoring or only a certain group of
            // categories that we should be showing.  By doing this here before
            // all of the nested processing we should keep out all but the
            // request categories while also not having a problem with having a
            // child but not a parent.  As we all know, categories are not asexual.

            if (ee()->TMPL->fetch_param('show') !== false) {
                if (strncmp(ee()->TMPL->fetch_param('show'), 'not ', 4) == 0) {
                    $not_these = explode('|', trim(substr(ee()->TMPL->fetch_param('show'), 3)));
                } else {
                    $these = explode('|', trim(ee()->TMPL->fetch_param('show')));
                }
            }

            foreach ($query->result_array() as $row) {
                if (isset($not_these) && in_array($row['cat_id'], $not_these)) {
                    continue;
                } elseif (isset($these) && ! in_array($row['cat_id'], $these)) {
                    continue;
                }

                $this->temp_array[$row['cat_id']] = array($row['cat_id'], $row['parent_id'], '1', $row['cat_name'], $row['cat_description'], $row['cat_image'], $row['cat_url_title']);

                foreach ($row as $key => $val) {
                    if (strpos($key, 'field') !== false) {
                        $this->temp_array[$row['cat_id']][$key] = $val;
                    }
                }
            }

            foreach ($this->temp_array as $key => $val) {
                if (0 == $val[1]) {
                    $this->cat_array[] = $val;
                    $this->process_subcategories($key);
                }
            }

            unset($this->temp_array);

            $this->category_count = 0;
            $total_results = count($this->cat_array);

            // Get category ID from URL for {if active} conditional
            ee()->load->helper('segment');
            $active_cat = parse_category($this->query_string);

            ee()->load->library('typography');
            $all = [];
            $parent_ids = array();

            foreach ($this->cat_array as $val) {
                if (!empty($val[1]) && !in_array($val[1], $parent_ids)) {
                    $parent_ids[] = $val[1];
                }
            }

            foreach ($this->cat_array as $key => $val) {
                $chunk = ee()->TMPL->tagdata;

                $cat_vars = array(
                    'category_name' => ee()->typography->format_characters($val[3]),
                    'category_url_title' => $val[6],
                    'category_description' => $val[4],
                    'category_image' => (string) $val[5],
                    'category_id' => $val[0],
                    'parent_id' => $val[1],
                    'has_children' => in_array($val[0], $parent_ids),
                    'active' => ($active_cat == $val[0] || $active_cat == $val[6])
                );

                // add custom fields for conditionals prep

                foreach ($this->catfields as $v) {
                    $cat_vars[$v['field_name']] = (! isset($val['field_id_' . $v['field_id']])) ? '' : $val['field_id_' . $v['field_id']];
                }

                $cat_vars['count'] = ++$this->category_count;
                $cat_vars['total_results'] = $total_results;
                $all[] = $cat_vars;
                $chunk = ee()->functions->prep_conditionals($chunk, $cat_vars);

                $chunk = str_replace(
                    array(
                        LD . 'category_name' . RD,
                        LD . 'category_url_title' . RD,
                        LD . 'category_description' . RD,
                        LD . 'category_image' . RD,
                        LD . 'category_id' . RD,
                        LD . 'parent_id' . RD
                    ),
                    array(
                        ee()->functions->encode_ee_tags($cat_vars['category_name']),
                        $cat_vars['category_url_title'],
                        ee()->functions->encode_ee_tags($cat_vars['category_description']),
                        $cat_vars['category_image'],
                        $cat_vars['category_id'],
                        $cat_vars['parent_id']
                    ),
                    $chunk
                );

                foreach ($path as $k => $v) {
                    if ($this->use_category_names == true) {
                        $chunk = str_replace($k, reduce_double_slashes($v . '/' . $this->reserved_cat_segment . '/' . $cat_vars['category_url_title']), $chunk);
                    } else {
                        $chunk = str_replace($k, reduce_double_slashes($v . '/C' . $cat_vars['category_id']), $chunk);
                    }
                }

                $chunk = $this->parseCategoryFields($cat_vars['category_id'], array_merge($val, $cat_vars), $chunk);

                /** --------------------------------
                /**  {count}
                /** --------------------------------*/
                if (strpos($chunk, LD . 'count' . RD) !== false) {
                    $chunk = str_replace(LD . 'count' . RD, $this->category_count, $chunk);
                }

                // {switch=}
                $chunk = ee()->TMPL->parse_switch($chunk, $this->category_count - 1);

                /** --------------------------------
                /**  {total_results}
                /** --------------------------------*/
                if (strpos($chunk, LD . 'total_results' . RD) !== false) {
                    $chunk = str_replace(LD . 'total_results' . RD, $total_results, $chunk);
                }

                $str .= $chunk;
            }

            if (ee()->TMPL->fetch_param('backspace')) {
                $str = substr($str, 0, - ee()->TMPL->fetch_param('backspace'));
            }
        }
        ee()->TMPL->set_data($all);
        ee()->load->library('file_field');
        $str = ee()->file_field->parse_string($str);

        return $str;
    }

    /**
      *  Process Subcategories
      */
    public function process_subcategories($parent_id)
    {
        foreach ($this->temp_array as $key => $val) {
            if ($parent_id == $val[1]) {
                $this->cat_array[] = $val;
                $this->process_subcategories($key);
            }
        }
    }

    /**
      *  Category archives
      */
    public function category_archive()
    {
        $sql = "SELECT exp_channel_category_groups.channel_id, exp_channel_category_groups.group_id FROM exp_channel_category_groups LEFT JOIN exp_channels ON exp_channel_category_groups.channel_id=exp_channels.channel_id WHERE site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";

        if ($channel = ee()->TMPL->fetch_param('channel')) {
            $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');
        }

        $cat_groups = ee()->db->query($sql);

        if ($cat_groups->num_rows() == 0) {
            return ee()->TMPL->no_results();
        }

        $group_ids = $cat_groups->row('cat_group');

        $channel_ids = array();
        $group_ids = array();
        foreach ($cat_groups->result_array() as $group) {
            $channel_ids[] = $group['channel_id'];
            $group_ids[] = $group['group_id'];
        }

        $channel_ids = array_unique($channel_ids);
        $group_ids = array_unique($group_ids);

        if ($category_group = ee()->TMPL->fetch_param('category_group')) {
            if (substr($category_group, 0, 4) == 'not ') {
                $x = explode('|', substr($category_group, 4));
                $group_ids = array_diff($group_ids, $x);
            } else {
                $x = explode('|', $category_group);

                $group_ids = array_intersect($group_ids, $x);
            }

            $group_ids = array_filter($group_ids, 'is_numeric');
        }

        $sql = "SELECT exp_category_posts.cat_id, exp_channel_titles.entry_id, exp_channel_titles.title, exp_channel_titles.url_title, exp_channel_titles.entry_date,
            exp_channels.channel_id, exp_channels.channel_name AS channel_short_name, exp_channels.channel_title AS channel, exp_channels.channel_url
                FROM exp_channel_titles, exp_category_posts, exp_channels
                WHERE exp_channel_titles.channel_id IN ('" . implode("','", $channel_ids) . "')
                AND exp_channel_titles.entry_id = exp_category_posts.entry_id
                AND exp_channels.channel_id = exp_channel_titles.channel_id
                ";

        $timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

        if (ee()->TMPL->fetch_param('sticky') === 'only') {
            $sql .= "AND exp_channel_titles.sticky = 'y'";
        }

        if (ee()->TMPL->fetch_param('show_future_entries') != 'yes') {
            $sql .= "AND exp_channel_titles.entry_date < " . $timestamp . " ";
        }

        if (ee()->TMPL->fetch_param('show_expired') != 'yes') {
            $sql .= "AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > " . $timestamp . ") ";
        }

        if ($status = ee()->TMPL->fetch_param('status')) {
            $status = str_replace('Open', 'open', $status);
            $status = str_replace('Closed', 'closed', $status);

            $sql .= ee()->functions->sql_andor_string($status, 'exp_channel_titles.status');
        } else {
            $sql .= "AND exp_channel_titles.status = 'open' ";
        }

        if (ee()->TMPL->fetch_param('show') !== false) {
            $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('show'), 'exp_category_posts.cat_id') . ' ';
        }

        $orderby = ee()->TMPL->fetch_param('orderby');

        $sql .= " ORDER BY ";

        if (ee()->TMPL->fetch_param('sticky') === 'yes') {
            $sql .= "exp_channel_titles.sticky desc, ";
        }

        switch ($orderby) {
            case 'date':
                $sql .= "exp_channel_titles.entry_date";

                break;
            case 'edit_date':
                $sql .= "exp_channel_titles.edit_date";

                break;
            case 'expiration_date':
                $sql .= "exp_channel_titles.expiration_date";

                break;
            case 'title':
                $sql .= "exp_channel_titles.title";

                break;
            case 'comment_total':
                $sql .= "exp_channel_titles.entry_date";

                break;
            case 'most_recent_comment':
                $sql .= "exp_channel_titles.recent_comment_date desc, exp_channel_titles.entry_date";

                break;
            default:
                $sql .= "exp_channel_titles.title";

                break;
        }

        $sort = ee()->TMPL->fetch_param('sort');

        switch ($sort) {
            case 'asc':
                $sql .= " asc";

                break;
            case 'desc':
                $sql .= " desc";

                break;
            default:
                $sql .= " asc";

                break;
        }

        $result = ee()->db->query($sql);
        $channel_array = array();

        $parent_only = (ee()->TMPL->fetch_param('parent_only') == 'yes') ? true : false;

        // Gather patterns for parsing and replacement of variable pairs
        $categories_pattern = "/" . LD . "categories\s*" . RD . "(.*?)" . LD . '\/' . "categories\s*" . RD . "/s";
        $titles_pattern = "/" . LD . "entry_titles\s*" . RD . "(.*?)" . LD . '\/' . "entry_titles\s*" . RD . "/s";

        $cat_chunk = (preg_match($categories_pattern, ee()->TMPL->tagdata, $match)) ? $match[1] : '';

        $c_path = array();

        if (preg_match_all("#" . LD . "path(=.+?)" . RD . "#", $cat_chunk, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $c_path[$matches[0][$i]] = ee()->functions->create_url(ee()->functions->extract_path($matches[1][$i]));
            }
        }

        $title_chunk = (preg_match($titles_pattern, ee()->TMPL->tagdata, $match)) ? $match[1] : '';

        $t_path = array();

        if (preg_match_all("#" . LD . "path(=.+?)" . RD . "#", $title_chunk, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $t_path[$matches[0][$i]] = ee()->functions->create_url(ee()->functions->extract_path($matches[1][$i]));
            }
        }

        $id_path = array();

        if (preg_match_all("#" . LD . "entry_id_path(=.+?)" . RD . "#", $title_chunk, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $id_path[$matches[0][$i]] = ee()->functions->create_url(ee()->functions->extract_path($matches[1][$i]));
            }
        }

        $return_data = '';

        $site_pages = config_item('site_pages');

        foreach (ee()->TMPL->site_ids as $site_id) {
            if ($site_id != ee()->config->item('site_id')) {
                $pages = ee()->config->site_pages($site_id);
                $site_pages[$site_id] = $pages[$site_id];
            }
        }

        $site_id = ee()->config->item('site_id');

        if (ee()->TMPL->fetch_param('style') == '' or ee()->TMPL->fetch_param('style') == 'nested') {
            if ($result->num_rows() > 0 && $title_chunk != '') {
                $i = 0;

                foreach ($result->result_array() as $row) {
                    $chunk = "<li>" . str_replace(LD . 'category_name' . RD, '', $title_chunk) . "</li>";

                    foreach ($t_path as $tkey => $tval) {
                        $chunk = str_replace($tkey, reduce_double_slashes($tval . '/' . $row['url_title']), $chunk);
                    }

                    foreach ($id_path as $tkey => $tval) {
                        $chunk = str_replace($tkey, reduce_double_slashes($tval . '/' . $row['entry_id']), $chunk);
                    }

                    $chunk = ee()->TMPL->parse_date_variables($chunk, array('entry_date' => $row['entry_date']));

                    $row['channel_url'] = parse_config_variables($row['channel_url']);

                    if (isset($site_pages[$site_id]['uris'][$row['entry_id']])) {
                        $row['page_uri'] = $site_pages[$site_id]['uris'][$row['entry_id']];
                        $row['page_url'] = ee()->functions->create_page_url($site_pages[$site_id]['url'], $site_pages[$site_id]['uris'][$row['entry_id']]);
                    } else {
                        $row['page_uri'] = '';
                        $row['page_url'] = '';
                    }

                    $chunk = ee()->TMPL->parse_variables_row($chunk, $row);

                    $channel_array[$i . '_' . $row['cat_id']] = str_replace(LD . 'title' . RD, $row['title'], $chunk);
                    $i++;
                }
            }

            $this->category_tree(array(
                'group_id' => implode('|', $group_ids),
                'channel_ids' => $channel_ids,
                'path' => $c_path,
                'template' => $cat_chunk,
                'channel_array' => $channel_array,
                'parent_only' => $parent_only,
                'show_empty' => ee()->TMPL->fetch_param('show_empty'),
                'strict_empty' => 'yes'
            ));

            if (count($this->category_list) > 0) {
                $id_name = (ee()->TMPL->fetch_param('id') === false) ? 'nav_cat_archive' : ee()->TMPL->fetch_param('id');
                $class_name = (ee()->TMPL->fetch_param('class') === false) ? 'nav_cat_archive' : ee()->TMPL->fetch_param('class');

                $this->category_list[0] = '<ul id="' . $id_name . '" class="' . $class_name . '">' . "\n";

                foreach ($this->category_list as $val) {
                    $return_data .= $val;
                }
            }
        } else {
            // fetch category field names and id's
            list($field_sqla, $field_sqlb) = $this->generateCategoryFieldSQL($group_ids);

            $sql = "SELECT DISTINCT (c.cat_id), c.group_id, c.cat_name, c.cat_url_title, c.cat_description, c.cat_image, c.parent_id, c.cat_order {$field_sqla}
                    FROM (exp_categories AS c";

            $sql .= ") {$field_sqlb}";

            if (ee()->TMPL->fetch_param('show_empty') == 'no') {
                $sql .= " LEFT JOIN exp_category_posts ON c.cat_id = exp_category_posts.cat_id ";

                if (count($channel_ids)) {
                    $sql .= " LEFT JOIN exp_channel_titles ON exp_category_posts.entry_id = exp_channel_titles.entry_id ";
                }
            }

            $sql .= " WHERE c.group_id IN ('" . implode("','", $group_ids) . "') ";

            if (ee()->TMPL->fetch_param('show_empty') == 'no') {
                if (count($channel_ids)) {
                    $sql .= "AND exp_channel_titles.channel_id IN ('" . implode("','", $channel_ids) . "') ";
                } else {
                    $sql .= " AND exp_channel_titles.site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";
                }

                if ($status = ee()->TMPL->fetch_param('status')) {
                    $status = str_replace('Open', 'open', $status);
                    $status = str_replace('Closed', 'closed', $status);

                    $sql .= ee()->functions->sql_andor_string($status, 'exp_channel_titles.status');
                } else {
                    $sql .= "AND exp_channel_titles.status = 'open' ";
                }

                if (ee()->TMPL->fetch_param('show_empty') == 'no') {
                    $sql .= "AND exp_category_posts.cat_id IS NOT NULL ";
                }
            }

            if (ee()->TMPL->fetch_param('show') !== false) {
                $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('show'), 'c.cat_id') . ' ';
            }

            if ($parent_only == true) {
                $sql .= " AND c.parent_id = 0";
            }

            $sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order, c.cat_id";
            $query = ee()->db->query($sql);

            if ($query->num_rows() > 0) {
                $used = array();
                $parent_ids = array();

                // Get category ID from URL for {if active} conditional
                ee()->load->helper('segment');
                $active_cat = parse_category($this->query_string);

                ee()->load->library('typography');

                foreach ($query->result_array() as $row) {
                    if (!empty($row['parent_id']) && !in_array($row['parent_id'], $parent_ids)) {
                        $parent_ids[] = $row['parent_id'];
                    }
                }

                foreach ($query->result_array() as $row) {
                    // We'll concatenate parsed category and title chunks here for
                    // replacing in the tagdata later
                    $categories_parsed = '';
                    $titles_parsed = '';

                    if (! isset($used[$row['cat_name']])) {
                        $chunk = $cat_chunk;

                        $cat_vars = array(
                            'category_name' => ee()->typography->format_characters($row['cat_name']),
                            'category_url_title' => $row['cat_url_title'],
                            'category_description' => $row['cat_description'],
                            'category_image' => (string) $row['cat_image'],
                            'category_id' => $row['cat_id'],
                            'parent_id' => $row['parent_id'],
                            'has_children' => in_array($row['cat_id'], $parent_ids),
                            'active' => ($active_cat == $row['cat_id'] || $active_cat == $row['cat_url_title'])
                        );

                        foreach ($this->catfields as $v) {
                            $cat_vars[$v['field_name']] = (! isset($row['field_id_' . $v['field_id']])) ? '' : $row['field_id_' . $v['field_id']];
                        }

                        $chunk = ee()->functions->prep_conditionals($chunk, $cat_vars);

                        $chunk = str_replace(
                            array(
                                LD . 'category_id' . RD,
                                LD . 'category_name' . RD,
                                LD . 'category_url_title' . RD,
                                LD . 'category_image' . RD,
                                LD . 'category_description' . RD,
                                LD . 'parent_id' . RD
                            ),
                            array(
                                $cat_vars['category_id'],
                                ee()->functions->encode_ee_tags($cat_vars['category_name']),
                                $cat_vars['category_url_title'],
                                $cat_vars['category_image'],
                                ee()->functions->encode_ee_tags($cat_vars['category_description']),
                                $cat_vars['parent_id']
                            ),
                            $chunk
                        );

                        foreach ($c_path as $ckey => $cval) {
                            $cat_seg = ($this->use_category_names == true) ? $this->reserved_cat_segment . '/' . $cat_vars['category_url_title'] : 'C' . $cat_vars['category_id'];
                            $chunk = str_replace($ckey, reduce_double_slashes($cval . '/' . $cat_seg), $chunk);
                        }

                        $chunk = $this->parseCategoryFields($cat_vars['category_id'], array_merge($row, $cat_vars), $chunk);

                        ee()->load->library('file_field');
                        $chunk = ee()->file_field->parse_string($chunk);

                        $categories_parsed .= $chunk;
                        $used[$cat_vars['category_name']] = true;
                    }

                    foreach ($result->result_array() as $trow) {
                        if ($trow['cat_id'] == $row['cat_id']) {
                            $chunk = str_replace(
                                array(LD . 'title' . RD, LD . 'category_name' . RD),
                                array($trow['title'],ee()->typography->format_characters($row['cat_name'])),
                                $title_chunk
                            );

                            foreach ($t_path as $tkey => $tval) {
                                $chunk = str_replace($tkey, reduce_double_slashes($tval . '/' . $trow['url_title']), $chunk);
                            }

                            foreach ($id_path as $tkey => $tval) {
                                $chunk = str_replace($tkey, reduce_double_slashes($tval . '/' . $trow['entry_id']), $chunk);
                            }

                            $chunk = ee()->TMPL->parse_date_variables($chunk, array('entry_date' => $trow['entry_date']));

                            $trow['channel_url'] = parse_config_variables($trow['channel_url']);

                            if (isset($site_pages[$site_id]['uris'][$trow['entry_id']])) {
                                $trow['page_uri'] = $site_pages[$site_id]['uris'][$trow['entry_id']];
                                $trow['page_url'] = ee()->functions->create_page_url($site_pages[$site_id]['url'], $site_pages[$site_id]['uris'][$trow['entry_id']]);
                            } else {
                                $trow['page_uri'] = '';
                                $trow['page_url'] = '';
                            }

                            $chunk = ee()->TMPL->parse_variables_row($chunk, $trow);

                            $titles_parsed .= $chunk;
                        }
                    }

                    // Parse row then concatenate on $return_data
                    $parsed_row = preg_replace($categories_pattern, $categories_parsed, ee()->TMPL->tagdata);
                    $parsed_row = preg_replace($titles_pattern, $titles_parsed, $parsed_row);

                    $return_data .= $parsed_row;
                }

                if (ee()->TMPL->fetch_param('backspace')) {
                    $return_data = substr($return_data, 0, - ee()->TMPL->fetch_param('backspace'));
                }
            }
        }

        return $return_data;
    }

    /** --------------------------------
    /**  Locate category parent
    /** --------------------------------*/
    // This little recursive gem will travel up the
    // category tree until it finds the category ID
    // number of any parents.  It's used by the function
    // below
    public function find_parent($parent, $all)
    {
        foreach ($all as $cat_id => $parent_id) {
            if ($parent == $cat_id) {
                $this->cat_full_array[] = $cat_id;

                if ($parent_id != 0) {
                    $this->find_parent($parent_id, $all);
                }
            }
        }
    }

    /**
      *  Category Tree
      *
      * This function and the next create a nested, hierarchical category tree
      */
    public function category_tree($cdata = array())
    {
        $default = array('group_id', 'channel_ids', 'path', 'template', 'depth', 'channel_array', 'parent_only', 'show_empty', 'strict_empty');

        foreach ($default as $val) {
            $$val = (! isset($cdata[$val])) ? '' : $cdata[$val];
        }

        if ($group_id == '') {
            return false;
        }

        list($field_sqla, $field_sqlb) = $this->generateCategoryFieldSQL($group_id);

        /** -----------------------------------
        /**  Are we showing empty categories
        /** -----------------------------------*/

        // If we are only showing categories that have been assigned to entries
        // we need to run a couple queries and run a recursive function that
        // figures out whether any given category has a parent.
        // If we don't do this we will run into a problem in which parent categories
        // that are not assigned to a channel will be supressed, and therefore, any of its
        // children will be supressed also - even if they are assigned to entries.
        // So... we will first fetch all the category IDs, then only the ones that are assigned
        // to entries, and lastly we'll recursively run up the tree and fetch all parents.
        // Follow that?  No?  Me neither...

        if ($show_empty == 'no') {
            // First we'll grab all category ID numbers

            $query = ee()->db->query("SELECT cat_id, parent_id FROM exp_categories
                                 WHERE group_id IN ('" . str_replace('|', "','", ee()->db->escape_str($group_id)) . "')
                                 ORDER BY group_id, parent_id, cat_order, cat_id");

            $all = array();

            // No categories exist?  Back to the barn for the night..
            if ($query->num_rows() == 0) {
                return false;
            }

            foreach ($query->result_array() as $row) {
                $all[$row['cat_id']] = $row['parent_id'];
            }

            // Next we'l grab only the assigned categories

            $sql = "SELECT DISTINCT(exp_categories.cat_id), exp_categories.parent_id, exp_categories.group_id, exp_categories.cat_order
                    FROM exp_categories
                    LEFT JOIN exp_category_posts ON exp_categories.cat_id = exp_category_posts.cat_id
                    LEFT JOIN exp_channel_titles ON exp_category_posts.entry_id = exp_channel_titles.entry_id ";

            $sql .= "WHERE group_id IN ('" . str_replace('|', "','", ee()->db->escape_str($group_id)) . "') ";

            $sql .= "AND exp_category_posts.cat_id IS NOT NULL ";

            if (count($channel_ids) && $strict_empty == 'yes') {
                $sql .= "AND exp_channel_titles.channel_id IN ('" . implode("','", $channel_ids) . "') ";
            } else {
                $sql .= "AND exp_channel_titles.site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";
            }

            if (($status = ee()->TMPL->fetch_param('status')) !== false) {
                $status = str_replace(array('Open', 'Closed'), array('open', 'closed'), $status);
                $sql .= ee()->functions->sql_andor_string($status, 'exp_channel_titles.status');
            } else {
                $sql .= "AND exp_channel_titles.status != 'closed' ";
            }

            /**------
            /**  We only select entries that have not expired
            /**------*/

            $timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

            if (ee()->TMPL->fetch_param('show_future_entries') != 'yes') {
                $sql .= " AND exp_channel_titles.entry_date < " . $timestamp . " ";
            }

            if (ee()->TMPL->fetch_param('show_expired') != 'yes') {
                $sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > " . $timestamp . ") ";
            }

            if ($parent_only === true) {
                $sql .= " AND parent_id = 0";
            }

            $sql .= " ORDER BY exp_categories.group_id, exp_categories.parent_id, exp_categories.cat_order, exp_categories.cat_id";

            $query = ee()->db->query($sql);

            if ($query->num_rows() == 0) {
                return false;
            }

            // All the magic happens here, baby!!

            foreach ($query->result_array() as $row) {
                if ($row['parent_id'] != 0) {
                    $this->find_parent($row['parent_id'], $all);
                }

                $this->cat_full_array[] = $row['cat_id'];
            }

            $this->cat_full_array = array_unique($this->cat_full_array);

            $sql = "SELECT c.cat_id, c.parent_id, c.cat_name, c.cat_url_title, c.cat_image, c.cat_description {$field_sqla}
            FROM exp_categories AS c
            {$field_sqlb}
            WHERE c.cat_id IN (";

            foreach ($this->cat_full_array as $val) {
                $sql .= $val . ',';
            }

            $sql = substr($sql, 0, -1) . ')';

            $sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order, c.cat_id";

            $query = ee()->db->query($sql);

            if ($query->num_rows() == 0) {
                return false;
            }
        } else {
            $sql = "SELECT DISTINCT(c.cat_id), c.group_id, c.parent_id, c.cat_name, c.cat_url_title, c.cat_image, c.cat_description, c.cat_order {$field_sqla}
                    FROM exp_categories AS c
                    {$field_sqlb}
                    WHERE c.group_id IN ('" . str_replace('|', "','", ee()->db->escape_str($group_id)) . "') ";

            if ($parent_only === true) {
                $sql .= " AND c.parent_id = 0";
            }

            $sql .= " ORDER BY c.group_id, c.parent_id, c.cat_order, c.cat_id";

            $query = ee()->db->query($sql);

            if ($query->num_rows() == 0) {
                return false;
            }
        }

        // Here we check the show parameter to see if we have any
        // categories we should be ignoring or only a certain group of
        // categories that we should be showing.  By doing this here before
        // all of the nested processing we should keep out all but the
        // request categories while also not having a problem with having a
        // child but not a parent.  As we all know, categories are not asexual

        if (ee()->TMPL->fetch_param('show') !== false) {
            if (strncmp(ee()->TMPL->fetch_param('show'), 'not ', 4) == 0) {
                $not_these = explode('|', trim(substr(ee()->TMPL->fetch_param('show'), 3)));
            } else {
                $these = explode('|', trim(ee()->TMPL->fetch_param('show')));
            }
        }

        foreach ($query->result_array() as $row) {
            if (isset($not_these) && in_array($row['cat_id'], $not_these)) {
                continue;
            } elseif (isset($these) && ! in_array($row['cat_id'], $these)) {
                continue;
            }

            $this->cat_array[$row['cat_id']] = array($row['parent_id'], $row['cat_name'], $row['cat_image'], $row['cat_description'], $row['cat_url_title']);

            foreach ($row as $key => $val) {
                if (strpos($key, 'field') !== false) {
                    $this->cat_array[$row['cat_id']][$key] = $val;
                }
            }
        }

        $this->temp_array = $this->cat_array;

        $open = 0;

        $this->category_count = 0;
        $total_results = count($this->cat_array);

        // Get category ID from URL for {if active} conditional
        ee()->load->helper('segment');
        $active_cat = parse_category($this->query_string);

        $this->category_subtree(
            array(
                'parent_id' => '0',
                'path' => $path,
                'template' => $template,
                'channel_array' => $channel_array
            ),
            $active_cat
        );
    }

    /**
      *  Category Sub-tree
      */
    public function category_subtree($cdata = array(), $active_cat = null)
    {
        $default = array('parent_id', 'path', 'template', 'depth', 'channel_array', 'show_empty');

        foreach ($default as $val) {
            $$val = (! isset($cdata[$val])) ? '' : $cdata[$val];
        }

        $open = 0;

        if ($depth == '') {
            $depth = 1;
        }

        $tab = '';
        for ($i = 0; $i <= $depth; $i++) {
            $tab .= "\t";
        }

        $total_results = count($this->cat_array);

        // Get category ID from URL for {if active} conditional
        if ($active_cat === null) {
            $active_cat = parse_category($this->query_string);
            ee()->load->helper('segment');
        }

        $parent_ids = array();

        foreach ($this->cat_array as $val) {
            if (!empty($val[0]) && !in_array($val[0], $parent_ids)) {
                $parent_ids[] = $val[0];
            }
        }

        foreach ($this->cat_array as $key => $val) {
            if ($parent_id == $val[0]) {
                if ($open == 0) {
                    $open = 1;
                    $this->category_list[] = "\n" . $tab . "<ul>\n";
                }

                $chunk = $template;

                ee()->load->library('typography');

                $cat_vars = array(
                    'category_name' => ee()->typography->format_characters($val[1]),
                    'category_url_title' => $val[4],
                    'category_description' => $val[3],
                    'category_image' => (string) $val[2],
                    'category_id' => $key,
                    'parent_id' => $val[0],
                    'has_children' => in_array($key, $parent_ids),
                    'active' => ($active_cat == $key || $active_cat == $val[4])
                );

                // add custom fields for conditionals prep
                foreach ($this->catfields as $v) {
                    $cat_vars[$v['field_name']] = (! isset($val['field_id_' . $v['field_id']])) ? '' : $val['field_id_' . $v['field_id']];
                }

                $cat_vars['count'] = ++$this->category_count;
                $cat_vars['total_results'] = $total_results;

                $chunk = ee()->functions->prep_conditionals($chunk, $cat_vars);

                $chunk = str_replace(
                    array(
                        LD . 'category_id' . RD,
                        LD . 'category_name' . RD,
                        LD . 'category_url_title' . RD,
                        LD . 'category_image' . RD,
                        LD . 'category_description' . RD,
                        LD . 'parent_id' . RD
                    ),
                    array(
                        $cat_vars['category_id'],
                        ee()->functions->encode_ee_tags($cat_vars['category_name']),
                        $cat_vars['category_url_title'],
                        $cat_vars['category_image'],
                        ee()->functions->encode_ee_tags($cat_vars['category_description']),
                        $cat_vars['parent_id']
                    ),
                    $chunk
                );

                foreach ($path as $pkey => $pval) {
                    if ($this->use_category_names == true) {
                        $chunk = str_replace($pkey, reduce_double_slashes($pval . '/' . $this->reserved_cat_segment . '/' . $val[4]), $chunk);
                    } else {
                        $chunk = str_replace($pkey, reduce_double_slashes($pval . '/C' . $key), $chunk);
                    }
                }

                $chunk = $this->parseCategoryFields($cat_vars['category_id'], array_merge($val, $cat_vars), $chunk);

                ee()->load->library('file_field');
                $chunk = ee()->file_field->parse_string($chunk);

                /** --------------------------------
                /**  {count}
                /** --------------------------------*/
                if (strpos($chunk, LD . 'count' . RD) !== false) {
                    $chunk = str_replace(LD . 'count' . RD, $this->category_count, $chunk);
                }

                // {switch=}
                $chunk = ee()->TMPL->parse_switch($chunk, $this->category_count - 1);

                /** --------------------------------
                /**  {total_results}
                /** --------------------------------*/
                if (strpos($chunk, LD . 'total_results' . RD) !== false) {
                    $chunk = str_replace(LD . 'total_results' . RD, $total_results, $chunk);
                }

                $this->category_list[] = $tab . "\t<li>" . $chunk;

                if (is_array($channel_array)) {
                    $fillable_entries = 'n';

                    foreach ($channel_array as $k => $v) {
                        $k = substr($k, strpos($k, '_') + 1);

                        if ($key == $k) {
                            if (! isset($fillable_entries) or $fillable_entries == 'n') {
                                $this->category_list[] = "\n{$tab}\t\t<ul>\n";
                                $fillable_entries = 'y';
                            }

                            $this->category_list[] = "{$tab}\t\t\t$v";
                        }
                    }
                }

                if (isset($fillable_entries) && $fillable_entries == 'y') {
                    $this->category_list[] = "{$tab}\t\t</ul>\n";
                }

                $t = '';

                $this->category_subtree(
                    array(
                        'parent_id' => $key,
                        'path' => $path,
                        'template' => $template,
                        'depth' => $depth + 2,
                        'channel_array' => $channel_array
                    ),
                    $active_cat
                );

                if (isset($fillable_entries) && $fillable_entries == 'y') {
                    $t .= "$tab\t";
                }

                $this->category_list[] = $t . "</li>\n";

                unset($this->temp_array[$key]);

                $this->close_ul($parent_id, $depth + 1);
            }
        }

        return $open;
    }

    /**
     * Parse category fields
     *
     * @param   int     $category_id    Category ID
     * @param   array   $data           Array that usually contains pertinant info
     * @param   string  $chunk          Tagdata currently being modified
     * @param   array   $variables      Array of variables found in the string to be parsed
     * @return  string  String with category fields parsed
     */
    public function parseCategoryFields($category_id, $data, $chunk, $variables = array())
    {
        // Load typography library for custom fields
        ee()->load->library('typography');
        ee()->typography->initialize(array(
            'convert_curly' => false
        ));

        $field_index = array();
        foreach ($this->catfields as $cat_field) {
            $field_index[$cat_field['field_name']] = $cat_field['field_id'];
        }

        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');

        if (empty($variables)) {
            $variables = ee()->TMPL->var_single;
        }

        // native metadata fields with will pass through here, treat them like text fields
        ee()->api_channel_fields->include_handler('text');
        $fieldtype = ee()->api_channel_fields->setup_handler('text', true);
        ee()->api_channel_fields->field_types['text'] = $fieldtype;
        //category image should be treated as file though
        $file_fieldtype = ee()->api_channel_fields->setup_handler('file', true);
        ee()->api_channel_fields->field_types['file'] = $file_fieldtype;

        foreach ($variables as $tag) {
            $var_props = ee('Variables/Parser')->parseVariableProperties($tag);
            $field_name = $var_props['field_name'];
            // only deal with variables we own
            if (! isset($data[$field_name])) {
                continue;
            }

            if (isset($field_index[$field_name]) && isset($data['field_id_' . $field_index[$field_name]])) {
                // custom fields
                $field_id = $field_index[$field_name];
                $cat_field = $this->cat_field_models[$field_id];

                $chunk = $cat_field->parse(
                    $data['field_id_' . $field_id],
                    $category_id,
                    'category',
                    $var_props,
                    $chunk,
                    array(
                        'channel_html_formatting' => $data['field_html_formatting'],
                        'channel_auto_link_urls' => 'n',
                        'channel_allow_img_urls' => 'y',
                        'field_ft_' . $field_id => $data['field_ft_' . $field_id]
                    ),
                    $tag
                );
            } elseif (isset($data[$field_name])) {
                // built-in fields
                $content = $data[$field_name];

                if (! empty($var_props['modifier'])) {

                    if ($field_name == 'category_image') {
                        $class = $file_fieldtype;
                        ee()->load->library('file_field');
                        ee()->api_channel_fields->field_type = 'file';
                        $content = ee()->file_field->parse_field($content);
                    } else {
                        ee()->api_channel_fields->field_type = 'text';
                        $class = $fieldtype;
                    }

                    if (isset($var_props['all_modifiers']) && !empty($var_props['all_modifiers'])) {
                        foreach ($var_props['all_modifiers'] as $modifier => $params) {
                            $parse_fnc = 'replace_' . $modifier;
                            if (method_exists($class, $parse_fnc)) {
                                $content = ee()->api_channel_fields->apply($parse_fnc, array(
                                    $content,
                                    $params,
                                    false
                                ));
                            } elseif (method_exists($class, 'replace_tag_catchall')) {
                                $content = ee()->api_channel_fields->apply('replace_tag_catchall', array(
                                    $content,
                                    $params,
                                    false,
                                    $modifier
                                ));
                            }
                        }
                    } else {
                        $parse_fnc = 'replace_' . $var_props['modifier'];
                        if (method_exists($class, $parse_fnc)) {
                            $content = ee()->api_channel_fields->apply($parse_fnc, array(
                                $content,
                                $var_props['params'],
                                false
                            ));
                        } elseif (method_exists($class, 'replace_tag_catchall')) {
                            $content = ee()->api_channel_fields->apply('replace_tag_catchall', array(
                                $content,
                                $var_props['params'],
                                false,
                                $var_props['modifier']
                            ));
                        }
                    }
                }

                $chunk = str_replace(LD . $tag . RD, $content, $chunk);
            } else {
                // Garbage collection
                if ($var_props['modifier']) {
                    $field_name = $field_name . ':' . $var_props['modifier'];
                }
                $chunk = str_replace(LD . $field_name . RD, '', $chunk);
            }
        }

        return $chunk;
    }

    /**
     * Called after $this->catfields is populated, caches associated CategoryField models
     */
    private function cacheCategoryFieldModels()
    {
        if (isset(ee()->session)) {
            $this->cat_field_models = ee()->session->cache(__CLASS__, 'cat_field_models') ?: array();
        } else {
            $this->cat_field_models = [];
        }

        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');

        // Get field names present in the template, sans modifiers
        $clean_field_names = array_map(function ($field) {
            $field = ee('Variables/Parser')->parseVariableProperties($field);

            return $field['field_name'];
        }, array_flip(ee()->TMPL->var_single));

        // Get field IDs for the category fields we need to fetch
        $field_ids = array();
        foreach ($this->catfields as $cat_field) {
            if (
                in_array($cat_field['field_name'], $clean_field_names) &&
                ! isset($this->cat_field_models[$cat_field['field_id']])
            ) {
                $field_ids[] = $cat_field['field_id'];
            }
        }

        if (empty($field_ids)) {
            return;
        }

        $this->cat_field_models += ee('Model')->get('CategoryField', array_unique($field_ids))
            ->all()
            ->indexBy('field_id');

        if (isset(ee()->session)) {
            ee()->session->set_cache(__CLASS__, 'cat_field_models', $this->cat_field_models);
        }
    }

    /**
      *  Close </ul> tags
      *
      * This is a helper function to the above
      */
    public function close_ul($parent_id, $depth = 0)
    {
        $count = 0;

        $tab = "";
        for ($i = 0; $i < $depth; $i++) {
            $tab .= "\t";
        }

        foreach ($this->temp_array as $val) {
            if ($parent_id == $val[0]) {
                $count++;
            }
        }

        if ($count == 0) {
            $this->category_list[] = $tab . "</ul>\n";
        }
    }

    /**
      *  Channel "category_heading" tag
      */
    public function category_heading()
    {
        if ($this->query_string == '' && !ee()->TMPL->fetch_param('category_url_title') && !ee()->TMPL->fetch_param('category_id')) {
            return ee()->TMPL->no_results();
        }

        // -------------------------------------------
        // 'channel_module_category_heading_start' hook.
        //  - Rewrite the displaying of category headings, if you dare!
        //
        if (ee()->extensions->active_hook('channel_module_category_heading_start') === true) {
            ee()->TMPL->tagdata = ee()->extensions->call('channel_module_category_heading_start');
            if (ee()->extensions->end_script === true) {
                return ee()->TMPL->tagdata;
            }
        }
        //
        // -------------------------------------------

        $qstring = $this->query_string;

        /** --------------------------------------
        /**  Remove page number
        /** --------------------------------------*/
        if (preg_match("#/P\d+#", $qstring, $match)) {
            $qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
        }

        /** --------------------------------------
        /**  Remove "N"
        /** --------------------------------------*/
        if (preg_match("#/N(\d+)#", $qstring, $match)) {
            $qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
        }

        // Is the category being specified by name?
        if (
            (
                (
                    $qstring !== ''
                    && $this->reserved_cat_segment !== ''
                    && in_array($this->reserved_cat_segment, explode('/', $qstring))
                )
                or ee()->TMPL->fetch_param('category_url_title')
            )
            && ee()->TMPL->fetch_param('channel')
        ) {
            $qstring = preg_replace("/(.*?)\/" . preg_quote($this->reserved_cat_segment) . "\//i", '', '/' . $qstring);

            $sql = "SELECT exp_channel_category_groups.channel_id, exp_channel_category_groups.group_id FROM exp_channel_category_groups LEFT JOIN exp_channels ON exp_channel_category_groups.channel_id=exp_channels.channel_id WHERE site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "')  AND ";

            $xsql = ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('channel'), 'channel_name');

            if (substr($xsql, 0, 3) == 'AND') {
                $xsql = substr($xsql, 3);
            }

            $sql .= ' ' . $xsql;

            $query = ee()->db->query($sql);

            if ($query->num_rows() > 0) {
                $valid = 'y';
                $valid_cats = [];

                if (ee()->TMPL->fetch_param('relaxed_categories') == 'yes') {
                    foreach ($query->result_array() as $row) {
                        $valid_cats[] = $row['group_id'];
                    }
                } else {
                    $channel_cat_groups = [];
                    foreach ($query->result_array() as $row) {
                        if (!isset($channel_cat_groups[$row['channel_id']])) {
                            $channel_cat_groups[$row['channel_id']] = [];
                        }
                        $channel_cat_groups[$row['channel_id']][] = $row['group_id'];
                    }
                    // if there's just one channel specified, use it's categories group;
                    // if multiple channels, only use the =categories groups that they share
                    if (count($channel_cat_groups) == 1) {
                        $valid_cats = $channel_cat_groups[array_keys($channel_cat_groups)[0]];
                    } else {
                        $valid_cats = call_user_func_array('array_intersect', $channel_cat_groups);
                    }
                }

                $valid_cats = array_unique($valid_cats);

                if (count($valid_cats) == 0) {
                    $valid = 'n';
                }
            } else {
                $valid = 'n';
            }

            if ($valid == 'y') {
                // the category URL title should be the first segment left at this point in $qstring,
                // but because prior to this feature being added, category names were used in URLs,
                // and '/' is a valid character for category names.  If they have not updated their
                // category url titles since updating to 1.6, their category URL title could still
                // contain a '/'.  So we'll try to get the category the correct way first, and if
                // it fails, we'll try the whole $qstring

                $temp = explode('/', $qstring);
                $cut_qstring = array_shift($temp);

                if (ee()->TMPL->fetch_param('category_url_title')) {
                    $cut_qstring = ee()->TMPL->fetch_param('category_url_title');
                }

                $result = ee()->db->query("SELECT cat_id FROM exp_categories
                                      WHERE cat_url_title='" . ee()->db->escape_str($cut_qstring) . "'
                                      AND group_id IN ('" . implode("','", $valid_cats) . "')");

                if ($result->num_rows() == 1) {
                    $qstring = !ee()->TMPL->fetch_param('category_url_title')
                        ? str_replace($cut_qstring, 'C' . $result->row('cat_id'), $qstring)
                        : 'C' . $result->row('cat_id');
                } else {
                    // give it one more try using the whole $qstring
                    $result = ee()->db->query("SELECT cat_id FROM exp_categories
                                          WHERE cat_url_title='" . ee()->db->escape_str($qstring) . "'
                                          AND group_id IN ('" . implode("','", $valid_cats) . "')");

                    if ($result->num_rows() == 1) {
                        $qstring = 'C' . $result->row('cat_id') ;
                    }
                }
            }
        }

        // Is the category being specified by ID?

        if (! preg_match("#(^|\/)C(\d+)#", $qstring, $match) and ! ee()->TMPL->fetch_param('category_id')) {
            return ee()->TMPL->no_results();
        }

        $cat_id = ee()->TMPL->fetch_param('category_id') !== false && ctype_digit(ee()->TMPL->fetch_param('category_id')) ? ee()->TMPL->fetch_param('category_id') : $match[2];

        // fetch category field names and id's

        if ($this->enable['category_fields'] === true) {
            // limit to correct category group
            $gquery = ee()->db->query("SELECT group_id FROM exp_categories WHERE cat_id = '" . ee()->db->escape_str($cat_id) . "'");

            if ($gquery->num_rows() == 0) {
                return ee()->TMPL->no_results();
            }

            list($field_sqla, $field_sqlb) = $this->generateCategoryFieldSQL($gquery->row('group_id'));
        } else {
            $field_sqla = '';
            $field_sqlb = '';
        }

        $query = ee()->db->query("SELECT c.cat_name, c.parent_id, c.cat_url_title, c.cat_description, c.cat_image {$field_sqla}
                            FROM exp_categories AS c
                            {$field_sqlb}
                            WHERE c.cat_id = '" . ee()->db->escape_str($cat_id) . "'");

        if ($query->num_rows() == 0) {
            return ee()->TMPL->no_results();
        }

        $row = $query->row_array();

        ee()->load->library('typography');

        $cat_vars = array(
            'category_name' => ee()->typography->format_characters($query->row('cat_name')),
            'category_url_title' => $query->row('cat_url_title'),
            'category_description' => $query->row('cat_description'),
            'category_image' => (string) $query->row('cat_image'),
            'category_id' => $cat_id,
            'parent_id' => $query->row('parent_id')
        );

        // add custom fields for conditionals prep
        foreach ($this->catfields as $v) {
            $cat_vars[$v['field_name']] = ($query->row('field_id_' . $v['field_id'])) ? $query->row('field_id_' . $v['field_id']) : '';
        }

        ee()->TMPL->set_data($cat_vars);

        ee()->TMPL->tagdata = ee()->functions->prep_conditionals(ee()->TMPL->tagdata, $cat_vars);

        ee()->TMPL->tagdata = str_replace(
            array(
                LD . 'category_id' . RD,
                LD . 'category_name' . RD,
                LD . 'category_url_title' . RD,
                LD . 'category_image' . RD,
                LD . 'category_description' . RD,
                LD . 'parent_id' . RD
            ),
            array(
                $cat_vars['category_id'],
                ee()->functions->encode_ee_tags($cat_vars['category_name']),
                $cat_vars['category_url_title'],
                $cat_vars['category_image'],
                ee()->functions->encode_ee_tags($cat_vars['category_description']),
                $cat_vars['parent_id']
            ),
            ee()->TMPL->tagdata
        );

        ee()->TMPL->tagdata = $this->parseCategoryFields($cat_vars['category_id'], array_merge($row, $cat_vars), ee()->TMPL->tagdata);

        ee()->load->library('file_field');
        ee()->TMPL->tagdata = ee()->file_field->parse_string(ee()->TMPL->tagdata);

        return ee()->TMPL->tagdata;
    }

    /** ---------------------------------------
    /**  Next / Prev entry tags
    /** ---------------------------------------*/
    public function next_entry()
    {
        return $this->next_prev_entry('next');
    }

    public function prev_entry()
    {
        return $this->next_prev_entry('prev');
    }

    public function next_prev_entry($which = 'next')
    {
        $which = ($which != 'next' and $which != 'prev') ? 'next' : $which;
        $sort = ($which == 'next') ? 'ASC' : 'DESC';

        // Don't repeat our work if we already know the single entry page details
        if (! isset(ee()->session->cache['channel']['single_entry_id']) or ! isset(ee()->session->cache['channel']['single_entry_date'])) {
            // no query string?  Nothing to do...
            if (($qstring = $this->query_string) == '') {
                return;
            }

            /** --------------------------------------
            /**  Remove page number
            /** --------------------------------------*/
            if (preg_match("#/P\d+#", $qstring, $match)) {
                $qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
            }

            /** --------------------------------------
            /**  Remove "N"
            /** --------------------------------------*/
            if (preg_match("#/N(\d+)#", $qstring, $match)) {
                $qstring = reduce_double_slashes(str_replace($match[0], '', $qstring));
            }

            if (strpos($qstring, '/') !== false) {
                $qstring = substr($qstring, 0, strpos($qstring, '/'));
            }

            /** ---------------------------------------
            /**  Query for the entry id and date
            /** ---------------------------------------*/
            ee()->db->select('t.entry_id, t.entry_date');
            ee()->db->from('channel_titles AS t');
            ee()->db->join('channels AS w', 'w.channel_id = t.channel_id', 'left');

            // url_title parameter
            if ($url_title = ee()->TMPL->fetch_param('url_title')) {
                ee()->db->where('t.url_title', $url_title);
            } else {
                // Found entry ID in query string
                if (is_numeric($qstring)) {
                    ee()->db->where('t.entry_id', $qstring);
                } else {
                    // Found URL title in query string
                    ee()->db->where('t.url_title', $qstring);
                }
            }

            ee()->db->where_in('w.site_id', ee()->TMPL->site_ids);

            // Channel paremter
            if ($channel_name = ee()->TMPL->fetch_param('channel')) {
                ee()->functions->ar_andor_string($channel_name, 'channel_name', 'w');
            }

            $query = ee()->db->get();

            // no results or more than one result?  Buh bye!
            if ($query->num_rows() != 1) {
                ee()->TMPL->log_item('Channel Next/Prev Entry tag error: Could not resolve single entry page id.');

                return;
            }

            $row = $query->row_array();

            ee()->session->cache['channel']['single_entry_id'] = $row['entry_id'];
            ee()->session->cache['channel']['single_entry_date'] = $row['entry_date'];
        }

        /** ---------------------------------------
        /**  Find the next / prev entry
        /** ---------------------------------------*/
        $ids = '';

        // Get included or excluded entry ids from entry_id parameter
        if (($entry_id = ee()->TMPL->fetch_param('entry_id')) != false) {
            $ids = ee()->functions->sql_andor_string($entry_id, 't.entry_id') . ' ';
        }

        $sql = 'SELECT t.entry_id, t.title, t.url_title, w.channel_name, w.channel_title, w.comment_url, w.channel_url, w.site_id
                FROM (exp_channel_titles AS t)
                LEFT JOIN exp_channels AS w ON w.channel_id = t.channel_id ';

        /* --------------------------------
        /*  We use LEFT JOIN when there is a 'not' so that we get
        /*  entries that are not assigned to a category.
        /* --------------------------------*/

        if ((substr(ee()->TMPL->fetch_param('category_group'), 0, 3) == 'not' or substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not') && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no') {
            $sql .= 'LEFT JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
                     LEFT JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ';
        } elseif (ee()->TMPL->fetch_param('category_group') or ee()->TMPL->fetch_param('category')) {
            $sql .= 'INNER JOIN exp_category_posts ON t.entry_id = exp_category_posts.entry_id
                     INNER JOIN exp_categories ON exp_category_posts.cat_id = exp_categories.cat_id ';
        }

        $sql .= ' WHERE t.entry_id != ' . ee()->session->cache['channel']['single_entry_id'] . ' ' . $ids;

        $timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

        if (ee()->TMPL->fetch_param('show_future_entries') != 'yes') {
            $sql .= " AND t.entry_date < {$timestamp} ";
        }

        // constrain by date depending on whether this is a 'next' or 'prev' tag
        if ($which == 'next') {
            $sql .= ' AND t.entry_date >= ' . ee()->session->cache['channel']['single_entry_date'] . ' ';
            $sql .= ' AND IF (t.entry_date = ' . ee()->session->cache['channel']['single_entry_date'] . ', t.entry_id > ' . ee()->session->cache['channel']['single_entry_id'] . ', 1) ';
        } else {
            $sql .= ' AND t.entry_date <= ' . ee()->session->cache['channel']['single_entry_date'] . ' ';
            $sql .= ' AND IF (t.entry_date = ' . ee()->session->cache['channel']['single_entry_date'] . ', t.entry_id < ' . ee()->session->cache['channel']['single_entry_id'] . ', 1) ';
        }

        if (ee()->TMPL->fetch_param('show_expired') != 'yes') {
            $sql .= " AND (t.expiration_date = 0 OR t.expiration_date > {$timestamp}) ";
        }

        $sql .= " AND w.site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";

        if ($channel_name = ee()->TMPL->fetch_param('channel')) {
            $sql .= ee()->functions->sql_andor_string($channel_name, 'channel_name', 'w') . " ";
        }

        if ($status = ee()->TMPL->fetch_param('status')) {
            $status = str_replace('Open', 'open', $status);
            $status = str_replace('Closed', 'closed', $status);

            $sql .= ee()->functions->sql_andor_string($status, 't.status') . " ";
        } else {
            $sql .= "AND t.status = 'open' ";
        }

        /**------
        /**  Limit query by category
        /**------*/

        if (ee()->TMPL->fetch_param('category')) {
            if (stristr(ee()->TMPL->fetch_param('category'), '&')) {
                /** --------------------------------------
                /**  First, we find all entries with these categories
                /** --------------------------------------*/
                $for_sql = (substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not') ? trim(substr(ee()->TMPL->fetch_param('category'), 3)) : ee()->TMPL->fetch_param('category');

                $csql = "SELECT exp_category_posts.entry_id, exp_category_posts.cat_id, " .
                        str_replace('SELECT', '', $sql) .
                        ee()->functions->sql_andor_string(str_replace('&', '|', $for_sql), 'exp_categories.cat_id');

                //exit($csql);

                $results = ee()->db->query($csql);

                if ($results->num_rows() == 0) {
                    return;
                }

                $type = 'IN';
                $categories = explode('&', ee()->TMPL->fetch_param('category'));
                $entry_array = array();

                if (substr($categories[0], 0, 3) == 'not') {
                    $type = 'NOT IN';

                    $categories[0] = trim(substr($categories[0], 3));
                }

                foreach ($results->result_array() as $row) {
                    $entry_array[$row['cat_id']][] = $row['entry_id'];
                }

                if (count($entry_array) < 2 or count(array_diff($categories, array_keys($entry_array))) > 0) {
                    return;
                }

                $chosen = call_user_func_array('array_intersect', $entry_array);

                if (count($chosen) == 0) {
                    return;
                }

                $sql .= "AND t.entry_id " . $type . " ('" . implode("','", $chosen) . "') ";
            } else {
                if (substr(ee()->TMPL->fetch_param('category'), 0, 3) == 'not' && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no') {
                    $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category'), 'exp_categories.cat_id', '', true) . " ";
                } else {
                    $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category'), 'exp_categories.cat_id') . " ";
                }
            }
        }

        if (ee()->TMPL->fetch_param('category_group')) {
            if (substr(ee()->TMPL->fetch_param('category_group'), 0, 3) == 'not' && ee()->TMPL->fetch_param('uncategorized_entries') !== 'no') {
                $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category_group'), 'exp_categories.group_id', '', true) . " ";
            } else {
                $sql .= ee()->functions->sql_andor_string(ee()->TMPL->fetch_param('category_group'), 'exp_categories.group_id') . " ";
            }
        }

        $sql .= " ORDER BY t.entry_date {$sort}, t.entry_id {$sort} LIMIT 1";

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) {
            return ee()->TMPL->no_results();
        }

        /** ---------------------------------------
        /**  Replace variables
        /** ---------------------------------------*/
        ee()->load->library('typography');

        $overrides = ee()->config->get_cached_site_prefs($query->row('site_id'));
        $channel_url = parse_config_variables($query->row('channel_url'), $overrides);
        $comment_url = parse_config_variables($query->row('comment_url'), $overrides);

        $comment_path = ($comment_url != '') ? $comment_url : $channel_url;

        $title = ee()->typography->format_characters($query->row('title'));

        $vars['0'] = array(
            'entry_id' => $query->row('entry_id'),
            'id_path' => array($query->row('entry_id'), array('path_variable' => true)),
            'path' => array($query->row('url_title'), array('path_variable' => true)),
            'title' => ee()->typography->formatTitle($title),
            'url_title' => $query->row('url_title'),
            'channel_short_name' => $query->row('channel_name'),
            'channel' => $query->row('channel_title'),
            'channel_url' => $channel_url,
            'comment_entry_id_auto_path' => reduce_double_slashes($comment_path . '/' . $query->row('entry_id')),
            'comment_url_title_auto_path' => reduce_double_slashes($comment_path . '/' . $query->row('url_title'))
        );

        // Presumably this is legacy
        if ($which == 'next') {
            $vars['0']['next_entry->title'] = $title;
        } else {
            $vars['0']['prev_entry->title'] = $title;
        }
        ee()->TMPL->set_data($vars['0']);

        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
    }

    /**
      *  Channel "month links"
      */
    public function month_links()
    {
        $return = '';

        //  Build query

        // Fetch the timezone array and calculate the offset so we can localize the month/year
        ee()->load->helper('date');
        $zones = timezones();

        $timezone = ee()->session->userdata('timezone', ee()->config->item('default_site_timezone'));

        $offset = (! isset($zones[$timezone]) or $zones[$timezone] == '') ? 0 : ($zones[$timezone] * 60 * 60);

        if (substr($offset, 0, 1) == '-') {
            $calc = 'entry_date - ' . substr($offset, 1);
        } elseif (substr($offset, 0, 1) == '+') {
            $calc = 'entry_date + ' . substr($offset, 1);
        } else {
            $calc = 'entry_date + ' . $offset;
        }

        $sql = "SELECT DISTINCT year(FROM_UNIXTIME(" . $calc . ")) AS year,
                        MONTH(FROM_UNIXTIME(" . $calc . ")) AS month
                        FROM exp_channel_titles
                        WHERE entry_id != ''
                        AND site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";

        $timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

        if (ee()->TMPL->fetch_param('show_future_entries') != 'yes') {
            $sql .= " AND exp_channel_titles.entry_date < " . $timestamp . " ";
        }

        if (ee()->TMPL->fetch_param('show_expired') != 'yes') {
            $sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > " . $timestamp . ") ";
        }

        /**------
        /**  Limit to/exclude specific channels
        /**------*/

        if ($channel = ee()->TMPL->fetch_param('channel')) {
            $wsql = "SELECT channel_id FROM exp_channels WHERE site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";

            $wsql .= ee()->functions->sql_andor_string($channel, 'channel_name');

            $query = ee()->db->query($wsql);

            if ($query->num_rows() > 0) {
                $sql .= " AND ";

                if ($query->num_rows() == 1) {
                    $sql .= "channel_id = '" . $query->row('channel_id') . "' ";
                } else {
                    $sql .= "(";

                    foreach ($query->result_array() as $row) {
                        $sql .= "channel_id = '" . $row['channel_id'] . "' OR ";
                    }

                    $sql = substr($sql, 0, - 3);

                    $sql .= ") ";
                }
            }
        }

        /**------
        /**  Add status declaration
        /**------*/

        if ($status = ee()->TMPL->fetch_param('status')) {
            $status = str_replace('Open', 'open', $status);
            $status = str_replace('Closed', 'closed', $status);

            $sstr = ee()->functions->sql_andor_string($status, 'status');

            if (stristr($sstr, "'closed'") === false) {
                $sstr .= " AND status != 'closed' ";
            }

            $sql .= $sstr;
        } else {
            $sql .= "AND status = 'open' ";
        }

        switch (ee()->TMPL->fetch_param('sort')) {
            case 'asc':
                $sort = "asc";
                break;

            case 'desc':
                $sort = "desc";
                break;

            default:
                $sort = "desc";
                break;
        }

        $sql .= " ORDER BY year $sort, month $sort";

        if (is_numeric(ee()->TMPL->fetch_param('limit'))) {
            $sql .= " LIMIT " . ee()->TMPL->fetch_param('limit');
        }

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) {
            return '';
        }

        $year_limit = (is_numeric(ee()->TMPL->fetch_param('year_limit'))) ? ee()->TMPL->fetch_param('year_limit') : 50;
        $total_years = 0;
        $current_year = '';

        foreach ($query->result_array() as $row) {
            $tagdata = ee()->TMPL->tagdata;

            $month = (strlen($row['month']) == 1) ? '0' . $row['month'] : $row['month'];
            $year = $row['year'];

            $month_name = ee()->localize->localize_month($month);

            //  Dealing with {year_heading}
            if (isset(ee()->TMPL->var_pair['year_heading'])) {
                if ($year == $current_year) {
                    $tagdata = ee()->TMPL->delete_var_pairs('year_heading', 'year_heading', $tagdata);
                } else {
                    $tagdata = ee()->TMPL->swap_var_pairs('year_heading', 'year_heading', $tagdata);

                    $total_years++;

                    if ($total_years > $year_limit) {
                        break;
                    }
                }

                $current_year = $year;
            }

            /** ---------------------------------------
            /**  prep conditionals
            /** ---------------------------------------*/
            $cond = array();

            $cond['month'] = ee()->lang->line($month_name[1]);
            $cond['month_short'] = ee()->lang->line($month_name[0]);
            $cond['month_num'] = $month;
            $cond['year'] = $year;
            $cond['year_short'] = substr($year, 2);

            $tagdata = ee()->functions->prep_conditionals($tagdata, $cond);

            //  parse path
            foreach (ee()->TMPL->var_single as $key => $val) {
                if (strncmp($key, 'path', 4) == 0) {
                    $tagdata = ee()->TMPL->swap_var_single(
                        $val,
                        ee()->functions->create_url(ee()->functions->extract_path($key) . '/' . $year . '/' . $month),
                        $tagdata
                    );
                }

                //  parse month (long)
                if ($key == 'month') {
                    $tagdata = ee()->TMPL->swap_var_single($key, ee()->lang->line($month_name[1]), $tagdata);
                }

                //  parse month (short)
                if ($key == 'month_short') {
                    $tagdata = ee()->TMPL->swap_var_single($key, ee()->lang->line($month_name[0]), $tagdata);
                }

                //  parse month (numeric)
                if ($key == 'month_num') {
                    $tagdata = ee()->TMPL->swap_var_single($key, $month, $tagdata);
                }

                //  parse year
                if ($key == 'year') {
                    $tagdata = ee()->TMPL->swap_var_single($key, $year, $tagdata);
                }

                //  parse year (short)
                if ($key == 'year_short') {
                    $tagdata = ee()->TMPL->swap_var_single($key, substr($year, 2), $tagdata);
                }
            }

            $return .= trim($tagdata) . "\n";
        }

        return $return;
    }

    public function related_category_entries()
    {
        // grab url_title= parameter, fallback on entry_id= param
        $current_entry = ee()->TMPL->fetch_param('url_title', ee()->TMPL->fetch_param('entry_id'));

        // try to divine one if no parameter was given
        if (! $current_entry) {
            $current_entry = $this->query_string;

            /** --------------------------------------
            /**  Remove page number
            /** --------------------------------------*/
            if (preg_match("#/P\d+#", $current_entry, $match)) {
                $current_entry = reduce_double_slashes(str_replace($match[0], '', $current_entry));
            }

            /** --------------------------------------
            /**  Remove "N"
            /** --------------------------------------*/
            if (preg_match("#/N(\d+)#", $current_entry, $match)) {
                $current_entry = reduce_double_slashes(str_replace($match[0], '', $current_entry));
            }

            /** --------------------------------------
            /**  Make sure to only get one segment
            /** --------------------------------------*/
            if (strpos($current_entry, '/') !== false) {
                $current_entry = substr($current_entry, 0, strpos($current_entry, '/'));
            }
        }

        /** ----------------------------------
        /**  Find Categories for Entry
        /** ----------------------------------*/
        $query = ee()->db->select('c.cat_id, c.cat_name')
            ->from('channel_titles t')
            ->join('category_posts p', 'p.entry_id = t.entry_id', 'INNER')
            ->join('categories c', 'p.cat_id = c.cat_id', 'INNER')
            ->where('c.cat_id IS NOT NULL')
            ->where_in('t.site_id', ee()->TMPL->site_ids);

        if (is_numeric($current_entry)) {
            $query->where('t.entry_id', $current_entry);
        } else {
            $query->where('t.url_title', $current_entry);
        }

        $query = ee()->db->get();

        if ($query->num_rows() == 0) {
            return ee()->TMPL->no_results();
        }

        /** ----------------------------------
        /**  Build category array
        /** ----------------------------------*/
        $cat_array = array();

        // We allow the option of adding or subtracting cat_id's
        $categories = (! ee()->TMPL->fetch_param('category')) ? '' : ee()->TMPL->fetch_param('category');

        if (strncmp($categories, 'not ', 4) == 0) {
            $categories = substr($categories, 4);
            $not_categories = explode('|', $categories);
        } else {
            $add_categories = explode('|', $categories);
        }

        foreach ($query->result_array() as $row) {
            if (! isset($not_categories) or array_search($row['cat_id'], $not_categories) === false) {
                $cat_array[] = $row['cat_id'];
            }
        }

        // User wants some categories added, so we add these cat_id's

        if (isset($add_categories) && count($add_categories) > 0) {
            foreach ($add_categories as $cat_id) {
                $cat_array[] = $cat_id;
            }
        }

        // Just in case
        $cat_array = array_unique($cat_array);

        if (count($cat_array) == 0) {
            return ee()->TMPL->no_results();
        }

        /** ----------------------------------
        /**  Build category string
        /** ----------------------------------*/
        $cats = '';

        foreach ($cat_array as $cat_id) {
            if ($cat_id != '') {
                $cats .= $cat_id . '|';
            }
        }
        $cats = substr($cats, 0, -1);

        /** ----------------------------------
        /**  Manually set parameters
        /** ----------------------------------*/
        unset(ee()->TMPL->tagparams['entry_id']);
        unset(ee()->TMPL->tagparams['url_title']);
        ee()->TMPL->tagparams['category'] = $cats;
        ee()->TMPL->tagparams['dynamic'] = 'off';
        ee()->TMPL->tagparams['not_entry_id'] = $current_entry; // Exclude the current entry

        // Set user submitted parameters

        $params = array('channel', 'username', 'status', 'orderby', 'sort');

        foreach ($params as $val) {
            if (ee()->TMPL->fetch_param($val) != false) {
                ee()->TMPL->tagparams[$val] = ee()->TMPL->fetch_param($val);
            }
        }

        if (! is_numeric(ee()->TMPL->fetch_param('limit'))) {
            ee()->TMPL->tagparams['limit'] = 10;
        }

        /** ----------------------------------
        /**  Run the channel parser
        /** ----------------------------------*/
        $this->initialize();
        $this->entry_id = '';
        $qstring = '';

        if (ee()->TMPL->fetch_param('custom_fields') != 'yes') {
            $this->enable['custom_fields'] = false;
        }

        if ($this->enable['custom_fields']) {
            $this->fetch_custom_channel_fields();
        }

        if (strpos(ee()->TMPL->tagdata, '{categories') === false) {
            $this->enable['categories'] = false;
        }

        $this->build_sql_query();

        if ($this->sql == '') {
            return ee()->TMPL->no_results();
        }

        $this->query = ee()->db->query($this->sql);

        if (ee()->TMPL->fetch_param('member_data') !== false && ee()->TMPL->fetch_param('member_data') == 'yes') {
            $this->fetch_custom_member_fields();
        }

        if ($this->enable['categories'] == true) {
            $this->fetch_categories();
        }

        $this->parse_channel_entries();

        return $this->return_data;
    }

    /**
      *  Fetch Disable Parameter
      */
    public function _fetch_disable_param()
    {
        $this->enable = array(
            'categories' => true,
            'category_fields' => true,
            'custom_fields' => true,
            'member_data' => true,
            'pagination' => true,
            'relationships' => true,
            'relationship_custom_fields' => true,
            'relationship_categories' => true,
        );

        if ($disable = ee()->TMPL->fetch_param('disable')) {
            if (strpos($disable, '|') !== false) {
                foreach (explode("|", $disable) as $val) {
                    if (isset($this->enable[$val])) {
                        $this->enable[$val] = false;
                    }
                }
            } elseif (isset($this->enable[$disable])) {
                $this->enable[$disable] = false;
            }
        }
    }

    /**
      *  Channel Calendar
      */
    public function calendar()
    {
        // -------------------------------------------
        // 'channel_module_calendar_start' hook.
        //  - Rewrite the displaying of the calendar tag
        //
        if (ee()->extensions->active_hook('channel_module_calendar_start') === true) {
            $edata = ee()->extensions->call('channel_module_calendar_start');
            if (ee()->extensions->end_script === true) {
                return $edata;
            }
        }
        //
        // -------------------------------------------

        if (! class_exists('Channel_calendar')) {
            require PATH_ADDONS . 'channel/mod.channel_calendar.php';
        }

        $WC = new Channel_calendar();

        return $WC->calendar();
    }

    /**
      *  Smiley pop up
      *
      * Used by the SAEF
      */
    public function smiley_pop()
    {
        if (ee()->session->userdata('member_id') == 0) {
            return ee()->output->fatal_error(ee()->lang->line('must_be_logged_in'));
        }

        $class_path = PATH_ADDONS . 'emoticon/emoticons.php';

        if (! is_file($class_path) or ! @include_once($class_path)) {
            return ee()->output->fatal_error('Unable to locate the smiley images');
        }

        if (! is_array($smileys)) {
            return;
        }

        $path = ee()->config->slash_item('emoticon_url');

        ob_start(); ?>
        <script type="text/javascript">
        <!--
        function add_smiley(smiley)
        {
            var el = opener.document.getElementById('submit_post').body;

            if ('selectionStart' in el) {
                newStart = el.selectionStart + smiley.length;

                el.value = el.value.substr(0, el.selectionStart) +
                                smiley +
                                el.value.substr(el.selectionEnd, el.value.length);
                el.setSelectionRange(newStart, newStart);
            }
            else if (opener.document.selection) {
                opener.document.selection.createRange().text = smiley;
            }
            else {
                el.value += " " + smiley + " ";
            }

            el.focus();
            window.close();
        }
        //-->
        </script>

        <?php

        $javascript = ob_get_contents();
        ob_end_clean();
        $r = $javascript;

        $i = 1;

        $dups = array();

        foreach ($smileys as $key => $val) {
            if ($i == 1 and substr($r, -5) != "<tr>\n") {
                $r .= "<tr>\n";
            }

            if (in_array($smileys[$key]['0'], $dups)) {
                continue;
            }

            $r .= "<td class='tableCellOne' align='center'><a href=\"#\" onclick=\"return add_smiley('" . $key . "');\"><img src=\"" . $path . $smileys[$key]['0'] . "\" width=\"" . $smileys[$key]['1'] . "\" height=\"" . $smileys[$key]['2'] . "\" alt=\"" . $smileys[$key]['3'] . "\" border=\"0\" /></a></td>\n";

            $dups[] = $smileys[$key]['0'];

            if ($i == 10) {
                $r .= "</tr>\n";

                $i = 1;
            } else {
                $i++;
            }
        }

        $r = rtrim($r);

        if (substr($r, -5) != "</tr>") {
            $r .= "</tr>\n";
        }

        $out = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
            . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{lang}" lang="{lang}">'
            . '<head>'
            . '<meta http-equiv="content-type" content="text/html; charset={charset}" />'
            . '<title>Smileys</title>'
            . '</head><body>';

        $out .= '<div id="content">'
            . '<div  class="tableBorderTopLeft">'
            . '<table cellpadding="3" cellspacing="0" border="0" style="width:100%;" class="tableBG">';
        $out .= $r;
        $out .= '</table></div></div></body></html>';

        print_r($out);
        exit;
    }

    public function form()
    {
        ee()->load->library('channel_form/channel_form_lib');

        if (! empty(ee()->TMPL)) {
            try {
                return ee()->channel_form_lib->entry_form();
            } catch (Channel_form_exception $e) {
                return $e->show_user_error();
            }
        }

        return '';
    }

    /**
     * submit_entry
     *
     * @return  void
     */
    public function submit_entry()
    {
        //exit if not called as an action
        if (REQ !== 'ACTION') {
            return '';
        }

        ee()->load->library('channel_form/channel_form_lib');

        try {
            ee()->channel_form_lib->submit_entry();
        } catch (Channel_form_exception $e) {
            return $e->show_user_error();
        }
    }

    /**
     * combo_loader
     *
     * @return  void
     */
    public function combo_loader()
    {
        if (ee()->input->get('type') == 'css') {
            $package = strtolower(ee()->input->get('package'));
            $file = ee()->input->get_post('file');
            $path = PATH_THIRD . $package . '/';

            if (file_exists($path . 'css/' . $file . '.css')) {
                ee()->output->out_type = 'cp_asset';
                ee()->output->enable_profiler(false);

                ee()->output->send_cache_headers(filemtime($path), 5184000, $path);

                @header('Content-type: text/css');

                ee()->output->set_output(file_get_contents($path . 'css/' . $file . '.css'));

                if (ee()->config->item('send_headers') == 'y') {
                    @header('Content-Length: ' . strlen(ee()->output->final_output));
                }
            }

            return;
        }

        ee()->load->library('channel_form/channel_form_lib');
        ee()->load->library('channel_form/channel_form_javascript');

        return ee()->channel_form_javascript->combo_load();
    }

    private function generateCategoryFieldSQL($group_ids = '')
    {
        if ($this->enable['category_fields'] !== true) {
            return array('', '');
        }

        $sql = "SELECT field_id, field_name FROM exp_category_fields WHERE site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "')";

        if (! empty($group_ids)) {
            if (! is_array($group_ids)) {
                $group_ids = array_unique(array_filter(explode('|', $group_ids)));
            }
            $sql .= " AND group_id IN ('" . implode("','", $group_ids) . "')";
        }

        $query = ee()->db->query($sql);

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $this->catfields[] = array('field_name' => $row['field_name'], 'field_id' => $row['field_id']);
            }
        }

        $this->cacheCategoryFieldModels();

        $field_sqla = ", cg.field_html_formatting, fd.* ";
        $field_sqlb = " LEFT JOIN exp_category_field_data AS fd ON fd.cat_id = c.cat_id
                        LEFT JOIN exp_category_groups AS cg ON cg.group_id = c.group_id ";

        foreach ($this->cat_field_models as $cat_field) {
            if ($cat_field->legacy_field_data) {
                continue;
            }

            $table = "exp_category_field_data_field_{$cat_field->field_id}";

            foreach ($cat_field->getColumnNames() as $column) {
                $field_sqla .= ", {$table}.{$column}";
            }

            $field_sqlb .= "LEFT JOIN {$table} ON {$table}.cat_id = c.cat_id ";
        }

        return array($field_sqla, $field_sqlb);
    }

    public function live_preview()
    {
        $entry_id = ee()->input->get_post('entry_id');
        $channel_id = ee()->input->get_post('channel_id');
        $return = ee()->input->get('return') ? base64_decode(rawurldecode(ee()->input->get('return'))) : null;
        $allowedOrigin = null;

        $allowedOrigin = base64_decode(rawurldecode(ee('Request')->get('from')));
        if (empty($allowedOrigin)) {
            if (!empty($return)) {
                $allowedOrigin = substr($return, 0, strpos($return, '/', 8));
            }
            if (empty($allowedOrigin)) {
                $configured_cp_url = explode('//', ee()->config->item('cp_url'));
                $configured_cp_domain = explode('/', $configured_cp_url[1]);
                $allowedOrigin = strtolower($configured_cp_domain[0]);
                if (strpos('http', $allowedOrigin) === false) {
                    $allowedOrigin = (ee('Request')->isEncrypted() ? 'https://' : 'http://') . $allowedOrigin;
                }
            }
        }

        $allAllowedOrigins = [];
        $configuredUrls = ee('Model')->get('Config')
            ->filter('key', 'IN', ['base_url', 'site_url', 'cp_url'])
            ->all()
            ->pluck('parsed_value');
        $extraDomains = ee('Config')->getFile()->get('allowed_preview_domains');
        if (!empty($extraDomains)) {
            if (!is_array($extraDomains)) {
                $extraDomains = explode(',', $extraDomains);
            }
            $configuredUrls = array_merge($configuredUrls, $extraDomains);
        }

        foreach ($configuredUrls as $configuredUrl) {
            $configuredUrl = trim($configuredUrl);
            foreach (['https://', 'http://', '//'] as $protocol) {
                if (strpos($configuredUrl, $protocol) === 0) {
                    $len = strlen($protocol);
                    $domain = substr($configuredUrl, $len, (strpos($configuredUrl, '/', $len) - $len));
                } else {
                    $domain = $configuredUrl;
                }
                $allAllowedOrigins[] = 'https://' . $domain;
                $allAllowedOrigins[] = 'http://' . $domain;
            }
        }
        $allAllowedOrigins = array_unique($allAllowedOrigins);

        @header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        @header('Access-Control-Allow-Methods: POST, OPTIONS');
        @header('Access-Control-Max-Age: 3600');
        if (array_key_exists('HTTP_ACCESS_CONTROL_REQUEST_HEADERS', $_SERVER)) {
            @header('Access-Control-Allow-Headers: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
        } else {
            @header('Access-Control-Allow-Headers: *');
        }

        if (ee('Request')->method() == 'OPTIONS') {
            exit();
        }

        if (!in_array($allowedOrigin, $allAllowedOrigins)) {
            ee()->lang->load('content');

            return ee()->output->show_user_error('off', lang('preview_domain_error_instructions'), lang('preview_cannot_display'));
        }

        $prefer_system_preview = ee()->input->get('prefer_system_preview') == 'y';

        return ee('LivePreview')->preview($channel_id, $entry_id, $return, $prefer_system_preview);
    }
}
// END CLASS

// EOF
