<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
require_once PATH_ADDONS . 'structure/addon.setup.php';
require_once PATH_ADDONS . 'structure/helper.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';
require_once PATH_ADDONS . 'structure/mod.structure.php';

use ExpressionEngine\Structure\Conduit\McpNav as AddonNav;

class Structure_mcp
{
    public $logging = false;
    public $structure;
    public $sql;
    public $version;
    public $nav;
    public $site_id;
    public $base_url;
    public $data;

    public $perms = array(
        'perm_admin_structure'         => 'Manage module settings',
        'perm_admin_channels'          => 'Manage channel settings',
        'perm_view_global_add_page'    => 'View global "Add page" link above page tree',
        'perm_view_add_page'           => 'View "Add page" link in page tree rows',
        'perm_view_view_page'          => 'View "View page" link in page tree rows',
        'perm_view_validation'         => 'Can Access Validation',
        'perm_view_nav_history'        => 'Can Access Nav History',
        'perm_delete'                  => 'Can delete',
        'perm_reorder'                 => 'Can reorder'

    );
    // Enable additional reordering options on a per-level basis
    // Used only in conjunction with per-member group reorder settings
    public $extra_reorder_options = false; // Default: FALSE

    /**
     * Constructor
     * @param bool $switch
     */
    public function __construct($switch = true)
    {
        $this->version = STRUCTURE_VERSION;

        // Load the Nav
        if (defined('REQ') && constant('REQ') === 'CP') {
            $this->nav = new AddonNav();
        }

        $this->sql = new Sql_structure();
        $this->structure = new Structure();
        $this->site_id = ee()->config->item('site_id');

        $this->base_url = ee('CP/URL', 'addons/settings/structure');

        if (! function_exists('json_decode')) {
            ee()->load->library('Services_json');
        }

        if ($this->logging === true) {
            ee()->load->library('logger');
        }

        $settings = $this->sql->get_settings();
        $channel_data = $this->structure->get_structure_channels('page');

        ee()->load->library('general_helper');

        ee()->cp->add_to_head("<link rel='stylesheet' href='" . $this->sql->theme_url() . "css/structure.css'>");
    }

    /**
     * Main CP page
     * @param string $message
     */
    public function index($message = false)
    {
        $this->set_cp_title('pages');

        $settings = $this->sql->get_settings();

        // Load Libraries and Helpers
        ee()->load->library('javascript');
        ee()->load->library('table');
        ee()->load->helper('path');
        ee()->load->helper('form');

        // Check if we have admin permission
        $permissions = array();
        $permissions['admin'] = $this->sql->user_access('perm_admin_structure', $settings);
        $permissions['view_add_page'] = $this->sql->user_access('perm_view_add_page', $settings);
        $permissions['view_view_page'] = $this->sql->user_access('perm_view_view_page', $settings);
        $permissions['view_global_add_page'] = $this->sql->user_access('perm_view_global_add_page', $settings);
        $permissions['delete'] = $this->sql->user_access('perm_delete', $settings);
        $permissions['reorder'] = $this->sql->user_access('perm_reorder', $settings);

        $rules = array();

        // Only EE6 and up have the `allow_preview` field on channels.
        if (version_compare(APP_VER, '6.1.0', '>=')) {
            // put fields to go faster.
            $builder = ee('Model')->get('Channel')->filter('site_id', '==', $this->site_id)->order('channel_id', 'ASC')->fields('allow_preview', 'channel_id')->all();
            $channel_rules = $builder->pluck('allow_preview');
            $channel_id = $builder->pluck('channel_id');

            for ($i = 0; $i < count($channel_id); $i++) {
                $rules[$channel_id[$i]] = $channel_rules[$i];
            }
        }

        // Enable/disable dragging and reordering
        // if ((isset($permissions['reorder']) && $permissions['reorder']) || $permissions['admin'])
        ee()->cp->load_package_js('jquery.ui.nestedsortable');
        ee()->cp->load_package_js('structure-nested-20170328002');
        ee()->cp->load_package_js('structure-actions');
        ee()->cp->load_package_js('structure-collapse');

        $site_pages = $this->sql->get_site_pages();
        $data['ee_ver'] = substr(APP_VER, 0, 1);
        $data['tabs'] = array('page-ui' => lang('all_pages'));
        $data['data'] = array('page-ui' => $this->sql->get_data());
        $data['valid_channels'] = $this->sql->get_structure_channels('page', '', 'alpha', true);
        $data['listing_cids'] = $this->structure->get_data_cids(true);
        $data['settings'] = $settings;
        $data['member_settings'] = $this->sql->get_member_settings();
        $data['cp_asset_data'] = $this->sql->get_cp_asset_data();
        $data['site_pages'] = count($site_pages) > 0 ? $site_pages : array();
        $data['site_uris'] = is_array($data['site_pages']) && array_key_exists('uris', $data['site_pages']) ? $data['site_pages']['uris'] : array();
        $data['asset_path'] = PATH_ADDONS . 'structure/views/';
        $data['permissions'] = $permissions;
        $data['page_count'] = $this->sql->get_page_count();
        $data['attributes'] = array('class' => 'structure-form', 'id' => 'delete_form');
        $data['status_colors'] = $this->sql->get_status_colors();
        $data['assigned_channels'] = is_array(ee()->session->userdata('assigned_channels')) ? ee()->session->userdata('assigned_channels') : array();
        $data['action_url'] = ee('CP/URL')->make('addons/settings/structure/delete');
        $data['theme_url'] = ee()->config->item('theme_folder_url') . 'third_party/structure';
        $data['extra_reorder_options'] = $this->extra_reorder_options;
        $data['homepage'] = array_search('/', $site_pages['uris']);
        $data['selected_tab'] = 0;
        $data['channel_rules'] = $rules;

        // Gut check to make sure they ran the update method!
        if (!ee()->db->field_exists('updated', 'structure')) {
            ee('CP/Alert')->makeInline("Structure Update Required: Please click the 'Update' button next to 'Structure' below")
                ->asIssue()
                ->withTitle("Structure Update Required: Please click the 'Update' button next to 'Structure' below")
                ->canClose()
                ->defer();

            ee()->functions->redirect(ee()->general_helper->cpURL('addons'));
        }

        // Get the last updated datetime.
        $data['updated_time'] = ee()->db->select('updated')->get_where('structure', array('dead' => 'root'), 1)->row()->updated;

        // -------------------------------------------
        // 'structure_index_view_data' hook.
        // - Used to expand the tree switcher (new tabs and content)
        //
        if (ee()->extensions->active_hook('structure_index_view_data') === true) {
            $data = ee()->extensions->call('structure_index_view_data', $data);
        }
        //
        // -------------------------------------------

        $page_choices = array();
        $page_selectors = array();
        if (is_array($data['valid_channels'])) {
            $page_choices = array_intersect_key($data['valid_channels'], $data['assigned_channels']);
        }
        //specific case that occurs if a single channel is not selected for in page selector still need to provide it with a valid add child page link.
        if (is_array($data['valid_channels'])) {
            $page_selectors = $data['valid_channels'];
        }

        $data['page_choices'] = $page_choices;

        if ($page_choices && count($page_choices) == 1) {
            $data['add_page_url'] = ee()->general_helper->cpURL('publish', 'create', array('channel_id' => key($page_choices)));
        } elseif ($page_selectors && count($page_choices) == 1) {
            $data['add_page_url'] = ee()->general_helper->cpURL('publish', 'create', array('channel_id' => key($page_choices)));
        } elseif ($data['page_count'] == 0) {
            $data['add_page_url'] = ee('CP/URL')->make('addons/settings/structure/channel_settings');
        } else {
            $data['add_page_url'] = '#';
        }

        $add_body = '';
        $add_urls = array();

        $vc_total = count($page_choices);
        $vci = 0;
        if (is_array($page_choices) && count($page_choices) > 0) {
            foreach ($page_choices as $key => $channel) {
                $vci++;
                $add_url = (string) ee()->general_helper->cpURL('publish', 'create', array('channel_id' => $key, 'template_id' => $channel['template_id']));
                $add_urls[] = $add_url;
                $add_body .= '<li';
                $add_body .= $vci == $vc_total ? ' class="last">' : '>';
                $add_body .= '<a rel="what" href="' . $add_url . '">' . $channel['channel_title'] . '</a></li>';
            }
        }

        if ($add_body) {
            $add_body = '<ul class="plain">' . $add_body . '</ul>';
        }

        $dialogs = array(
            'add' => array(
                'urls' => $add_urls,
                'title' => ee()->lang->line('select_page_type'),
                'body' => $add_body,
                'buttons' => array('cancel' => ee()->lang->line('cancel'))
            ),
            'del' => array(
                'title' => '',
                'body' => ee()->lang->line('structure_delete_confirm'),
                'buttons' => array(
                    'del' => ee()->lang->line('delete_page'),
                    'cancel' => ee()->lang->line('cancel')
                )
            )
        );

        $settings_array = array(
            'dialogs' => $dialogs,
            'site_id' => ee()->config->item('site_id'),
            'xid' => XID_SECURE_HASH,
            'global_add_page' => $settings['show_global_add_page'],
            'show_picker' => $settings['show_picker'],
            'can_reorder' => $permissions['reorder'] ? true : false,
            'admin' => $permissions['admin'] ? true : false
        );

        $settings_json = json_encode($settings_array);

        ee()->cp->add_to_foot('<script type="text/javascript">var structure_updated = "' . $data['updated_time'] . '"; var structure_settings = ' . $settings_json . ';</script>');

        if (empty($data['data']['page-ui'])) {
            return ee()->general_helper->view('get_started', $data, true);
        }

        return ee()->general_helper->view('index', $data, true);
    }

    public function ajax_collapse()
    {
        $closed_ids = json_encode(ee()->input->get_post('collapsed'));
        $member_id = ee()->session->userdata('member_id');

        $data = array(
            'site_id' => $this->site_id,
            'member_id' => $member_id,
            'nav_state' => $closed_ids
        );

        $result = ee()->db->get_where('structure_members', array('site_id' => $this->site_id, 'member_id' => $member_id), 1);

        if ($result->num_rows > 0) {
            ee()->db->where(array('site_id' => $this->site_id, 'member_id' => $member_id))->update('structure_members', $data);
        } else {
            ee()->db->insert('structure_members', $data);
        }
        die(json_encode($data, true));
    }

    public function link()
    {
        $entry_id = ee()->input->get_post('entry_id');
        $site_pages = $this->sql->get_site_pages();

        // this is to fix a bug in EE3.4.x. It should not replace anything otherwise
        $ee_url = ee()->functions->create_page_url($site_pages['url'], $site_pages['uris'][$entry_id], false);
        $url = str_replace("{base_url}/", ee()->config->item('base_url'), $ee_url);

        // Hook to override the url generated for each structure link (ex: Transcribe's multi-lingual language domains).
        if (ee()->extensions->active_hook('structure_generate_page_url_end') === true) {
            $url = ee()->extensions->call('structure_generate_page_url_end', $url);
        }

        redirect($url);
    }

    /**
     * Reorder Structure Pages
     *
     * @return AJAX POST for reordering
     **/
    public function ajax_reorder()
    {
        // Get the last updated datetime.
        $last_updated_time = ee()->db->select('updated')->get_where('structure', array('dead' => 'root'), 1)->row()->updated;

        // Make sure the user has a current copy of the data before we reorder it to help
        // prevent corruption when 2+ users (or 1 user with multiple windows) are sorting data.
        if (empty($_POST['timestamp']) || $_POST['timestamp'] != $last_updated_time) {
            die(json_encode(array('success' => false, 'errors' => lang('structure_reorder_invalid_timestamp'))));
        }

        // Grab the AJAX post
        if (isset($_POST['page-ui']) && is_array($_POST['page-ui'])) {
            $sortable = $_POST['page-ui'];
        } else {
            die(json_encode(array('success' => false, 'errors' => lang('structure_reorder_invalid_data'))));
        }

        if (isset($_GET['site_id']) && is_numeric($_GET['site_id']) && $_GET['site_id'] > 0) {
            $site_id = $_GET['site_id'];
        } else {
            die(json_encode(array('success' => false, 'errors' => lang('structure_reorder_invalid_site_id'))));
        }

        // capture nav history here
        // add_structure_nav_revision($site_id, 'Pre Ajax reorder');

        // Convert the array to php
        $data = $this->structure->nestedsortable_to_nestedset($sortable);

        $titles = array();
        $site_pages = $this->sql->get_site_pages(false, true);
        $structure_data = $this->sql->get_data();

        $uris = $site_pages['uris'];

        // Get Page Slugs
        foreach ($uris as $key => $uri) {
            $slug = trim($uri, '/');
            if (strpos($slug, '/')) {
                $slug = substr(strrchr($slug, '/'), 1);
            }

            if ($uri == "/") {
                $slug = $uri;
            }

            @$titles[$key] .= $slug;
        }

        // Build an entry id string to limit the scope of results to
        // prevent memory errors on sites with lots of entries.
        $entry_ids = array_keys($data);
        $entry_ids_string = implode(',', $entry_ids);

        // Build an array with all current channel_ids
        $results = ee()->db->query("SELECT entry_id,channel_id FROM exp_channel_titles WHERE site_id = $this->site_id AND entry_id IN ($entry_ids_string)");

        $channel_data = array();
        if ($results->num_rows() > 0) {
            foreach ($results->result_array() as $row) {
                $channel_data[$row['entry_id']] = $row['channel_id'];
            }
        }

        $row_insert = array();
        $page_uris = array();

        foreach ($data as $key => $row) {
            $depth = count($row['crumb']);

            $row['site_id'] = $site_id;
            $row['entry_id'] = $entry_id = $row['crumb'][$depth - 1];
            $row['parent_id'] = $depth < 2 ? 0 : $row['crumb'][$depth - 2];
            $row['channel_id'] = (!empty($channel_data[$entry_id]) ? $channel_data[$entry_id] : 0);
            $row['listing_cid'] = (!empty($structure_data[$entry_id]['listing_cid']) ? $structure_data[$entry_id]['listing_cid'] : 0);
            $row['dead'] = '';
            $row['hidden'] = (!empty($structure_data[$entry_id]['hidden']) ? $structure_data[$entry_id]['hidden'] : 0);
            $row['structure_url_title'] = (!empty($structure_data[$entry_id]['structure_url_title']) ? $structure_data[$entry_id]['structure_url_title'] : 'MISSING');
            $row['template_id'] = (!empty($structure_data[$entry_id]['template_id']) ? $structure_data[$entry_id]['template_id'] : 0);

            // build URI path for pages
            $uri_titles = array();

            if (!empty($data[$key]['crumb']) && is_array($data[$key]['crumb'])) {
                foreach ($data[$key]['crumb'] as $entry_id) {
                    $uri_titles[] = (!empty($titles[$entry_id]) ? $titles[$entry_id] : '');
                }
            }

            // Remove invalid row fields
            unset($row['depth']);
            unset($row['crumb']);

            // Build pages URI
            if (!empty($uri_titles) && is_array($uri_titles)) {
                $page_uris[$key] = trim(implode('/', $uri_titles), '/');
            } else {
                $page_uris[$key] = 'MISSING';
            }

            // Account for "/" home page
            $page_uris[$key] = $page_uris[$key] == '' ? '/' : '/' . $page_uris[$key];

            // be sanitary
            foreach ($row as $field => $value) {
                $row[$field] = ee()->db->escape_str($value);
            }

            // build insert rows
            $row_insert[] = "('" . implode("','", $row) . "')";
        }

        // Multi-line insert of all Structure Data
        $sql = "REPLACE INTO exp_structure (" . implode(', ', array_keys($row)) . ") VALUES " . implode(', ', $row_insert);
        ee()->db->query($sql);

        // Update the timestamp on the root node so we can ensure another reorder with older data doesn't corrupt the tree.
        $updated_time = date('Y-m-d H:i:s');
        ee()->db->where('dead', 'root')->update('exp_structure', array('updated' => $updated_time));

        // Update Site Pages
        $site_pages['uris'] = $page_uris;

        // Sorting pages blows away the listing data, so all URLs for listing pages
        // are no longer in the site_pages array... lets fix that.
        foreach ($site_pages['uris'] as $entry_id => $uri) {
            // get the listing channel_id tied to this entry_id
            $listing_channel = $this->sql->get_listing_channel($entry_id);

            if ($listing_channel !== false) {
                // Retrieve all entries for listing_channel id FROM the structure_listings table
                $listing_entries = $this->sql->get_channel_listing_entries($listing_channel);

                // get the entry_id and url_title for the listing channel
                $channel_entries = ee()->db->query("SELECT entry_id, url_title FROM exp_channel_titles WHERE channel_id = $listing_channel AND site_id = $site_id LIMIT 99999999");

                // get default template
                $structure_channels = $this->structure->get_structure_channels();
                $default_template = (!empty($structure_channels[$listing_channel]['template_id']) ? $structure_channels[$listing_channel]['template_id'] : 0);

                $listing_data = array();

                // loop over the channel entries for the listing
                foreach ($channel_entries->result_array() as $c_entry) {
                    // populate the listing_data
                    $listing_data[] = array(
                        'site_id' => $site_id,
                        'channel_id' => $listing_channel,
                        'parent_id' => $entry_id,
                        'entry_id' => $c_entry['entry_id'],
                        'template_id' => (!empty($listing_entries[$c_entry['entry_id']]['template_id']) ? $listing_entries[$c_entry['entry_id']]['template_id'] : $default_template),
                        'parent_uri' => (!empty($site_pages['uris'][$entry_id]) ? $site_pages['uris'][$entry_id] : ''),
                        'uri' => (!empty($listing_entries[$c_entry['entry_id']]['uri']) ? $listing_entries[$c_entry['entry_id']]['uri'] : $c_entry['url_title'])
                    );

                    $site_pages['uris'][$c_entry['entry_id']] = $this->structure->create_full_uri($site_pages['uris'][$entry_id], (!empty($listing_entries[$c_entry['entry_id']]['uri']) ? $listing_entries[$c_entry['entry_id']]['uri'] : $c_entry['url_title']));
                    $site_pages['templates'][$c_entry['entry_id']] = (!empty($listing_entries[$c_entry['entry_id']]['template_id']) ? $listing_entries[$c_entry['entry_id']]['template_id'] : $default_template);
                }
            }
        }

        // And save this moved page to the array
        $this->structure->set_site_pages($site_id, $site_pages);
        if ($this->logging === true) {
            ee()->logger->log_action("Nav Reordered by " . ee()->session->userdata('screen_name'));
        }

        // capture nav history here
        add_structure_nav_revision($site_id, 'Post Ajax Reorder');

        // -------------------------------------------
        // 'structure_reorder_end' hook.
        //
        if (ee()->extensions->active_hook('structure_reorder_end') === true) {
            ee()->extensions->call('structure_reorder_end', $data, $site_pages);
        }
        //
        // -------------------------------------------

        die(json_encode(array('success' => true, 'timestamp' => $updated_time)));
    }

    /**
     * Channel settings page
     * @param string $message
     */
    public function channel_settings($message = false)
    {
        // Load Libraries and Helpers
        ee()->load->library('javascript');
        ee()->load->library('table');
        ee()->load->helper('form');

        ee()->cp->load_package_js('structure-actions');
        ee()->cp->load_package_js('structure-forms');

        // Set Breadcrumb and Page Title
        ee()->cp->set_breadcrumb($this->base_url, ee()->lang->line('structure_module_name'));
        $this->set_cp_title('cp_channel_settings_title');

        $settings = $this->sql->get_settings();

        // Check if we have admin permission
        $permissions = array();
        $permissions['admin'] = $this->sql->user_access('perm_admin_structure', $settings);
        $permissions['view_add_page'] = $this->sql->user_access('perm_view_add_page', $settings);
        $permissions['delete'] = $this->sql->user_access('perm_limited_delete', $settings);
        $permissions['admin_channels'] = $this->sql->user_access('perm_admin_channels', $settings);

        // Vars to send into view
        $vars = array();
        $vars['ee_ver'] = substr(APP_VER, 0, 1);
        $vars['data'] = $this->sql->get_data();
        $vars['action_url'] = ee('CP/URL')->make('addons/settings/structure/channel_settings_submit');
        $vars['attributes'] = array('class' => 'structure-form', 'id' => 'structure_settings');
        $vars['channel_data'] = $this->sql->get_structure_channels('', '', 'alpha');
        $vars['are_page_channels'] = $this->sql->get_structure_channels('page', '', 'alpha');
        $vars['page_count'] = $this->sql->get_page_count();
        $vars['templates'] = $this->sql->get_templates();
        $vars['permissions'] = $permissions;
        $vars['channel_check'] = false;
        $vars['valid_channels'] = $this->sql->get_structure_channels('page', '', 'alpha', true);
        $vars['assigned_channels'] = is_array(ee()->session->userdata('assigned_channels')) ? ee()->session->userdata('assigned_channels') : array();

        $page_choices = array();
        if (is_array($vars['valid_channels'])) {
            $page_choices = array_intersect_key($vars['valid_channels'], $vars['assigned_channels']);
        }

        $vars['page_choices'] = $page_choices;

        if ($page_choices && count($page_choices) == 1) {
            $vars['add_page_url'] = ee()->general_helper->cpURL('publish', 'create', array('channel_id' => key($page_choices)));
        } else {
            $vars['add_page_url'] = '#';
        }

        $add_body = '';
        $add_urls = array();

        $vc_total = !empty($vars['valid_channels']) ? count($vars['valid_channels']) : 0;
        $vci = 0;
        if (is_array($vars['valid_channels']) && count($vars['valid_channels']) > 0) {
            foreach ($vars['valid_channels'] as $key => $channel) {
                $vci++;
                $add_url = (string) ee()->general_helper->cpURL('publish', 'create', array('channel_id' => $key, 'template_id' => $channel['template_id']));
                $add_urls[] = $add_url;
                $add_body .= '<li';
                $add_body .= $vci == $vc_total ? ' class="last">' : '>';
                $add_body .= '<a href="' . $add_url . '">' . $channel['channel_title'] . '</a></li>';
            }
        }
        if ($add_body) {
            $add_body = '<ul class="plain">' . $add_body . '</ul>';
        }

        $dialogs = array(
            'add' => array(
                'urls' => $add_urls,
                'title' => ee()->lang->line('select_page_type'),
                'body' => $add_body,
                'buttons' => array('cancel' => ee()->lang->line('cancel'))
            ),
            'del' => array(
                'title' => '',
                'body' => ee()->lang->line('structure_delete_confirm'),
                'buttons' => array(
                    'del' => ee()->lang->line('delete_page'),
                    'cancel' => ee()->lang->line('cancel')
                )
            )
        );

        $settings_array = array(
            'dialogs' => $dialogs,
            'site_id' => ee()->config->item('site_id'),
            'xid' => XID_SECURE_HASH,
            'global_add_page' => $settings['show_global_add_page'],
            'show_picker' => $settings['show_picker'],
        );

        $settings_json = json_encode($settings_array);

        ee()->cp->add_to_foot('
        <script type="text/javascript">
            var structure_settings = ' . $settings_json . ';
        </script>');

        // Check for ANY channels
        $query = ee()->db->query("SELECT channel_id FROM exp_channels WHERE site_id = $this->site_id");
        if ($query->num_rows() > 0) {
            $vars['channel_check'] = true;
        }

        return ee()->general_helper->view('channel_settings', $vars, true);
    }

    // Process form data from the channel settings area
    public function channel_settings_submit()
    {
        $channel_data = $this->sql->get_structure_channels('', '', 'alpha');

        $form_data = array();
        foreach ($_POST as $key => $value) {
            $form_data[$key] = array(
                'site_id' => $this->site_id,
                'channel_id' => $key,
                'type' => $value['type'],
                'template_id' => $value['template_id'],
                'split_assets' => isset($value['split_assets']) ? $value['split_assets'] : 'n',
                'show_in_page_selector' => isset($value['show_in_page_selector']) ? $value['show_in_page_selector'] : 'n'
            );
        }

        $channels = array();
        $results = ee()->db->get_where('structure_channels', array('site_id' => $this->site_id));
        if ($results->num_rows() > 0) {
            foreach ($results->result_array() as $row) {
                $channels[$row['channel_id']] = $row;
            }
        }

        $vars = array();
        $to_be_deleted = array();
        $updated_channels = array();
        $unmanaged_channels = array();

        // Insert the shiny new data
        foreach ($form_data as $channel_id => $data) {
            // If channel is updated to be 'unmanaged', mark it as to_be_deleted but don't update the record prematurely.
            // Otherwise, Update or Insert the data.
            if ($data['type'] == 'unmanaged' && ($channel_data[$channel_id]['type'] === 'page' || $channel_data[$channel_id]['type'] === 'listing')) {
                $to_be_deleted[] = $data['channel_id'];
            } else {
                if (count($channels) > 0 && array_key_exists($channel_id, $channels)) {
                    ee()->db->where(array('channel_id' => $channel_id, 'site_id' => $this->site_id))->update('structure_channels', $data);
                } else {
                    ee()->db->insert('structure_channels', $data);
                }

                if ($data['type'] == 'unmanaged') {
                    $unmanaged_channels[] = $channel_id;
                } else {
                    $updated_channels[] = $channel_id;
                }
            }
        }

        // EE 4.2 Live Preview requires a Preview URL entered in each channel or the Live Preview breaks
        // so if this is a Structure managed channel, make sure something is in the Preview URL field.
        foreach ($form_data as $channel_id => $data) {
            if (ee()->db->field_exists('preview_url', 'channels') && $data['type'] != 'asset') {
                if (!empty($updated_channels)) {
                    ee()->db->where("(preview_url='' OR preview_url IS NULL)");
                    ee()->db->where_in('channel_id', $channel_id);
                    ee()->db->update('channels', array('preview_url' => 'Managed by Structure - Changes here will not have any effect'));
                }

                if (!empty($unmanaged_channels)) {
                    ee()->db->where('preview_url', 'Managed by Structure - Changes here will not have any effect');
                    ee()->db->where_in('channel_id', $channel_id);
                    ee()->db->update('channels', array('preview_url' => ''));
                }
            } elseif (ee()->db->field_exists('preview_url', 'channels') && $data['type'] == 'asset') {
                $builder = ee('Model')->get('Channel')->filter('channel_id', '==', $channel_id)->first();
                $builder->preview_url = null;
                $builder->save();
            }
        }

        if (count($to_be_deleted) > 0) {
            $this->set_cp_title('confirm_delete_channels');
            ee()->cp->set_breadcrumb($this->base_url, ee()->lang->line('structure_module_name'));

            // Get channel titles
            ee()->db->select('channel_id, channel_title')->from('channels')->where_in('channel_id', $to_be_deleted);
            $results = ee()->db->get();

            $vars['ee_ver'] = substr(APP_VER, 0, 1);
            $vars['channel_titles'] = $results->result_array();
            $vars['to_be_deleted'] = implode(',', $to_be_deleted);

            $vars['action_url'] = ee('CP/URL')->make('addons/settings/structure/delete_channels');
            $vars['attributes'] = array('class' => 'forms', 'id' => 'delete_channel');
            $vars['base_url'] = ee('CP/URL')->make('addons/settings/structure/channel_settings');

            return ee()->general_helper->view('delete_channels_confirm', $vars, true);
        }

        ee('CP/Alert')->makeInline('Channel Settings Updated')
            ->asSuccess()->withTitle('Channel Settings Updated')
            ->canClose()->defer();

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/structure/channel_settings'));
    }

    public function delete_channels()
    {
        $channel_ids = explode(',', ee()->input->get_post('channel_ids'));

        // add structure nav history beofre deleting data by channel
        $deleted_structure_data_channels = print_r($channel_ids, true);
        // add_structure_nav_revision($this->site_id, 'Pre deleting data by channel_ids - ' . $deleted_structure_data_channels);

        foreach ($channel_ids as $key => $channel) {
            $this->structure->delete_data_by_channel($channel);

            // Mark the channel as "unmanaged" and set it's values back to defaults.
            $channel_data = array(
                'site_id' => $this->site_id,
                'channel_id' => $channel,
                'type' => 'unmanaged',
                'template_id' => 0,
                'split_assets' => 'n',
                'show_in_page_selector' => 'n'
            );

            ee()->db->where(array('channel_id' => $channel, 'site_id' => $this->site_id))->update('structure_channels', $channel_data);
        }

        // save nav history after deleting channels
        add_structure_nav_revision($this->site_id, 'Post deleting data by channel_ids - ' . $deleted_structure_data_channels);

        ee('CP/Alert')->makeInline('Channels Removed Successfully!')
            ->asSuccess()->withTitle('Channels Removed Successfully!')
            ->canClose()->defer();

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/structure/channel_settings'));
    }

    /**
     * Module settings page
     * @param string $message
     */
    public function module_settings($message = false)
    {
        // Load Libraries and Helpers
        ee()->load->library('javascript');
        ee()->load->library('table');
        ee()->load->helper('form');
        ee()->cp->load_package_js('structure-forms');

        $site_id = ee()->config->item('site_id');

        $defaults = array(
            'show_picker'           => 'y',
            'show_view_page'        => 'y',
            'show_status'           => 'y',
            'show_page_type'        => 'y',
            'show_global_add_page'  => 'y',
            'redirect_on_login'     => 'n',
            'redirect_on_publish'   => 'n',
            'hide_hidden_templates' => 'n',
            'add_trailing_slash' => 'y'
        );

        // Set Breadcrumb and Page Title
        ee()->cp->set_breadcrumb($this->base_url, ee()->lang->line('structure_module_name'));
        $this->set_cp_title('cp_module_settings_title');

        $settings = $this->sql->get_settings();
        $groups = $this->sql->get_member_groups();

        // Check if we have admin permission
        $permissions = array();
        $permissions['admin'] = $this->sql->user_access('perm_admin_structure', $settings);
        $permissions['view_nav_history'] = $this->sql->user_access('perm_view_nav_history', $settings);
        $permissions['view_validation'] = $this->sql->user_access('perm_view_validation', $settings);
        $permissions['reorder'] = $this->sql->user_access('perm_reorder', $settings);
        $permissions['view_add_page'] = $this->sql->user_access('perm_view_add_page', $settings);
        $permissions['delete'] = $this->sql->user_access('perm_limited_delete', $settings);

        // Vars to send into view
        $vars = array();
        $vars['ee_ver'] = substr(APP_VER, 0, 1);
        $vars['action_url'] = ee('CP/URL')->make('addons/settings/structure/module_settings_submit');
        $vars['attributes'] = array('class' => 'structure-form', 'id' => 'module_settings');
        $vars['groups'] = $groups;
        $vars['perms'] = $this->perms;
        $vars['settings'] = $settings;
        $vars['permissions'] = $permissions;
        $vars['extension_is_installed'] = $this->sql->extension_is_installed();
        $vars['redirect_types'] = array(
            'y' => 'All Entries',
            'structure_only' => 'Structure Managed Only',
            'n' => 'No'
        );
        if ($this->extra_reorder_options === true) {
            $vars['level_permission_types'] = array(
                'all'       => 'All pages',
                'not_top_1' => 'All but top level',
                'not_top_2' => 'All but top 2 levels',
                'not_top_3' => 'All but top 3 levels',
                'none'      => 'No pages'
            );
        } else {
            $vars['level_permission_types'] = array(
                'all' => 'All pages',
                'not_top_1' => 'All but top level',
                'none' => 'No pages'
            );
        }

        // Check to make sure all settings have a value
        foreach ($defaults as $key => $default) {
            if (! isset($vars['settings'][$key])) {
                $vars['settings'][$key] = $default;
            }
        }

        return ee()->general_helper->view('module_settings', $vars, true);
    }

    // Process form data from the module settings area
    public function module_settings_submit()
    {
        $site_id = ee()->config->item('site_id');

        // clense current settings out of DB
        $sql = "DELETE FROM exp_structure_settings WHERE site_id = $site_id";
        ee()->db->query($sql);

        // insert settings into DB
        foreach ($_POST as $key => $value) {
            // Good heavens, this is just plain ghetto. If there is no "perm", it's a "setting"
            // if if there's no "perm" AND it's not a number, then it's a multi-option permission.
            $value = strpos($key, 'perm_') === 0 && is_numeric($value) ? 'y' : $value;
            if ($key !== 'submit') {
                ee()->db->query(ee()->db->insert_string(
                    "exp_structure_settings",
                    array(
                        'var'       => $key,
                        'var_value' => $value,
                        'site_id'   => $site_id
                    )
                ));
            }
        }

        ee('CP/Alert')->makeInline('Structure Settings Updated')
            ->asSuccess()->withTitle('Structure Settings Updated')
            ->canClose()->defer();

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/structure/module_settings'));
    }

    public function delete()
    {
        $ids = ee()->input->get_post('toggle');

        $flat_ids = print_r($ids, true);

        // Add Structure history pre deleting data
        // add_structure_nav_revision($this->site_id, 'Pre deleting data - ids ' . $flat_ids);

        $this->structure->delete_data($ids);

        // Add Structure history post deleting data
        add_structure_nav_revision($this->site_id, 'Post deleting data - ids ' . $flat_ids);

        ee()->functions->redirect($this->base_url);
    }

    /**
     * Retrieve site path
     */
    public function get_site_path()
    {
        // extract path info
        $site_url_path = parse_url(ee()->functions->fetch_site_index(), PHP_URL_PATH);

        $path_parts = pathinfo($site_url_path);
        $site_path = $path_parts['dirname'];

        $site_path = str_replace("\\", "/", $site_path);

        return $site_path;
    }

    /**
     * Validation page to make sure Structure is behaving properly & some tools to fix potential issues.
     **/
    public function validation()
    {
        // get settings
        $settings = $this->sql->get_settings();

        // confirm user has access, if not redirect to structure main page
        if (!$this->sql->user_access('perm_view_validation', $settings)) {
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/structure/index'));
        }

        $listing_channels = $this->sql->get_structure_channels('listing');

        // If we have listing channels, make sure the Structure map of channels is up to date.
        if (is_array($listing_channels) && count($listing_channels) > 0) {
            foreach ($listing_channels as $channel_id => $row) {
                ee()->db->where('channel_id', $channel_id);
                ee()->db->update('structure_listings', array('site_id' => $this->site_id));
            }
        }

        $vars = array();

        // Set Breadcrumb and Page Title
        ee()->cp->set_breadcrumb($this->base_url, ee()->lang->line('structure_module_name'));
        $this->set_cp_title('validation');
        $duplicates = $this->sql->cleanup_check();

        $vars['ee_ver'] = substr(APP_VER, 0, 1);
        $vars['action_url'] = ee('CP/URL')->make('addons/settings/structure/validation_submit');
        $vars['attributes'] = array('class' => 'structure-form', 'id' => '');

        $vars['total_site_pages_entries'] = (!empty($duplicates['total_site_pages_entries']) ? $duplicates['total_site_pages_entries'] : 0);
        $vars['total_structure_entries'] = (!empty($duplicates['total_structure_entries']) ? $duplicates['total_structure_entries'] : 0);

        if ($vars['total_structure_entries'] == $vars['total_site_pages_entries']) {
            $vars['total_entries_class'] = 'success';
        } else {
            $vars['total_entries_class'] = 'error';
        }

        $vars['total_site_pages_duplicates'] = $duplicates['total_site_pages_duplicates'];
        $vars['orphaned_entries'] = $duplicates['orphaned_entries'];

        $vars['ee_orphans'] = (!empty($duplicates['ee_orphans']) ? $duplicates['ee_orphans'] : 0);

        $vars['site_pages_orphans'] = (!empty($duplicates['site_pages_orphans']) ? $duplicates['site_pages_orphans'] : 0);
        $vars['site_pages_listing_orphans'] = (!empty($duplicates['site_pages_listing_orphans']) ? $duplicates['site_pages_listing_orphans'] : 0);
        $vars['structure_orphans'] = (!empty($duplicates['structure_orphans']) ? $duplicates['structure_orphans'] : 0);
        $vars['structure_listing_orphans'] = (!empty($duplicates['structure_listing_orphans']) ? $duplicates['structure_listing_orphans'] : 0);

        if ($vars['ee_orphans'] == 0 && $vars['site_pages_orphans'] == 0 && $vars['structure_orphans'] == 0 && $vars['structure_listing_orphans'] == 0) {
            $vars['total_missing_class'] = 'success';
        } else {
            $vars['total_missing_class'] = 'error';
        }

        $vars['validation_action_enabled'] = $duplicates['validation_action_enabled'];

        $vars['duplicate_rights'] = $duplicates['duplicate_rights'];
        $vars['duplicate_lefts'] = $duplicates['duplicate_lefts'];
        $vars['site_pages_uri_duplicates'] = $duplicates['site_pages_uri_duplicates'];

        $vars['mismatch_url_entries'] = $duplicates['mismatch_url_entries'];
        $vars['template_id_errors'] = $duplicates['template_id_errors'];

        $vars['listing_id_fix_url'] = ee('CP/URL')->make('addons/settings/structure/listing_site_id_fix');

        $vars['entries_missing_from_structure'] = $this->entries_missing_from_structure();

        $vars['themes_dir'] = URL_THEMES;

        // -------------------------------------------
        // 'structure_data_validation' hook.
        // - Used to allow other add-ons to validate structure data
        //
        if (ee()->extensions->active_hook('structure_data_validation') === true) {
            $vars['other_validations'] = ee()->extensions->call('structure_data_validation', $vars);
        }
        //
        // -------------------------------------------
        return ee()->general_helper->view('validation', $vars, true);
    }

    public function validation_site_pages()
    {
        $site_pages = $this->sql->get_site_pages(true);
        echo '<pre>';
        var_dump($site_pages);
        echo '</pre>';
        exit;
    }

    public function validation_rebuild_structure()
    {
        $this->sql->update_integrity_data();
        echo 'Integrity Data Restored';
        exit;
    }

    /**
     * This function is used to display the previsous navigations a user has had set through Structure
     *
     * @method nav_history
     * @return view
     */
    public function nav_history()
    {
        // get settings
        $settings = $this->sql->get_settings();

        // confirm user has access, if not redirect to structure main page
        if (!$this->sql->user_access('perm_view_nav_history', $settings)) {
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/structure/index'));
        }

        ee()->load->library('pagination');
        ee()->load->library('table');

        ee()->cp->load_package_js('structure-history');

        // setup pagination
        $config = array();
        $config['base_url'] = $this->base_url . AMP . 'method=nav_history';
        $config['page_query_string'] = true;
        $config['total_rows'] = ee()->db->get('structure_nav_history')->num_rows();
        $config['per_page'] = 50;

        ee()->pagination->initialize($config);

        // generate the links
        $this->data['pagination'] = ee()->pagination->create_links();

        // get current pagination page
        $current_page = ee()->input->get('per_page', 1);

        $structure_nav_history = ee()->db->select('*')->order_by('id', 'desc')->get('structure_nav_history', $config['per_page'], $current_page);
        $this->data['structure_nav_history'] = $structure_nav_history->result();
        $this->data['base_url'] = $this->base_url;

        return ee()->load->view('nav_history', $this->data, true);
    }

    /**
     * This function is used to restore the chosen navigation state.
     *
     * @method restore
     * @return restore nav they have chosen and redirect user back to navigation choice page
     */
    public function restore()
    {
        $id = ee()->input->get_post('id');

        // quick check to make sure its a number
        if (is_numeric($id)) {
            $nav_to_rollback_to = ee()->db->get_where('structure_nav_history', array('id' => $id));

            if ($nav_to_rollback_to->num_rows == 1) {
                // we have our nav... lets restore it
                $nav_to_rollback_to = $nav_to_rollback_to->row();

                // lets restore site_pages
                ee()->db->where('site_id', $nav_to_rollback_to->site_id)->update('sites', array('site_pages' => $nav_to_rollback_to->site_pages));

                // ok, now lets restore the structure table for this site id...
                // second paramater of true forces the decode to an array for our insert_batch
                $structure_table_data = json_decode($nav_to_rollback_to->structure, true);

                // ok remove old stuff for this site_id from the structure table
                ee()->db->delete('structure', array('site_id' => $nav_to_rollback_to->site_id));

                ee()->db->insert_batch('structure', $structure_table_data);

                // nav is restored... lets make sure the DB sets the right one as active.
                ee()->db->where('current', '1')->update('structure_nav_history', array('current' => 0));
                ee()->db->where('id', $id)->update('structure_nav_history', array('current' => 1, 'restored_date' => date('Y-m-d H:i:s')));

                // we succeeded... lets go ahead and send the user back to the nav history screen
                ee('CP/Alert')->makeInline('Updated!')
                    ->asSuccess()->withTitle('Updated!')
                    ->canClose()->defer();

                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/structure/nav_history'));
            }
        }
    }

    // Process form data from the module settings area
    public function validation_submit()
    {
        if (!empty($_GET['mode'])) {
            $mode = $_GET['mode'];
        } else {
            $mode = ee()->input->post('mode');
        }

        $this->sql->cleanup($mode);
        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/structure/validation'));
    }

    public function listing_site_id_fix()
    {
        $listing_channels = $this->sql->get_structure_channels('listing');

        foreach ($listing_channels as $channel_id => $row) {
            ee()->db->where('channel_id', $channel_id);
            ee()->db->update('structure_listings', array('site_id' => $this->site_id));
        }

        ee('CP/Alert')->makeInline('Updated!')
            ->asSuccess()->withTitle('Updated!')
            ->canClose()->defer();

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/structure/validation'));
    }

    public function entries_missing_from_structure()
    {
        $channels_result = $this->sql->get_structure_channels('page');

        $missing = array();
        $channels = array();

        if (!empty($channels_result)) {
            foreach ($channels_result as $channel_id => $row) {
                $channels[] = $channel_id;
            }

            $missing_entries = ee()->db->query("SELECT t1.entry_id, t1.title FROM exp_channel_titles t1 LEFT JOIN exp_structure t2 ON t2.entry_id=t1.entry_id WHERE t1.channel_id IN (" . implode(',', $channels) . ") AND t2.entry_id IS NULL")->result();

            if (!empty($missing_entries)) {
                foreach ($missing_entries as $missing_entry) {
                    $missing[] = array(
                        'ee_url' => ee()->general_helper->cpURL('publish', 'edit', array('entry_id' => $missing_entry->entry_id)),
                        'entry_id' => $missing_entry->entry_id,
                        'title' => $missing_entry->title
                    );
                }
            }
        }

        return $missing;
    }

    private function set_cp_title($title)
    {
        ee()->view->cp_page_title = ee()->lang->line($title);
    }
}
/* END Class */

/* End of file mcp.structure.php */
