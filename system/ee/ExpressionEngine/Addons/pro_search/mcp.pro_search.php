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

use ExpressionEngine\Library\CP\Table;

// include base class
if (! class_exists('Pro_search_base')) {
    require_once(PATH_ADDONS . 'pro_search/base.pro_search.php');
}

/**
 * Pro Search Module Control Panel class
 */
class Pro_search_mcp
{
    // Use the base trait
    use Pro_search_base;

    // --------------------------------------------------------------------
    // CONSTANTS
    // --------------------------------------------------------------------

    const MAX_WEIGHT = 3;
    const VIEW_LOG_LIMIT = 25;
    const PREVIEW_PAD = 50;
    const PREVIEW_LIMIT = 100;
    const DEBUG = false;

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Allowed field types for replacing
     *
     * @access     private
     * @var        array
     */
    private $allowed_types = array(
        'text',
        'textarea',
        'rte',
        'wygwam',
        'matrix',
        'grid',
        'nsm_tiny_mce',
        'wyvern',
        'expresso',
        'editor',
        'reedactor',
        'redactee',
        'illuminated',
        'wb_markitup'
    );

    /**
     * Data array for views
     *
     * @var        array
     * @access     private
     */
    private $data = array();

    /**
     * View heading
     *
     * @var        string
     * @access     private
     */
    private $heading;

    /**
     * View breadcrumb
     *
     * @var        array
     * @access     private
     */
    private $crumb = array();

    /**
     * Active menu item
     *
     * @var        string
     * @access     private
     */
    private $active;

    /**
     * Shortcut to current member group
     *
     * @var        int
     * @access     private
     */
    private $member_group;

    /**
     * Model shortcuts
     *
     * @var        object
     * @access     private
     */
    private $collection;
    private $shortcuts;
    private $groups;

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @return      void
     */
    public function __construct()
    {
        // Initialize base data for addon
        $this->initializeBaseData();

        // -------------------------------------
        //  Get member group shortcut
        // -------------------------------------

        $this->member_group = (int) @ee()->session->userdata['group_id'];

        // -------------------------------------
        //  Model shortcuts
        // -------------------------------------

        $this->collection = & ee()->pro_search_collection_model;
        $this->shortcuts = & ee()->pro_search_shortcut_model;
        $this->groups = & ee()->pro_search_group_model;
    }

    // --------------------------------------------------------------------

    /**
     * Module home page
     *
     * @access      public
     * @return      string
     */
    public function index()
    {
        // --------------------------------------
        // Get action ID for open search URL
        // --------------------------------------

        $this->data['search_url']
            = ee()->functions->fetch_site_index(0, 0)
            . QUERY_MARKER . 'ACT='
            . ee()->cp->fetch_action_id($this->class_name, 'catch_search')
            . AMP . 'keywords={searchTerms}';

        // --------------------------------------
        // Get action ID for building an index
        // --------------------------------------

        $this->data['build_url']
            = ee()->functions->fetch_site_index(0, 0)
            . QUERY_MARKER . 'ACT='
            . ee()->cp->fetch_action_id($this->class_name, 'build_index')
            . AMP . 'key=';

        // --------------------------------------
        // Add this version and settings to the view
        // --------------------------------------

        $this->data['version'] = $this->version;
        $this->data['settings'] = ee()->pro_search_settings->get();

        // --------------------------------------
        // Page title
        // --------------------------------------

        $this->_set_cp_var('cp_page_title', lang('pro_search_module_name'));

        return $this->view('index');
    }

    // --------------------------------------------------------------------

    /**
     * Extension settings
     *
     * @access      public
     * @return      string
     */
    public function settings()
    {
        // --------------------------------------
        // The sections
        // --------------------------------------

        $sections = array();

        // --------------------------------------
        // Encode Query
        // --------------------------------------

        $sections[0][] = array(
            'title' => 'encode_query',
            'desc' => 'encode_query_help',
            'fields' => array(
                'encode_query' => array(
                    'type'  => 'yes_no',
                    'value' => ee()->pro_search_settings->get('encode_query'),
                    'disabled' => (ee()->config->item('uri_protocol') == 'QUERY_STRING')
                )
            )
        );

        // --------------------------------------
        // Default Result Page
        // --------------------------------------

        $sections[0][] = array(
            'title' => 'default_result_page',
            'desc' => 'default_result_page_help',
            'fields' => array(
                'default_result_page' => array(
                    'type'  => 'text',
                    'value' => ee()->pro_search_settings->get('default_result_page')
                )
            )
        );

        // --------------------------------------
        // Build Index ACT key
        // --------------------------------------
        $actUrl = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->cp->fetch_action_id($this->class_name, 'build_index') . AMP . 'key=' . ee()->pro_search_settings->get('build_index_act_key') . AMP . 'collection_id=1';

        $sections[0][] = array(
            'title' => 'build_index_act_key',
            'desc' => sprintf(lang('build_index_act_key_help'), $actUrl),
            'fields' => array(
                'build_index_act_key' => array(
                    'type'  => 'text',
                    'value' => ee()->pro_search_settings->get('build_index_act_key')
                )
            )
        );

        // --------------------------------------
        // Search Log Size
        // --------------------------------------

        $sections[0][] = array(
            'title' => 'search_log_size',
            'desc' => 'search_log_size_help',
            'fields' => array(
                'search_log_size' => array(
                    'type'  => 'short-text',
                    'value' => ee()->pro_search_settings->get('search_log_size'),
                    'label' => ''
                )
            )
        );

        // --------------------------------------
        // Batch Size
        // --------------------------------------

        $sections[0][] = array(
            'title' => 'batch_size',
            'desc' => 'batch_size_help',
            'fields' => array(
                'batch_size' => array(
                    'type'  => 'short-text',
                    'value' => ee()->pro_search_settings->get('batch_size'),
                    'label' => ''
                )
            )
        );

        // --------------------------------------
        // Excerpt length
        // --------------------------------------

        $sections[0][] = array(
            'title' => 'excerpt_length',
            'desc' => 'excerpt_length_help',
            'fields' => array(
                'excerpt_length' => array(
                    'type'  => 'short-text',
                    'value' => ee()->pro_search_settings->get('excerpt_length'),
                    'label' => ''
                )
            )
        );

        // --------------------------------------
        // Filters
        // --------------------------------------

        // Load lib
        ee()->load->library('pro_search_filters');

        // Get names
        $filters = ee()->pro_search_filters->names();
        $choices = array();

        // Choices to display
        foreach ($filters as $f) {
            $choices[$f] = ucwords(str_replace('_', ' ', $f));
        }

        // Active filters (non-disabled)
        $chosen = array_values(array_diff(
            $filters,
            ee()->pro_search_settings->get('disabled_filters')
        ));

        $sections[0][] = array(
            'title' => 'filters',
            'desc' => 'filters_help',
            'fields' => array(
                'filters' => array(
                    'type'  => 'checkbox',
                    'value' => $chosen,
                    'choices' => $choices,
                    'wrap' => true
                )
            )
        );

        // --------------------------------------
        // Add hilite tags to data array
        // --------------------------------------

        $tags = ee()->pro_search_settings->hilite_tags;
        $choices = array('' => lang('do_not_hilite'));

        foreach ($tags as $t) {
            $choices[$t] = sprintf(lang('use_hilite_tag'), "{$t}");
        }

        $sections['keywords'][] = array(
            'title' => 'excerpt_hilite',
            'desc' => 'excerpt_hilite_help',
            'fields' => array(
                'excerpt_hilite' => array(
                    'type'  => 'select',
                    'value' => ee()->pro_search_settings->get('excerpt_hilite'),
                    'choices' => $choices
                )
            )
        );

        // --------------------------------------
        // Hilite title?
        // --------------------------------------

        $sections['keywords'][] = array(
            'title' => 'title_hilite',
            //'desc' => 'title_hilite_help',
            'fields' => array(
                'title_hilite' => array(
                    'type'  => 'yes_no',
                    'value' => ee()->pro_search_settings->get('title_hilite') ?: 'n'
                )
            )
        );

        // --------------------------------------
        // min_word_len
        // --------------------------------------

        // $query = ee()->db->query("SHOW VARIABLES LIKE 'ft_min_word_len'");
        // $default = (($row = $query->row_array()) && isset($row['Value']))
        //  ? $row['Value']
        //  : 4;

        $sections['keywords'][] = array(
            'title' => 'min_word_length',
            'desc' => 'min_word_length_help',
            'fields' => array(
                'min_word_length' => array(
                    'type'  => 'short-text',
                    'value' => ee()->pro_search_settings->get('min_word_length'),
                    'label' => ''
                )
            )
        );

        // --------------------------------------
        // Stop Words
        // --------------------------------------

        $words = preg_replace('/\s+/', "\n", ee()->pro_search_settings->get('stop_words'));
        $sections['keywords'][] = array(
            'title' => 'stop_words',
            'desc' => 'stop_words_help',
            'fields' => array(
                'stop_words' => array(
                    'type'  => 'textarea',
                    'value' => trim($words)
                )
            )
        );

        // --------------------------------------
        // Ignore Words
        // --------------------------------------

        $words = preg_replace('/\s+/', "\n", ee()->pro_search_settings->get('ignore_words'));
        $sections['keywords'][] = array(
            'title' => 'ignore_words',
            'desc' => 'ignore_words_help',
            'fields' => array(
                'ignore_words' => array(
                    'type'  => 'textarea',
                    'value' => trim($words)
                )
            )
        );

        // --------------------------------------
        // Permissions - get member groups
        // --------------------------------------
        $groups = ee('Model')->get('Role')
            ->filter('role_id', 'IN', ee('Permission')->rolesThatHave('can_access_cp'))
            ->filter('role_id', 'NOT IN', range(1, 4))
            ->order('name', 'ASC')
            ->all()->getDictionary('role_id', 'name');

        // Add permissions to form if there are groups
        if (! empty($groups)) {
            $perms = ee()->pro_search_settings->permissions();

            // A row for each group
            foreach ($groups as $id => $name) {
                $row = array('title' => $name);

                // A different checkbox for each permission type
                foreach ($perms as $perm) {
                    $row['fields'][$perm] = array(
                        'type'    => 'checkbox',
                        'choices' => array($id => html_entity_decode(lang($perm))),
                        'value'   => ee()->pro_search_settings->get($perm)
                    );
                }

                $sections['permissions'][] = $row;
            }
        }

        // --------------------------------------
        // Set breadcrumb
        // --------------------------------------

        $this->data = array(
            'base_url' => $this->mcp_url('save_settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'sections' => $sections
        );

        $this->_set_cp_var('cp_page_title', lang('settings'));
        $this->_set_cp_crumb($this->mcp_url(), lang('pro_search_module_name'));

        // --------------------------------------
        // Load view
        // --------------------------------------

        return $this->view('form');
    }

    /**
     * Save settings
     *
     * @access     public
     * @return     void
     */
    public function save_settings()
    {
        // --------------------------------------
        // Initiate settings array
        // --------------------------------------

        $settings = array();

        // --------------------------------------
        // Loop through default settings, check
        // for POST values, fallback to default
        // --------------------------------------

        foreach (ee()->pro_search_settings->default_settings as $key => $val) {
            if (($settings[$key] = ee()->input->post($key)) === false) {
                $settings[$key] = $val;
            }
        }

        // -------------------------------------
        // Get filters
        // -------------------------------------

        ee()->load->library('pro_search_filters');
        $all = ee()->pro_search_filters->names();
        $filters = (array) ee()->input->post('filters');

        $settings['disabled_filters'] = array_values(array_diff($all, $filters));

        // --------------------------------------
        // Convert stop/ignore words
        // --------------------------------------

        $settings['stop_words'] = pro_prep_word_list($settings['stop_words']);
        $settings['ignore_words'] = pro_prep_word_list($settings['ignore_words']);

        // --------------------------------------
        // Save serialized settings
        // --------------------------------------

        ee()->db->where('class', $this->class_name . '_ext');
        ee()->db->update('extensions', array('settings' => serialize($settings)));

        // --------------------------------------
        // Set feedback message
        // --------------------------------------

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('settings_saved'))
            ->addToBody(sprintf(lang('settings_saved_desc'), $this->info->getName()))
            ->defer();

        // --------------------------------------
        // Redirect back to overview
        // --------------------------------------

        ee()->functions->redirect($this->mcp_url('settings'));
    }

    // --------------------------------------------------------------------

    /**
     * List collections screen
     *
     * @access      public
     * @return      string
     */
    public function collections()
    {
        // --------------------------------------
        // Init table
        // --------------------------------------

        $table = ee('CP/Table', array(
            'sortable' => false,
        ));

        // No results
        $table->setNoResultsText('no_collections_exist');

        // Table columns
        $table->setColumns(array(
            'id',
            'collection_label',
            'collection_name',
            'channel',
            'entries',
            'index_options' => array('encode' => false),
            array('type' => Table::COL_CHECKBOX)
        ));

        // --------------------------------------
        // Initiate table data
        // --------------------------------------

        $rows = array();

        // --------------------------------------
        // Generate table data
        // --------------------------------------

        if ($cols = $this->collection->get_by_site($this->site_id)) {
            // Set table limit to total amount of cols
            $table->config['limit'] = count($cols);

            // Get channel ids
            $channel_ids = array_unique(pro_flatten_results($cols, 'channel_id'));

            // Query DB to get totals
            $totals = ee()->db
                ->select('channel_id, COUNT(*) AS num_entries')
                ->from('channel_titles')
                ->where_in('channel_id', $channel_ids)
                ->group_by('channel_id')
                ->get()
                ->result_array();

            $totals = pro_flatten_results($totals, 'num_entries', 'channel_id');

            // Get oldest index dates
            $index_dates = ee()->pro_search_index_model->get_oldest_index();

            // Get all channel names for reference
            $channels = ee('Model')
                ->get('Channel')
                ->filter('channel_id', 'IN', $channel_ids)
                ->all();

            $channels = $channels->getDictionary('channel_id', 'channel_title');

            // Keep track of new and old collections
            $old = $new = array();

            // Loop through columns and add to data
            foreach ($cols as $col) {
                // Shortcut to collection id
                $id = $col['collection_id'];

                // Total entries in this collection
                $total = @$totals[$col['channel_id']] ?: 0;

                // ID
                $row = array($id);

                // LABEL
                $row[] = array(
                    'content' => $col['collection_label'],
                    'href' => $this->mcp_url('edit_collection/' . $id)
                );

                // NAME
                $row[] = $col['collection_name'];

                // CHANNEL
                $row[] = @$channels[$col['channel_id']] ?: '?';

                // # ENTRIES
                $row[] = $total;

                // BUILD OPTIONS
                if ($total) {
                    // Rebuild Index item
                    $items = array('find' => array(
                        'href'       => '#',
                        'title'      => lang('index'),
                        'data-build' => 'index'
                    ));

                    // Rebuild lexicon item
                    if ($col['language']) {
                        $items['glossary'] = array(
                            'href'       => '#',
                            'title'      => lang('lexicon'),
                            'data-build' => 'lexicon'
                        );

                        $items['sync'] = array(
                            'href'       => '#',
                            'title'      => lang('both'),
                            'data-build' => 'both'
                        );
                    } else {
                        // make more clear that languages required for these tools. People thought they were missing
                        $items['glossary'] = array(
                            'href'       => '#',
                            'title'      => lang('set_langauge_to_use_tool'),
                            'disabled' => 'true'
                        );

                        $items['sync'] = array(
                            'href'       => '#',
                            'title'      => lang('set_langauge_to_use_tool'),
                            'disabled' => 'true'
                        );
                    }

                    // Add custom row
                    $row[] = array(
                        'attrs' => array(
                            'class'           => 'pro-index',
                            'data-total'      => $total,
                            'data-collection' => $id,
                            'data-lexicon'    => $col['language'] ? 'true' : 'false'
                        ),
                        'html' => ee('View')
                            ->make('ee:_shared/toolbar')
                            ->render(array('toolbar_items' => $items))
                    );
                } else {
                    // No entries, so no build options
                    $row[] = ''; //lang('no_entries');
                }

                // CHECKBOX
                $row[] = array(
                    'name'  => 'collection_id[]',
                    'value' => $id,
                    'data'  => array(
                        'confirm' => htmlspecialchars($col['collection_label'], ENT_QUOTES)
                    )
                );

                // Add to table data
                $rows[] = $row;

                // TODO: re-enable this somehow

                // // Is this a new collection?
                // if ( ! isset($index_dates[$id]) && $total)
                // {
                //  $new[] = $col['collection_label'];
                // }

                // Is this an old collection?
                if (isset($index_dates[$id]) && $index_dates[$id] < $col['edit_date']) {
                    $old[] = $col['collection_label'];
                }
            } // End foreach

            // Show warning when a collection is out of date
            if ($old) {
                $lang_key = 'index_status_old_' . (count($old) > 1 ? 'many' : 'one');

                ee('CP/Alert')->makeInline('shared-form')
                    ->asWarning()
                    ->withTitle(lang('index_out_of_date'))
                    ->addToBody(lang($lang_key))
                    ->addToBody($old)
                    ->addSeparator()
                    ->addToBody(lang('alt_click'))
                    ->now();
            }
        }

        $table->setData($rows);

        // --------------------------------------
        // For batch deletion
        // --------------------------------------

        ee()->cp->add_js_script('file', 'cp/confirm_remove');

        // --------------------------------------
        // Compose view data
        // --------------------------------------

        $this->data = array(
            'table'          => $table->viewData(),
            'create_new_url' => $this->mcp_url('edit_collection/new'),
            'remove_url'     => $this->mcp_url('delete_collection'),
            'pagination'     => false
        );

        // Title and crumb
        $this->_set_cp_var('cp_page_title', lang('collections'));
        $this->_set_cp_crumb($this->mcp_url(), lang('pro_search_module_name'));

        // Active menu-item
        $this->active = 'collections';

        // Return the view
        return $this->view('list');
    }

    // --------------------------------------------------------------------

    /**
     * Create new collection or edit existing one
     *
     * @access      public
     * @return      string
     */
    public function edit_collection($collection_id = 'new')
    {
        // --------------------------------------
        // Get collection by id or empty row
        // --------------------------------------

        $collection = (is_numeric($collection_id))
            ? $this->collection->get_one($collection_id)
            : $this->collection->empty_row();

        // --------------------------------------
        // Get settings for this collection
        // --------------------------------------

        if (strlen($collection['settings'])) {
            $collection['settings'] = pro_search_decode($collection['settings'], false);
        }

        // --------------------------------------
        // Set default excerpt data
        // --------------------------------------

        if (! strlen($collection['excerpt'])) {
            $collection['excerpt'] = '0';
        }

        // --------------------------------------
        // Set default modifier data
        // --------------------------------------

        if (! strlen($collection['modifier'])) {
            $collection['modifier'] = '1';
        }

        // --------------------------------------
        // Initiate form sections
        // --------------------------------------

        $sections = array();

        // --------------------------------------
        // Channel selection
        // --------------------------------------

        $channels = ee('Model')
            ->get('Channel')
            ->with('CategoryGroups')
            ->filter('site_id', $this->site_id)
            ->order('channel_title', 'ASC')
            ->all();

        $choices = $channels->getDictionary('channel_id', 'channel_title');

        // Prepend choices with blank option when new
        if ($collection_id == 'new') {
            $choices = array('' => '--') + $choices;
        }

        $sections[0][] = array(
            'title' => 'channel',
            'fields' => array(
                'collection_id' => array(
                    'type'  => 'hidden',
                    'value' => $collection_id
                ),
                'channel_id' => array(
                    'required' => true,
                    'type' => 'select',
                    'choices' => $choices,
                    'group_toggle' => $channels->getDictionary('channel_id', 'channel_name'),
                    'value' => $collection['channel_id']
                )
            )
        );

        // --------------------------------------
        // Collection Label
        // --------------------------------------

        $sections[0][] = array(
            'title' => 'collection_label',
            'desc' => 'collection_label_notes',
            'fields' => array(
                'collection_label' => array(
                    'required' => true,
                    'type' => 'text',
                    'value' => $collection['collection_label']
                )
            )
        );

        // --------------------------------------
        // Collection Name
        // --------------------------------------

        $sections[0][] = array(
            'title' => 'collection_name',
            'desc' => 'collection_name_notes',
            'fields' => array(
                'collection_name' => array(
                    'required' => true,
                    'type' => 'text',
                    'value' => $collection['collection_name']
                )
            )
        );

        // --------------------------------------
        // Language
        // --------------------------------------

        $choices = array('' => '--');

        foreach (pro_languages() as $name => $code) {
            $choices[$code] = $name . ' – ' . $code;
        }

        $sections[0][] = array(
            'title' => 'collection_language',
            'desc' => 'collection_language_notes',
            'fields' => array(
                'language' => array(
                    'type' => 'select',
                    'choices' => $choices,
                    'value' => $collection['language']
                )
            )
        );

        // --------------------------------------
        // Modifier
        // --------------------------------------

        $sections[0][] = array(
            'title' => 'collection_modifier',
            'desc' => 'collection_modifier_notes',
            'fields' => array(
                'modifier' => array(
                    'type' => 'short-text',
                    'label' => '',
                    'value' => $collection['modifier']
                )
            )
        );

        // --------------------------------------
        // Create grouped sections to show/hide
        // --------------------------------------

        // Category groups
        $catgroups = ee('Model')
            ->get('CategoryGroup')
            ->filter('site_id', $this->site_id)
            ->order('group_name', 'ASC')
            ->all();

        // Category fields
        $catfields = ee('Model')
            ->get('CategoryField')
            ->filter('site_id', $this->site_id)
            ->order('field_order', 'ASC')
            ->all();

        foreach ($channels as $channel) {
            // Channel fields for this channel
            $cf = $channel->getAllCustomFields();

            $cf->filter(function ($field) {
                return get_bool_from_string($field->field_search);
            });

            // Prepend the title field to it
            $cf = array($channel->title_field_label) + $cf->getDictionary('field_id', 'field_label');

            // Now we can use it for the excerpt field
            $sections[] = array(
                'group' => $channel->channel_name,
                'label' => $channel->channel_title,
                'settings' => array(
                    'excerpt' => array(
                        'title' => 'excerpt',
                        'fields' => array(array(
                            'type' => 'select',
                            'name' => "excerpt[{$channel->channel_id}]",
                            'choices' => $cf,
                            'value' => $collection['excerpt']
                        ))
                    )
                )
            );

            // Channel Field weights
            $channel_fields = array();

            foreach ($cf as $id => $name) {
                $channel_fields[] = array(
                    'title' => $name,
                    'fields' => array(array(
                        'type'  => 'slider',
                        'name'  => "settings[{$channel->channel_id}][{$id}]",
                        'value' => (int) @$collection['settings'][$id],
                        'max'   => static::MAX_WEIGHT,
                        'unit'  => ''
                    ))
                );
            }

            $sections[] = array(
                'group' => $channel->channel_name,
                'label' => lang('channel_fields'),
                'settings' => $channel_fields
            );

            // Category Field weights
            if ($channel->CategoryGroups) {
                // So we get the right lang labels
                ee()->lang->loadfile('admin_content');

                // Loop through each category group of this channel
                foreach ($channel->CategoryGroups as $group) {

                    $group_id = $group->getId();
                    // Skip references to non-existent category groups
                    if (! in_array($group_id, $catgroups->getDictionary('group_id', 'group_id'))) {
                        continue;
                    }

                    $category_fields = array();

                    // Get this group
                    $group = $catgroups->filter('group_id', $group_id)->first();

                    // Get category fields for this group
                    $cf = $catfields->filter('group_id', $group_id);

                    // Prepend name and description to it
                    $cf = array(
                        'cat_name' => lang('category_name'),
                        'cat_description' => lang('category_description')
                    ) + $cf->getDictionary('field_id', 'field_label');

                    // Add to rows
                    foreach ($cf as $id => $name) {
                        // Key for this cat_group:cat_field
                        $key = $group_id . ':' . $id;

                        $category_fields[] = array(
                            'title' => $name,
                            'fields' => array(array(
                                'type'  => 'slider',
                                'name'  => "settings[{$channel->channel_id}][{$key}]",
                                'value' => (int) @$collection['settings'][$key],
                                'max'   => static::MAX_WEIGHT,
                                'unit'  => ''
                            ))
                        );
                    }

                    // Add it to its own category group section
                    $sections[] = array(
                        'group' => $channel->channel_name,
                        'label' => lang('category_group') . ': ' . $group->group_name,
                        'settings' => $category_fields
                    );
                }
            } // done with category field weights
        } // done looping through channels

        // --------------------------------------
        // Create array for JS object
        // --------------------------------------

        $js = array();

        foreach ($channels as $row) {
            $js[$row->channel_id] = array(
                'channel_title' => $row->channel_title,
                'channel_name'  => $row->channel_name
            );
        }

        ee()->javascript->set_global('pro_search_channels', $js);

        // --------------------------------------
        // For switching between channels
        // --------------------------------------

        ee()->cp->add_js_script('file', 'cp/form_group');

        // --------------------------------------
        // Compose view data
        // --------------------------------------

        $this->data = array(
            'base_url'              => $this->mcp_url('save_collection'),
            'save_btn_text'         => 'save_collection',
            'save_btn_text_working' => 'btn_saving',
            'sections'              => $sections
        );

        // Title and crumb
        $title = lang($collection_id == 'new' ? 'create_new_collection' : 'edit_collection');

        $this->_set_cp_var('cp_page_title', $title);
        $this->_set_cp_crumb($this->mcp_url(), lang('pro_search_module_name'));
        $this->_set_cp_crumb($this->mcp_url('collections'), lang('collections'));

        // Active menu-item
        $this->active = 'collections';

        return $this->view('form');
    }

    // --------------------------------------------------------------------

    /**
     * Save changes to given collection
     *
     * @access      public
     * @return      void
     */
    public function save_collection()
    {
        // --------------------------------------
        // Set return url
        // --------------------------------------

        $return_url = $this->mcp_url('collections');

        // --------------------------------------
        // Get collection id
        // --------------------------------------

        if (($collection_id = ee()->input->post('collection_id')) === false) {
            ee()->functions->redirect($return_url);
        }

        // --------------------------------------
        // Set site id to current site
        // --------------------------------------

        $_POST['site_id'] = $this->site_id;

        // --------------------------------------
        // Title shouldn't be empty
        // --------------------------------------

        if (! strlen($_POST['collection_label'])) {
            $_POST['collection_label'] = lang('new_collection');
        }

        // --------------------------------------
        // Check channel
        // --------------------------------------

        if (! ($channel_id = ee()->input->post('channel_id'))) {
            show_error(lang('channel_cannot_be_empty'));
        }

        // --------------------------------------
        // Check collection name
        // --------------------------------------

        // It should be filled in
        if (! ($collection_name = trim(ee()->input->post('collection_name')))) {
            show_error(lang('collection_name_cannot_be_empty'));
        }

        // It should be formatted correctly
        if (! preg_match('#^[\-a-zA-Z0-9_]+$#', $collection_name)) {
            show_error(lang('collection_name_has_wrong_chars'));
        }

        // And it should be unique
        if (
            ee()->db->where(array(
                'site_id'          => $this->site_id,
                'collection_name'  => $collection_name,
                'collection_id !=' => $collection_id
            ))->from(ee()->pro_search_collection_model->table())->count_all_results()
        ) {
            show_error(lang('collection_name_exists'));
        }

        // --------------------------------------
        // Check modifier
        // --------------------------------------

        $mod = (float) ee()->input->post('modifier');

        // Check modifier validity
        if ($mod <= 0) {
            $mod = 1;
        }
        if ($mod > 9999) {
            $mod = 9999;
        }

        $_POST['modifier'] = $mod;

        // --------------------------------------
        // Check Excerpt
        // --------------------------------------

        $excerpts = ee()->input->post('excerpt');

        $_POST['excerpt'] = isset($excerpts[$channel_id]) ? $excerpts[$channel_id] : 0;

        // --------------------------------------
        // Check Settings
        // --------------------------------------

        $settings = (array) ee()->input->post('settings');

        // Check field weights
        if (isset($settings[$channel_id])) {
            $settings = $settings[$channel_id];
        } else {
            $settings = array();
        }

        // Clean it
        $settings = array_filter($settings);

        // It's nicer to sort the settings
        ksort($settings);

        // Encode the settings to JSON
        $settings = pro_search_encode($settings, false);

        // Set settings in POST so model can handle it
        $_POST['settings'] = $settings;

        // --------------------------------------
        // Add edit date to POST vars if new or settings changed
        // --------------------------------------

        // Initiate edit date
        $edit_date = ee()->localize->now;

        // Check old settings
        if (is_numeric($collection_id)) {
            $old_collection = ee()->pro_search_collection_model->get_one($collection_id);

            // if the new encoded settings are the same as the settings on record,
            // we don't need to change the edit date
            if (
                $old_collection['channel_id'] == $channel_id and
                $old_collection['settings'] == $settings
            ) {
                $edit_date = false;
            }
        }

        if ($edit_date) {
            $_POST['edit_date'] = $edit_date;
        }

        // --------------------------------------
        // Insert or update record
        // --------------------------------------

        if (is_numeric($collection_id)) {
            ee()->pro_search_collection_model->update($collection_id);
        } else {
            $collection_id = ee()->pro_search_collection_model->insert();
        }

        // --------------------------------------
        // Set feedback message
        // --------------------------------------

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('changes_saved'))
            ->defer();

        // --------------------------------------
        // Redirect back to overview
        // --------------------------------------

        ee()->functions->redirect($return_url);
    }

    // --------------------------------------------------------------------

    /**
     * Delete a collection
     *
     * @access      public
     * @return      void
     */
    public function delete_collection()
    {
        // --------------------------------------
        // Check collection id
        // --------------------------------------

        if ($collection_id = ee()->input->post('collection_id')) {
            // --------------------------------------
            // Delete in 2 tables
            // --------------------------------------

            ee()->pro_search_collection_model->delete($collection_id);
            ee()->pro_search_index_model->delete($collection_id, 'collection_id');

            // --------------------------------------
            // Set feedback message
            // --------------------------------------

            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('collection_deleted'))
                ->defer();
        }

        // --------------------------------------
        // Go home
        // --------------------------------------

        ee()->functions->redirect($this->mcp_url('collections'));
    }

    // --------------------------------------------------------------------

    /**
     * Display lexicon page
     *
     * @access      public
     * @return      array
     */
    public function lexicon()
    {
        // --------------------------------------
        // Load lib
        // --------------------------------------

        ee()->load->library('pro_search_words');

        // --------------------------------------
        // Get word count per languate
        // --------------------------------------

        $counts = ee()->pro_search_word_model->get_lang_count();
        $total_langs = count($counts);
        $total_words = array_sum($counts);

        // --------------------------------------
        // Set data based on that
        // --------------------------------------

        $this->data['base_url'] = $this->mcp_url();
        $this->data['counts'] = $counts;
        $this->data['total_words'] = $total_words;
        $this->data['total_langs'] = $total_langs;
        $this->data['languages'] = pro_languages();
        $this->data['default'] = $counts ? key($counts) : 'en';
        $this->data['words'] = array();

        // --------------------------------------
        // Get right status
        // --------------------------------------

        if (empty($total_words)) {
            $status = 'lexicon_status_none';
        } elseif ($total_words === 1) {
            $status = 'lexicon_status_one_one';
        } else {
            $status = 'lexicon_status_many_' . ($total_langs > 1 ? 'many' : 'one');
        }

        $this->data['status'] = sprintf(
            lang($status),
            number_format($this->data['total_words']),
            number_format($this->data['total_langs'])
        );

        // --------------------------------------
        // Get stuff?
        // --------------------------------------

        $lang = ee()->input->post('language');
        $json = array();

        // --------------------------------------
        // Find words
        // --------------------------------------

        if ($find = ee()->input->post('find')) {
            // Init words
            $words = array();

            // Clean up word
            if ($word = ee()->pro_search_words->clean($find)) {
                // Add left wildcard
                if (substr($find, 0, 1) == '*') {
                    $word = '%' . $word;
                }

                // Add right wildcard
                if (substr($find, -1) == '*') {
                    $word .= '%';
                }

                $words = ee()->pro_search_word_model->find($word, $lang);
            }

            // Results?
            $found = count($words);

            switch ($found) {
                case 0:
                    $status = 'lexicon_found_none';
                    $str = $find;

                    break;

                case 1:
                    $status = 'lexicon_found_one';
                    $str = '';

                    break;

                default:
                    $status = 'lexicon_found_many';
                    $str = number_format($found);
                    $json['status'] = sprintf('Found %s matching words.', number_format($found));
            }

            $json['status'] = sprintf(lang($status), $str);
            $json['found'] = $words;
        }

        // --------------------------------------
        // Add a word
        // --------------------------------------

        if ($add = ee()->input->post('add')) {
            // Clean up word
            $word = ee()->pro_search_words->clean($add);

            // Valid and no spaces!
            if (ee()->pro_search_words->is_valid($word) && !preg_match('/\s/', $word)) {
                ee()->pro_search_word_model->insert_ignore(array(
                    'site_id'  => $this->site_id,
                    'language' => $lang,
                    'word'     => $word,
                    'length'   => ee()->pro_multibyte->strlen($word),
                    'clean'    => ee()->pro_search_words->remove_diacritics($word)
                ));

                $added = (bool) ee()->db->affected_rows();

                $status = $added ? 'lexicon_add_ok' : 'lexicon_add_existing';
                $str = $word;
            } else {
                $status = 'lexicon_add_invalid';
                $str = $add;
            }

            $json['status'] = sprintf(lang($status), $str);
        }

        // --------------------------------------
        // Remove a word
        // --------------------------------------

        if (
            ($remove = ee()->input->post('remove')) &&
            ($lang = ee()->input->post('language'))
        ) {
            $json = array();

            if ($word = ee()->pro_search_word_model->get_one($remove, 'word')) {
                ee()->pro_search_word_model->delete($remove, $lang);
                $json['status'] = sprintf(lang('lexicon_removed_word'), $word['word']);
            }
        }

        // --------------------------------------
        // Cater for ajax calls
        // --------------------------------------

        if (is_ajax() && ! empty($json)) {
            die(json_encode($json));
        }

        // --------------------------------------
        // Set breadcrumb
        // --------------------------------------

        $this->_set_cp_var('cp_page_title', lang('lexicon'));
        $this->_set_cp_crumb($this->mcp_url(), lang('pro_search_module_name'));
        $this->active = 'lexicon';

        // --------------------------------------
        // Load view
        // --------------------------------------

        return $this->view('lexicon');
    }

    // --------------------------------------------------------------------

    /**
     * List Shortcut Groups
     *
     * @access      public
     * @return      string
     */
    public function groups()
    {
        // --------------------------------------
        // Init table
        // --------------------------------------

        $table = ee('CP/Table', array(
            'sortable' => false
        ));

        // No results
        $table->setNoResultsText('no_groups_exist');

        // Table columns
        $table->setColumns(array(
            'id',
            'group_label',
            'manage' => array('type' => Table::COL_TOOLBAR),
            array('type' => Table::COL_CHECKBOX)
        ));

        // --------------------------------------
        // Initiate table data
        // --------------------------------------

        $data = array();

        // --------------------------------------
        // Get all groups
        // --------------------------------------

        $groups = $this->groups->get_by_site($this->site_id);
        $counts = $groups ? $this->shortcuts->get_group_counts($this->site_id) : array();

        foreach ($groups as $group) {
            $id = $group['group_id'];
            $edit = $this->mcp_url('edit_group/' . $id);

            // ID
            $row = array($id);

            // LABEL
            $row[] = array(
                'content' => $group['group_label'],
                'href'    => $edit
            );

            // TOOLBAR
            $row[] = array('toolbar_items' => array(
                'edit' => array(
                    'href'  => $edit,
                    'title' => lang('edit_group'),
                ),
                'txt-only' => array(
                    'href' => $this->mcp_url('shortcuts/' . $id),
                    'content' => lang('shortcuts') . ' (' . (@$counts[$id] ?: 0) . ')'
                )
            ));

            // CHECKBOX
            $row[] = array(
                'name'  => 'group_id[]',
                'value' => $id,
                'data'  => array(
                    'confirm' => $group['group_label']
                )
            );

            $data[] = $row;
        }

        $table->setData($data);

        // --------------------------------------
        // For batch deletion
        // --------------------------------------

        ee()->cp->add_js_script(array('file' => array('cp/confirm_remove')));

        // --------------------------------------
        // Set title and breadcrumb and view page
        // --------------------------------------

        $this->data = array(
            'table'          => $table->viewData(),
            'create_new_url' => $this->mcp_url('edit_group/new'),
            'remove_url'     => $this->mcp_url('delete_group'),
            'pagination'     => false
        );

        // --------------------------------------
        // Set title and breadcrumb and view page
        // --------------------------------------

        $this->_set_cp_var('cp_page_title', lang('groups'));
        $this->_set_cp_crumb($this->mcp_url(), lang('pro_search_module_name'));
        $this->active = 'groups';

        return $this->view('list');
    }

    /**
     * Edit short group
     *
     * @access      public
     * @return      string
     */
    public function edit_group($group_id = 'new')
    {
        // --------------------------------------
        // Get short group
        // --------------------------------------

        if ($group_id == 'new') {
            $group = $this->groups->empty_row();
            $group['group_id'] = $group_id;
        } else {
            $group = $this->groups->get_one($group_id);
        }

        // --------------------------------------
        // Set data for view
        // --------------------------------------

        $this->data = array(
            'base_url' => $this->mcp_url('save_group'),
            'cp_page_title' => lang('edit_group'),
            'save_btn_text' => 'save_changes',
            'save_btn_text_working' => 'btn_saving',
            'sections' => array(array(array(
                'title' => 'group_label',
                'fields' => array(
                    'group_id' => array(
                        'type' => 'hidden',
                        'value' => $group['group_id']
                    ),
                    'group_label' => array(
                        'required' => true,
                        'type' => 'text',
                        'value' => $group['group_label']
                    )
                )
            )))
        );

        // --------------------------------------
        // Set title and breadcrumb and view page
        // --------------------------------------

        $this->_set_cp_var('cp_page_title', lang('edit_group'));
        $this->_set_cp_crumb($this->mcp_url(), lang('pro_search_module_name'));
        $this->_set_cp_crumb($this->mcp_url('groups'), lang('groups'));
        $this->active = 'groups';

        return $this->view('form');
    }

    /**
     * Save short group
     *
     * @access      public
     * @return      string
     */
    public function save_group()
    {
        // --------------------------------------
        // Data to save
        // --------------------------------------

        $data = array();

        // --------------------------------------
        // Get short group
        // --------------------------------------

        $data['site_id'] = $this->site_id;
        $data['group_label'] = trim(ee()->input->post('group_label'));

        // --------------------------------------
        // Insert/update
        // --------------------------------------

        if (($group_id = ee()->input->post('group_id')) == 'new') {
            $this->groups->insert($data);
        } else {
            $this->groups->update($group_id, $data);
        }

        // --------------------------------------
        // Set feedback message
        // --------------------------------------

        ee()->session->set_flashdata('msg', 'changes_saved');

        // --------------------------------------
        // Return to the group list page
        // --------------------------------------

        ee()->functions->redirect($this->mcp_url('groups'));
    }

    /**
     * Delete group and its shortcuts
     */
    public function delete_group()
    {
        // Make sure an ID is posted
        if ($group_id = ee()->input->post('group_id')) {
            // Delete it
            $this->groups->delete($group_id);
            $this->shortcuts->delete_by_group($group_id);

            // --------------------------------------
            // Set feedback message
            // --------------------------------------

            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('group_deleted'))
                ->defer();
        }

        // Go back
        ee()->functions->redirect($this->mcp_url('groups'));
    }

    // --------------------------------------------------------------------

    /**
     * List shortcuts for given group
     *
     * @access      public
     * @return      string
     */
    public function shortcuts($group_id)
    {
        // --------------------------------------
        // Get group, get out when not given
        // --------------------------------------

        if (! ($group = $this->groups->get_one($group_id))) {
            ee()->functions->redirect($this->mcp_url('groups'));
        }

        // --------------------------------------
        // Init table
        // --------------------------------------

        $table = ee('CP/Table', array(
            'reorder'  => true,
            'sortable' => false,
            'attrs'    => array(
                'class' => 'pro-shortcuts',
                'data-order-url' => $this->mcp_url('order_shortcuts'),
                'data-group-id'  => $group_id
            )
        ));

        // No results
        $table->setNoResultsText('no_shortcuts_in_group');

        // Table columns
        $table->setColumns(array(
            'id',
            'shortcut_label',
            'shortcut_name',
            array('type' => Table::COL_CHECKBOX)
        ));

        // --------------------------------------
        // Initiate table data
        // --------------------------------------

        $data = array();

        // --------------------------------------
        // Get all shortcuts in this group
        // --------------------------------------

        $shortcuts = $this->shortcuts->get_by_group($group_id);

        foreach ($shortcuts as $shortcut) {
            $id = $shortcut['shortcut_id'];
            $edit = $this->mcp_url('edit_shortcut/' . $id);

            // ID
            $row = array($id);

            // LABEL
            $row[] = array(
                'content' => $shortcut['shortcut_label'],
                'href'    => $edit
            );

            // LABEL
            $row[] = array(
                'content' => $shortcut['shortcut_name']
            );

            // CHECKBOX
            $row[] = array(
                'name'  => 'shortcut_id[]',
                'value' => $id,
                'data'  => array(
                    'confirm' => $shortcut['shortcut_label']
                )
            );

            $data[] = $row;
        }

        $table->setData($data);

        // --------------------------------------
        // Set title and breadcrumb and view page
        // --------------------------------------

        $this->data = array(
            'table'          => $table->viewData(),
            'create_new_url' => $this->mcp_url('edit_shortcut/new', 'group_id=' . $group_id),
            'remove_url'     => $this->mcp_url('delete_shortcut', 'group_id=' . $group_id),
            'pagination'     => false
        );

        // --------------------------------------
        // Set title and breadcrumb and view page
        // --------------------------------------

        $this->_set_cp_var('cp_page_title', $group['group_label']);
        $this->_set_cp_crumb($this->mcp_url(), lang('pro_search_module_name'));
        $this->_set_cp_crumb($this->mcp_url('groups'), lang('groups'));
        $this->active = 'groups';

        ee()->cp->add_js_script(array(
            'file'   => array('cp/confirm_remove', 'cp/sort_helper'),
            'plugin' => 'ee_table_reorder'
        ));

        return $this->view('list');
    }

    /**
     * Edit shortcut
     *
     * @access      public
     * @return      string
     */
    public function edit_shortcut($shortcut_id = 'new')
    {
        // --------------------------------------
        // Get all groups
        // --------------------------------------

        $groups = $this->groups->get_by_site($this->site_id);
        $groups = pro_flatten_results($groups, 'group_label', 'group_id');

        // --------------------------------------
        // If there is no group, create a new one
        // --------------------------------------

        if (empty($groups)) {
            // Default group name
            $group_label = 'Default';

            // The group ID
            $group_id = ee()->pro_search_group_model->insert(array(
                'site_id'     => $this->site_id,
                'group_label' => $group_label
            ));

            // Remember this
            $groups = array($group_id => $group_label);
        }

        // --------------------------------------
        // Determine group ID
        // --------------------------------------

        $group_id = ee()->input->get('group_id') ?: key($groups);

        // --------------------------------------
        // Get IDs
        // --------------------------------------

        if ($shortcut_id == 'new') {
            $row = $this->shortcuts->empty_row();
            $row['group_id'] = $group_id;
            $title = lang('new_shortcut');
        } else {
            $row = $this->shortcuts->get_one($shortcut_id);
            $group_id = $row['group_id'];
            $title = lang('edit_shortcut');
        }

        // --------------------------------------
        // Are we getting it from the log?
        // --------------------------------------

        if ($log_id = ee()->input->get('log_id')) {
            if ($log = ee()->pro_search_log_model->get_one($log_id)) {
                $params = pro_search_decode($log['parameters'], false);

                // Add keywords to params
                if ($log['keywords']) {
                    $params['keywords'] = $log['keywords'];
                }

                $row['parameters'] = $params;
            }
        }

        // --------------------------------------
        // JSON-ify the parameters
        // --------------------------------------

        $json = json_encode($row['parameters']);

        // Make it html-safe
        $json = htmlspecialchars($json);

        // --------------------------------------
        // Get all groups
        // --------------------------------------

        $this->data = array(
            'base_url' => $this->mcp_url('save_shortcut'),
            'cp_page_title' => $title,
            'save_btn_text' => 'save_changes',
            'save_btn_text_working' => 'btn_saving',
            'sections' => array(array(
                array(
                    'title' => 'group',
                    'fields' => array(
                        'shortcut_id' => array(
                            'type' => 'hidden',
                            'value' => $shortcut_id
                        ),
                        'group_id' => array(
                            'type' => 'select',
                            'choices' => $groups,
                            'value' => $row['group_id']
                        )
                    )
                ),
                array(
                    'title' => 'shortcut_label',
                    'fields' => array(
                        'shortcut_label' => array(
                            'type' => 'text',
                            'value' => $row['shortcut_label']
                        )
                    )
                ),
                array(
                    'title' => 'shortcut_name',
                    'fields' => array(
                        'shortcut_name' => array(
                            'type' => 'text',
                            'value' => $row['shortcut_name']
                        )
                    )
                ),
                array(
                    'title' => 'parameters',
                    'fields' => array(
                        array(
                            'type' => 'html',
                            'content' => ee('View')
                                ->make($this->package . ':shortcut_params')
                                ->render(array('json' => $json))
                        )
                    )
                )
            ))
        );

        // --------------------------------------
        // Current group name?
        // --------------------------------------

        $group_name = $group_id ? $groups[$group_id] : false;

        // --------------------------------------
        // Set title and breadcrumb and view page
        // --------------------------------------

        $this->_set_cp_var('cp_page_title', $title);
        $this->_set_cp_crumb($this->mcp_url(), lang('pro_search_module_name'));
        $this->_set_cp_crumb($this->mcp_url('groups'), lang('groups'));
        $this->_set_cp_crumb($this->mcp_url('shortcuts/' . $group_id), $group_name);

        $this->active = 'groups';

        return $this->view('form');
    }

    /**
     * Save shortcut
     *
     * @access      public
     * @return      void
     */
    public function save_shortcut()
    {
        // --------------------------------------
        // Read parameters
        // --------------------------------------

        $keys = ee()->input->post('param-key');
        $vals = ee()->input->post('param-val');

        $params = (is_array($keys) && is_array($vals))
            ? array_combine($keys, $vals)
            : array();

        // --------------------------------------
        // Compose data to insert
        // --------------------------------------

        $data = array(
            'shortcut_id'    => null,
            'site_id'        => $this->site_id,
            'group_id'       => ee()->input->post('group_id'),
            'shortcut_name'  => ee()->input->post('shortcut_name'),
            'shortcut_label' => ee()->input->post('shortcut_label'),
            'parameters'     => pro_search_encode($params, false)
        );

        if (is_numeric(($shortcut_id = ee()->input->post('shortcut_id')))) {
            $data['shortcut_id'] = $shortcut_id;
        }

        // --------------------------------------
        // Validate the saved_search data
        // --------------------------------------

        if (($validated = $this->shortcuts->validate($data)) === false) {
            show_error(array_map('lang', $this->shortcuts->errors()));
        }

        // --------------------------------------
        // And insert/update it
        // --------------------------------------

        if (empty($validated['shortcut_id'])) {
            $validated['shortcut_id'] = $this->shortcuts->insert($validated);
        } else {
            $this->shortcuts->update($validated['shortcut_id'], $validated);
        }

        // --------------------------------------
        // Set feedback message
        // --------------------------------------

        ee()->session->set_flashdata('msg', 'changes_saved');

        // --------------------------------------
        // Return to the group list page
        // --------------------------------------

        ee()->functions->redirect($this->mcp_url('shortcuts/' . $data['group_id']));
    }

    /**
     * Delete shotcut
     */
    public function delete_shortcut()
    {
        // Make sure an ID is posted
        if ($shortcut_id = ee()->input->post('shortcut_id')) {
            // Delete it
            $this->shortcuts->delete($shortcut_id);

            // And set feedback message
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('shortcut_deleted'))
                ->defer();
        }

        // Optionally go back to group
        $uri = ($group_id = ee()->input->get_post('group_id'))
            ? 'shortcuts/' . $group_id
            : 'groups';

        // Go back
        ee()->functions->redirect($this->mcp_url($uri));
    }

    /**
     * Order shortcuts
     */
    public function order_shortcuts()
    {
        // Get order from POST
        if (($order = ee()->input->post('order')) && is_array($order)) {
            foreach ($order as $i => $id) {
                $this->shortcuts->update($id, array('sort_order' => $i + 1));
            }
        }

        if (AJAX_REQUEST) {
            die('true');
        } else {
            ee()->functions->redirect($this->mcp_url());
        }
    }

    // --------------------------------------------------------------------

    /**
     * First half of Find & Replace
     *
     * @access      public
     * @return      string
     */
    public function find()
    {
        // --------------------------------------
        // Get this member's id and group id
        // --------------------------------------

        $member_id = ee()->session->userdata('member_id');
        $member_group = $this->member_group;

        // --------------------------------------
        // Get allowed channels
        // --------------------------------------

        $channel_ids = ee()->functions->fetch_assigned_channels();

        // Quick & dirty error message display when there aren't any channels
        if (empty($channel_ids)) {
            show_error('No channels found');
        }

        // --------------------------------------
        // Get hidden fields according to publish layouts
        // --------------------------------------

        $hidden = array();

        $query = ee()->db->select('channel_id, field_layout')
            ->from('layout_publish')
               //->where('member_group', $member_group)
            ->where_in('channel_id', $channel_ids)
            ->get();

        // Loop thru each publish layout
        foreach ($query->result() as $row) {
            // Unserialize details and loop thru tabs
            foreach (unserialize($row->field_layout) as $tab => $layout) {
                // For each tab, loop thru fields and check if they're visible
                // If not visible, add it to hidden array
                foreach ($layout as $field => $options) {
                    if (isset($options['visible']) && $options['visible'] == false && is_numeric($field)) {
                        $hidden[$row->channel_id][] = $field;
                    }
                }
            }
        }

        // --------------------------------------
        // Get list of channels and fields for selection
        // --------------------------------------

        $channels = $cat_groups = array();
        $allowed = $this->allowed_types;

        $query = ee('Model')
            ->get('Channel')
            ->with('CategoryGroups')
            ->filter('site_id', $this->site_id)
            ->filter('channel_id', 'IN', $channel_ids)
            ->all();

        // Change flat resultset into nested one
        foreach ($query as $row) {
            if (! isset($channels[$row->channel_id])) {
                $channels[$row->channel_id] = array(
                    'channel_title'   => $row->channel_title,
                    'fields'          => array('title' => lang('title'))
                );

                $cat_groups = array_merge($cat_groups, $row->CategoryGroups->pluck('group_id'));
            }

            // Channel fields for this channel
            $cf = $row->getAllCustomFields()->filter(function ($field) use ($allowed) {
                return in_array($field->field_type, $allowed);
            });

            // Skip hidden fields
            // if (isset($hidden[$row->channel_id]) && in_array($row->field_id, $hidden[$row->channel_id]))
            // {
            //  continue;
            // }

            foreach ($cf as $field) {
                $channels[$row->channel_id]['fields'][$field->field_id] = $field->field_label;
            }
        }

        $this->data['channels'] = $channels;

        // --------------------------------------
        // Categories filter
        // --------------------------------------

        $categories = array();
        $allowed = ($member_group == 1) ? false : $this->_get_permitted_categories($member_id);

        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_categories');

        // Generate category tree array
        if ($cat_groups && $tree = ee()->api_channel_categories->category_tree($cat_groups)) {
            // Get category group names
            $query = ee()->db->select('group_id, group_name')
                ->from('category_groups')
                ->where_in('group_id', $cat_groups)
                ->order_by('group_name')
                ->get();
            $groups = pro_flatten_results($query->result_array(), 'group_name', 'group_id');

            // Loop thru tree
            foreach ($tree as $row) {
                // Skip categories that aren't allowed
                if (is_array($allowed) && ! in_array($row[0], $allowed)) {
                    continue;
                }

                // Add category group to array
                if (! isset($categories[$row[2]])) {
                    $categories[$row[2]] = array(
                        'group_name' => $groups[$row[2]],
                        'cats'       => array()
                    );
                }

                // Indent level for child categories
                $indent = ($row[5] > 1) ? str_repeat(NBS, $row[5] - 1) : '';

                // Add category itself to array
                $categories[$row[2]]['cats'][$row[0]] = array(
                    'name' => $row[1],
                    'indent' => $indent
                );
            }
        }

        // Add categories array to data
        $this->data['categories'] = $categories;

        // --------------------------------------
        // Check if we need to preview
        // --------------------------------------

        if (ee()->input->get('preview') == 'yes') {
            // Move this bulk to different method for clarity
            $this->_show_preview();
        }

        // --------------------------------------
        // Check if we need to show feedback message
        // --------------------------------------

        if ($feedback = ee()->session->flashdata('replace_feedback')) {
            $this->data['feedback'] = pro_search_decode($feedback);
        }

        // --------------------------------------
        // Form action
        // --------------------------------------

        $this->data['action'] = $this->mcp_url('find', 'preview=yes');

        // --------------------------------------
        // Set title and breadcrumb
        // --------------------------------------

        $this->_set_cp_var('cp_page_title', lang('find_replace'));
        $this->_set_cp_crumb($this->mcp_url(), lang('pro_search_module_name'));
        $this->active = 'find';

        return $this->view('find_replace');
    }

    /**
     * Show preview table based on given keywords and fields
     *
     * @access      public
     * @return      string
     */
    private function _show_preview()
    {
        // --------------------------------------
        // Check prerequisites
        // --------------------------------------

        $member_id = ee()->session->userdata('member_id');
        $keywords = ee()->input->post('keywords');
        $fields = ee()->input->post('fields');
        $cats = ee()->input->post('cats');

        if (! ($keywords && $fields)) {
            if (is_ajax()) {
                die('No keywords or fields given.');
            } else {
                return;
            }
        }

        // Save this POST data as encoded data, so we know that what has been
        // previewed, is also used for the actual replacement
        $this->data['encoded_preview'] = pro_search_encode($_POST);
        $this->data['keywords'] = htmlspecialchars($keywords);

        // --------------------------------------
        // Get permitted categories, if it's installed
        // --------------------------------------

        $allowed_cats = ($this->member_group == 1) ? false : $this->_get_permitted_categories($member_id, true);
        $selected_cats = empty($cats) ? array() : $cats;

        // --------------------------------------
        // Compose query to get the matching entries
        // --------------------------------------

        $builder = ee('Model')
            ->get('ChannelEntry');

        // Loop thru each channel and its fields
        foreach ($fields as $channel_id => $field_ids) {
            $builder->orFilterGroup();
            $builder->filter('channel_id', $channel_id);

            $builder->filterGroup();
            // Per field, we need to add the LIKE clause to search it
            foreach ($field_ids as $attr) {
                // Field id could be numeric (for field_id_1) or not (for title)
                if (is_numeric($attr)) {
                    $attr = 'field_id_' . $attr;
                }

                $builder->orFilter($attr, 'LIKE', "%{$keywords}%");
            }

            $builder->endFilterGroup();
            $builder->endFilterGroup();
        }

        // Limit to user's own entries
        if ($this->member_group != 1 && ee()->session->userdata('can_edit_other_entries') == 'n') {
            //ee()->db->where('t.author_id', $member_id);
            $builder->filter('author_id', $member_id);
        }

        // Join category_posts if necessary
        if ($selected_cats) {
            $builder->with('Categories');
            $builder->filter('Categories.cat_id', 'IN', $selected_cats);
        }

        $builder->order('entry_id', 'desc');
        $builder->limit(static::PREVIEW_LIMIT);

        $query = $builder->all();

        // --------------------------------------
        // Create nested array from results, with match preview
        // --------------------------------------

        $preview = array();
        $keyword_length = strlen($keywords);

        foreach ($query as $row) {
            $row = $row->toArray();

            $row['matches'] = array();

            foreach ($fields[$row['channel_id']] as $field_id) {
                // Field name shortcut
                $field = (is_numeric($field_id) ? $row['field_id_' . $field_id] : $row[$field_id]);

                if ($matches = pro_strpos_all($field, $keywords)) {
                    $subs = pro_substr_pad($field, $matches, $keyword_length, static::PREVIEW_PAD);
                    $subs = array_map('htmlspecialchars', $subs);
                    foreach ($subs as &$sub) {
                        $sub = pro_hilite($sub, htmlspecialchars($keywords));
                    }
                    $row['matches'][$field_id] = $subs;
                }
            }

            $row['edit_entry_url'] = ee('CP/URL', 'publish/edit/entry/' . $row['entry_id'])->compile();

            if ($row['matches']) {
                $preview[] = $row;
            }
        }

        $this->data['preview'] = $preview;

        // --------------------------------------
        // Create form action
        // --------------------------------------

        $this->data['form_action'] = $this->mcp_url('replace');

        // --------------------------------------
        // If Ajax request, load parial view and exit
        // --------------------------------------

        if (is_ajax()) {
            //  Do CSRF jig
            //$this->_add_csrf_tokens_to_view();

            die(ee()->load->view('ajax_preview', $this->data, true));
        }
    }

    /**
     * Perform find & replace in DB
     *
     * @access      public
     * @return      void
     */
    public function replace()
    {
        if (! ($data = ee()->input->post('encoded_preview'))) {
            ee()->functions->redirect($this->mcp_url('find'));
            exit;
        }

        $data = pro_search_decode($data);
        $keywords = $data['keywords'];
        $replacement = ee()->input->post('replacement');
        $entries = ee()->input->post('entries');

        if (! ($data && $entries)) {
            ee()->functions->redirect($this->mcp_url('find'));
            exit;
        }

        // --------------------------------------
        // Compose all needed queries
        // --------------------------------------

        $sql = array();
        $sql_replace_tmpl = "%s = REPLACE(%s, '%s', '%s')";
        $sql_update_tmpl = "UPDATE %s SET %s WHERE entry_id IN (%s);";
        $sql_keywords = ee()->db->escape_str($keywords);
        $sql_replacement = ee()->db->escape_str($replacement);
        $all_entries = array();

        foreach ($entries as $channel_id => $entry_ids) {
            // initiate arrays per channel
            $tables = array();

            // Get field ids
            $field_ids = $data['fields'][$channel_id];

            // SQL safe entry ids
            $sql_entries = implode(',', $entry_ids);

            // Loop thru each field id and add update statement to batch
            foreach ($field_ids as $field_id) {
                if (is_numeric($field_id)) {
                    $field = 'field_id_' . $field_id;

                    // EE4 table
                    $table = 'channel_data_field_' . $field_id;
                    if (ee()->db->table_exists($table)) {
                        $tables[$table][] = sprintf($sql_replace_tmpl, $field, $field, $sql_keywords, $sql_replacement);
                    }

                    // Legacy table
                    $table = 'channel_data';
                    if (ee()->db->table_exists($table) && ee()->db->field_exists($field, $table)) {
                        $tables[$table][] = sprintf($sql_replace_tmpl, $field, $field, $sql_keywords, $sql_replacement);
                    }
                } else {
                    $tables['channel_titles'][] = sprintf($sql_replace_tmpl, $field_id, $field_id, $sql_keywords, $sql_replacement);
                }

                // Replace Grid/Matrix columns?
                if (
                    ($is_grid = $this->_field_is_type($field_id, 'grid')) ||
                    ($is_matrix = $this->_field_is_type($field_id, 'matrix'))
                ) {
                    $col_table = $is_grid ? 'grid_columns' : 'matrix_cols';
                    $data_table = $is_grid ? 'channel_grid_field_' . $field_id : 'matrix_data';

                    // Replace SQL for searchable columns
                    foreach ($this->_get_cols($field_id, $col_table) as $col_id) {
                        $col = 'col_id_' . $col_id;
                        $tables[$data_table][] = sprintf($sql_replace_tmpl, $col, $col, $sql_keywords, $sql_replacement);
                    }
                }
            }

            // Add query to change edit date
            $tables['channel_titles'][] = sprintf("edit_date = '%s'", ee()->localize->now);

            // Compose SQL from each table and statements
            foreach ($tables as $table => $statements) {
                $sql[] = sprintf(
                    $sql_update_tmpl,
                    ee()->db->dbprefix . $table,
                    implode(', ', $statements),
                    $sql_entries
                );
            }

            // Add entry_ids to all_entries
            $all_entries = array_unique(array_merge($all_entries, $entry_ids));
        }

        // --------------------------------------
        // Execute all queries!
        // --------------------------------------

        foreach ($sql as $query) {
            ee()->db->query($query);
        }

        // --------------------------------------
        // Update index for affected entries
        // --------------------------------------

        ee()->load->library('Pro_search_index');
        ee()->pro_search_index->build_by_entry($all_entries);

        // --------------------------------------
        // Clear cache
        // --------------------------------------

        ee()->functions->clear_caching('all', '', true);

        // --------------------------------------
        // Add to replace log
        // --------------------------------------

        ee()->pro_search_replace_log_model->insert(array(
            'site_id'      => $this->site_id,
            'member_id'    => ee()->session->userdata['member_id'],
            'replace_date' => ee()->localize->now,
            'keywords'     => $data['keywords'],
            'replacement'  => $replacement,
            'fields'       => pro_search_encode($data['fields'], false),
            'entries'      => '|' . implode('|', $all_entries) . '|'
        ));

        // -------------------------------------
        // 'pro_search_post_replace' hook.
        //  - Do something after the replace action
        // -------------------------------------

        if (ee()->extensions->active_hook('pro_search_post_replace') === true) {
            ee()->extensions->call('pro_search_post_replace', $all_entries);
        }

        // --------------------------------------
        // Set feedback msg
        // --------------------------------------

        $this->data['feedback'] = array(
            'keywords'      => $keywords,
            'replacement'   => $replacement,
            'total_entries' => count($all_entries)
        );

        // --------------------------------------
        // Go back to F&R home
        // --------------------------------------

        if (is_ajax()) {
            die(ee()->load->view('ajax_replace_feedback', $this->data, true));
        } else {
            ee()->session->set_flashdata('replace_feedback', pro_search_encode($this->data['feedback']));
            ee()->functions->redirect($this->mcp_url('find'));
        }
    }

    // --------------------------------------------------------------------

    /**
     * View replace log
     *
     * @access      public
     * @return      string
     */
    public function replace_log()
    {
        // --------------------------------------
        // Get member ID
        // --------------------------------------

        $member_id = ee()->session->userdata('member_id');

        // --------------------------------------
        // Get total rows of log
        // --------------------------------------

        if ($this->member_group != 1) {
            ee()->db->where('member_id', $member_id);
        }

        $total = ee()->pro_search_replace_log_model->get_site_count();

        // --------------------------------------
        // Init table
        // --------------------------------------

        $table = ee('CP/Table', array(
            'sortable' => false,
            'class'    => 'replace-log'
        ));

        // No results
        $table->setNoResultsText('replace_log_is_empty');

        // Table columns
        $table->setColumns(array(
            'keywords',
            'replacement',
            'member',
            'date',
            'affected_entries' => array('encode' => false),
        ));

        // Table rows
        $rows = array();

        // --------------------------------------
        // If this site has logs, generate this lot
        // --------------------------------------

        if ($total) {
            // Pagination: get page var from GET and determine start row from it
            $page = ee('Request')->get('page', 1);
            $start = ($page - 1) * static::VIEW_LOG_LIMIT;

            // Generate pagination, if necessary
            if ($total > static::VIEW_LOG_LIMIT) {
                $this->data['pagination'] = ee('CP/Pagination', $total)
                    ->currentPage($page)
                    ->perPage(static::VIEW_LOG_LIMIT)
                    ->render($this->mcp_url('replace_log', null, true));
            }

            // Get search log
            ee()->db
                ->select('members.screen_name, pro_search_replace_log.*')
                ->join('members', 'members.member_id = pro_search_replace_log.member_id', 'left')
                ->where('site_id', $this->site_id)
                ->order_by('replace_date', 'desc')
                ->limit(static::VIEW_LOG_LIMIT, $start);

            // Filter by member_id if not a superadmin
            if ($this->member_group != 1) {
                ee()->db->where('members.member_id', $member_id);
            }

            if ($log = ee()->pro_search_replace_log_model->get_all()) {
                // Set pagination status
                $this->data['status'] = sprintf(
                    lang('viewing_rows'),
                    $start + 1,
                    (($to = $start + static::VIEW_LOG_LIMIT) > $total) ? $total : $to,
                    $total
                );

                // Add rows to data table
                foreach ($log as $l) {
                    // Keywords
                    $row = array($l['keywords']);

                    // Replacement
                    $row[] = $l['replacement'];

                    // Member
                    $row[] = $l['member_id'] ? $l['screen_name'] : '--';

                    // Date
                    $row[] = ee()->localize->human_time($l['replace_date']);

                    // Affected entries
                    $row[] = sprintf(
                        '<a class="m-link" rel="modal-replace-details" href="%s">%d</a>',
                        $this->mcp_url('replace_details/' . $l['log_id']),
                        count(array_filter(explode('|', $l['entries'])))
                    );

                    // Add to rows
                    $rows[] = $row;
                }
            }

            // Clear replace log for superadmins
            if ($this->member_group == 1) {
                // Add bulk action to view
                $this->data['bulk_action'] = $this->mcp_url('clear_replace_log');
                $this->data['bulk_button'] = lang('clear_replace_log');
            }

            // Add modal for log details
            ee('CP/Modal')->addModal(
                'replace-details',
                ee('View')->make('ee:_shared/modal')->render(array(
                    'name'     => 'modal-replace-details',
                    'contents' => ''
                ))
            );
        }

        // --------------------------------------
        // Set the table data with generated rows and add to view
        // --------------------------------------

        $table->setData($rows);

        $this->data['table'] = $table->viewData();

        // --------------------------------------
        // Set title and breadcrumb and view page
        // --------------------------------------

        $this->_set_cp_var('cp_page_title', lang('replace_log'));
        $this->_set_cp_crumb($this->mcp_url(), lang('pro_search_module_name'));

        // Active menu item
        $this->active = 'replace_log';

        return $this->view('log');
    }

    /**
     * Clear the replace log for current site
     *
     * @access      public
     * @return      void
     */
    public function clear_replace_log()
    {
        // Delete
        ee()->db->where('site_id', $this->site_id);
        ee()->db->delete(ee()->pro_search_replace_log_model->table());

        // Set message
        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('replace_log_cleared'))
            ->defer();

        // And go back
        ee()->functions->redirect($this->mcp_url('replace_log'));
    }

    /**
     * View replace details, called by ajax in modal
     *
     * @access      public
     * @return      string
     */
    public function replace_details($log_id)
    {
        // --------------------------------------
        // Get the id and row of details
        // --------------------------------------

        $log = ee()->pro_search_replace_log_model->get_one($log_id);

        $entry_ids = array_filter(explode('|', $log['entries']));

        // --------------------------------------
        // Get titles for entries
        // --------------------------------------

        $query = ee()->db
            ->select('t.entry_id, t.title, c.channel_title')
            ->from('channel_titles t')
            ->join('channels c', 't.channel_id = c.channel_id', 'inner')
            ->where_in('t.entry_id', $entry_ids)
            ->order_by('t.entry_id', 'desc')
            ->get();

        // --------------------------------------
        // Init table
        // --------------------------------------

        $table = ee('CP/Table', array(
            'sortable' => false,
            'class'    => 'replace-log-details'
        ));

        // No results
        $table->setNoResultsText('replace_log_is_empty');

        // Table columns
        $table->setColumns(array(
            'id',
            'title',
            'channel'
        ));

        // Table rows
        $rows = array();

        foreach ($query->result() as $r) {
            $rows[] = array(
                $r->entry_id,
                array(
                    'href' => ee('CP/URL', 'publish/edit/entry/' . $r->entry_id)->compile(),
                    'content' => $r->title
                ),
                $r->channel_title
            );
        }

        $table->setData($rows);

        $output = ee('View')->make('ee:_shared/table')->render($table->viewData());

        if (is_ajax()) {
            die($output);
        }
    }

    // --------------------------------------------------------------------

    /**
     * View search log
     *
     * @access      public
     * @return      string
     */
    public function search_log($filter = null)
    {
        // --------------------------------------
        // Keep track of this page's path
        // --------------------------------------

        $path = __FUNCTION__;

        // --------------------------------------
        // Check if filter form was posted
        // --------------------------------------

        if ($f = ee('Request')->post('filter')) {
            // Encode it and add it to the current path
            if ($f = array_filter($f, 'pro_not_empty')) {
                $path .= '/' . pro_search_encode($f);
            }

            // Go to same url with encoded filter
            ee()->functions->redirect($this->mcp_url($path));
        }

        // --------------------------------------
        // Get total, unfiltered, rows of log
        // --------------------------------------

        $max = (int) ee()->pro_search_settings->get('search_log_size');
        $total = (int) ee()->pro_search_log_model->get_site_count();

        // Prune now?
        if ($max && $total > $max) {
            ee()->pro_search_log_model->prune($this->site_id, $max);
            $total = $max;
        }

        // --------------------------------------
        // Init table
        // --------------------------------------

        $table = ee('CP/Table', array(
            'sortable' => false,
        ));

        // No results
        $table->setNoResultsText($total ? 'no_matching_rows' : 'search_log_is_empty');

        // Table columns
        $table->setColumns(array(
            'keywords',
            'num_results',
            'member',
            'ip_address',
            'search_date',
            'parameters' => array('encode' => false),
            array('type' => Table::COL_TOOLBAR)
        ));

        // Table rows
        $rows = array();

        // --------------------------------------
        // If this site has logs, generate this lot
        // --------------------------------------

        if ($total) {
            // Add export action link
            $this->data['actions']['export_search_log'] = $this->mcp_url('export_search_log');

            // Check for given filters
            $filters = is_string($filter)
                ? pro_search_decode($filter)
                : array();

            // Expand path with filter
            if (is_string($filter)) {
                $path .= '/' . $filter;
            }

            // Generate the filters form
            // Initiate view data
            $data = array('active' => $filters);

            // Get all unique members
            $member_ids = ee()->pro_search_log_model->get_member_ids();
            $members = ee()->db
                ->select('member_id, screen_name')
                ->from('members')
                ->where_in('member_id', $member_ids)
                ->order_by('screen_name', 'asc')
                ->get()->result_array();

            $members = pro_flatten_results($members, 'screen_name', 'member_id');
            $data['members'] = $members;

            // Get all days of searching
            $data['dates'] = ee()->pro_search_log_model->get_dates();

            // Add form action to data
            $data['filter_url'] = $this->mcp_url('search_log');

            // Generate view
            $this->data['filters'] = ee('View')
                ->make($this->package . ':search_log_filters')
                ->render($data);

            // If there are filters, add site_id and re-calculate the total
            if ($filters) {
                $filters['site_id'] = $this->site_id;
                $total = (int) ee()->pro_search_log_model->get_filtered_count($filters);
            }

            // Pagination: get page var from GET and determine start row from it
            $page = ee('Request')->get('page', 1);
            $start = ($page - 1) * static::VIEW_LOG_LIMIT;

            // Generate pagination, if necessary
            if ($total > static::VIEW_LOG_LIMIT) {
                $this->data['pagination'] = ee('CP/Pagination', $total)
                    ->currentPage($page)
                    ->perPage(static::VIEW_LOG_LIMIT)
                    ->render($this->mcp_url($path, null, true));
            }

            // Get search log, taking pagination into account
            ee()->db->order_by('search_date', 'desc');
            ee()->db->limit(static::VIEW_LOG_LIMIT, $start);

            if ($log = ee()->pro_search_log_model->get_filtered_rows($filters)) {
                // Set log status
                $this->data['status'] = sprintf(
                    lang('viewing_rows'),
                    $start + 1,
                    (($to = $start + static::VIEW_LOG_LIMIT) > $total) ? $total : $to,
                    $total
                );

                // Generate table rows
                foreach ($log as $row) {
                    // Keywords
                    $r = array($row['keywords']);

                    // Num results
                    $r[] = !empty($row['num_results']) ? number_format($row['num_results']) : 0;

                    // Member
                    $r[] = isset($members[$row['member_id']])
                        ? $members[$row['member_id']]
                        : '';

                    // IP
                    $r[] = $row['ip_address'];

                    // Search date
                    $r[] = ee()->localize->human_time($row['search_date']);

                    // Parameters
                    if ($p = pro_search_decode($row['parameters'], false)) {
                        $r[] = array(
                            'attrs' => array('class' => 'params'),
                            'html'  => ee('View')
                                ->make($this->package . ':search_log_params')
                                ->render(array('params' => $p))
                        );
                    } else {
                        $r[] = '';
                    }

                    // Shortcut toolbar
                    $r[] = array(
                        'toolbar_items' => array(
                            // 'view' => array(
                            //  'href'  => '#',
                            //  'title' => 'View details'
                            // ),
                            'next' => array(
                                'href'  => $this->mcp_url('edit_shortcut/new', 'log_id=' . $row['log_id']),
                                'title' => lang('create_shortcut_from_log')
                            )
                        )
                    );

                    // Add row to table body
                    $rows[] = $r;
                }
            }

            // Add bulk action to view
            $this->data['bulk_action'] = $this->mcp_url('clear_search_log');
            $this->data['bulk_button'] = lang('clear_search_log');
        }

        // --------------------------------------
        // Set the table data with generated rows and add to view
        // --------------------------------------

        $table->setData($rows);

        $this->data['table'] = $table->viewData();

        // --------------------------------------
        // Set title and breadcrumb and view page
        // --------------------------------------

        $this->_set_cp_var('cp_page_title', lang('search_log'));
        $this->_set_cp_crumb($this->mcp_url(), lang('pro_search_module_name'));

        // Active menu item
        $this->active = 'search_log';

        return $this->view('log');
    }

    /**
     * Clear the search log for current site
     *
     * @access      public
     * @return      void
     */
    public function clear_search_log()
    {
        // Delete
        ee()->db->where('site_id', $this->site_id);
        ee()->db->delete(ee()->pro_search_log_model->table());

        // Set message
        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('search_log_cleared'))
            ->defer();

        // And go back
        ee()->functions->redirect($this->mcp_url('search_log'));
    }

    /**
     * Download/export search log
     *
     * @access      public
     * @return      void
     */
    public function export_search_log()
    {
        // --------------------------------------
        // Load download helper
        // --------------------------------------

        ee()->load->helper('download');

        // --------------------------------------
        // Table/prefix
        // --------------------------------------

        $t = ee()->pro_search_log_model->table();

        // --------------------------------------
        // Get all log records
        // --------------------------------------

        $query = ee()->db
            ->select(array("{$t}.parameters", "{$t}.keywords",
                "{$t}.num_results", 'members.screen_name AS member',
                "{$t}.ip_address", "FROM_UNIXTIME({$t}.search_date) AS `date`"))
            ->from($t)
            ->join('members', "members.member_id = {$t}.member_id", 'left')
            ->where('site_id', $this->site_id)
            ->order_by('search_date', 'desc')
            ->get();

        // --------------------------------------
        // Get keys
        // --------------------------------------

        $keys = array_keys($query->row_array());
        $pk = array_shift($keys);

        // --------------------------------------
        // Get additional keys from params
        // --------------------------------------

        foreach ($query->result_array() as $row) {
            // Get the params
            $params = pro_search_decode(array_shift($row), false);

            foreach (array_keys($params) as $key) {
                if (array_search($key, $keys) === false) {
                    $keys[] = $key;
                }
            }
        }

        // --------------------------------------
        // Fill the log
        // --------------------------------------

        $log = array();
        $log[] = $this->_csv_row($keys);

        foreach ($query->result_array() as $row) {
            $params = array_shift($row);
            $params = pro_search_decode($params, false);
            $row = array_merge($row, $params);

            $log_row = array();

            foreach ($keys as $k => $v) {
                $log_row[$k] = isset($row[$v]) ? $row[$v] : '';
            }

            $log[] = $this->_csv_row($log_row);
        }

        $log = implode("\n", $log);

        // --------------------------------------
        // File name
        // --------------------------------------

        $name = 'search_log_' . date('YmdHi') . '.csv';

        // --------------------------------------
        // And download it
        // --------------------------------------

        force_download($name, $log);
    }

    /**
     * Transform an array to a CSV row
     *
     * @access     private
     * @param      array
     * @return     string
     */
    private function _csv_row($array)
    {
        $row = array();

        foreach ($array as $val) {
            // CSV-escaping of double quotes by doubling double quotes
            $row[] = '"' . str_replace('"', '""', $val) . '"';
        }

        return implode(',', $row);
    }

    // --------------------------------------------------------------------

    /**
     * Builds stuff index
     *
     * @access      public
     * @return      string
     */
    public function build()
    {
        // --------------------------------------
        // Only members are allowed to do this
        // --------------------------------------

        if (! ee()->session->userdata('member_id')) {
            show_error('Operation not permitted');
        }

        // --------------------------------------
        // Load index library
        // --------------------------------------

        ee()->load->library('pro_search_index');

        // --------------------------------------
        // Get info from Query String
        // --------------------------------------

        $build = ee()->input->post('build');
        $rebuild = ee()->input->post('rebuild');

        $col_id = (int) ee()->input->post('collection_id');
        $start = (int) ee()->input->post('start');

        // --------------------------------------
        // Delete existing collection if rebuild == 'yes'
        // --------------------------------------

        if ($start === 0 && $rebuild == 'yes') {
            ee()->pro_search_index_model->delete($col_id, 'collection_id');
        }

        // --------------------------------------
        // Call private build_index method
        // --------------------------------------

        $response = ee()->pro_search_index->build_batch($col_id, $start, $build);

        // --------------------------------------
        // Optimize table when we're done
        // --------------------------------------

        if ($response === true) {
            ee()->pro_search_index_model->optimize();
        }

        // --------------------------------------
        // Return JSON
        // --------------------------------------

        if (is_ajax()) {
            die(json_encode($response));
        }
    }

    // --------------------------------------------------------------------
    // PRIVATE METHODS
    // --------------------------------------------------------------------

    /**
     * Get array of channel_id => cat_ids for this member
     *
     * @access      private
     * @param       int
     * @return      array
     */
    private function _get_permitted_categories($member_id, $nested = false)
    {
        $categories = false;

        // --------------------------------------
        // Bail out if category permissions ext is not installed
        // --------------------------------------

        $package = 'category_permissions';

        if (array_key_exists($package, ee()->addons->get_installed('extensions'))) {
            $categories = array();

            // Load CatPerm model so we can use their stuff
            ee()->load->add_package_path(PATH_THIRD . $package);
            ee()->load->model($package . '_model', $package);

            // Get array of category ids
            if (ee()->$package->member_has_category_permissions($member_id)) {
                $cat_ids = ee()->$package->get_member_permitted_categories($member_id);
            } else {
                $cat_ids = array();
            }

            // Clean up after us
            ee()->load->remove_package_path(PATH_THIRD . $package);

            // If we have categories, associate them with group ids
            if ($cat_ids) {
                // $query = ee()->db->select('t.channel_id, t.entry_id')
                //        ->from('channel_titles t')
                //        ->join('category_posts cp', 't.entry_id = cp.entry_id', 'left')
                //        ->where_in('cp.cat_id', $cat_ids)
                //        ->or_where('cp.cat_id IS NULL')
                //        ->get();
                //
                // foreach ($query->result() AS $row)
                // {
                //  $categories[$row->channel_id][] = $row->entry_id;
                // }

                if ($nested === false) {
                    return $cat_ids;
                }

                $cats_by_group = array();

                // First, organize categories by category group
                $query = ee()->db->select('cat_id, group_id')
                    ->from('categories')
                    ->where('site_id', $this->site_id)
                    ->where_in('cat_id', $cat_ids)
                    ->get();

                foreach ($query->result() as $row) {
                    $cats_by_group[$row->group_id][] = $row->cat_id;
                }

                // Then get channel and their cat groups
                $channels = ee('Model')
                    ->get('Channel')
                    ->with('CategoryGroups')
                    ->filter('site_id', $this->site_id)
                    ->all();

                // And associate channel with cat ids
                foreach ($channels as $channel) {
                    foreach ($channel->CategoryGroups as $group) {
                        if (! isset($categories[$row->channel_id])) {
                            $categories[$row->channel_id] = array();
                        }

                        $categories[$row->channel_id] = array_merge(
                            $categories[$row->channel_id],
                            $cats_by_group[$group->group_id]
                        );
                    }
                }
            }
        }

        return $categories;
    }

    // --------------------------------------------------------------------

    /**
     * Is given field ID a of given type field?
     *
     * @access      private
     * @param       int
     * @param       string
     * @return      mixed [int|bool]
     */
    private function _field_is_type($id, $type)
    {
        static $fields = array();

        if (empty($fields)) {
            $query = ee()->db->select('field_id, field_type')
                ->from('channel_fields')
                ->where('site_id', $this->site_id)
                ->get();

            $fields = pro_flatten_results($query->result_array(), 'field_type', 'field_id');
        }

        return (isset($fields[$id]) && $fields[$id] == $type);
    }

    /**
     * Get array of column ids for given Grid or Matrix field
     *
     * @access      private
     * @param       int
     * @param       string
     * @return      array
     */
    private function _get_cols($id, $table = 'grid_columns')
    {
        static $cols = array();

        if (! isset($cols[$table][$id])) {
            $query = ee()->db->select('col_id')
                ->from($table)
                ->where('field_id', $id)
                ->where('col_search', 'y')
                ->get();

            $cols[$table][$id] = pro_flatten_results($query->result_array(), 'col_id');
        }

        return $cols[$table][$id];
    }

    // --------------------------------------------------------------------

    /**
     * Permissions: can current user manage collections?
     *
     * @access      protected
     * @return      bool
     */
    protected function can_manage()
    {
        return $this->_can_i('manage');
    }

    /**
     * Permissions: can current user manage the lexicon?
     *
     * @access      protected
     * @return      bool
     */
    protected function can_manage_lexicon()
    {
        return $this->_can_i('manage_lexicon');
    }

    /**
     * Permissions: can current user manage shortcuts?
     *
     * @access      protected
     * @return      bool
     */
    protected function can_manage_shortcuts()
    {
        return $this->_can_i('manage_shortcuts');
    }

    /**
     * Permissions: can current user find and replace?
     *
     * @access      protected
     * @return      bool
     */
    protected function can_replace()
    {
        return $this->_can_i('replace');
    }

    /**
     * Permissions: can current user view search log?
     *
     * @access      protected
     * @return      bool
     */
    protected function can_view_search_log()
    {
        return $this->_can_i('view_search_log');
    }

    /**
     * Permissions: can current user view replace log?
     *
     * @access      protected
     * @return      bool
     */
    protected function can_view_replace_log()
    {
        return $this->_can_i('view_replace_log');
    }

    /**
     * Can I do what? SuperAdmins always can.
     *
     * @access      private
     * @return      bool
     */
    private function _can_i($do_what)
    {
        $can = (array) ee()->pro_search_settings->get('can_' . $do_what);

        return ($this->member_group === 1 || in_array($this->member_group, $can));
    }

    // --------------------------------------------------------------------

    /**
     * Set cp var
     *
     * @access     private
     * @param      string
     * @param      string
     * @return     void
     */
    private function _set_cp_var($key, $val)
    {
        ee()->view->$key = $val;

        if ($key == 'cp_page_title') {
            $this->heading = $val;
            $this->data[$key] = $val;
        }
    }

    /**
     * Set cp breadcrumb
     *
     * @access     private
     * @param      string
     * @param      string
     * @return     void
     */
    private function _set_cp_crumb($url, $text)
    {
        $this->crumb[$url] = $text;
    }

    /**
     * View add-on page
     *
     * @access     protected
     * @param      string
     * @return     string
     */
    private function view($file)
    {
        // -------------------------------------
        //  Load CSS and JS
        // -------------------------------------

        $version = '&amp;v=' . (static::DEBUG ? time() : $this->version);

        ee()->cp->load_package_css($this->package . $version);
        ee()->cp->load_package_js($this->package . $version);

        // -------------------------------------
        //  Add permissions to data
        // -------------------------------------

        foreach (ee()->pro_search_settings->permissions() as $perm) {
            $this->data['member_' . $perm] = $this->$perm();
        }

        $this->data['member_group'] = $this->member_group;

        // -------------------------------------
        //  Add JS language object
        // -------------------------------------

        $lang = array();
        $js_lang_lines = array(
            'deleting',
            'optimizing',
            'done',
            'no_keywords_given',
            'no_fields_selected',
            'no_entries_selected',
            'working'
        );

        foreach ($js_lang_lines as $line) {
            $lang[$line] = lang($line);
        }

        ee()->javascript->set_global('PRO_lang', $lang);

        // -------------------------------------
        //  Main page header
        // -------------------------------------

        // Define header
        $header = array('title' => $this->info->getName());

        // Add manage buttons to header
        if ($this->member_group == 1) {
            $header['toolbar_items'] = array(
                'settings' => array(
                    'href'  => $this->mcp_url('settings'),
                    'title' => lang('settings')
                )
            );
        }

        // And actually set the header
        ee()->view->header = $header;

        // -------------------------------------
        //  Add-on menu
        // -------------------------------------

        $sidebar = ee('CP/Sidebar')->make();

        // Link to Collections
        if ($this->can_manage()) {
            $head = $sidebar->addHeader(lang('collections'), $this->mcp_url('collections'))
                ->withButton(lang('new'), $this->mcp_url('edit_collection/new'));

            // Is it active?
            if ($this->active == 'collections') {
                $head->isActive();
            }
        }

        // Link to Shortcuts
        if ($this->can_manage_shortcuts()) {
            $head = $sidebar->addHeader(lang('groups'), $this->mcp_url('groups'))
                ->withButton(lang('new'), $this->mcp_url('edit_group/new'));

            // Is it active?
            if ($this->active == 'groups') {
                $head->isActive();
            }
        }

        // Utilities
        if ($this->can_manage_lexicon() || $this->can_replace()) {
            $head = $sidebar->addHeader('Utilities');
            $list = $head->addBasicList();

            if ($this->can_manage_lexicon()) {
                $item = $list->addItem(lang('lexicon'), $this->mcp_url('lexicon'));

                // Is it active?
                if ($this->active == 'lexicon') {
                    $item->isActive();
                }
            }

            if ($this->can_replace()) {
                $item = $list->addItem(lang('find_replace'), $this->mcp_url('replace'));

                // Is it active?
                if ($this->active == 'find') {
                    $item->isActive();
                }
            }
        }

        // Logs
        if ($this->can_view_search_log() || $this->can_view_replace_log()) {
            $head = $sidebar->addHeader('Logs');
            $list = $head->addBasicList();

            if ($this->can_view_search_log()) {
                $item = $list->addItem(lang('search_log'), $this->mcp_url('search_log'));

                // Is it active?
                if ($this->active == 'search_log') {
                    $item->isActive();
                }
            }

            if ($this->can_view_replace_log()) {
                $item = $list->addItem(lang('replace_log'), $this->mcp_url('replace_log'));

                // Is it active?
                if ($this->active == 'replace_log') {
                    $item->isActive();
                }
            }
        }

        // -------------------------------------
        //  Return the view
        // -------------------------------------

        $view = array(
            'heading' => $this->heading,
            'breadcrumb' => $this->crumb,
            'body' => ee('View')->make($this->package . ':' . $file)->render($this->data)
        );

        return $view;
    }
}
// End Class

/* End of file mcp.pro_search.php */
