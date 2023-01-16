<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// include base class
if (! class_exists('Pro_search_base')) {
    require_once(PATH_ADDONS . 'pro_search/base.pro_search.php');
}

/**
 * Pro Search Module class
 */
class Pro_search
{
    // Use the base trait
    use Pro_search_base;

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Shortcut to Pro_search_params object
     *
     * @access      private
     * @var         object
     */
    private $params;

    /**
     * Shortcut to Pro_search_settings object
     *
     * @access      private
     * @var         object
     */
    private $settings;

    /**
     * Shortcut used
     *
     * @access      private
     * @var         mixed
     */
    private $shortcut;

    /**
     * Latest Log ID
     *
     * @access      private
     * @var         mixed
     */
    private $log_id;

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    public function __construct()
    {
        // Initialize base data for addon
        $this->initializeBaseData();

        ee()->load->library('Pro_search_params');

        $this->params = & ee()->pro_search_params;
        $this->settings = & ee()->pro_search_settings;
    }

    /**
     * Filters
     *
     * @access      public
     * @return      string
     */
    public function filters()
    {
        // --------------------------------------
        // Load up language file
        // --------------------------------------

        ee()->lang->loadfile($this->package);

        // --------------------------------------
        // Read parameters
        // --------------------------------------

        $this->params->set();

        // --------------------------------------
        // Overwrite with shortcut?
        // --------------------------------------

        $this->_get_shortcut();

        // --------------------------------------
        // Prep params for template variables
        // --------------------------------------

        $vars = array();

        foreach ($this->params->get() as $key => $val) {
            $vars[$this->settings->prefix . $key . ':raw'] = $val;
            $vars[$this->settings->prefix . $key] = pro_format($val);
        }

        // --------------------------------------
        // Add shortcut data to vars
        // --------------------------------------

        if ($this->shortcut) {
            foreach (ee()->pro_search_shortcut_model->get_template_attrs() as $key) {
                $vars[$this->settings->prefix . $key] = $this->shortcut[$key];
            }
        }

        // --------------------------------------
        // Get search collections for this site
        // --------------------------------------

        // Check to see whether we actually need to get collections
        $get_collections = false;
        $collections = array();
        $active_collections = array();

        // Get them only if the var pair exists
        foreach (pro_array_get_prefixed(ee()->TMPL->var_pair, 'collections') as $val) {
            $this->_log('Collections variable pair found');

            $get_collections = true;

            // Parameters in var pair? Remember them
            if ($val !== false) {
                $get_collections = $val;
            }
        }

        // Get the collections if necessary
        if ($get_collections !== false) {
            $this->_log('Getting search collection details');

            // Init by site ids
            $collections = ee()->pro_search_collection_model->get_by_site($this->params->site_ids());

            // Handle show="foo"
            if (isset($get_collections['show'])) {
                $collections = ee()->pro_search_collection_model->get_by_param($get_collections['show'], $collections);
            }

            // Handle lang="bar"
            if (isset($get_collections['lang'])) {
                list($val, $in) = $this->params->explode($get_collections['lang']);

                $collections = ee()->pro_search_collection_model->get_by_language($val, $in, $collections);
            }

            // Reset keys
            $collections = array_values($collections);

            // --------------------------------------
            // Define collection meta data
            // --------------------------------------

            $meta = array(
                'collection_count'  => 0,
                'total_collections' => count($collections)
            );

            // --------------------------------------
            // Get array of active collections
            // --------------------------------------

            $col = $this->params->get('collection');

            if (! empty($col)) {
                if (is_string($col)) {
                    list($active_collections, $in) = $this->params->explode($col);
                } elseif (is_array($col)) {
                    $active_collections = $col;
                }
            }

            // --------------------------------------
            // Loop thru collections, modify rows
            // --------------------------------------

            // Numeric collections?
            $attr = pro_array_is_numeric($active_collections) ? 'collection_id' : 'collection_name';

            foreach ($collections as &$row) {
                unset($row['site_id'], $row['settings']);

                // Make strings html-safe
                $row = array_map('htmlspecialchars', $row);

                // Change language to prefixed
                $row['collection_language'] = $row['language'];

                // Is collection selected?
                $row['collection_is_active'] = in_array($row[$attr], $active_collections) ? 'y' : '';

                // Increment collection count
                $meta['collection_count']++;

                // Forget some
                unset($row['language']);

                // Merge meta with row
                $row = array_merge($row, $meta);
            }
        }

        // --------------------------------------
        // Add collections to vars array
        // --------------------------------------

        $vars['collections'] = $collections;

        // --------------------------------------
        // Handle error messages
        // --------------------------------------

        // Main error message
        $vars['error_message'] = ee()->session->flashdata('error_message');

        // Errors per field
        if (($errors = ee()->session->flashdata('errors')) && is_array($errors)) {
            foreach ($errors as $field) {
                $vars[$this->settings->prefix . $field . '_missing'] = true;
            }
        }

        // --------------------------------------
        // Parse it now
        // --------------------------------------

        $tagdata = ee()->TMPL->parse_variables_row(ee()->TMPL->tagdata, $vars);
        $tagdata = $this->_post_parse($tagdata);

        // --------------------------------------
        // Return output
        // --------------------------------------

        return $tagdata;
    }

    /**
     * Call $this->filters and wrap a form around it
     *
     * @access     public
     * @return     string
     */
    public function form()
    {
        // --------------------------------------
        // Initiate data arrays for form creation
        // --------------------------------------

        $data = $params = $form_params = array();

        // --------------------------------------
        // Collect form params
        // --------------------------------------

        foreach (array_keys(pro_array_get_prefixed(ee()->TMPL->tagparams, 'form_', true)) as $key) {
            $form_params[$key] = $this->_extract_tagparam('form_' . $key);
        }

        // --------------------------------------
        // Read parameters
        // --------------------------------------

        $this->params->set();

        // --------------------------------------
        // Set parameters to shortcut?
        // --------------------------------------

        if (
            ($shortcut = $this->_get_shortcut()) &&
            ($this->_extract_tagparam('remember_shortcut') == 'yes')
        ) {
            $params = array_merge($params, $shortcut['parameters']);
        }

        // --------------------------------------
        // Are we remembering parameters?
        // --------------------------------------

        if ($remember = ee()->TMPL->fetch_param('remember')) {
            foreach (explode('|', $remember) as $key) {
                $params[$key] = $this->params->get($key);
            }
        }

        // --------------------------------------
        // Overwrite-able params
        // --------------------------------------

        foreach (array('result_page') as $key) {
            $params[$key] = ee()->TMPL->fetch_param($key);
        }

        // --------------------------------------
        // One-off params
        // --------------------------------------

        foreach (array('required', 'force_protocol') as $key) {
            $params[$key] = $this->_extract_tagparam($key);
        }

        // --------------------------------------
        // Encode and put parameters in hidden form field
        // --------------------------------------

        if ($params = array_filter($params, 'pro_not_empty')) {
            $data['hidden_fields']['params'] = pro_search_encode($params);
        }

        // --------------------------------------
        // Define the action ID
        // --------------------------------------

        $data['hidden_fields']['ACT'] = ee()->functions->fetch_action_id($this->class_name, 'catch_search');

        // --------------------------------------
        // Get opening form tag
        // --------------------------------------

        $form = ee()->functions->form_declaration($data);

        // --------------------------------------
        // Add form params to it
        // --------------------------------------

        if ($form_params) {
            $form = str_replace('<form ', '<form ' . pro_param_string($form_params) . ' ', $form);
        }

        // --------------------------------------
        // Return output
        // --------------------------------------

        return $form . $this->filters() . '</form>';
    }

    // --------------------------------------------------------------------

    /**
     * Save Search form
     *
     * @access     public
     * @return     string
     */
    public function save()
    {
        // --------------------------------------
        // Check permissions
        // --------------------------------------

        if (! $this->_can_manage_shortcuts()) {
            $this->_log('Member not allowed to save shortcuts, returning empty string');

            return;
        }

        // --------------------------------------
        // Put parameters in params value
        // --------------------------------------

        $this->params->set();

        // --------------------------------------
        // If no valid query is given, bail out - no use to saving it
        // --------------------------------------

        if (! $this->params->valid_query()) {
            $this->_log('Invalid query given, returning no results');

            return ee()->TMPL->no_results();
        }

        // --------------------------------------
        // Initiate data array for form creation
        // --------------------------------------

        $data = array();

        // Define hidden fields
        $data['hidden_fields']['ACT'] = ee()->functions->fetch_action_id($this->class_name, 'save_search');
        $data['hidden_fields']['params'] = pro_search_encode($this->params->get());
        $data['hidden_fields']['group_id'] = ee()->TMPL->fetch_param('group_id');

        // --------------------------------------
        // Get opening form tag
        // --------------------------------------

        $form = ee()->functions->form_declaration($data);

        // --------------------------------------
        // Collect form params
        // --------------------------------------

        $form_params = pro_array_get_prefixed(ee()->TMPL->tagparams, 'form_', true);

        // --------------------------------------
        // Add form params to it
        // --------------------------------------

        if ($form_params) {
            $form = str_replace('<form ', '<form ' . pro_param_string($form_params) . ' ', $form);
        }

        // --------------------------------------
        // Return output
        // --------------------------------------

        return $form . ee()->TMPL->tagdata . '</form>';
    }

    /**
     * Save a search
     */
    public function save_search()
    {
        // --------------------------------------
        // Check permissions
        // --------------------------------------

        if (! $this->_can_manage_shortcuts()) {
            show_error('not_authorized');
        }

        // --------------------------------------
        // Check security
        // --------------------------------------

        if (
            method_exists(ee()->security, 'restore_xid') &&
            version_compare(APP_VER, '2.8.0', '<')
        ) {
            ee()->security->restore_xid();
        }

        // --------------------------------------
        // Get Group ID from post
        // --------------------------------------

        if (! ($group_id = ee()->input->post('group_id'))) {
            // @todo: fallback to default group?
        }

        // --------------------------------------
        // Compose data to insert
        // --------------------------------------

        $data = array(
            'site_id'        => $this->site_id,
            'group_id'       => $group_id,
            'shortcut_name'  => ee()->input->post('shortcut_name'),
            'shortcut_label' => ee()->input->post('shortcut_label'),
            'parameters'     => ee()->input->post('params')
        );

        // --------------------------------------
        // Validate the shortcut data
        // --------------------------------------

        if (($validated = ee()->pro_search_shortcut_model->validate($data)) === false) {
            // Load the language file so we can show an error
            ee()->lang->loadfile($this->package);

            // Pass errors through lang()
            $errors = array_map('lang', ee()->pro_search_shortcut_model->errors());

            // and show 'em
            show_error($errors);
        }

        // --------------------------------------
        // And insert it
        // --------------------------------------

        $shortcut_id = ee()->pro_search_shortcut_model->insert($validated);

        // --------------------------------------
        // Return to previous
        // --------------------------------------

        $this->_go_back(null);
    }

    /**
     * Display shorts
     *
     * @access     public
     * @return     string
     */
    public function shortcuts()
    {
        // --------------------------------------
        // Fields to select, reused for ordering
        // --------------------------------------

        $select = array('shortcut_id', 'shortcut_name', 'shortcut_label', 'parameters', 'sort_order');

        // --------------------------------------
        // Start query
        // --------------------------------------

        ee()->db->select($select)->from(ee()->pro_search_shortcut_model->table());

        // --------------------------------------
        // Filter by shortcut ID
        // --------------------------------------

        if ($shortcut_id = ee()->TMPL->fetch_param('shortcut_id')) {
            list($items, $in) = $this->params->explode($shortcut_id);

            ee()->db->{($in ? 'where_in' : 'where_not_in')}('shortcut_id', $items);
        }

        // --------------------------------------
        // Filter by site
        // --------------------------------------

        if ($sites = array_values(ee()->TMPL->site_ids)) {
            ee()->db->where_in('site_id', $sites);
        }

        // --------------------------------------
        // Filter by group ID
        // --------------------------------------

        if ($group_id = ee()->TMPL->fetch_param('group_id')) {
            list($items, $in) = $this->params->explode($group_id);

            ee()->db->{($in ? 'where_in' : 'where_not_in')}('group_id', $items);
        }

        // --------------------------------------
        // Filter by short name
        // --------------------------------------

        if ($shortcut_name = ee()->TMPL->fetch_param('shortcut_name')) {
            list($items, $in) = $this->params->explode($shortcut_name);

            ee()->db->{($in ? 'where_in' : 'where_not_in')}('shortcut_name', $items);
        }

        // --------------------------------------
        // Order by
        // --------------------------------------

        if (
            ($orderby = ee()->TMPL->fetch_param('orderby', 'sort_order')) &&
            in_array($orderby, $select)
        ) {
            $sort = strtolower(ee()->TMPL->fetch_param('sort', 'asc'));

            if (! in_array($sort, array('asc', 'desc'))) {
                $sort = 'asc';
            }

            ee()->db->order_by($orderby, $sort);
        }

        // --------------------------------------
        // Limit, offset
        // --------------------------------------

        if (($limit = ee()->TMPL->fetch_param('limit', 100)) && is_numeric($limit)) {
            $offset = (int) ee()->TMPL->fetch_param('offset', 0);

            ee()->db->limit($limit, $offset);
        }

        // --------------------------------------
        // Get the rows
        // --------------------------------------

        $rows = ee()->db->get()->result_array();

        // --------------------------------------
        // Nothing? No results
        // --------------------------------------

        if (empty($rows)) {
            $this->_log('No shortcuts found');

            return ee()->TMPL->no_results();
        }

        // --------------------------------------
        // Are there {shortcut_url result_page=""} vars?
        // --------------------------------------

        $urls = array();

        foreach (pro_array_get_prefixed(ee()->TMPL->var_single, 'shortcut_url') as $var) {
            $urls[$var] = array();

            // Read out the parameters so we can override them
            if (preg_match_all("/([\w\-:]+)\s*=\s*('|\")(.+?)\\2/", $var, $matches)) {
                foreach ($matches[0] as $i => $val) {
                    $urls[$var][$matches[1][$i]] = $matches[3][$i];
                }
            }
        }

        // --------------------------------------
        // Modify the rows
        // --------------------------------------

        foreach ($rows as &$row) {
            $params = pro_search_decode($row['parameters'], false);

            foreach ($urls as $key => $val) {
                $row[$key] = $this->_create_url(array_merge($params, $val));
            }

            // Don't need the raw json
            unset($row['parameters']);
        }

        // --------------------------------------
        // Parse it, dawg
        // --------------------------------------

        $tagdata = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $rows);

        return $tagdata;
    }

    // --------------------------------------------------------------------

    /**
     * Show search results
     *
     * @access      public
     * @return      string
     */
    public function results()
    {
        // --------------------------------------
        // Avoid no_results conflict
        // --------------------------------------

        $this->_prep_no_results();

        // --------------------------------------
        // Set the parameters
        // --------------------------------------

        $this->params->set();

        // --------------------------------------
        // Get the latest Log ID
        // --------------------------------------

        $this->log_id = ee()->session->flashdata(ee()->pro_search_log_model->key);

        // --------------------------------------
        // Are we using a shortcut?
        // --------------------------------------

        if (! $this->_get_shortcut() && $this->_extract_tagparam('require_shortcut') == 'yes') {
            $this->_log('Shortcut required but not given');

            return $this->_no_results();
        }

        // --------------------------------------
        // If query parameter is set but empty or invalid,
        // show no_results and abort
        // --------------------------------------

        if (! $this->params->query_given() && $this->_extract_tagparam('require_query') == 'yes') {
            $this->_log('Query required but not given');

            return $this->_no_results();
        }

        // Query given but not valid == no results
        if ($this->params->query_given() && ! $this->params->valid_query()) {
            $this->_log('Returning no results due to invalid query');

            return $this->_no_results();
        }

        // --------------------------------------
        // Merge tagparams into all params, set default params
        // --------------------------------------

        $this->params->combine();
        $this->params->set_defaults();

        // -------------------------------------
        // 'pro_search_pre_search' hook.
        //  - Do something just before the search is executed
        // -------------------------------------

        if (ee()->extensions->active_hook('pro_search_pre_search') === true) {
            $params = $this->params->get();
            $params = ee()->extensions->call('pro_search_pre_search', $params);
            if (ee()->extensions->end_script === true) {
                return ee()->TMPL->tagdata;
            }
            $this->params->overwrite($params);
        }

        // --------------------------------------
        // Optionally log search
        // --------------------------------------

        if (
            $this->params->get('log_search') == 'yes' &&
            ! preg_match('#/P\d+/?$#', ee()->uri->uri_string())
        ) {
            // Will set log_id to NULL if not logged, purposely
            $this->log_id = $this->_log_search($this->params->get());

            // Also set cache
            pro_set_cache($this->package, ee()->pro_search_log_model->key, $this->log_id);
        }

        // --------------------------------------
        // Check orderby_sort=""
        // --------------------------------------

        $obs = $this->params->get('orderby_sort');

        if ($obs && strpos($obs, '|') !== false) {
            $obs = explode('|', $obs, 2);
            $this->params->set('orderby', $obs[0]);
            $this->params->set('sort', $obs[1]);
        }

        unset($obs);

        // --------------------------------------
        // Load and apply all available filters
        // --------------------------------------

        ee()->load->library('Pro_search_fields');
        ee()->load->library('Pro_search_filters');

        ee()->pro_search_filters->filter();

        // --------------------------------------
        // What entry IDs do we have as a result?
        // --------------------------------------

        $entry_ids = ee()->pro_search_filters->entry_ids();

        // --------------------------------------
        // If entry_ids is an array, some filters fired
        // --------------------------------------

        if (is_array($entry_ids)) {
            // Empty array -> No results
            if (empty($entry_ids)) {
                $this->_log('Filters found no matches, returning no results');

                return $this->_no_results();
            } else {
                // Yay! We have results! Now, which param should we populate?
                if ($fixed_order_param = $this->params->get('fixed_order')) {
                    $entry_ids = $this->params->merge($entry_ids, $fixed_order_param);
                }

                if ($entry_id_param = $this->params->get('entry_id')) {
                    $entry_ids = $this->params->merge($entry_ids, $entry_id_param);
                }

                if (empty($entry_ids)) {
                    $this->_log('No results after entry_id/fixed_order');

                    return $this->_no_results();
                }

                // Set the IDs again
                ee()->pro_search_filters->set_entry_ids($entry_ids);

                // Which param are we setting?
                $param = (ee()->pro_search_filters->fixed_order() || $fixed_order_param)
                    ? 'fixed_order'
                    : 'entry_id';

                // Set it
                $this->params->set($param, implode('|', $entry_ids));

                // Log it
                $this->_log("Setting {$param} param");
            }
        } elseif ($entry_ids = ee()->pro_search_filters->exclude()) {
            $this->_log('Excluding entries only');

            // We are excluding by default
            $in = false;

            // Check existing entry ID parameter
            if ($entry_id_param = $this->params->get('entry_id')) {
                // Overwrite $in var and get IDs from parameter
                list($ids, $in) = $this->params->explode($entry_id_param);

                // If it already has an inclusive list, subtract the $exclude ids
                // otherwise, add the $exclude ids
                $method = $in ? 'array_diff' : 'array_merge';

                $entry_ids = $method($ids, $entry_ids);

                // Empty results?
                if (empty($entry_ids)) {
                    $this->_log('No results after handling the exising entry_id parameter');

                    return $this->_no_results();
                }
            }

            $this->params->set('entry_id', $this->params->implode($entry_ids, $in));
        }

        // -------------------------------------
        // 'pro_search_post_search' hook.
        //  - Do something just after the search is executed
        // -------------------------------------

        if (ee()->extensions->active_hook('pro_search_post_search') === true) {
            $params = $this->params->get();
            $params = ee()->extensions->call('pro_search_post_search', $params);
            if (ee()->extensions->end_script === true) {
                return ee()->TMPL->tagdata;
            }
            $this->params->overwrite($params);
        }

        // --------------------------------------
        // Set misc tagparams
        // --------------------------------------

        $this->params->apply();

        // --------------------------------------
        // Log the set parameters
        // --------------------------------------

        $this->_log('Parameters set: ' . pro_param_string(array_merge(
            ee()->TMPL->tagparams,
            ee()->TMPL->search_fields
        )));

        // --------------------------------------
        // Pre-apply parameters as vars
        // --------------------------------------

        $this->_log('Pre-applying search vars to tagdata');

        ee()->TMPL->tagdata = ee()->TMPL->parse_variables_row(
            ee()->TMPL->tagdata,
            $this->params->get_vars($this->settings->prefix)
        );

        // --------------------------------------
        // Set parameter so extension kicks in
        // --------------------------------------

        $this->params->apply('pro_search', 'yes');

        // --------------------------------------
        // Remember the shortcut, if there
        // --------------------------------------

        pro_set_cache($this->package, 'shortcut', $this->shortcut);

        // --------------------------------------
        // Initiate tagdata
        // --------------------------------------

        $tagdata = false;

        // -------------------------------------
        // 'pro_search_channel_entries' hook.
        //  - Call your own channel:entries, fall back to default
        // -------------------------------------

        if (ee()->extensions->active_hook('pro_search_channel_entries') === true) {
            $tagdata = ee()->extensions->call('pro_search_channel_entries');
        }

        // --------------------------------------
        // If not set by the extension, use native channel:entries
        // --------------------------------------

        if ($tagdata === false) {
            $tagdata = $this->_channel_entries();
        }

        // --------------------------------------
        // Don't post_parse no_results
        // --------------------------------------

        $tagdata = ($tagdata == ee()->TMPL->no_results)
            ? $this->_no_results($tagdata)
            : $this->_post_parse($tagdata);

        return $tagdata;
    }

    // --------------------------------------------------------------------

    /**
     * Display search collections
     *
     * @access      public
     * @return      string
     */
    public function collections()
    {
        // --------------------------------------
        // Check site
        // --------------------------------------

        $site_ids = ee()->TMPL->site_ids;

        // --------------------------------------
        // Get collections
        // --------------------------------------

        $rows = array_values(ee()->pro_search_collection_model->get_by_site($site_ids));

        // --------------------------------------
        // Filter by collection="" param
        // --------------------------------------

        if ($val = ee()->TMPL->fetch_param('collection')) {
            $rows = ee()->pro_search_collection_model->get_by_param($val, $rows);
        }

        // --------------------------------------
        // Filter by lang="" param
        // --------------------------------------

        if ($val = ee()->TMPL->fetch_param('lang')) {
            list($val, $in) = $this->params->explode($val);
            $rows = ee()->pro_search_collection_model->get_by_language($val, $in, $rows);
        }

        // --------------------------------------
        // Parse template
        // --------------------------------------

        if ($rows = array_values($rows)) {
            foreach ($rows as &$row) {
                // Correct key
                $row['collection_language'] = $row['language'];

                // Remove these
                unset($row['language'], $row['settings']);

                // Escape
                $row = array_map('htmlspecialchars', $row);
            }

            return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $rows);
        } else {
            return ee()->TMPL->no_results();
        }
    }

    // --------------------------------------------------------------------

    /**
     * Display given keywords (shortcut for param tag)
     *
     * @access      public
     * @return      string
     */
    public function keywords()
    {
        return $this->param('keywords');
    }

    /**
     * Display any parameter outside of tags
     *
     * @access      public
     * @return      string
     */
    public function param($which = false)
    {
        // --------------------------------------
        // Set the parameters
        // --------------------------------------

        $this->params->set();

        // --------------------------------------
        // Do we have a parameter to get?
        // --------------------------------------

        if (! ($which = ee()->TMPL->fetch_param('get', $which))) {
            $this->_log("Parameter {$which} not found");

            return ee()->TMPL->no_results();
        }

        // --------------------------------------
        // What's the parameter value?
        // --------------------------------------

        $it = (string) $this->params->get($which);

        // --------------------------------------
        // Get the format in which to return the value
        // --------------------------------------

        $format = ee()->TMPL->fetch_param('format', 'html');

        // --------------------------------------
        // Check if we need to return the value in a loop
        // --------------------------------------

        if ($as = ee()->TMPL->fetch_param('as')) {
            // Init vars array
            $vars = array();

            // Get values
            list($vals, $in) = $this->params->explode($it);

            // Loop through param values
            foreach ($vals as $val) {
                $vars[] = array($as => pro_format($val, $format));
            }

            $it = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
        } else {
            $it = pro_format($it, $format);
        }

        // Please
        return $it;
    }

    // --------------------------------------------------------------------

    /**
     * Display popular keywords
     *
     * @access      public
     * @return      string
     */
    public function popular()
    {
        // --------------------------------------
        // Filter by site
        // --------------------------------------

        ee()->db->where('site_id', $this->site_id);

        // --------------------------------------
        // Limiting?
        // --------------------------------------

        if (! ($limit = (int) ee()->TMPL->fetch_param('limit'))) {
            $limit = 10;
        }

        ee()->db->limit($limit);

        // --------------------------------------
        // Get terms
        // --------------------------------------

        if ($rows = ee()->pro_search_log_model->get_popular_keywords()) {
            // Get orderby and sort params
            $orderby = ee()->TMPL->fetch_param('orderby', 'search_count');
            $sort = ee()->TMPL->fetch_param('sort', (($orderby == 'search_count') ? 'desc' : 'asc'));

            foreach ($rows as &$row) {
                $kw = $row['keywords'];
                $row['keywords_raw'] = $kw;
                $row['keywords'] = pro_format($kw, 'html');
                $row['keywords_url'] = pro_format($kw, 'url');
                $row['keywords_clean'] = pro_format($kw, 'clean');
                $row['keywords_param'] = pro_format($kw, 'ee-encode');
            }

            // Different orderby?
            switch (ee()->TMPL->fetch_param('orderby')) {
                case 'keywords':
                    usort($rows, 'pro_by_keywords');
                    if ($sort == 'desc') {
                        $rows = array_reverse($rows);
                    }

                    break;

                case 'random':
                    shuffle($rows);

                    break;

                default:
                    if ($sort == 'asc') {
                        $rows = array_reverse($rows);
                    }
            }
        }

        return $rows
            ? ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $rows)
            : ee()->TMPL->no_results();
    }

    // --------------------------------------------------------------------

    /**
     * Generate Open Search URL
     *
     * @access      public
     * @return      string
     */
    public function url()
    {
        // --------------------------------------
        // Are we starting from scratch?
        // --------------------------------------

        $reset = ($this->_extract_tagparam('reset') == 'yes');

        // --------------------------------------
        // Get optional query_string param
        // --------------------------------------

        $qs = $this->_extract_tagparam('query_string');

        // --------------------------------------
        // Set internal params if not resetting
        // --------------------------------------

        if ($reset) {
            $this->params->reset();
        } else {
            // Set the params
            $this->params->set();

            // Set query string if given
            if ($qs) {
                $this->params->set($qs);
            }

            // And shortcut
            $this->_get_shortcut();
        }

        // --------------------------------------
        // Loop through tagparams and add them to the query string
        // --------------------------------------

        // init toggle array
        $toggle = array();
        $ignore = array('query', 'encode', 'cache', 'refresh', 'parse', 'shortcut');

        // Override with tagparams
        foreach (ee()->TMPL->tagparams as $key => $val) {
            if (in_array($key, $ignore) || ! is_string($val)) {
                continue;
            }

            // Decode value
            $val = pro_format($val, 'ee-decode');

            // Check for toggle values
            if (substr($key, 0, 7) == 'toggle:') {
                $toggle[substr($key, 7)] = $val;

                continue;
            }

            // Add to query string
            $this->params->set($key, $val);
        }

        // --------------------------------------
        // Handle toggle values
        // --------------------------------------

        foreach ($toggle as $key => $val) {
            if ($current_val = $this->params->get($key)) {
                // Read current value
                list($values, $in) = $this->params->explode($current_val);

                // check if value is there
                if (($i = array_search($val, $values)) === false) {
                    // Not there, add it
                    $values[] = $val;
                } else {
                    // Is there, remove it
                    unset($values[$i]);
                }

                $val = $this->params->implode($values, $in);
            }

            // Add the new value to the parameter array (could be NULL)
            $this->params->set($key, $val);
        }

        // --------------------------------------
        // Clean up the parameters before making the URL
        // --------------------------------------

        $params = array_filter($this->params->get(), 'pro_not_empty');

        // --------------------------------------
        // Then compose the URL, encoded or not
        // --------------------------------------

        if (ee()->TMPL->fetch_param('encode', 'yes') == 'no') {
            // Build non-encoded URL
            $url = ee()->functions->fetch_site_index()
                 . QUERY_MARKER . 'ACT='
                 . ee()->functions->fetch_action_id($this->package, 'catch_search')
                 . AMP . http_build_query($params, '', AMP);
        } else {
            // Get the result page from the params
            $url = $this->_create_url($params);
        }

        return $url;
    }

    // --------------------------------------------------------------------

    /**
     * Generate suggestions based on keywords given
     *
     * @access      public
     * @return      string
     */
    public function suggestions()
    {
        // --------------------------------------
        // Load Words lib
        // --------------------------------------

        ee()->load->library('Pro_search_words');

        // --------------------------------------
        // use 'if no_suggestions' for no_results
        // --------------------------------------

        $this->_prep_no_results('no_suggestions');

        // --------------------------------------
        // Set internal params
        // --------------------------------------

        $this->params->set();

        // --------------------------------------
        // Get keywords
        // --------------------------------------

        $keywords = $this->params->get('keywords');
        $keywords = ee()->TMPL->fetch_param('keywords', $keywords);
        $keywords = ee()->pro_search_words->clean($keywords);

        // --------------------------------------
        // Get language
        // --------------------------------------

        $lang = $this->params->get('keywords:lang');
        $lang = ee()->TMPL->fetch_param('keywords:lang', $lang);

        // --------------------------------------
        // Get distance
        // --------------------------------------

        $distance = (int) ee()->TMPL->fetch_param('distance', 2);

        // Limit to 1, 2 or 3
        if ($distance < 1) {
            $distance = 1;
        }
        if ($distance > 3) {
            $distance = 3;
        }

        // --------------------------------------
        // Get limit and sites
        // --------------------------------------

        $limit = (int) ee()->TMPL->fetch_param('limit', 5);
        $sites = $this->params->site_ids();

        // --------------------------------------
        // Filter out keywords into the words
        // --------------------------------------

        $keywords = explode(' ', $keywords);
        $keywords = array_filter($keywords, array(ee()->pro_search_words, 'is_valid'));

        $words = $keywords
            ? ee()->pro_search_word_model->get_unknown($keywords, $lang, $sites)
            : array();

        // Only continue if we have words
        if (empty($words)) {
            $this->_log('No valid words to base suggesions on');

            return ee()->TMPL->no_results();
        }

        // --------------------------------------
        // Get method on which to base the suggestions
        // --------------------------------------

        $method = ee()->TMPL->fetch_param('method');

        // --------------------------------------
        // Initiate suggestions
        // --------------------------------------

        $this->_log('Getting suggestions for ' . implode(', ', $words) . ' in ' . $lang);

        // Using soundex?
        if ($method == 'soundex') {
            $suggestions = ee()->pro_search_word_model->get_sounds($words, $lang, $sites, $distance);
            $suggestions = pro_flatten_results($suggestions, 'word');

            // Show random suggestions
            shuffle($suggestions);

            // And limit them
            $suggestions = array_slice($suggestions, 0, $limit);
        } else {
            // Or the default: Levenshtein
            $suggestions = ee()->pro_search_word_model->get_suggestions($words, $lang, $sites, $distance, $limit);
        }

        // --------------------------------------
        // Convert into actual rows
        // --------------------------------------

        $total = count($suggestions);
        $count = 0;
        $rows = array();

        foreach ($suggestions as $word) {
            $rows[] = array(
                'suggestion_count'   => ++$count,
                'total_suggestions'  => $total,
                'suggestion'         => $word,
                'suggestion:upper'   => ee()->pro_multibyte->strtoupper($word),
                'suggestion:ucfirst' => ucfirst($word)
            );
        }

        return $rows
            ? ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $rows)
            : ee()->TMPL->no_results();
    }

    // --------------------------------------------------------------------
    // ACT METHODS
    // --------------------------------------------------------------------

    /**
     * Build collection index
     *
     * @access      public
     * @return      string
     */
    public function build_index()
    {
        // ACT key must be given
        $build_index_act_key = $this->settings->get('build_index_act_key');
        $given_key = ee()->input->get_post('key');

        // Bail out if keys don't match
        if (! ($given_key && $build_index_act_key == $given_key && REQ == 'ACTION')) {
            show_error(ee()->lang->line('not_authorized'));
        }

        // --------------------------------------
        // Backward compat
        // --------------------------------------

        if (
            method_exists(ee()->security, 'restore_xid') &&
            version_compare(APP_VER, '2.8.0', '<')
        ) {
            ee()->security->restore_xid();
        }

        // --------------------------------------
        // Load library
        // --------------------------------------

        ee()->load->library('pro_search_index');

        // --------------------------------------
        // Get IDs from get or post and make sure they're numeric
        // --------------------------------------

        $entry_ids = $this->_get_ids('entry_id');
        $col_ids = $this->_get_ids('collection_id');
        $col_total = count($col_ids);

        // --------------------------------------
        // Check for start and rebuild options
        // --------------------------------------

        $start = ee()->input->get_post('start');
        $rebuild = ee()->input->get_post('rebuild');

        // --------------------------------------
        // Update given entries only
        // --------------------------------------

        if (! $col_total && ! empty($entry_ids)) {
            $response = ee()->pro_search_index->build_by_entry($entry_ids);
        } elseif ($col_total && $start === false) {
            // Update given collections only, not in batches
            // No batch, just build all given collections
            foreach ($col_ids as $col_id) {
                $response = ee()->pro_search_index->build_by_collection($col_id, $entry_ids);

                // Bail out if FALSE is returned
                if (! $response) {
                    break;
                }
            }
        } elseif ($col_total == 1 && is_numeric($start)) {
            // Batch-update given single collection

            // Focus on single collection ID
            $col_id = $col_ids[0];

            // Batch -- ignores entry IDs
            $start = (int) $start;

            // Rebuilding? Delete collection first
            if ($start === 0 && $rebuild == 'yes') {
                ee()->pro_search_index_model->delete($col_id, 'collection_id');
            }

            // Build the batch
            $response = ee()->pro_search_index->build_batch($col_id, $start);

            // Optimize table if we're done
            if ($response === true) {
                ee()->pro_search_index_model->optimize();
            }
        } else {
            // Invalid action
            show_error('Invalid action');
        }

        // --------------------------------------
        // Exit through the gift shop
        // --------------------------------------

        if (is_ajax()) {
            die(json_encode($response));
        }
    }

    /**
     * Check get/post for given key, return numeric values
     */
    private function _get_ids($key)
    {
        // If not given, return empty array
        if (! ($ids = ee()->input->get_post($key))) {
            return array();
        }

        // Make sure the IDs
        if (! is_array($ids)) {
            $ids = preg_split('/\D+/', $ids, 0, PREG_SPLIT_NO_EMPTY);
        }

        // Filter the ids, bail out if we end up empty
        if (! ($ids = array_filter($ids))) {
            return array();
        }

        // Check for numeric IDs only
        if (! pro_array_is_numeric($ids)) {
            show_error('Non-numeric IDs given for ' . $key);
        }

        return $ids;
    }

    // --------------------------------------------------------------------

    /**
     * Catch search form submission
     *
     * @access      public
     * @return      void
     */
    public function catch_search()
    {
        // --------------------------------------
        // Initiate data array; will be encrypted
        // and put in the URI later
        // --------------------------------------

        $data = array();

        if ($params = ee()->input->post('params')) {
            $data = pro_search_decode($params);
        }

        // --------------------------------------
        // Check other data
        // --------------------------------------

        foreach (array_merge($_GET, $_POST) as $key => $val) {
            // Keys to skip
            if (in_array($key, array('ACT', 'XID', 'csrf_token', 'params', 'site_id'))) {
                continue;
            }

            // Add post var to data
            $data[$key] = is_array($val)
                ? implode('|', array_filter($val, 'pro_not_empty'))
                : $val;
        }

        // --------------------------------------
        // Clean up the data array
        // --------------------------------------

        $data = array_filter($data, 'pro_not_empty');

        // --------------------------------------
        // 'pro_search_catch_search' extension hook
        //  - Check incoming data and optionally change it
        // --------------------------------------

        if (ee()->extensions->active_hook('pro_search_catch_search') === true) {
            $data = ee()->extensions->call('pro_search_catch_search', $data);
            if (ee()->extensions->end_script === true) {
                return;
            }

            // Clean again to be sure
            $data = array_filter($data, 'pro_not_empty');
        }

        // --------------------------------------
        // Check for required parameter
        // --------------------------------------

        if (isset($data['required'])) {
            // Init errors
            $errors = array();

            // Get required as array
            list($required, $in) = $this->params->explode($data['required']);

            foreach ($required as $req) {
                // Break out when empty
                // @TODO: enhance for multiple fields
                if (empty($data[$req])) {
                    $errors[] = $req;
                }
            }

            // Go back
            if ($errors) {
                ee()->session->set_flashdata('errors', $errors);
                $this->_go_back('fields_missing');
            }

            // remove from data
            unset($data['required']);
        }

        // --------------------------------------
        // Optionally log search query
        // --------------------------------------

        if ($this->_log_search($data)) {
            // Remember log ID,
            // so we can update the # results later
            ee()->session->set_flashdata(ee()->pro_search_log_model->key, $this->log_id);
        }

        // --------------------------------------
        // Result URI: result page & cleaned up data, encoded
        // --------------------------------------

        $url = $this->_create_url($data, '&');

        // --------------------------------------
        // Redirect to result page
        // --------------------------------------

        // Empty out flashdata to avoid serving of JSON for ajax request
        if (AJAX_REQUEST && count(ee()->session->flashdata)) {
            ee()->session->flashdata = array();
        }

        ee()->functions->redirect($url);
    }

    // --------------------------------------------------------------------
    // PRIVATE METHODS
    // --------------------------------------------------------------------

    /**
     * Create URL for given page and encoded query
     *
     * @access     private
     * @param      array
     * @param      string
     * @return     string
     */
    private function _create_url($query = array(), $amp = AMP)
    {
        // --------------------------------------
        // If no page, get default
        // --------------------------------------

        $page = isset($query['result_page'])
              ? $query['result_page']
              : $this->settings->get('default_result_page');

        // Remove trailing slash
        $page = rtrim($page, '/');

        // --------------------------------------
        // Hash in the result page?
        // --------------------------------------

        if (strpos($page, '#') !== false) {
            list($page, $hash) = explode('#', $page, 2);
            $hash = '#' . $hash;
        } else {
            $hash = '';
        }

        // --------------------------------------
        // Force a protocol?
        // --------------------------------------

        $protocol = false;

        if (
            isset($query['force_protocol']) &&
            in_array($query['force_protocol'], array('http', 'https'))
        ) {
            $protocol = $query['force_protocol'];
            unset($query['force_protocol']);
        }

        // --------------------------------------
        // Encode the query or not?
        // --------------------------------------

        if ($this->settings->get('encode_query') == 'y') {
            // Custom query position?
            if (strpos($page, '%s') === false) {
                $page .= '/%s';
            }

            $url = sprintf($page, pro_search_encode($query));
            $qs = '';
        } else {
            unset($query['result_page']);

            // Create query string
            $url = $page;
            $qs = http_build_query($query, '', $amp);

            // Clean up and remove 'dangerous' chars
            foreach (array('?', ';', ':', '|') as $i => $char) {
                $replacement = ($i < 2) ? '' : $char;
                $qs = str_replace(urlencode($char), $replacement, $qs);
            }

            if ($qs) {
                $qs = '?' . $qs;
            }
        }

        // --------------------------------------
        // If URI isn't a full url, make it so
        // --------------------------------------

        if (! preg_match('/^https?:\/\//', $url)) {
            $url = ee()->functions->create_url($url);
        }

        // --------------------------------------
        // Make sure the protocol is set according to force_protocol param
        // --------------------------------------

        if ($protocol) {
            $url = preg_replace('/^https?/', $protocol, $url);
        }

        return $url . $qs . $hash;
    }

    // --------------------------------------------------------------------

    /**
     * Read a shortcut param, set it and return it
     *
     * @access     private
     * @return     mixed
     */
    private function _get_shortcut()
    {
        if (is_null($this->shortcut)) {
            $row = false;

            if ($shortcut = ee()->TMPL->fetch_param('shortcut')) {
                $attr = is_numeric($shortcut) ? 'shortcut_id' : 'shortcut_name';

                $msg = ($row = ee()->pro_search_shortcut_model->get_one($shortcut, $attr))
                    ? 'Shortcut found'
                    : "Shortcut {$shortcut} not found";

                $this->_log($msg);
            }

            $this->shortcut = $row;
        }

        // Overwrite the query? Defaults to Yes
        if ($this->shortcut && ee()->TMPL->fetch_param('force_shortcut', 'yes') == 'yes') {
            $this->_log('Overwriting given query with shortcut search');
            $this->params->overwrite($this->shortcut['parameters'], true);
        }

        return $this->shortcut;
    }

    /**
     * Check to see if current member can manage shortcuts
     */
    private function _can_manage_shortcuts()
    {
        // Check permissions; only allowed members can save searches
        $allowed_groups = $this->settings->get('can_manage_shortcuts');
        $member_group = ee()->session->userdata('group_id');

        // Force array
        if (! is_array($allowed_groups)) {
            $allowed_groups = array();
        }

        // SuperAdmins are always okay
        $allowed_groups[] = 1;

        return in_array($member_group, $allowed_groups);
    }

    // --------------------------------------------------------------------

    /**
     * Log given search parameters
     *
     * @access      private
     * @param       array
     * @return      void
     */
    private function _log_search($data = array())
    {
        if (($search_log_size = $this->settings->get('search_log_size')) !== '0' && is_numeric($search_log_size)) {
            $keywords = isset($data['keywords']) ? $data['keywords'] : '';

            // Don't add keywords to log parameters
            unset($data['keywords']);

            // Log search
            $this->log_id = ee()->pro_search_log_model->insert(array(
                'site_id'     => $this->site_id,
                'member_id'   => ee()->session->userdata['member_id'],
                'search_date' => ee()->localize->now,
                'ip_address'  => ee()->session->userdata['ip_address'],
                'keywords'    => $keywords,
                'parameters'  => pro_search_encode($data, false)
            ));

            // Prune log
            // Rand trick borrowed from native search module
            if ((rand() % 100) < 5) {
                ee()->pro_search_log_model->prune($this->site_id, $search_log_size);
            }

            // Return the log ID inserted
            return $this->log_id;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Check for {if pro_search_no_results}
     *
     * @access      private
     * @return      void
     */
    private function _prep_no_results($open = 'pro_search_no_results')
    {
        // Shortcut to tagdata
        $td = & ee()->TMPL->tagdata;
        $open = 'if ' . $open;
        $close = '/if';

        // Check if there is a custom no_results conditional
        if (strpos($td, $open) !== false && preg_match('#' . LD . $open . RD . '(.*?)' . LD . $close . RD . '#s', $td, $match)) {
            $this->_log("Prepping {$open} conditional");

            // Check if there are conditionals inside of that
            if (stristr($match[1], LD . 'if')) {
                $match[0] = ee('Variables/Parser')->getFullTag($td, $match[0], LD . 'if', LD . '/if' . RD);
            }

            // Set template's no_results data to found chunk
            ee()->TMPL->no_results = substr($match[0], strlen(LD . $open . RD), -strlen(LD . $close . RD));

            // Remove no_results conditional from tagdata
            $td = str_replace($match[0], '', $td);
        }
    }

    /**
     * Process no_results
     *
     * @access      private
     * @return      string
     */
    private function _no_results($tagdata = null)
    {
        // Set to default no_results data by default
        if (is_null($tagdata)) {
            $tagdata = ee()->TMPL->no_results;
        }

        // Check if there are pro_search vars present
        if (strpos($tagdata, $this->settings->prefix) !== false) {
            $this->_log('Found pro_search variables in no_results block, calling filters to parse');

            $vars = ee('Variables/Parser')->extractVariables($tagdata);

            ee()->TMPL->var_single = $vars['var_single'];
            ee()->TMPL->var_pair = $vars['var_pair'];
            ee()->TMPL->tagdata = $tagdata;

            $tagdata = $this->filters();
        } else {
            $tagdata = ee()->TMPL->no_results();
        }

        // Update search log with no results
        if ($this->log_id) {
            ee()->pro_search_log_model->add_num_results(0, $this->log_id);
        }

        return $tagdata;
    }

    // --------------------------------------------------------------------

    /**
     * Loads the Channel module and runs its entries() method
     *
     * @access      private
     * @return      void
     */
    private function _channel_entries()
    {
        // --------------------------------------
        // Make sure the following params are set
        // --------------------------------------

        $set_params = array(
            'dynamic'  => 'no',
            'paginate' => 'bottom'
        );

        foreach ($set_params as $key => $val) {
            if (! ee()->TMPL->fetch_param($key)) {
                ee()->TMPL->tagparams[$key] = $val;
            }
        }

        // --------------------------------------
        // Take care of related entries
        // --------------------------------------

        if (version_compare(APP_VER, '2.6.0', '<')) {
            // We must do this, 'cause the template engine only does it for
            // channel:entries or search:search_results. The bastard.
            ee()->TMPL->tagdata = ee()->TMPL->assign_relationship_data(ee()->TMPL->tagdata);

            // Add related markers to single vars to trigger replacement
            foreach (ee()->TMPL->related_markers as $var) {
                ee()->TMPL->var_single[$var] = $var;
            }
        }

        // --------------------------------------
        // Get channel module
        // --------------------------------------

        $this->_log('Calling the channel module');

        if (! class_exists('channel')) {
            require_once PATH_MOD . 'channel/mod.channel.php';
        }

        // --------------------------------------
        // Create new Channel instance
        // --------------------------------------

        $channel = new Channel();

        // --------------------------------------
        // Let the Channel module do all the heavy lifting
        // --------------------------------------

        $tagdata = $channel->entries();

        // --------------------------------------
        // Update search log with results number
        // --------------------------------------

        if ($this->log_id) {
            // Read total from channel class:
            // this is set only if pagination was triggered
            $total = $channel->absolute_results;

            // If no total is given, check the cache, which is set by the channel module
            if (! $total && ($entry_ids = pro_get_cache('channel', 'entry_ids'))) {
                $total = count($entry_ids);
            }

            // Then, if we still have a total, update the search log
            if ($total) {
                ee()->pro_search_log_model->add_num_results($total, $this->log_id);
            }
        }

        // --------------------------------------
        // Return the parsed tagdata
        // --------------------------------------

        return $tagdata;
    }

    // --------------------------------------------------------------------

    /**
     * Redirect to referrer with some flashdata
     *
     * @access      private
     * @param       string
     * @return      void
     */
    private function _go_back($with_message)
    {
        ee()->session->set_flashdata('error_message', $with_message);
        ee()->functions->redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ee()->functions->fetch_site_index());
    }

    // --------------------------------------------------------------------

    /**
     * Transform {pro_search:url} to {exp:pro_search:url query=""}
     *
     * @access     private
     * @param      string
     * @return     string
     */
    private function _rewrite_url_vars($haystack)
    {
        $needle = LD . 'pro_search:url';
        $replace = LD . 'exp:pro_search:url %s="%s"';
        $param = ($this->settings->get('encode_query') == 'y') ? 'query' : 'query_string';

        if (strpos($haystack, $needle) !== false) {
            // Make sure the query's an array
            $query = is_array($this->params->query) ? $this->params->query : array();

            // For Form and Filters tag, add the tagparams to the query, too
            // The Results tag might have other params assigned or hard-coded
            // parameters, which needn't be added to the query
            if (ee()->TMPL->tagparts[1] != 'results' && is_array($this->params->tagparams)) {
                $query = array_merge($query, $this->params->tagparams);
            }

            // Encode it
            $query = empty($query) ? '' : pro_search_encode($query);

            // And replace it in the template
            $haystack = str_replace($needle, sprintf($replace, $param, $query), $haystack);
        }

        return $haystack;
    }

    /**
     * Replace query_string var, automatically fix pagination links
     *
     * @access     private
     * @param      string
     * @return     string
     */
    private function _maintain_query_string($tagdata)
    {
        // Don't do anything if query's encoded
        if ($this->settings->get('encode_query') == 'y') {
            return $tagdata;
        }

        // Query string var
        $var = LD . $this->settings->prefix . 'query_string' . RD;

        // Fix pagination for Results tag
        if (ee()->TMPL->fetch_param('pro_search') == 'yes') {
            // Structure, Publisher, and other naughty add-ons that
            // mess around with the URI object.
            $uri = new EE_URI();
            $uri->_fetch_uri_string();

            // Get current URL
            $url = ee()->config->site_url($uri->uri_string());

            // Strip away pagination segment
            $url = preg_replace('#/P\d+/?$#', '', $url);

            // Make it safe
            $url = preg_quote($url, '#');

            // Now find all similar URLs in tagdata without the var next to it
            $tagdata = preg_replace("#(['\"])({$url}(/P\d+)?/?)\\1#", "$1$2{$var}$1", $tagdata);
        }

        // Get the query string
        if ($qs = (string) ee()->input->server('QUERY_STRING')) {
            $qs = '?' . str_replace('&', '&amp;', $qs);
        }

        // Replace {pro_search_query_string} vars
        $tagdata = str_replace($var, $qs, $tagdata);

        return $tagdata;
    }

    /**
     * Post parse tagdata
     *
     * @access      private
     * @param       string
     * @return      string
     */
    private function _post_parse($tagdata)
    {
        // If we're not encoding, maintain query string vars/URLs
        $tagdata = $this->_maintain_query_string($tagdata);

        // CLean up prefixed variables
        $tagdata = preg_replace('#' . LD . $this->settings->prefix . '.*?' . RD . '#i', '', $tagdata);

        // Prep {if foo IN (bar)} conditionals
        $tagdata = pro_prep_in_conditionals($tagdata);

        // Transform {pro_search:url ...} to their tag syntax equivalents
        // to avoid parse order woes
        $tagdata = $this->_rewrite_url_vars($tagdata);

        return $tagdata;
    }

    // --------------------------------------------------------------------

    /**
     * Extract a parameter from tagparams (get and unset)
     *
     * @access     private
     * @param      string
     * @param      mixed
     * @return     mixed
     */
    private function _extract_tagparam($key, $fallback = null)
    {
        $val = ee()->TMPL->fetch_param($key, $fallback);
        unset(ee()->TMPL->tagparams[$key]);

        return $val;
    }

    // --------------------------------------------------------------------

    /**
     * Log message to Template Logger
     *
     * @access     private
     * @param      string
     * @return     void
     */
    private function _log($msg)
    {
        ee()->TMPL->log_item("Pro Search: {$msg}");
    }
}
// End Class

/* End of file mod.pro_search.php */
