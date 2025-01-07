<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use EllisLab\ExpressionEngine\Service\URL;

require_once PATH_ADDONS . 'structure/addon.setup.php';
require_once PATH_ADDONS . 'structure/helper.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';
require_once PATH_ADDONS . 'structure/mod.structure.php';

class Structure_ext
{
    public $settings = array();
    public $settings_exist = 'n';

    public $sql;
    public $version;
    public $site_pages;
    public $entry_id;
    public $parent_id;
    public $page_title;
    public $uri;
    public $structure;
    public $segment_1;
    public $top_id;

    public function __construct($settings = '')
    {
        $this->version = STRUCTURE_VERSION;

        $this->sql = new Sql_structure();

        if (!ee()->addons_model->module_installed('structure')) {
            return false;
        }

        $this->site_pages = $this->sql->get_site_pages();

        $this->entry_id = false;
        $this->parent_id = false;
        $this->page_title = false;
    }

    /**
     * Activate Extension
     * @return void
     */
    public function activate_extension()
    {
        $hooks = array(
            'after_channel_entry_save'          => 'after_channel_entry_save',
            'sessions_end'                      => 'sessions_end',
            'entry_submission_redirect'         => 'entry_submission_redirect',
            'cp_member_login'                   => 'cp_member_login',
            'sessions_start'                    => 'sessions_start',
            'pagination_create'                 => 'pagination_create',
            'wygwam_config'                     => 'wygwam_config',
            'core_template_route'               => 'core_template_route',
            'entry_submission_end'              => 'entry_submission_end',
            'channel_form_submit_entry_end'     => 'channel_form_submit_entry_end',
            'template_post_parse'               => 'template_post_parse',
            'cp_custom_menu'                    => 'cp_custom_menu',
            'publish_live_preview_route'        => 'publish_live_preview_route',
            'entry_save_and_close_redirect'     => 'entry_save_and_close_redirect',
        );

        foreach ($hooks as $hook => $method) {
            $this->registerExtension($method, $hook);
        }
    }

    /**
     * Disable Extension
     * @return void
     */
    public function disable_extension()
    {
        ee()->db->where('class', 'Structure_ext');
        ee()->db->delete('extensions');

        ee()->db->where('data', 'Structure_ext');
        ee()->db->delete('menu_items');

        return true;
    }

    /**
     * Update Extension
     * @return  mixed   void on update / false if none
     */
    public function update_extension($current = false)
    {
        $updated = false;

        if (! $current || $current == $this->version) {
            return false;
        }

        // add pagination and wygwam hooks
        if (version_compare($current, '3.0', "<")) {
            $this->registerExtension("channel_module_create_pagination");
            $this->registerExtension("wygwam_config");
            $updated = true;
        }

        // add saef hook
        if (version_compare($current, '3.0.5', "<")) {
            $this->registerExtension("entry_submission_end");
            $updated = true;
        }

        // add safecracker hook
        if (version_compare($current, '3.1.4', "<")) {
            $this->registerExtension("safecracker_submit_entry_end");
            $updated = true;
        }

        // add template_post_parse hook
        if (version_compare($current, '3.2.4', "<")) {
            $this->registerExtension("template_post_parse");
            $updated = true;
        }

        // add core_template_route hook
        if (version_compare($current, '3.2.5', "<")) {
            $this->registerExtension("core_template_route");
            $updated = true;
        }

        // add after_channel_entry_save hook
        if (version_compare($current, '4.0.0-a.1', "<")) {
            $this->registerExtension("after_channel_entry_save");

            ee()->db->update(
                'extensions',
                array('hook' => 'pagination_create'),
                array('method' => 'channel_module_create_pagination', 'class' => __CLASS__)
            );

            $updated = true;
        }

        // add cp_custom_menu hook
        if (version_compare($current, '4.0.1', "<")) {
            $this->registerExtension("cp_custom_menu");
            $updated = true;
        }

        if (version_compare($current, '4.1.0', "<")) {
            $this->registerExtension("sessions_end");
            $this->unregisterExtension("after_channel_entry_insert");
            $this->unregisterExtension("after_channel_entry_update");
            $updated = true;
        }

        if (version_compare($current, '4.1.4', "<")) {
            $this->registerExtension("after_channel_entry_save");
            $updated = true;
        }

        if (version_compare($current, '4.3.10', "<")) {
            $this->registerExtension("publish_live_preview_route");
            $updated = true;
        }

        if (version_compare($current, '4.3.17', "<")) {
            $this->registerExtension("publish_live_preview_route");
            $this->registerExtension("entry_save_and_close_redirect");
            $updated = true;
        }

        if (version_compare($current, '4.4.3', "<")) {
            $this->registerExtension("entry_save_and_close_redirect");
            $updated = true;
        }

        if (version_compare($current, '4.8.0', "<")) {
            $this->unregisterExtension("rte_autocomplete_pages");
            $updated = true;
        }

        if ($updated) {
            $this->updateVersion();
        }
    }

    /**************************************************\
     ******************* ALL HOOKS: *******************
    \**************************************************/

    /**
     * cp_custom_menu
     */
    public function cp_custom_menu($menu)
    {
        // Do work only on control panel requests
        if (REQ != 'CP') {
            return true;
        }

        $menu->addItem('Structure', ee('CP/URL')->make('addons/settings/structure/index'));
    }

    public function publish_live_preview_route($data, $uri, $template_id)
    {
        $structure_uri = '';
        if (!empty($data['structure__parent_id'])) {
            $structure_uri = (!empty($this->site_pages['uris'][$data['structure__parent_id']])) ? rtrim($this->site_pages['uris'][$data['structure__parent_id']], '/') . '/' : '';
        }

        $structure_uri .= (!empty($data['structure__uri'])) ? $data['structure__uri'] : $uri;

        $structure_uri = Structure_Helper::remove_double_slashes($structure_uri);

        if (isset($data['entry_id']) && !empty($data['entry_id'])) {
            ee()->uri->page_query_string = $data['entry_id'];
        }

        return array(
            'uri' => $structure_uri,
            'template_id' => (!empty($data['structure__template_id']) ? $data['structure__template_id'] : $template_id),
        );
    }

    public function entry_save_and_close_redirect($entry, $orig_redirect_url = '')
    {
        $meta['channel_id'] = $entry->channel_id;

        // Determine if we should redirect after save and close based on the Structure settings.
        $redirect_url = $this->_redirect_url($entry->entry_id, $meta, null, true, null, false);

        // EE Does not pass the redirect url in this call nor does it check for null on the call return
        // so we have to create our own default redirect if we're not redirecting.
        if (empty($redirect_url) && empty($orig_redirect_url)) {
            $redirect_url = ee('CP/URL')->make('publish/edit/', array('filter_by_channel' => $entry->channel_id));
        }

        // fallback to passed value
        if (empty($redirect_url)) {
            $redirect_url = $orig_redirect_url;
        }

        return $redirect_url;
    }

    public function sessions_end($session)
    {
        // If we set a redirect flashdata flag after saving an entry on the previous page, perform the redirect
        // now (to fix the hook firing order issue described below).
        if (!empty($session->flashdata['structure_redirect']) && $session->flashdata['structure_redirect'] == 1) {
            $meta = array();
            $meta['channel_id'] = 0;

            if (!empty($session->flashdata['structure_channel_id'])) {
                $meta['channel_id'] = $session->flashdata['structure_channel_id'];
            }

            $path = 'addons/settings/structure/index';

            $session_type = ee()->config->item('cp_session_type');

            if ($session_type == 'cs') {
                $session_id = $session->userdata['fingerprint'];
            } elseif ($session_type == 's') {
                $session_id = $session->userdata['session_id'];
            } else {
                $session_id = null;
            }

            $url = new \ExpressionEngine\Library\CP\URL(
                $path,
                $session_id,
                '',
                ee()->config->item('cp_url')
            );

            $url = $url->compile();

            $should_redirect = $this->_redirect_url(null, $meta, null, true, null, true);

            // We have to do a PHP `header()` call here because `ee()->redirect()` calls `ee()->session()` which isn't available yet.
            if ($should_redirect) {
                header('Cache-Control: no-cache');
                header('Pragma: no-cache');
                die(header("Location: $url", true, 307));
            }
        }
    }

    public function after_channel_entry_save($entry, $values)
    {
        if (REQ !== 'CP') {
            return false;
        }

        // If `submit` is set, and it is not set to edit ("Save" from the Channel Entry instead of "Save and Close"),
        // set a custom flashdata flag we check in `sessions_end` to do the redirect on the next page. We have to do
        // this because of EE firing this hook BEFORE `after_channel_entry_update` which causes issues with other add-ons.
        // Confirmed with Kevin Cupp that they will not be changing the hook order for v4 at least.
        $submit_mode = ee()->input->post('submit');

        if (!empty($submit_mode) && ($submit_mode === 'save_and_close' || $submit_mode === 'finish')) {
            ee()->session->set_flashdata('structure_redirect', '1');
            ee()->session->set_flashdata('structure_channel_id', $entry->channel_id);
        }
    }

    public function entry_submission_redirect()
    {
        // Get the function arguments
        $args = func_get_args();

        $entry = $args[0];

        if (ee()->input->post('submit') == 'save') {
            $redirect_url = ee('CP/URL')->make('publish/edit/entry/' . $entry->getId());
        } else {
            $redirect_url = ee('CP/URL')->make('publish/create/' . $entry->channel_id);
        }

        return $redirect_url;
    }

    public function cp_member_login()
    {
        $settings = $this->sql->get_settings();

        if (AJAX_REQUEST) {
            return;
        }

        if (isset($settings['redirect_on_login']) && $settings['redirect_on_login'] == 'y') {
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/structure/index'));
        }
    }

    private function _redirect_url($entry_id, $meta, $data, $cp_call, $orig_loc, $return_boolean = false)
    {
        $settings = $this->sql->get_settings();

        if ($cp_call === true && isset($settings['redirect_on_publish']) && $settings['redirect_on_publish'] != 'n') {
            if ($settings['redirect_on_publish'] == 'y') {
                if ($return_boolean) {
                    return true;
                } else {
                    return ee('CP/URL')->make('addons/settings/structure/index');
                }
            }

            if ($settings['redirect_on_publish'] == 'structure_only') {
                $ci = ee()->db->get_where('structure_channels', array('channel_id' => $meta['channel_id']));
                if ($ci->num_rows() > 0 && $ci->row('type') != 'unmanaged') {
                    if ($return_boolean) {
                        return true;
                    } else {
                        return ee('CP/URL')->make('addons/settings/structure/index');
                    }
                }
            }
        }

        if (!empty(ee()->extensions->last_call)) {
            $orig_loc = ee()->extensions->last_call;
        }

        if ($return_boolean) {
            return false;
        } else {
            return $orig_loc;
        }
    }

    public function sessions_start($ee)
    {
        $isLivePreviewRequest = false;

        // Check our request type is suitable. Live Preview adds a CP request type but
        // we need to check the URL so we're not handling non-preview requests.
        $original_uri = $this->sql->get_uri();
        $this->uri = $original_uri;

        $settings = $this->sql->get_settings();
        $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : null;

        // If this is a live-preview request, we have to process things slightly differently.
        if (REQ === 'CP' && strpos($this->uri, 'cp/publish/preview') !== false) {
            $isLivePreviewRequest = true;
        } elseif (REQ == 'ACTION') {
            $action_id = ee()->db->select('action_id')
                ->where('class', 'Channel')
                ->where('method', 'live_preview')
                ->get('actions');
            if ($action_id->num_rows() > 0 && ee()->input->get('ACT') == $action_id->row('action_id')) {
                $isLivePreviewRequest = true;
            }
        }
        if ($isLivePreviewRequest) {
            $this->page_title = ee()->input->post('title');
            $segment_count = ee()->uri->total_segments();

            if ($segment_count === 5) {
                $this->entry_id = ee()->uri->segment(5);
            }

            if (!empty($this->entry_id)) {
                $channel_id = $this->sql->get_channel_by_entry_id($this->entry_id);
                if ($this->sql->get_channel_type($channel_id) === 'unmanaged') {
                    return;
                }
            }

            $this->uri = ee()->input->post('structure__uri');

            // Are we being sent a Structure Parent ID? If so, get the parent's URI.
            if (!empty(ee()->input->post('structure__parent_id'))) {
                $this->parent_id = ee()->input->post('structure__parent_id');

                if (!empty($this->site_pages['uris'][$this->parent_id])) {
                    $this->uri = rtrim($this->site_pages['uris'][$this->parent_id], '/') . '/' . ltrim($this->uri, '/');
                }
            }

            ee()->uri->_set_uri_string($this->uri);
            ee()->uri->segments = array();
            ee()->uri->_explode_segments();
            ee()->uri->_reindex_segments();

            $this->segment_1 = ee()->uri->segment(1) ? '/' . ee()->uri->segment(1) : false;

            $this->top_id = array_search($this->segment_1 . $trailing_slash, $this->site_pages['uris']);

            $this->_create_global_vars(true);

        // ee()->uri->_set_uri_string($original_uri);
        // ee()->uri->segments = array();
        // ee()->uri->_explode_segments();
        // ee()->uri->_reindex_segments();
        } elseif ((REQ == 'PAGE' || REQ == 'ACTION') && array_key_exists('uris', $this->site_pages) && is_array($this->site_pages['uris']) && count($this->site_pages['uris']) > 0) {
            // -------------------------------------------
            //  Sanitize the URL for pagination and other bypasses
            // -------------------------------------------
            // $this->_create_clean_structure_segments();

            // -------------------------------------------
            //  Set all other class variables
            // -------------------------------------------

            $this->entry_id = array_search(strtolower($this->uri), array_map('strtolower', $this->site_pages['uris']));
            $this->parent_id = $this->sql->get_parent_id($this->entry_id, null);
            $this->segment_1 = ee()->uri->segment(1) ? '/' . ee()->uri->segment(1) : false;

            $this->top_id = array_search($this->segment_1 . $trailing_slash, $this->site_pages['uris']);

            // -------------------------------------------
            //  Create all Structure global variabes
            // -------------------------------------------

            $this->_create_global_vars();
        }
    }

    // adding this function since it's needed for comments in newer versions of EE still
    public function pagination_create($ee_obj)
    {
        return $this->channel_module_create_pagination($ee_obj);
    }

    public function channel_module_create_pagination($ee_obj)
    {
        $segment_array = explode('/', ee()->uri->uri_string);
        $segment_count = count($segment_array);
        $last_segment = $segment_array[$segment_count - 1];

        unset($segment_array[$segment_count - 1]);
        $new_basepath = Structure_Helper::remove_double_slashes('/' . implode('/', $segment_array));

        if (preg_match('/P\d+/', $last_segment)) {
            $ee_obj->offset = substr($last_segment, 1);
            $ee_obj->basepath = $this->site_pages['url'] . $new_basepath;

            // this is to fix a bug in EE3.4.x. It should not replace anything otherwise
            $ee_obj->basepath = str_replace("{base_url}/", ee()->config->item('base_url'), $ee_obj->basepath);
            $ee_obj->basepath = Structure_Helper::remove_double_slashes($ee_obj->basepath);
        }
    }

    public function wygwam_config($config, $settings)
    {
        if (empty($config)) {
            $config = array();
        }
        if (empty($settings)) {
            $settings = array();
        }

        // If another extension shares the same hook,
        // we need to get the latest and greatest config
        if (!empty(ee()->extensions->last_call)) {
            $config = ee()->extensions->last_call;
        }

        $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y';
        $slash = "";

        if ($trailing_slash !== false) {
            $slash = "/";
        }

        // get EE's record of site pages
        $site_pages = ee()->config->item('site_pages');
        $site_id = ee()->config->item('site_id');

        if (is_array($site_pages)) {
            $pages = $this->sql->get_data();
            foreach ($pages as $entry_id => $page_data) {
                // ignore if EE doesn't have a record of this page
                if (! isset($site_pages[$site_id]['uris'][$entry_id])) {
                    continue;
                }

                // this is to fix a bug in EE3.4.x. It should not replace anything otherwise
                $ee_url = ee()->functions->create_page_url($site_pages[$site_id]['url'] . $slash, $site_pages[$site_id]['uris'][$entry_id], false);
                $url = str_replace("{base_url}/", ee()->config->item('base_url'), $ee_url);

                // add this page to the config
                $config['link_types']['Structure Pages'][] = array(
                    'label' => $page_data['title'],
                    'label_depth' => $page_data['depth'],
                    'url' => $url
                );
            }

            $listing_channels = $this->sql->get_structure_channels('listing');

            if ($listing_channels !== false) {
                foreach ($listing_channels as $channel => $row) {
                    $entries = $this->sql->get_entry_titles_by_channel($row['channel_id']);
                    foreach ($entries as $page_data) {
                        // ignore if EE doesn't have a record of this page
                        if (! isset($site_pages[$site_id]['uris'][$page_data['entry_id']])) {
                            continue;
                        }

                        // this is to fix a bug in EE3.4.x. It should not replace anything otherwise
                        $ee_url = ee()->functions->create_page_url($site_pages[$site_id]['url'], $site_pages[$site_id]['uris'][$page_data['entry_id']], false);
                        $url = str_replace("{base_url}/", ee()->config->item('base_url'), $ee_url);

                        $config['link_types']['Structure Listing: ' . $row['channel_title']][] = array(
                            'label' => $page_data['title'],
                            'label_depth' => 0,
                            'url' => $url
                        );
                    }
                }
            }
        }

        return $config;
    }

    public function core_template_route($uri_string)
    {
        $segment_array = explode('/', $uri_string);
        $segment_count = count($segment_array);
        $last_segment = $segment_array[$segment_count - 1];

        if (preg_match('/P\d+/', $last_segment)) {
            $settings = $this->sql->get_settings();
            unset($segment_array[$segment_count - 1]);
            $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : null;

            $new_uri_string = Structure_Helper::remove_double_slashes('/' . implode('/', $segment_array) . $trailing_slash);

            $entry_id = array_search($new_uri_string, $this->site_pages['uris']);

            if ($entry_id) {
                $template_id = $this->site_pages['templates'][$entry_id];
                ee()->uri->page_query_string = $entry_id;

                // TODO:: I think we only need to select the template name and the group name.
                // This could be a large speed increase -- Matt
                ee()->db->select('*');
                ee()->db->from('templates');
                ee()->db->join('template_groups', 'templates.group_id = template_groups.group_id');
                ee()->db->where('templates.template_id', $template_id);

                $result = ee()->db->get();
                if ($result->num_rows() > 0) {
                    $row = $result->row();

                    return array($row->group_name, $row->template_name);
                }
            }
        }

        return ee()->extensions->last_call;
    }

    public function entry_submission_end($entry_id, $meta, $data)
    {
        // die('ese');
        // if (REQ == 'CP')
        //  return;

        // $channel_id = $meta['channel_id'];
        // $channel_type = $this->sql->get_channel_type($channel_id);

        // // If the current channel is not assigned as any sort of Structure channel, then stop
        // if ($channel_type == 'page' || $channel_type == 'listing')
        // {
        //  $site_pages = $this->sql->get_site_pages();

        //  // get form fields
        //  $entry_data = array(
        //      'channel_id'    => $channel_id,
        //      'entry_id'      => $entry_id,
        //      'uri'           => array_key_exists('structure_uri', $data) ? $data['structure_uri'] : $meta['url_title'],
        //      'template_id'   => array_key_exists('structure_template', $data) ? $data['structure_template'] : $this->sql->get_default_template($channel_id),
        //      'listing_cid'   => 0,
        //      'hidden'        => array_key_exists('structure_hidden', $data) && $data['structure_hidden'] == 'y' ? 'y' : 'n'
        //  );

        //  if ($channel_type == 'listing')
        //  {
        //      $entry_data['parent_id'] = $this->sql->get_listing_parent($channel_id);
        //      $entry_data['listing_cid'] = $this->sql->get_listing_channel($entry_data['parent_id']);
        //      $entry_data['uri'] = $this->sql->create_uri($entry_data['uri'], $meta['url_title']);
        //      $entry_data['parent_uri'] = $site_pages['uris'][$entry_data['parent_id']];

        //      $this->sql->set_listing_data($entry_data);
        //  }
        //  else // page
        //  {
        //      $entry_data['parent_id'] = array_key_exists('structure_parent_id', $data) ? $data['structure_parent_id'] : 0;
        //      $parent_uri = isset($site_pages['uris'][$entry_data['parent_id']]) ? $site_pages['uris'][$entry_data['parent_id']] : '/';
        //      $entry_data['uri'] = $this->sql->create_page_uri($parent_uri, $entry_data['uri']);
        //      $entry_data['listing_cid'] = $this->sql->get_listing_channel_by_id($entry_id) ? $this->sql->get_listing_channel_by_id($entry_id) : 0;

        //      require_once PATH_ADDONS.'structure/mod.structure.php';
        //         $this->structure = new Structure();

        //      $this->structure->set_data($entry_data, true);
        //  }
        // }
    }

    public function safecracker_submit_entry_end($obj)
    {
        $site_id = ee()->config->item('site_id');

        $this->site_pages = $this->sql->get_site_pages(true);

        ee()->load->helper('url');

        // The constants in this safecracker game.
        $channel_id = $obj->channel['channel_id'];
        $channel_type = $this->sql->get_channel_type($channel_id);

        if ($channel_type == null) {
            return;
        }

        // If we're not working with Structure data, let's kill this quickly.
        if (! isset($obj->entry['entry_id']) || ($channel_type != 'page' && $channel_type != 'listing')) {
            return;
        }

        // add structure nav history before a safe craker form has been saved
        // add_structure_nav_revision($site_id, 'Pre save by safe craker form');

        // These may not always be available so putting them *after* the conditional
        $entry_id = $obj->entry['entry_id'];

        // This defaults to false if not a listing entry
        $listing_entry = $this->sql->get_listing_entry($entry_id);

        /*
        |-------------------------------------------------------------------------
        | Template ID
        |-------------------------------------------------------------------------
        */
        $default_template = $listing_entry ? $listing_entry['template_id'] : $this->sql->get_default_template($channel_id);

        $template_id = pick(
            structure_array_get($obj->EE->api_sc_channel_entries->data, 'structure_template_id'),
            structure_array_get($this->site_pages['templates'], $entry_id)
        );

        if (! $this->sql->is_valid_template($template_id)) {
            $template_id = $default_template;
        }

        /*
        |-------------------------------------------------------------------------
        | URI
        |-------------------------------------------------------------------------
        */
        $default_uri = $listing_entry ? structure_array_get($listing_entry, 'uri') : structure_array_get($this->site_pages['uris'], $entry_id);

        $uri = Structure_Helper::tidy_url(
            pick(
                structure_array_get($obj->EE->api_sc_channel_entries->data, 'structure_uri'),
                Structure_Helper::get_slug($default_uri),
                $obj->entry['url_title']
            )
        );

        /*
        |-------------------------------------------------------------------------
        | Parent ID
        |-------------------------------------------------------------------------
        */
        $default_parent_id = $channel_type == 'listing' ? $this->sql->get_listing_parent($channel_id) : 0;

        $parent_id = pick(
            structure_array_get($obj->EE->api_sc_channel_entries->data, 'structure_parent_id'),
            $this->sql->get_parent_id($entry_id, null),
            $default_parent_id
        );

        /*
        |-------------------------------------------------------------------------
        | Parent URI
        |-------------------------------------------------------------------------
        */
        $parent_uri = structure_array_get($this->site_pages['uris'], $parent_id, '/');

        /*
        |-------------------------------------------------------------------------
        | URL
        |-------------------------------------------------------------------------
        */
        $url = $channel_type == 'listing' ? $uri : $this->sql->create_full_uri($parent_uri, $uri);

        /*
        |-------------------------------------------------------------------------
        | Listing Channel ID
        |-------------------------------------------------------------------------
        */
        $listing_cid = $this->sql->get_listing_channel($parent_id);

        /*
        |-------------------------------------------------------------------------
        | Hidden State
        |-------------------------------------------------------------------------
        */
        $hidden = pick(
            structure_array_get($obj->EE->api_sc_channel_entries->data, 'structure_hidden'),
            $this->sql->get_hidden_state($entry_id),
            'n'
        );

        /*
        |-------------------------------------------------------------------------
        | Entry data to be processed and saved
        |-------------------------------------------------------------------------
        */
        $entry_data = array(
            'channel_id'  => $channel_id,
            'entry_id'    => $entry_id,
            'uri'         => $url,
            'parent_uri'  => $parent_uri,
            'template_id' => $template_id,
            'parent_id'   => $parent_id,
            'listing_cid' => $listing_cid,
            'hidden'      => $hidden
        );

        if ($channel_type == 'listing') {
            $this->sql->set_listing_data($entry_data);
        } else {
            require_once PATH_ADDONS . 'structure/mod.structure.php';
            $this->structure = new Structure();
            $this->structure->set_data($entry_data);
        }

        // add structure nav history after a safe craker form has been saved
        add_structure_nav_revision($site_id, 'Post save by safe craker form');
    }

    public function template_post_parse($final_template, $sub, $site_id)
    {
        if (!empty(ee()->extensions->last_call)) {
            $final_template = ee()->extensions->last_call;
        }

        // page_url_for
        $final_template = preg_replace_callback("({structure:page_url_for:(\d{1,})})", array(&$this, '_parse_tag_url_for'), $final_template);

        // page_uri_for
        $final_template = preg_replace_callback("({structure:page_uri_for:(\d{1,})})", array(&$this, '_parse_tag_uri_for'), $final_template);

        // page_title_for
        $final_template = preg_replace_callback("({structure:page_title_for:(\d{1,})})", array(&$this, '_parse_tag_title_for'), $final_template);

        // page_slug_for
        $final_template = preg_replace_callback("({structure:page_slug_for:(\d{1,})})", array(&$this, '_parse_tag_slug_for'), $final_template);

        $final_template = preg_replace_callback("({structure:child_ids_for:(\d{1,})})", array(&$this, '_parse_tag_child_ids_for'), $final_template);

        return $final_template;
    }

    /**************************************************\
     ******************* ALL ELSE: ********************
    \**************************************************/

    // For 2.7 Compatibility
    public function channel_form_submit_entry_end($obj)
    {
        $site_id = ee()->config->item('site_id');
        $this->site_pages = $this->sql->get_site_pages(true);

        ee()->load->helper('url');

        // The constants in this safecracker game.
        $channel_id = (gettype($obj->channel) == 'object' ? $obj->channel->channel_id : $obj->channel['channel_id']);
        $channel_type = $this->sql->get_channel_type($channel_id);

        if ($channel_type == null) {
            return;
        }

        $entry_id = 0;
        $url_title = '';
        if (isset($obj->entry)) {
            if (gettype($obj->entry) == 'object') {
                if (isset($obj->entry->entry_id)) {
                    $entry_id = $obj->entry->entry_id;
                }
                if (isset($obj->entry->url_title)) {
                    $url_title = $obj->entry->url_title;
                }
            } else {
                if (isset($obj->entry['entry_id'])) {
                    $entry_id = $obj->entry['entry_id'];
                }
                if (isset($obj->entry['url_title'])) {
                    $url_title = $obj->entry['url_title'];
                }
            }
        }

        // If we're not working with Structure data, let's kill this quickly.
        if (empty($entry_id) || ($channel_type != 'page' && $channel_type != 'listing')) {
            return;
        }

        // add structure nav history before updating it
        // add_structure_nav_revision($site_id, 'Pre save by channel form');

        // These may not always be available so putting them *after* the conditional

        // This defaults to false if not a listing entry
        $listing_entry = $this->sql->get_listing_entry($entry_id);

        /*
        |-------------------------------------------------------------------------
        | Template ID
        |-------------------------------------------------------------------------
        */
        $default_template = $listing_entry ? $listing_entry['template_id'] : $this->sql->get_default_template($channel_id);

        $template_id = pick(
            ee()->input->post('structure_template_id'),
            structure_array_get($obj->entry, 'structure_template_id'),
            structure_array_get($this->site_pages['templates'], $entry_id)
        );

        if (! $this->sql->is_valid_template($template_id)) {
            $template_id = $default_template;
        }

        /*
        |-------------------------------------------------------------------------
        | URI
        |-------------------------------------------------------------------------
        */
        $default_uri = $listing_entry ? structure_array_get($listing_entry, 'uri') : structure_array_get($this->site_pages['uris'], $entry_id);

        $uri = Structure_Helper::tidy_url(
            pick(
                ee()->input->post('structure_uri'),
                structure_array_get($obj->entry, 'structure_uri'),
                Structure_Helper::get_slug($default_uri),
                $url_title
            )
        );

        /*
        |-------------------------------------------------------------------------
        | Parent ID
        |-------------------------------------------------------------------------
        */
        $default_parent_id = $channel_type == 'listing' ? $this->sql->get_listing_parent($channel_id) : 0;

        $parent_id = pick(
            ee()->input->post('structure_parent_id'),
            structure_array_get($obj->entry, 'structure_parent_id'),
            $this->sql->get_parent_id($entry_id, null),
            $default_parent_id
        );

        /*
        |-------------------------------------------------------------------------
        | Parent URI
        |-------------------------------------------------------------------------
        */
        $parent_uri = structure_array_get($this->site_pages['uris'], $parent_id, '/');

        /*
        |-------------------------------------------------------------------------
        | URL
        |-------------------------------------------------------------------------
        */
        $url = $channel_type == 'listing' ? $uri : $this->sql->create_full_uri($parent_uri, $uri);

        /*
        |-------------------------------------------------------------------------
        | Listing Channel ID
        |-------------------------------------------------------------------------
        */
        $listing_cid = $this->sql->get_listing_channel($parent_id);

        /*
        |-------------------------------------------------------------------------
        | Hidden State
        |-------------------------------------------------------------------------
        */
        $hidden = pick(
            structure_array_get($obj->entry, 'structure_hidden'),
            $this->sql->get_hidden_state($entry_id),
            'n'
        );

        /*
        |-------------------------------------------------------------------------
        | Entry data to be processed and saved
        |-------------------------------------------------------------------------
        */
        $entry_data = array(
            'channel_id'  => $channel_id,
            'entry_id'    => $entry_id,
            'uri'         => $url,
            'parent_uri'  => $parent_uri,
            'template_id' => $template_id,
            'parent_id'   => $parent_id,
            'listing_cid' => $listing_cid,
            'hidden'      => $hidden
        );

        if ($channel_type == 'listing') {
            $site_pages = $this->sql->get_site_pages(true);
            $uri = 'start';
            if (isset($site_pages['uris'][$entry_id])) {
                //entry already existed
                $site_pages_uri = $site_pages['uris'][$entry_id];
                $uri_pieces = explode('/', $site_pages_uri);
                $uri_pieces = array_filter($uri_pieces);
                $uri = end($uri_pieces);
            } else {
                //new entry
                $uri = $obj->entry->url_title;
            }
            $entry_data['uri'] = $uri;
            $this->sql->set_listing_data($entry_data);
        } else {
            require_once PATH_ADDONS . 'structure/mod.structure.php';
            $this->structure = new Structure();
            $this->structure->set_data($entry_data);
        }
        // add structure nav history after a channel form has been submitted.
        add_structure_nav_revision($site_id, 'Post save by channel form');
    }

    public function _is_search()
    {
        $qstring = ee()->uri->query_string;
        $string_array = explode("/", $qstring);

        $search_id_key = count($string_array) - 2;
        $search_id = array_key_exists($search_id_key, $string_array) ? $string_array[$search_id_key] : false;

        if ($search_id !== false) {
            $query = ee()->db->get_where('modules', array('module_name' => 'Search'));
            if ($query->num_rows() > 0) {
                // Fetch the cached search query
                $query = ee()->db->get_where('search', array('search_id' => $search_id));

                // if ($query->num_rows() > 0 || $query->row('total_results') > 0)
                if (count($query->result_array()) > 0 && ($query->num_rows() > 0 || $query->row('total_results') > 0)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function _create_clean_structure_segments()
    {
        // Create pagination_segment and last_segment
        $segment_count = ee()->uri->total_segments();
        $last_segment = ee()->uri->segment($segment_count);

        // Check for pagination
        $pagination_segment = false;
        if (preg_match("/^P\d/", $last_segment) && $this->_is_search() === false) {
            $pagination_segment = $segment_count;
            $pagination_page = substr($last_segment, 1);

            ee()->config->_global_vars['structure_pagination_segment'] = $pagination_segment; // {structure_pagination_segment}
            ee()->config->_global_vars['structure_pagination_page'] = $pagination_page; // {structure_pagination_page}
            ee()->config->_global_vars['structure_last_segment'] = $last_segment; // {structure_last_segment}

            // Clean and dirty laundry, thanks to Freebie's cleverness
            $clean_array = array();
            $dirty_array = explode('/', ee()->uri->uri_string);

            // move any segments that don't match patterns to clean array
            foreach ($dirty_array as $segment) {
                if ($pagination_segment !== false && $segment != 'P' . $pagination_page) {
                    array_push($clean_array, $segment);
                }
            }

            // -------------------------------------------
            //  Clean up and overwrite the URI vars
            // -------------------------------------------

            // Rewrite the uri_string
            if (count($clean_array) != 0) {
                $clean_string = '/' . implode('/', $clean_array);

                if (array_search($clean_string, $this->site_pages['uris'])) {
                    ee()->uri->uri_string = $clean_string;

                    ee()->config->_global_vars['structure_debug_uri_cleaned'] = ee()->uri->uri_string;

                    ee()->uri->segments = array();
                    ee()->uri->rsegments = array();
                    ee()->uri->_explode_segments();

                    // Load the router class
                    $RTR = & load_class('Router', 'core');
                    $RTR->_parse_routes();

                    // re-index the segments
                    ee()->uri->_reindex_segments();
                }
            }
        }
    }

    private function _create_global_vars($isLivePreviewRequest = false)
    {
        $settings = $this->sql->get_settings();

        $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : null;

        if (empty($this->page_title)) {
            $this->page_title = $this->entry_id !== false ? $this->sql->get_page_title($this->entry_id) : false;
        }

        // This is to fix an EE bug that is holding us back
        $this->site_pages['url'] = str_replace("{base_url}/", ee()->config->item('base_url'), $this->site_pages['url']);

        // utility global vars
        ee()->config->_global_vars['structure:is:page'] = $isLivePreviewRequest || ($this->entry_id !== false && $this->sql->is_listing_entry($this->entry_id) !== true) ? true : false;
        ee()->config->_global_vars['structure:is:listing'] = $this->sql->is_listing_entry($this->entry_id);
        ee()->config->_global_vars['structure:is:listing:parent'] = $this->sql->get_listing_channel($this->entry_id) !== false && $this->sql->is_listing_entry($this->entry_id) === false ? true : false;

        // current page global vars
        ee()->config->_global_vars['structure:page:entry_id'] = $this->entry_id !== false ? $this->entry_id : false; // {page:entry_id}
        ee()->config->_global_vars['structure:page:template_id'] = $this->entry_id !== false ? $this->site_pages['templates'][$this->entry_id] : false; // {page:template_id}
        ee()->config->_global_vars['structure:page:title'] = $this->page_title; // {page:title}
        ee()->config->_global_vars['structure:page:slug'] = $isLivePreviewRequest || $this->entry_id !== false ? ee()->uri->segment(ee()->uri->total_segments()) : false;
        ee()->config->_global_vars['structure:page:uri'] = $isLivePreviewRequest || $this->entry_id !== false ? $this->uri : false;
        ee()->config->_global_vars['structure:page:url'] = $isLivePreviewRequest || $this->entry_id !== false ? Structure_Helper::remove_double_slashes($this->site_pages['url'] . ee()->config->_global_vars['structure:page:uri']) : false; // {page:url}
        ee()->config->_global_vars['structure:page:channel'] = $this->entry_id !== false ? $this->sql->get_channel_by_entry_id($this->entry_id) : false; // {page:channel}
        ee()->config->_global_vars['structure:page:channel_short_name'] = $this->entry_id !== false ? $this->sql->get_channel_name_by_channel_id(ee()->config->_global_vars['structure:page:channel']) : false; // {page:channel_short_name}
        ee()->config->_global_vars['structure:page:hidden'] = $this->entry_id !== false ? $this->sql->get_hidden_state($this->entry_id) : false;

        // parent page global vars
        ee()->config->_global_vars['structure:parent:entry_id'] = $this->parent_id !== false ? $this->parent_id : false; // {page:entry_id}
        ee()->config->_global_vars['structure:parent:title'] = $this->parent_id !== false ? $this->sql->get_page_title($this->parent_id) : false; // {page:title}
        ee()->config->_global_vars['structure:parent:slug'] = $this->parent_id !== false ? ee()->uri->segment(ee()->uri->total_segments() - 1) : false; // {parent:slug}
        ee()->config->_global_vars['structure:parent:uri'] = $this->parent_id !== false && isset($this->site_pages['uris'][$this->parent_id]) ? $this->site_pages['uris'][$this->parent_id] : false; // {parent:relative_url}
        ee()->config->_global_vars['structure:parent:url'] = $this->parent_id !== false && ee()->config->_global_vars['structure:parent:uri'] !== false ? Structure_Helper::remove_double_slashes($this->site_pages['url'] . ee()->config->_global_vars['structure:parent:uri']) : false; // {parent:url}
        ee()->config->_global_vars['structure:parent:child_ids'] = $this->parent_id !== false && ee()->uri->segment(2) ? implode('|', $this->sql->get_child_entries($this->parent_id)) : false;
        ee()->config->_global_vars['structure:parent:channel'] = $this->parent_id !== false ? $this->sql->get_channel_by_entry_id($this->parent_id) : false; // {page:channel}
        ee()->config->_global_vars['structure:parent:channel_short_name'] = $this->parent_id !== false ? $this->sql->get_channel_name_by_channel_id(ee()->config->_global_vars['structure:parent:channel']) : false; // {page:channel_short_name}

        // top page global vars
        ee()->config->_global_vars['structure:top:entry_id'] = $this->segment_1 !== false ? $this->top_id : false; // {top:entry_id}
        ee()->config->_global_vars['structure:top:title'] = $this->segment_1 !== false ? $this->sql->get_page_title($this->top_id) : false; // {top:title}
        ee()->config->_global_vars['structure:top:slug'] = $this->segment_1 !== false ? ee()->uri->segment(1) : false; // {top:slug}
        ee()->config->_global_vars['structure:top:uri'] = $this->segment_1 !== false ? '/' . ee()->uri->segment(1) . $trailing_slash : false; // {top:relative_url}
        ee()->config->_global_vars['structure:top:url'] = $this->segment_1 !== false ? Structure_Helper::remove_double_slashes($this->site_pages['url'] . ee()->uri->segment(1) . $trailing_slash) : false; // {top:url}

        // listing global vars
        ee()->config->_global_vars['structure:child_listing:channel_id'] = $this->sql->get_listing_channel($this->entry_id) !== false && is_numeric($this->entry_id) ? $this->sql->get_listing_channel($this->entry_id) : false;
        ee()->config->_global_vars['structure:child_listing:short_name'] = $this->sql->get_listing_channel($this->entry_id) !== false && is_numeric($this->entry_id) ? $this->sql->get_listing_channel_short_name(ee()->config->_global_vars['structure:child_listing:channel_id']) : false;

        // freebie
        ee()->config->_global_vars['structure:freebie:entry_id'] = isset(ee()->config->_global_vars['freebie_debug_uri']) ? array_search('/' . ee()->config->_global_vars['freebie_debug_uri'], $this->site_pages['uris']) : false;

        // child global var
        $child_ids = $this->sql->get_child_entries($this->entry_id);
        ee()->config->_global_vars['structure:child_ids'] = is_array($child_ids) && count($child_ids) > 0 ? implode('|', $child_ids) : false;

        // sibling global var
        $sibling_ids = array_diff($this->sql->get_child_entries($this->parent_id), array($this->entry_id));
        ee()->config->_global_vars['structure:sibling_ids'] = is_array($sibling_ids) && count($sibling_ids) > 0 ? implode('|', $sibling_ids) : false;

        // structure_segment global vars
        $segments = array_pad(ee()->uri->segments, 10, '');
        for ($i = 1; $i <= count($segments); $i++) {
            ee()->config->_global_vars['structure_' . $i] = $segments[$i - 1]; // {structure_X}
        }

        $segment_count = ee()->uri->total_segments();
        $last_segment = ee()->uri->segment($segment_count);
        ee()->config->_global_vars['structure_last_segment'] = $last_segment; // {structure_last_segment}
    }

    public function _parse_tag_url_for($m)
    {
        $settings = $this->sql->get_settings();

        $url = array_key_exists($m[1], $this->site_pages['uris']) ? Structure_Helper::remove_double_slashes($this->site_pages['url'] . $this->site_pages['uris'][$m[1]]) : '';

        // This is to fix an EE bug that is holding us back
        // TODO
        $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : null;
        $url = str_replace("{base_url}/", ee()->config->item('base_url'), $url);
        $url = rtrim($url, "/");
        $url .= $trailing_slash;

        // Hook to override the url we generate for each structure link (ex: Transcribe's multi-lingual language domains).
        if (ee()->extensions->active_hook('structure_generate_page_url_end') === true) {
            $url = ee()->extensions->call('structure_generate_page_url_end', $url);
        }

        return $url;
    }

    public function _parse_tag_uri_for($m)
    {
        $slug = array_key_exists($m[1], $this->site_pages['uris']) ? $this->site_pages['uris'][$m[1]] : null;

        return $slug;
    }

    public function _parse_tag_title_for($m)
    {
        $title = $this->sql->get_entry_title($m[1]);

        return $title;
    }

    public function _parse_tag_slug_for($m)
    {
        $slug = array_key_exists($m[1], $this->site_pages['uris']) ? $this->site_pages['uris'][$m[1]] : null;

        return $this->sql->get_slug($slug);
    }

    public function _parse_tag_child_ids_for($m)
    {
        $child_ids = $this->sql->get_child_entries($m[1]);

        return $child_ids !== false && count($child_ids) > 0 ? implode('|', $child_ids) : false;
    }

    public function registerExtension($method, $hook = null, $priority = 10, $enabled = 'y')
    {
        // if hook is empty, it should really just be the same thing as $method
        if (!$hook) {
            $hook = $method;
        }

        if (!isset($this->settings) || !is_array($this->settings)) {
            $this->settings = array();
        }

        // We are searching the database for this extension, and determining if it already exists
        $already_exists = (bool) ee()->db->get_where('extensions', array(
            'class'  => get_class($this),
            'method' => $method,
            'hook'   => $hook))->num_rows;

        // if it already exists, lets not add another.
        if ($already_exists) {
            return true;
        }

        $data = array(
            'class'    => 'Structure_ext',
            'method'   => $method,
            'hook'     => $hook,
            'settings' => serialize($this->settings),
            'priority' => $priority,
            'version'  => $this->version,
            'enabled'  => $enabled,
        );

        ee()->db->insert('extensions', $data);

        return true;
    }

    protected function unregisterExtension($method, $hook = null)
    {
        // if hook is empty, it should really just be the same thing as $method
        if (!$hook) {
            $hook = $method;
        }

        // Remove the hook from the `exp_extensions` table. It doesn't matter if it doesn't exist.
        ee()->db->delete('extensions', array(
            'class'  => 'Structure_ext',
            'method' => $method,
            'hook'   => $hook));

        return true;
    }

    protected function updateVersion()
    {
        ee()->db->update(
            'extensions',
            array(
                'version' => $this->version,
            ),
            array(
                'class' => 'Structure_ext',
            )
        );

        return true;
    }
}
