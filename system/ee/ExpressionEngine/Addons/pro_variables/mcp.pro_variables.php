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
if (! class_exists('Pro_variables_base')) {
    require_once(PATH_ADDONS . 'pro_variables/base.pro_variables.php');
}

/**
 * Pro Variables Module Class - CP
 */
class Pro_variables_mcp
{
    // Use the base trait
    use Pro_variables_base;

    // --------------------------------------------------------------------
    // CONSTANTS
    // --------------------------------------------------------------------

    public const DEBUG = false;

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Shortcut to libraries
     *
     * @access      private
     * @var         object
     */
    public $settings;
    private $types;
    private $sync;

    /**
     * Shortcut to models
     *
     * @access      private
     * @var         object
     */
    private $vars;
    private $groups;

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

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access     public
     * @return     void
     */
    public function __construct()
    {
        // Initialize base data for addon
        $this->initializeBaseData();

        // Set shortcuts
        $this->settings = & ee()->pro_variables_settings;
        $this->types = & ee()->pro_variables_types;
        $this->sync = & ee()->pro_variables_sync;
        $this->vars = & ee()->pro_variables_variable_model;
        $this->groups = & ee()->pro_variables_group_model;
    }

    // --------------------------------------------------------------------
    //  EDIT VARIABLE DATA
    // --------------------------------------------------------------------

    /**
     * Main index page displays a group
     */
    public function index()
    {
        return $this->group();
    }

    /**
     * Home screen
     *
     * @access     public
     * @return     string
     */
    public function group($group_id = null)
    {
        // -------------------------------------
        //  Load sync lib and sync with native global_vars
        // -------------------------------------

        $this->sync->native();

        // -------------------------------------
        //  Get the groups
        // -------------------------------------

        if (! ($groups = $this->get_groups())) {
            $this->data['msg'] = 'no_variables_found';

            return $this->view('no-results');
        }

        // -------------------------------------
        //  Get current group ID and set it to the first if not given
        // -------------------------------------

        // Make it the first one if not given
        if (is_null($group_id) && count($groups)) {
            reset($groups);
            $first = current($groups);
            $group_id = $first['group_id'];
        } elseif (! in_array($group_id, array_keys($groups))) {
            // Show error if group is unknown
            show_404();
        }

        // Focus on active group
        $group = $groups[$group_id];

        // -------------------------------------
        //  Get variables
        // -------------------------------------

        // Filter out hidden vars
        if (! $this->settings->can_manage()) {
            ee()->db->where('is_hidden', 'n');
        }

        $vars = $this->vars->get_by_group($group_id);

        // Init sections
        $sections = array();
        $section = 0;

        // Group instructions
        if (! empty($group['group_notes'])) {
            $sections[$section][] = PVUI::text($group['group_notes']);
        }

        // -------------------------------------
        //  Do we have skipped vars?
        // -------------------------------------

        $skipped = (array) ee()->session->flashdata('skipped');
        $labels = array();

        // -------------------------------------
        //  Loop thru vars and add custom data
        // -------------------------------------

        if ($vars) {
            // @TODO: remove, as EE should do this
            ee()->lang->loadfile('content');

            // Load enabled types
            $types = $this->types->load_enabled();

            // Loop through each var and generate input field
            foreach ($vars as $var) {
                if ($var['variable_type'] == 'pro_rte') {
                    $var['variable_type'] = 'rte';
                }
                // Get variable type object from var row
                $obj = $this->types->get($var);

                // Split by 3 newlines to seperate var header from description
                $tmp = explode("\n\n\n", $var['variable_notes'], 2);

                // If we have a header, overwrite $section var
                if (count($tmp) == 2) {
                    list($section, $var['variable_notes']) = $tmp;
                    //$sections[0][] = PVUI::text($text);
                }

                // Attributes to add to this field
                $attrs = array(
                    'data-id' => $obj->id(),
                    'data-type' => $obj->type()
                );

                // Add name to attributes
                if ($this->settings->can_manage()) {
                    $attrs['data-name'] = $var['variable_name'];
                }

                // Add optional error to attributes
                if (array_key_exists($obj->id(), $skipped)) {
                    $attrs['data-error'] = htmlspecialchars(lang($skipped[$obj->id()]), ENT_QUOTES);
                    $labels[] = '<li>' . ($var['variable_label'] ?: $var['variable_name']) . '</li>';
                }

                // Initiate section rowinput row
                $row = array(
                    'title' => $var['variable_label'] ?: $var['variable_name'],
                    'desc'  => $var['variable_notes'] ?: null,
                    'attrs' => $attrs,
                    'wide'  => $obj->wide(),
                    'grid'  => $obj->grid()
                );

                // Get input html from var type
                $input = $obj->display_field($var['variable_data']);

                // If the input is array, use that
                // Otherwise it's raw HTML
                $row['fields'] = is_array($input)
                    ? $input
                    : array(array(
                        'type'    => 'html',
                        'content' => (string) $input
                    ));

                // Add to this lot
                $sections[$section][] = $row;

                // Create buttons
                $buttons = array(array(
                    'name' => '',
                    'type' => 'submit',
                    'value' => '',
                    'text' => 'pro_variables_save',
                    'working' => 'btn_saving',
                    'shortcut' => 's'
                ));

                // Add button for clearing cache
                $clear = ee()->pro_variables_settings->get('clear_cache');

                // Opt in for clear cache
                if (substr($clear, 0, 1) == 'o') {
                    $buttons[] = array(
                        'name' => 'clear_cache',
                        'type' => 'submit',
                        'value' => 'y',
                        'text' => 'save_and_clear',
                        'working' => 'btn_saving'
                    );
                }
            }

            // Show inline alert?
            if ($labels) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asWarning()
                    ->withTitle(lang('skipped_vars'))
                    ->addToBody(lang('pro_variables_saved_except') . '<ul>' . implode("\n", $labels) . '</ul>')
                    ->now();
            }

            $this->data = array(
                'base_url' => $this->mcp_url('save/' . $group_id),
                'has_file_input' => true,
                'buttons' => $buttons,
                'sections' => $sections
            );

            ee()->javascript->set_global('PRO.group_id', $group_id);
            ee()->javascript->set_global('PRO.edit_var_url', $this->mcp_url('edit_var/%d'));

            $view = 'form';
        } else {
            $this->data['msg'] = 'no_vars_in_group';
            $view = 'no-results';
        }

        // -------------------------------------
        //  Title and Crumbs
        // -------------------------------------

        $this->set_cp_var('cp_page_title', $group['group_label']);
        $this->set_cp_crumb($this->mcp_url(), $this->info->getName());

        $this->active = $group_id;

        return $this->view($view);
    }

    /**
     * Saves variable data from home screen
     *
     * @access     public
     * @return     void
     */
    public function save($group_id)
    {
        // -------------------------------------
        // From whence we came
        // -------------------------------------

        $url = $this->mcp_url('group/' . $group_id);

        // -------------------------------------
        // Get vars in group
        // -------------------------------------

        if (empty($_POST) || ! ($vars = $this->vars->get_meta_by_group($group_id))) {
            ee()->functions->redirect($url);
        }

        // Filter out hidden vars for non-managers
        if (! $this->settings->can_manage()) {
            $vars = array_filter($vars, function ($v) {
                return ($v['is_hidden'] == 'n');
            });
        }

        $vars = pro_associate_results($vars, $this->vars->pk());

        // -------------------------------------
        //  Initiate skipped array
        // -------------------------------------

        $skipped = array();

        // -------------------------------------
        //  Loop through posted vars and save new values
        // -------------------------------------

        $types = pro_flatten_results($vars, 'variable_type');
        $types[] = Pro_variables_types::DEFAULT_TYPE;
        $types = $this->types->load(array_unique($types));

        foreach ($vars as $id => $meta) {
            // Get object for this var
            $obj = $this->types->get($meta);

            // Get var data from post
            $data = ee('Request')->post($obj->input_name());

            // Save Input Also Validates!
            // if FALSE is returned, skip this var
            if (($data = $obj->save($data)) === false) {
                $skipped[$id] = $obj->error_msg;

                continue;
            }

            // Update record
            $this->vars->update($id, array(
                'variable_data' => !is_null($data) ? $data : '',
                'edit_date'     => time()
            ));

            // Call post_save
            $obj->post_save($data);
        }

        // -------------------------------------
        //  Display alert or remember skipped
        // -------------------------------------

        if (empty($skipped)) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('saved_vars'))
                ->addToBody(lang('pro_variables_saved'))
                ->defer();
        } else {
            ee()->session->set_flashdata('skipped', $skipped);
        }

        // -------------------------------------
        //  Clear cache?
        // -------------------------------------

        if ($this->settings->clear_cache == 'y' || ee('Request')->post('clear_cache') == 'y') {
            ee()->functions->clear_caching('all', '', true);
        }

        // -------------------------------------
        // 'pro_variables_post_save' hook.
        //  - Do something after Pro Variables are saved
        // -------------------------------------

        if (ee()->extensions->active_hook('pro_variables_post_save') === true) {
            ee()->extensions->call('pro_variables_post_save', array_keys($vars), $skipped);
        }

        // -------------------------------------
        //  Go home
        // -------------------------------------

        ee()->functions->redirect($url);
    }

    // --------------------------------------------------------------------
    //  MANAGE VARIABLES - CRUD
    // --------------------------------------------------------------------

    /**
     * Show table of all variables
     *
     * @access     public
     * @return     string
     */
    public function vars()
    {
        // Redirect if this is not a var manager
        if (! $this->settings->can_manage()) {
            ee()->functions->redirect($this->mcp_url());
        }

        // -------------------------------------
        //  Get the vars
        // -------------------------------------

        $vars = $this->vars->get_meta();

        // -------------------------------------
        //  Get allowed types
        // -------------------------------------

        $types = $this->types->load_enabled();

        // --------------------------------------
        //  Get all groups
        // --------------------------------------

        $groups = $this->get_groups();
        $groups = pro_flatten_results($groups, 'group_label', 'group_id');

        // --------------------------------------
        // Shortcuts to see if early parsing and save as file are enabled
        // --------------------------------------

        $early = $this->settings->get('register_globals') != 'n';
        // $file  = $this->settings->get('save_as_files') == 'y';

        // --------------------------------------
        // Init table
        // --------------------------------------

        $table = ee('CP/Table', array(
            'sortable' => false,
        ));

        // No results
        $table->setNoResultsText('no_variables_found');

        // Columns
        $cols = array(
            'variable_name',
            'variable_label',
            'variable_group',
            'variable_type',
            'is_hidden_th' => array('encode' => false)
        );

        // Optional columns
        if ($early) {
            $cols['early_parsing_th'] = array('encode' => false);
        }
        // if ($file)  $cols['save_as_file_th'] = array('encode' => FALSE);

        // The rest of the columns (clone and checkbox)
        $cols['clone'] = array('type' => Table::COL_TOOLBAR);
        $cols[] = array('type' => Table::COL_CHECKBOX);

        // Table columns
        $table->setColumns($cols);

        // --------------------------------------
        // Initiate table data
        // --------------------------------------

        $rows = array();

        // -------------------------------------
        //  Loop through vars and modify rows
        // -------------------------------------

        foreach ($vars as $var) {
            // Shortcut to ID
            $id = $var['variable_id'];

            // Init row
            $row = array();

            // Name
            $row[] = array(
                'content' => $var['variable_name'],
                'href' => $this->mcp_url('edit_var/' . $id, 'from=vars')
            );

            // Label
            $row[] = $var['variable_label'];

            // Group
            $row[] = array(
                'content' => $groups[$var['group_id']],
                'href' => $this->mcp_url('edit_group/' . $var['group_id'])
            );

            // Type
            $type = array_key_exists($var['variable_type'], $types)
                ? $var['variable_type']
                : ($var['variable_type'] == 'pro_rte' ? 'rte' : Pro_variables_types::DEFAULT_TYPE);

            $row[] = $types[$type]['name'];

            // Hidden
            $row[] = PVUI::onoff($this->mcp_url('toggle/is_hidden/' . $id), $var['is_hidden']);

            // Early
            if ($early) {
                $row[] = PVUI::onoff($this->mcp_url('toggle/early_parsing/' . $id), $var['early_parsing']);
            }

            // File
            // if ($file) $row[] = PVUI::onoff($this->mcp_url('toggle/save_as_file/'.$id), $var['save_as_file']);

            // Toolbar
            $row[] = array('toolbar_items' => array(
                // Toolbar items
                'copy' => array(
                    'href' => $this->mcp_url('edit_var/new/' . $id),
                    'title' => lang('clone')
                )
            ));

            // Checkbox
            $row[] = array(
                'name'  => 'variable_id[]',
                'value' => $id,
                'data'  => array(
                    'confirm' => htmlspecialchars($var['variable_name'], ENT_QUOTES)
                )
            );

            // Add to table rows
            $rows[] = $row;
        }

        $table->setData($rows);

        $this->data = array(
            'table'      => $table->viewData($this->mcp_url('save_list', null, true)),
            'remove_url' => $this->mcp_url('delete'),
            'pagination' => false,
            'create_new_url' => $this->mcp_url('edit_var/new'),
            'settings'   => $this->settings->get(),
            'groups'     => $groups,
            'types'      => $types
        );

        // --------------------------------------
        // For batch deletion
        // --------------------------------------

        ee()->cp->add_js_script('file', 'cp/confirm_remove');
        ee()->javascript->set_global('lang.remove_confirm', '### ' . lang('variables'));

        // -------------------------------------
        //  Title and Crumbs
        // -------------------------------------

        $this->set_cp_var('cp_page_title', lang('manage_variables'));
        $this->set_cp_crumb($this->mcp_url(), $this->info->getName());
        $this->active = __FUNCTION__;

        // -------------------------------------
        //  Return list view
        // -------------------------------------

        return $this->view('list');
    }

    /**
     * Update single variable, used in Manage Variables screen
     *
     * @access     public
     * @return     int
     */
    public function toggle($type, $var_id)
    {
        if (ee('Request')->method() == 'POST') {
            // Only toggle on POST requests
            $result = $this->vars->toggle($type, $var_id);

            // Bail out on Ajax requests
            if (ee('Request')->isAjax()) {
                die(json_encode($result));
            }
        }

        // Go back to manage
        ee()->functions->redirect($this->mcp_url('vars'));
    }

    /**
     * Saves variable list
     *
     * @access     public
     * @return     mixed     [void|string]
     */
    public function save_list()
    {
        // -------------------------------------
        //  Get vars from POST
        // -------------------------------------

        if ($vars = ee('Request')->post('variable_id')) {
            // -------------------------------------
            //  Get action to perform with list
            // -------------------------------------

            $action = ee('Request')->post('bulk_action');
            $data = array();
            $msg = null;

            // Get var types
            $types = $this->types->load_enabled();

            if ($action == 'delete') {
                // Delete confirmation should be handled by EE
                $this->delete($vars);
                $msg = 'pro_variables_deleted';
            } elseif (array_key_exists($action, $types)) {
                $data['variable_type'] = $action;
            } elseif ($action == 'show') {
                $data['is_hidden'] = 'n';
            } elseif ($action == 'hide') {
                $data['is_hidden'] = 'y';
            } elseif ($action == 'enable_early_parsing') {
                $data['early_parsing'] = 'y';
            } elseif ($action == 'disable_early_parsing') {
                $data['early_parsing'] = 'n';
            } elseif ($action == 'enable_save_as_file') {
                $data['save_as_file'] = 'y';
            } elseif ($action == 'disable_save_as_file') {
                $data['save_as_file'] = 'n';
            } elseif (is_numeric($action)) {
                $data['group_id'] = $action;
            }

            // Batch update the vars if data is given
            if ($data) {
                $this->vars->update($vars, $data);
                $msg = 'pro_variables_saved';
            }
        }

        // -------------------------------------
        //  Display alert
        // -------------------------------------

        if ($msg) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang($msg))
                ->defer();
        }

        ee()->functions->redirect($this->mcp_url('vars'));
    }

    /**
     * Deletes variables
     *
     * @access     public
     * @return     void
     */
    public function delete($vars = null)
    {
        // -------------------------------------
        //  Get var ids
        // -------------------------------------

        $vars = ee('Request')->post('variable_id') ?: $vars;

        // -------------------------------------
        // 'pro_variables_delete' hook.
        //  - Do something just before Pro Variables are deleted
        // -------------------------------------

        if (ee()->extensions->active_hook('pro_variables_delete') === true) {
            ee()->extensions->call('pro_variables_delete', $vars);
        }

        // -------------------------------------
        //  Get vars
        // -------------------------------------

        $rows = $this->vars->get_meta($vars);

        // -------------------------------------
        //  Load var types
        // -------------------------------------

        $this->types->load_enabled();

        // -------------------------------------
        //  Call API for each var deleted
        // -------------------------------------

        foreach ($rows as $var) {
            $obj = $this->types->get($var);
            $obj->delete();
        }

        // -------------------------------------
        //  Delete from global variables and pro variables
        // -------------------------------------

        $this->vars->delete($vars);

        // -------------------------------------
        //  Go to manage screen and show message
        // -------------------------------------

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('pro_variables_deleted'))
            ->defer();

        ee()->functions->redirect($this->mcp_url('vars'));
    }

    /**
     * Show edit form to edit single variable
     *
     * @access     public
     * @return     string
     */
    public function edit_var($var_id = 'new', $clone_id = null)
    {
        // @TODO: remove, as EE should do this
        ee()->lang->loadfile('admin_content');
        ee()->lang->loadfile('channel');

        // -------------------------------------
        //  Are we coming from a certain group?
        // -------------------------------------

        $from = ee('Request')->get('from');

        // -------------------------------------
        //  Create new, clone or edit?
        // -------------------------------------

        if ($var_id == 'new') {
            // Get clone row or empty one
            $var = $clone_id
                ? $this->vars->get_meta($clone_id)
                : $this->vars->empty_row();

            // Overwrite the defaults
            $var['variable_id'] = $var_id;
            $var['variable_name'] = '';

            // Optionally overwrite group_id
            if (is_numeric($from)) {
                $var['group_id'] = $from;
            }
        } else {
            // Get existing row
            $var = $this->vars->get_meta($var_id);
        }

        // --------------------------------------
        // Initiate form sections
        // --------------------------------------

        $sections = array();

        $sections[0][] = array(
            'title' => 'variable_name',
            'desc'  => 'variable_name_help',
            'fields' => array(
                'from' => array(
                    'type'  => 'hidden',
                    'value' => $from
                ),
                'variable_id' => array(
                    'type'  => 'hidden',
                    'value' => $var_id
                ),
                'variable_order' => array(
                    'type'  => 'hidden',
                    'value' => $var['variable_order']
                ),
                'variable_name' => array(
                    'required' => true,
                    'type' => 'text',
                    'value' => $var['variable_name']
                )
            )
        );

        // --------------------------------------
        // Var Label
        // --------------------------------------

        $sections[0][] = array(
            'title' => 'variable_label',
            'desc' => 'variable_label_help',
            'fields' => array(
                'variable_label' => array(
                    'type' => 'text',
                    'value' => $var['variable_label']
                )
            )
        );

        // --------------------------------------
        // Var notes
        // --------------------------------------

        $sections[0][] = array(
            'title' => 'variable_notes',
            'desc' => 'variable_notes_help',
            'fields' => array(
                'variable_notes' => array(
                    'type' => 'textarea',
                    'value' => $var['variable_notes']
                )
            )
        );

        // -------------------------------------
        // Variable groups
        // -------------------------------------

        // Get all groups
        $choices = pro_flatten_results($this->get_groups(), 'group_label', 'group_id');

        // Remove possible ungrouped option
        unset($choices['0']);

        $sections[0][] = array(
            'title' => 'variable_group',
            'fields' => array(
                'group_id' => array(
                    'type' => 'select',
                    'value' => $var['group_id'],
                    'choices' => array('0' => '--') + $choices
                )
            )
        );

        // -------------------------------------
        // Hidden?
        // -------------------------------------

        $sections[0][] = array(
            'title' => 'is_hidden',
            'desc' => 'is_hidden_help',
            'fields' => array(
                'is_hidden' => array(
                    'type' => 'yes_no',
                    'value' => $var['is_hidden']
                )
            )
        );

        // -------------------------------------
        // Parse early?
        // -------------------------------------

        $sections[0][] = array(
            'title' => 'early_parsing',
            'desc' => 'early_parsing_help',
            'fields' => array(
                'early_parsing' => array(
                    'type' => 'yes_no',
                    'value' => $var['early_parsing'],
                    'disabled' => ($this->settings->get('register_globals') == 'n')
                )
            )
        );

        // -------------------------------------
        // Save as file?
        // -------------------------------------

        // $sections[0][] = array(
        //  'title' => 'save_as_file',
        //  'desc' => 'save_as_file_help',
        //  'fields' => array(
        //      'save_as_file' => array(
        //          'type' => 'yes_no',
        //          'value' => $var['save_as_file'],
        //          'disabled' => ($this->settings->get('save_as_files') == 'n')
        //      )
        //  )
        // );

        // -------------------------------------
        //  Get types
        // -------------------------------------

        $types = $this->types->load_enabled();

        $groups = array();

        // -------------------------------------
        //  Get type settings
        // -------------------------------------

        foreach ($types as $type => $row) {
            // Make a copy of the current var
            $v = $var;

            // Force this type
            $v['variable_type'] = $type;

            // Get object
            $obj = $this->types->get($v);

            // Do we have settings?
            $settings = $obj->display_settings();

            ee()->load->add_package_path(PATH_ADDONS . $this->package);

            // Skip empty settings
            if (empty($settings)) {
                continue;
            }

            // Get the first key
            $key = key($settings);

            // If it's a string, the group is already given
            if (is_string($key)) {
                // Use the given group for the show/hide toggle
                $groups[$type] = $settings[$key]['group'];

                // Add the whole shebang to the sections
                $sections += $settings;
            } else {
                // Otherwise, it (should) be an array of arrays (legacy):
                // array(array(label, field), array(label, field))

                // Set the group
                $groups[$type] = $type;

                // We initialise the section
                $section = array(
                    'group' => $type,
                    'label' => $row['name']
                );

                // Loop through the settings
                foreach ($settings as $setting) {
                    // Determine title/content
                    list($title, $content) = (count($setting) == 2)
                        ? $setting
                        : array(null, current($setting));

                    // Add title/content to section
                    $section['settings'][] = array(
                        'title' => $title,
                        'wide' => empty($title),
                        'fields' => array(array(
                            'type' => 'html',
                            'content' => $content
                        ))
                    );
                }

                // And add to all sections
                $sections[] = $section;
            }
        }

        // -------------------------------------
        //  Type select
        // -------------------------------------

        // Determine fallback
        $type = array_key_exists($var['variable_type'], $types)
            ? $var['variable_type']
            : ($var['variable_type'] == 'pro_rte' ? 'rte' : Pro_variables_types::DEFAULT_TYPE);

        $sections[0][] = array(
            'title' => 'variable_type',
            'desc' => 'variable_type_help',
            'fields' => array(
                'variable_type' => array(
                    'type' => 'select',
                    'value' => $type,
                    'choices' => pro_flatten_results($types, 'name', 'type'),
                    'group_toggle' => $groups
                )
            )
        );

        // -------------------------------------
        //  Create Var options
        // -------------------------------------

        if ($var_id == 'new') {
            $section = 'creation_options';

            // Create Var options
            $sections[$section][] = array(
                'title' => 'variable_data',
                'desc' => 'variable_data_help',
                'fields' => array(
                    'variable_data' => array(
                        'type' => 'textarea',
                        'value' => ''
                    )
                )
            );

            // Suffix options
            $sections[$section][] = array(
                'title' => 'variable_suffix',
                'desc' => 'variable_suffix_help',
                'fields' => array(
                    'variable_suffix' => array(
                        'type' => 'text',
                        'value' => ''
                    )
                )
            );
        }

        // -------------------------------------
        //  Make sure the JS is loaded to switch groups
        // -------------------------------------

        ee()->cp->add_js_script('file', 'cp/form_group');

        // -------------------------------------
        //  Title and Crumbs
        // -------------------------------------

        $title = ($var_id == 'new') ? lang('create_variable') : lang('edit_variable');

        // $this->data['from']   = ee()->input->get_post('from');

        // -------------------------------------
        //  Do we have errors in flashdata?
        // -------------------------------------

        $this->data = array(
            'base_url' => $this->mcp_url('save_var'),
            'cp_page_title' => $title,
            'save_btn_text' => 'save_changes',
            'save_btn_text_working' => 'btn_saving',
            'sections' => $sections
        );

        $this->set_cp_var('cp_page_title', $title);
        $this->set_cp_crumb($this->mcp_url(), $this->info->getName());
        $this->active = 'vars';

        // -------------------------------------
        //  Load view
        // -------------------------------------

        return $this->view('form');
    }

    /**
     * Saves variable data
     *
     * @access     public
     * @return     void
     */
    public function save_var()
    {
        // -------------------------------------
        //  Where are we coming from?
        // -------------------------------------

        $from = ee('Request')->post('from');

        // -------------------------------------
        //  Get variable_id
        // -------------------------------------

        if (! ($var_id = ee('Request')->post('variable_id'))) {
            show_error('Variable ID not found');
        }

        // -------------------------------------
        //  Get name and suffix
        // -------------------------------------

        $var_name = trim((string) ee('Request')->post('variable_name'));
        $suffix = trim((string) ee('Request')->post('variable_suffix'));
        $vars = array();

        // -------------------------------------
        //  If var_id is new and we have a suffix, init the multiple vars
        // -------------------------------------

        if ($var_id == 'new' && $suffix) {
            if (strpos($var_name, '{suffix}') !== false) {
                $var_name = str_replace('{suffix}', '%s', $var_name);
            } elseif (strpos($var_name, '%s') === false) {
                $var_name .= '_%s';
            }

            foreach (array_unique(explode(' ', $suffix)) as $sfx) {
                // Skip illegal ones
                if (! preg_match('/^[\w-]+$/', $sfx)) {
                    continue;
                }

                $vars[$sfx] = sprintf($var_name, $sfx);
            }
        } else {
            $vars[null] = $var_name;
        }

        // -------------------------------------
        //  Check validity of each var name
        // -------------------------------------

        $errors = array();

        foreach ($vars as $name) {
            // Check for correct syntax
            if (! preg_match('/^[\w-]+$/', $name)) {
                $errors[] = lang('invalid_variable_name') . ': ' . $name;
            }

            // Check if it doesn't exist
            if ($this->vars->var_exists($name, $var_id)) {
                $errors[] = lang('variable_name_already_exists') . ': ' . $name;
            }

            if (in_array($name, ee()->cp->invalid_custom_field_names())) {
                ee()->lang->load('design');
                $errors[] = lang('reserved_name');
            }
        }

        if ($errors) {
            ee()->output->show_user_error('submission', $errors);
        }

        // -------------------------------------
        //  Initiate var we're gonna save
        // -------------------------------------

        $var = array('variable_id' => $var_id);

        // -------------------------------------
        //  Check boolean values
        // -------------------------------------

        foreach (array('early_parsing', 'is_hidden', 'save_as_file') as $key) {
            $var[$key] = ee('Request')->post($key) ?: 'n';
        }

        // -------------------------------------
        //  Check other regular vars
        // -------------------------------------

        foreach (array('group_id', 'variable_label', 'variable_notes', 'variable_type', 'variable_order') as $key) {
            $var[$key] = ee('Request')->post($key, '');
        }

        // -------------------------------------
        //  Get variable settings
        // -------------------------------------

        $settings = ee('Request')->post('variable_settings');

        // Focus on this type's settings, fallback to empty array
        $var['variable_settings'] = isset($settings[$var['variable_type']])
            ? $settings[$var['variable_type']]
            : array();

        // -------------------------------------
        //  Load and get our type object
        // -------------------------------------

        $this->types->load_one($var['variable_type']);

        $obj = $this->types->get($var);

        // -------------------------------------
        //  Call save_settings from API, fallback to default handling
        // -------------------------------------

        // Call API for custom handling of settings
        $var['variable_settings'] = $obj->save_settings();

        // Encode
        $var['variable_settings'] = json_encode($var['variable_settings']);

        // -------------------------------------
        //  Insert / Update
        // -------------------------------------

        $all = array();

        if ($var_id == 'new') {
            $row = $var;

            // Is there default data?
            $row['variable_data'] = (string) ee('Request')->post('variable_data');

            // Set the site ID
            $row['site_id'] = $this->site_id;

            // Order!
            $order = (int) $this->vars->max_order($row['group_id']);

            // Insert each new var
            foreach ($vars as $sfx => $name) {
                // Add the name
                $row['variable_name'] = $name;

                // Suffix! Amend the label
                if (! empty($sfx) && $var['variable_label']) {
                    // Check label for marker
                    $label = str_replace('{suffix}', '%s', $var['variable_label']);

                    // Add marker if not found
                    if (strpos($label, '%s') === false) {
                        $label .= ' (%s)';
                    }

                    // Create label
                    $row['variable_label'] = sprintf($label, $sfx);
                }

                // Order!
                $row['variable_order'] = ++$order;

                // And insert
                $id = $this->vars->insert($row);

                // Keep track
                $all[$id] = $row;
            }
        } else {
            // Just the one
            $var['variable_name'] = array_shift($vars);

            $this->vars->update($var_id, $var);

            // Keep track
            $all[$var_id] = $var;
        }

        // -------------------------------------
        //  Trigger post_save_settings
        // -------------------------------------

        foreach ($all as $vid => $vdata) {
            // Overwrite variable ID
            $vdata['variable_id'] = $vid;

            // Get new object
            $obj = $this->types->get($vdata);

            // Call the API
            $obj->post_save_settings();
        }

        // -------------------------------------
        //  Return url
        // -------------------------------------

        if ($from == 'vars') {
            $return_url = $this->mcp_url('vars');
        } elseif ($from == $var['group_id']) {
            $return_url = $this->mcp_url('group/' . $from);
        } else {
            $return_url = $this->mcp_url('edit_var/' . $var_id);
        }

        // -------------------------------------
        //  Return with message
        // -------------------------------------

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('var_saved'))
            ->defer();

        ee()->functions->redirect($return_url);
    }

    // --------------------------------------------------------------------
    //  GROUPS
    // --------------------------------------------------------------------

    /**
     * Show edit group form
     *
     * @access     public
     * @return     string
     */
    public function edit_group($group_id = null)
    {
        // -------------------------------------
        //  Check permissions and group id
        // -------------------------------------

        if (! $this->settings->can_manage() || is_null($group_id)) {
            ee()->functions->redirect($this->mcp_url());
        }

        // -------------------------------------
        //  Get details if group_id is not 'new'
        // -------------------------------------

        $vars = array();

        if ($group_id == 'new') {
            $row = $this->groups->empty_row();

            $row['site_id'] = $this->site_id;
            $row['group_id'] = $group_id;
        } else {
            $group_id = (int) $group_id;

            // Account for ungrouped group
            if ($group_id) {
                // Show 404 if group is not found
                if (! ($row = $this->groups->get_one($group_id))) {
                    show_404();
                }
            } else {
                // Set row details to label only
                $row = array('group_label' => lang('ungrouped'));
            }

            $vars = $this->vars->get_meta_by_group($group_id);
        }

        // --------------------------------------
        // Initiate form sections
        // --------------------------------------

        $sections = array();

        // --------------------------------------
        // Group Label
        // --------------------------------------

        $sections[0][] = array(
            'title' => 'group_label',
            'fields' => array(
                'group_id' => array(
                    'type' => 'hidden',
                    'value' => $group_id
                ),
                'group_label' => array(
                    'required' => true,
                    'type' => 'text',
                    'value' => $row['group_label'],
                    'disabled' => ($group_id === 0)
                )
            )
        );

        // --------------------------------------
        // Group notes
        // --------------------------------------

        if ($group_id) {
            $sections[0][] = array(
                'title' => 'group_notes',
                'desc' => 'group_notes_help',
                'fields' => array(
                    'group_notes' => array(
                        'type' => 'textarea',
                        'value' => $row['group_notes']
                    )
                )
            );
        }

        // --------------------------------------
        // Re-orderable vars
        // --------------------------------------

        if ($vars) {
            $sections[0][] = array(
                'title' => 'variable_order',
                'fields' => array(array(
                    'type' => 'html',
                    'content' => ee('View')
                        ->make($this->package . ':vars-in-group')
                        ->render(array('vars' => $vars))
                ))
            );
        }

        // --------------------------------------
        // Save as new group
        // --------------------------------------

        if ($vars && ! in_array($group_id, array('new', 0))) {
            $sections[0][] = array(
                'title' => 'save_as_new_group_label',
                'fields' => array(
                    'save_as_new_group' => array(
                        'type' => 'yes_no',
                        'value' => 'n'
                    )
                )
            );

            $name = 'new_group_options';

            // New group options
            $section = array(
                'group' => $name,
                'label' => $name,
            );

            $attrs = array(
                'data-section-group' => $name
            );

            // Duplicate vars?
            $section['settings'][] = array(
                'title' => 'duplicate_variables',
                'attrs' => $attrs,
                'fields' => array(
                    'duplicate_variables' => array(
                        'type' => 'yes_no',
                        'value' => 'n'
                    )
                )
            );

            // Var suffix
            $section['settings'][] = array(
                'title' => 'variable_suffix',
                'desc' => 'group_variable_suffix_help',
                'attrs' => $attrs,
                'fields' => array(
                    'variable_suffix' => array(
                        'type' => 'text'
                    )
                )
            );

            // Suffix option
            $section['settings'][] = array(
                'title' => 'suffix_options',
                'attrs' => $attrs,
                'fields' => array(
                    'with_suffix' => array(
                        'type' => 'select',
                        'choices' => array(
                            'append' => lang('append_suffix'),
                            'replace' => lang('replace_suffix')
                        )
                    )
                )
            );

            $sections[] = $section;
        }

        // --------------------------------------
        // Compose view data
        // --------------------------------------

        $this->data = array(
            'base_url' => $this->mcp_url('save_group'),
            'save_btn_text' => 'save_group',
            'save_btn_text_working' => 'btn_saving',
            'sections' => $sections
        );

        // -------------------------------------
        //  Title and Crumbs
        // -------------------------------------

        $this->set_cp_var('cp_page_title', lang('edit_group'));
        $this->set_cp_crumb($this->mcp_url(), $this->info->getName());
        $this->active = 'groups';

        // -------------------------------------
        //  Feed to view
        // -------------------------------------

        return $this->view('form');
    }

    /**
     * Save group
     *
     * @access     public
     * @return     void
     */
    public function save_group()
    {
        // -------------------------------------
        //  Get group_id
        // -------------------------------------

        if (($group_id = ee('Request')->post('group_id', false)) === false) {
            // No id found, exit!
            ee()->functions->redirect($this->mcp_url());
        }

        // -------------------------------------
        //  Save As New group?
        // -------------------------------------

        if ($save_as_new = (ee('Request')->post('save_as_new_group') == 'y')) {
            $group_id = 'new';
        }

        // Force integer
        if ($group_id != 'new') {
            $group_id = (int) $group_id;
        }

        // -------------------------------------
        //  Quickly validate duplication of vars
        // -------------------------------------

        $duplicate = (ee('Request')->post('duplicate_variables') == 'y');
        $suffix = ee('Request')->post('variable_suffix');

        if ($save_as_new && $duplicate && ! $suffix) {
            show_error(lang('suffix_required'));
        }

        // Skip the following for Ungrouped
        if ($group_id !== 0) {
            // -------------------------------------
            //  Get group_label
            // -------------------------------------

            if (! ($group_label = ee('Request')->post('group_label'))) {
                // No label found, exit!
                show_error(lang('no_group_label'));
            }

            // -------------------------------------
            //  Insert / update group
            // -------------------------------------

            $data = array(
                'group_label' => $group_label,
                'group_notes' => ee('Request')->post('group_notes'),
                'site_id'     => $this->site_id
            );
        }

        // -------------------------------------
        //  Get posted vars, if any
        // -------------------------------------

        $vars = ee('Request')->post('vars');

        // -------------------------------------
        //  Process group insert/update
        // -------------------------------------

        if ($group_id === 'new') {
            // Insert new group in DB
            $group_id = $this->groups->insert($data);

            // Add new vars in group, if necessary
            if ($save_as_new && $duplicate && $vars) {
                $this->duplicate_vars_to_group($vars, $group_id, $suffix);
            }
        } else {
            // Ungrouped group can only sort, update details for the rest
            if ($group_id > 0) {
                $this->groups->update($group_id, $data);
            }

            // Update variable order
            if ($vars) {
                $this->vars->update_var_order($vars);
            }
        }

        // -------------------------------------
        //  Go back from whence they came
        // -------------------------------------

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('group_saved'))
            ->addToBody(lang('group_saved_changes'))
            ->defer();

        ee()->functions->redirect($this->mcp_url('group/' . $group_id));
    }

    /**
     * Duplicates given variable ids into given group id
     *
     * @access     private
     * @param      array
     * @param      int
     * @param      string
     * @return     void
     */
    private function duplicate_vars_to_group($var_ids, $group_id, $suffix)
    {
        // -------------------------------------
        // Clean up suffix
        // -------------------------------------

        $suffix = trim(preg_replace('/[^\w\-_:]/', '', $suffix), '_');

        // Still valid?
        if (! $suffix) {
            return false;
        }

        // -------------------------------------
        //  Do what with suffix?
        // -------------------------------------

        $with_suffix = ee('Request')->post('with_suffix');

        // -------------------------------------
        //  Fetch vars to duplicate
        // -------------------------------------

        $rows = $this->vars->get_all($var_ids);

        // -------------------------------------
        //  Load types
        // -------------------------------------

        $types = pro_flatten_results($rows, 'variable_type');
        $types[] = Pro_variables_types::DEFAULT_TYPE;
        $types = array_unique($types);

        $this->types->load($types);

        // -------------------------------------
        //  Loop through vars, duplicate into group
        // -------------------------------------

        foreach ($rows as $row) {
            if ($with_suffix == 'replace' && strpos($row['variable_name'], '_') !== false) {
                $new_name = preg_replace('/_[0-9a-z]+$/i', "_{$suffix}", $row['variable_name']);
            } else {
                $new_name = $row['variable_name'] . '_' . $suffix;
            }

            // Skip existing ones
            if ($this->vars->var_exists($new_name, $row['variable_id'])) {
                continue;
            }

            // Overwrite name and group
            $row['variable_name'] = $new_name;
            $row['group_id'] = $group_id;

            // Insert and catch new ID
            $row['variable_id'] = $this->vars->insert($row);

            // Create object
            $obj = $this->types->get($row);

            // Fire these
            $obj->post_save_settings();
            $obj->post_save($row['variable_data']);
        }
    }

    /**
     * Deletes variable group
     *
     * @access     public
     * @return     void
     */
    public function delete_group()
    {
        // Get group id
        if ($group_id = ee('Request')->post('id')) {
            // Delete from both table, update vars
            $this->groups->delete($group_id);
            $this->vars->ungroup($group_id);

            // Show message
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('pro_variable_group_deleted'))
                //->addToBody(lang('group_saved_changes'))
                ->defer();
        }

        ee()->functions->redirect($this->mcp_url());
    }

    /**
     * Save new sort order for groups
     *
     * @access     public
     * @return     void
     */
    public function save_group_order()
    {
        // Request must be an Ajax request
        if (! ee('Request')->isAjax()) {
            return;
        }

        // And Groups must be given
        if (! ($groups = ee('Request')->post('groups'))) {
            return;
        }

        // Turn into array
        if (! is_array($groups)) {
            $groups = explode('|', $groups);
        }

        // And update the groups
        foreach ($groups as $i => $id) {
            $this->groups->update($id, array('group_order' => $i));
        }

        return true;
    }

    // --------------------------------------------------------------------
    //  SETTINGS
    // --------------------------------------------------------------------

    /**
     * Extension settings form
     *
     * @access     public
     * @param      array
     * @return     string
     */
    public function settings()
    {
        // --------------------------------------
        // Sync native vars
        // --------------------------------------

        $this->sync->native();

        // --------------------------------------
        // Initiate form sections
        // --------------------------------------

        $sections = array();

        // --------------------------------------
        // List of member groups
        // --------------------------------------

        $groups = ee('Model')->get('Role')
            ->filter('role_id', 'IN', ee('Permission')->rolesThatHave('can_access_cp'))
            ->order('name', 'ASC')
            ->all()->getDictionary('role_id', 'name');

        $active = $this->settings->get('can_manage');
        $active[] = 1;

        $sections[0][] = array(
            'title' => 'can_manage',
            'desc' => 'can_manage_help',
            'fields' => array(
                'can_manage' => array(
                    'type' => 'checkbox',
                    'choices' => $groups,
                    'value' => $active,
                    'disabled_choices' => array(1)
                )
            )
        );

        // --------------------------------------
        // Clear Cache options
        // --------------------------------------

        $choices = array(
            'n' => ucfirst(lang('no')),
            'o' => lang('clear_cache_opt'),
            'y' => ucfirst(lang('yes'))
        );

        $sections[0][] = array(
            'title' => 'clear_cache',
            'desc' => 'clear_cache_help',
            'fields' => array(
                'clear_cache' => array(
                    'type'    => 'select',
                    'value'   => substr($this->settings->get('clear_cache'), 0, 1),
                    'choices' => $choices
                )
            )
        );

        // --------------------------------------
        // Early Parsing
        // --------------------------------------

        $choices = array(
            'n' => ucfirst(lang('no')),
            'y' => lang('register_globals_before'),
            'a' => lang('register_globals_after')
        );

        $sections[0][] = array(
            'title' => 'register_globals',
            'desc' => 'register_globals_help',
            'fields' => array(
                'register_globals' => array(
                    'type'    => 'select',
                    'value'   => $this->settings->get('register_globals'),
                    'choices' => $choices
                )
            )
        );

        // --------------------------------------
        // Save as files option
        // --------------------------------------

        // $sections[0][] = array(
        //  'title' => 'save_as_files',
        //  'desc' => 'save_as_files_help',
        //  'fields' => array(
        //      'save_as_files' => array(
        //          'type'     => 'yes_no',
        //          'value'    => $this->settings->get('save_as_files'),
        //          'disabled' => ee()->pro_variables_settings->is_config('save_as_files')
        //      )
        //  )
        // );

        // --------------------------------------
        // One way sync
        // --------------------------------------

        // $sections[0][] = array(
        //  'title' => 'one_way_sync',
        //  'desc' => 'one_way_sync_help',
        //  'fields' => array(
        //      'one_way_sync' => array(
        //          'type'     => 'yes_no',
        //          'value'    => $this->settings->get('one_way_sync'),
        //          'disabled' => ee()->pro_variables_settings->is_config('one_way_sync')
        //      )
        //  )
        // );

        // --------------------------------------
        // File path
        // --------------------------------------

        // $sections[0][] = array(
        //  'title' => 'file_path',
        //  'desc' => 'file_path_help',
        //  'fields' => array(
        //      'file_path' => array(
        //          'type'     => 'text',
        //          'value'    => $this->settings->get('file_path'),
        //          'disabled' => ee()->pro_variables_settings->is_config('file_path')
        //      )
        //  )
        // );

        // --------------------------------------
        // Sync URL
        // --------------------------------------

        // $sync_url = ($this->settings->license_key)
        //  ? ee()->functions->fetch_site_index(0, 0)
        //  . QUERY_MARKER.'ACT='
        //  . ee()->cp->fetch_action_id('Pro_variables', 'sync')
        //  . AMP.'key='
        //  . $this->settings->license_key
        //  : '';
        //
        // $sections[0][] = array(
        //  'title' => 'sync_url',
        //  'desc' => 'sync_url_help',
        //  'fields' => array(
        //      'sync_url' => array(
        //          'type'     => 'html',
        //          'content'  => $sync_url,
        //      )
        //  )
        // );

        // --------------------------------------
        // List of var types
        // --------------------------------------

        $choices = array();

        foreach ($this->types->load_all() as $key => $type) {
            $choices[$key] = $type['name']; // .' &mdash; <i>'.$type['version'].'</i>';
        }

        $value = $this->settings->get('enabled_types');

        if (! in_array(Pro_variables_types::DEFAULT_TYPE, $value)) {
            $value[] = Pro_variables_types::DEFAULT_TYPE;
        }

        $sections[0][] = array(
            'title' => 'variable_types',
            'desc' => 'variable_types_help',
            'fields' => array(
                'enabled_types' => array(
                    'wrap' => true,
                    'type' => 'checkbox',
                    'choices' => $choices,
                    'value' => $value,
                    'disabled_choices' => array(Pro_variables_types::DEFAULT_TYPE)
                )
            )
        );

        // --------------------------------------
        // Compose view data
        // --------------------------------------

        $this->data = array(
            'base_url' => $this->mcp_url('save_settings'),
            'save_btn_text' => 'save_settings',
            'save_btn_text_working' => 'saving',
            'sections' => $sections
        );

        // --------------------------------------
        // Set title and breadcrumb
        // --------------------------------------

        $this->set_cp_var('cp_page_title', lang('extension_settings'));
        $this->set_cp_crumb($this->mcp_url(), $this->info->getName());

        return $this->view('form');
    }

    /**
     * Save extension settings
     *
     * @access     public
     * @return     void
     */
    public function save_settings()
    {
        $settings = array();

        // -------------------------------------
        // Loop through default settings,
        // put POST values in settings array
        // -------------------------------------

        foreach ($this->settings->default_settings() as $key => $val) {
            $settings[$key] = ee('Request')->post($key) ?: $val;
        }

        // -------------------------------------
        // Then apply config overrides
        // -------------------------------------

        $settings = $this->settings->set($settings);

        // -------------------------------------
        // Check path backslashes
        // -------------------------------------

        if (strpos($settings['file_path'], '\\')) {
            $settings['file_path'] = addslashes($settings['file_path']);
        }

        // -------------------------------------
        // Make sure enabled_types is an array
        // -------------------------------------

        if (! is_array($settings['enabled_types'])) {
            $settings['enabled_types'] = array();
        }

        $settings['enabled_types'] = array_unique($settings['enabled_types']);

        // -------------------------------------
        // Make sure enabled_types always contains the default type
        // -------------------------------------

        if (! in_array(Pro_variables_types::DEFAULT_TYPE, $settings['enabled_types'])) {
            $settings['enabled_types'][] = Pro_variables_types::DEFAULT_TYPE;
        }

        // -------------------------------------
        // Make sure can_manage is an array
        // -------------------------------------

        if (! is_array($settings['can_manage'])) {
            $settings['can_manage'] = array();
        }

        // -------------------------------------
        // Save the serialized settings in DB
        // -------------------------------------

        ee()->db->update(
            'extensions',
            array('settings' => serialize($settings)),
            "class = '{$this->class_name}_ext'"
        );

        // --------------------------------------
        // Set feedback message
        // --------------------------------------

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('settings_saved'))
            ->addToBody(sprintf(lang('settings_saved_desc'), $this->info->getName()))
            ->defer();

        // -------------------------------------
        // Redirect back to extension page
        // -------------------------------------

        ee()->functions->redirect($this->mcp_url('settings'));
    }

    // --------------------------------------------------------------------

    /**
     * Return an MCP URL
     *
     * @access     private
     * @param      string
     * @param      mixed     [array|string]
     * @param      bool
     * @return     mixed
     */
    private function mcp_url($path = null, $extra = null, $obj = false)
    {
        // Base settings
        $segments = array('addons', 'settings', $this->package);

        // Add method to segments, of given
        if (is_string($path)) {
            $segments[] = $path;
        }
        if (is_array($path)) {
            $segments = array_merge($segments, $path);
        }

        // Create the URL
        $url = ee('CP/URL', implode('/', $segments));

        // Add the extras to it
        if (! empty($extra)) {
            // convert to array
            if (! is_array($extra)) {
                parse_str($extra, $extra);
            }

            // And add to the url
            $url->addQueryStringVariables($extra);
        }

        // Return it
        return ($obj) ? $url : $url->compile();
    }

    /**
     * Set cp var
     *
     * @access     private
     * @param      string
     * @param      string
     * @return     void
     */
    private function set_cp_var($key, $val)
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
    private function set_cp_crumb($url, $text)
    {
        $this->crumb[$url] = $text;
    }

    // --------------------------------------------------------------------

    /**
     * Groups navigation
     *
     * @access     private
     * @return     void
     */
    private function get_groups()
    {
        // Local cache
        static $groups;

        // -------------------------------------
        //  Already known? Return that
        // -------------------------------------

        if (! is_null($groups)) {
            return $groups;
        }

        // -------------------------------------
        //  Get the group IDs or NULL when all
        // -------------------------------------

        $counts = $this->vars->get_group_count($this->settings->can_manage());

        // -------------------------------------
        //  Return an empty array when there aren't any vars to display
        // -------------------------------------

        if (is_array($counts) && empty($counts)) {
            // Just get all the groups
            $groups = ee()->pro_variables_group_model->get_by_site();
            $groups = pro_associate_results($groups, 'group_id');

            return $groups;
        }

        // -------------------------------------
        //  Get the group IDs or NULL when all
        // -------------------------------------

        $group_ids = $this->settings->can_manage() ? null : array_keys($counts);

        // -------------------------------------
        //  Now, get all the groups
        // -------------------------------------

        $groups = $this->groups->get_by_ids($group_ids);
        $groups = pro_associate_results($groups, 'group_id');

        // Add ungrouped to groups if present
        if (array_key_exists(0, $counts) || empty($groups)) {
            $groups[0] = array(
                'group_id'    => '0',
                'group_label' => lang('ungrouped'),
                'group_notes' => ''
            );
        }

        // Loop through groups and add var count to them
        foreach ($groups as &$g) {
            $g['var_count'] = array_key_exists($g['group_id'], $counts)
                ? $counts[$g['group_id']]
                : 0;
        }

        // -------------------------------------
        //  Add save group order URL to JS global
        // -------------------------------------

        ee()->javascript->set_global('PRO.save_group_order_url', $this->mcp_url('save_group_order'));

        // -------------------------------------
        //  return the groups
        // -------------------------------------

        return $groups;
    }

    // --------------------------------------------------------------------

    /**
     * View add-on page
     *
     * @access     private
     * @param      string
     * @return     string
     */
    private function view($file)
    {
        // -------------------------------------
        //  Load CSS and JS
        // -------------------------------------

        $version = '&amp;lv=' . (static::DEBUG ? time() : $this->version);

        ee()->cp->add_js_script('plugin', 'ui.touch.punch');
        ee()->cp->add_js_script('ui', 'sortable');

        ee()->cp->load_package_css($this->package . $version);
        ee()->cp->load_package_js($this->package . $version);

        // -------------------------------------
        //  Main page header
        // -------------------------------------

        // Define header
        $header = array('title' => $this->info->getName());

        // SuperAdmins can access settings
        if (ee()->session->userdata('group_id') == 1) {
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
        //  Add menu to page if manager
        // -------------------------------------

        // Can we manage? (shortcut)
        $manager = $this->settings->can_manage();

        // Define the sidebar
        $sidebar = ee('CP/Sidebar')->make();

        // First item is the Variable Groups header
        $header = $sidebar->addHeader('Groups');

        // Optionally add new group link
        if ($manager) {
            $header->withButton(lang('new'), $this->mcp_url('edit_group/new'));
        }

        // Make active?
        if ($this->active == 'groups') {
            $header->isActive();
        }

        // Add Folders (var groups) to the sidebar, if present
        if ($groups = $this->get_groups()) {
            // Initiate folder list
            $list = $header->addFolderList('groups')
                ->withRemoveUrl($this->mcp_url('delete_group'));

            // Loop through groups and add to list
            foreach ($groups as $key => $val) {
                // Add the folder to the list
                $item = $list->addItem($val['group_label'], $this->mcp_url('group/' . $key));

                if ($manager) {
                    // Add edit/remove items for managers
                    $item->withEditUrl($this->mcp_url('edit_group/' . $key));
                    $item->identifiedBy($key);
                    $item->withRemoveConfirmation(sprintf(lang('delete_group_conf'), $val['group_label']));

                    // Ungrouped cannot be deleted
                    if ($key == 0) {
                        $item->cannotRemove();
                    }
                } else {
                    // No edit/remove links for non-managers
                    $item->cannotEdit();
                    $item->cannotRemove();
                }

                // Is the item active?
                if (is_numeric($this->active) && is_numeric($key) && $this->active == $key) {
                    $item->isActive();
                }
            }
        }

        // Manage Variables link
        if ($manager) {
            $extra = is_numeric($this->active) ? 'from=' . $this->active : null;

            $header = $sidebar->addHeader(lang('variables'), $this->mcp_url('vars'))
                ->withButton(lang('new'), $this->mcp_url('edit_var/new', $extra));

            if ($this->active == 'vars') {
                $header->isActive();
            }

            ee()->cp->add_js_script('file', 'cp/confirm_remove');
        }

        // -------------------------------------
        //  Add EE version to global JS object
        // -------------------------------------

        ee()->javascript->set_global(
            'PRO.iconFont',
            'fontawesome'
        );

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

/* End file mcp.pro_variables.php */
