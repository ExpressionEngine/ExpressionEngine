<?php

require_once PATH_ADDONS . 'structure/addon.setup.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';
require_once PATH_ADDONS . 'structure/mod.structure.php';
require_once PATH_ADDONS . 'structure/helper.php';

use ExpressionEngine\Structure\Conduit\StaticCache;
use ExpressionEngine\Structure\Conduit\PersistentCache;

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
class Structure_tab
{
    public $version;
    public $sql;
    public $structure;
    public $nset;

    public function __construct()
    {
        $this->version = STRUCTURE_VERSION;

        $this->sql = new Sql_structure();
        $this->structure = new Structure();
    }

    public function default_tab()
    {
        $settings[] = array(
            'field_id'              => '',
            'field_label'           => '',
            'field_required'        => 'n',
            'field_data'            => '',
            'field_list_items'      => '',
            'field_fmt'             => '',
            'field_instructions'    => '',
            'field_show_fmt'        => 'n',
            'field_fmt_options'     => array(),
            'field_pre_populate'    => 'n',
            'field_text_direction'  => 'ltr',
            'field_type'            => 'text',
            'field_maxl'            => '1'
        );

        return $settings;
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        $site_pages = $this->sql->get_site_pages(true);
        $uri = array_key_exists($entry->entry_id, $site_pages['uris']) ? $site_pages['uris'][$entry->entry_id] : '';
        if (!empty($uri)) {
            return '<a href="' . Structure_Helper::remove_double_slashes(ee()->functions->fetch_site_index(0, 0) . $uri) . '" target="_blank"><i class="fal fa-link"></i></a>';
        }
        return '';
    }

    public function getTableColumnConfig()
    {
        return [
            'encode' => false
        ];
    }

    public function display($channel_id, $entry_id = '')
    {
        return $this->publish_tabs($channel_id, $entry_id);
    }

    public function publish_tabs($channel_id, $entry_id = '')
    {
        $settings = array();
        if (empty($channel_id)) {
            $channel_id = ee()->input->get_post('channel_id') ? ee()->input->get_post('channel_id') : $this->sql->get_channel_by_entry_id($entry_id);
        }

        $cached_structure_channels = StaticCache::get('publish_tabs__get_structure_channels');

        if (!empty($cached_structure_channels)) {
            $structure_channels = $cached_structure_channels;
        } else {
            $structure_channels = $this->structure->get_structure_channels();
            StaticCache::set('publish_tabs__get_structure_channels', $structure_channels);
        }

        $channel_type = $structure_channels[$channel_id]['type'] ?? null;

        ee()->lang->loadfile('structure');

        // Kill the Structure tab if channel is not managed by Structure
        if (($channel_type != 'page' && $channel_type != 'listing') || (isset($permissions['admin']) && $permissions['admin'] != true)) {
            return array();
        }

        ee()->load->helper('form');

        if (REQ == 'CP') {
            if (empty($entry_id)) {
                ee()->cp->add_js_script('plugin', 'ee_url_title');

                if (ee()->input->get('parent_id')) {
                    ee()->javascript->output('$("section.wrap div.tab-wrap > form").prepend(\'<input type="hidden" name="structure__parent_id" value="' . ee()->input->get('parent_id') . '" />\');');
                }

                ee()->javascript->output('
                    $("#edit_group_prefs").hide();
                    $("input[name=\'title\']").bind("keyup keydown", function() {
                        $(this).ee_url_title("input[name=\'structure__uri\']");
                    });
                ');
            }

            ee()->javascript->output('
                if(!$("input[name=structure__uri]").val() && $("input[name=url_title]").val()) {
                    $("input[name=structure__uri]").val($("input[name=url_title]").val());
                }
            ');
        }

        $structure_settings = $this->sql->get_settings();
        $site_pages = $this->sql->get_site_pages(true);

        $site_id = ee()->config->item('site_id');
        if (empty($entry_id)) {
            $entry_id = ee()->input->get_post('entry_id') !== false ? ee()->input->get_post('entry_id') : 0;
        }

        $data = $this->sql->get_data();
        $cids = isset($data['channel_ids']) ? $data['channel_ids'] : array();
        $lcids = isset($data['listing_cids']) ? $data['listing_cids'] : array();

        // overide defaults and previous data with data from the form if available (SAEFs?)
        // $uri         = ee()->input->get_post('structure__uri') ? ee()->input->get_post('structure__uri') : $uri;
        // $listing     = ee()->input->get_post('structure__listing') ? ee()->input->get_post('structure__listing') : $listing;
        // $listing_cid = ee()->input->get_post('structure__listing_channel') ? ee()->input->get_post('structure__listing_channel') : $listing_cid;

        $listing_parent = $this->sql->get_listing_parent($channel_id);

        /** -------------------------------------
        /**  Field: Parent ID
        /** -------------------------------------*/
        if ($channel_type == 'page' && array_key_exists($entry_id, $data) && !empty($data[$entry_id]['parent_id'])) {
            $parent_id = $data[$entry_id]['parent_id'];
        } elseif (ee()->input->get_post('parent_id')) {
            $parent_id = ee()->input->get_post('parent_id');
        } elseif ($listing_parent) {
            $parent_id = $listing_parent;
        } else {
            $parent_id = 0;
        }

        $parent_uri = $channel_type == 'page' && $parent_id && array_key_exists($parent_id, $site_pages['uris']) ? $site_pages['uris'][$parent_id] : null;
        $selected_parent = array($parent_id);
        $parent_ids = $this->get_parent_fields($entry_id, $data);

        $cached_structure_channels_page = StaticCache::get('publish_tabs__get_structure_channels_page');

        if (!empty($cached_structure_channels_page)) {
            $structure_channels_page = $cached_structure_channels_page;
        } else {
            $structure_channels_page = $this->structure->get_structure_channels('page');
            StaticCache::set('publish_tabs__get_structure_channels_page', $structure_channels_page);
        }

        if (array_key_exists($channel_id, $structure_channels_page)) {
            $settings['parent_id'] = array(
                'field_id'              => 'parent_id',
                'field_label'           => lang('tab_parent_entry'),
                'field_required'        => 'n',
                'field_data'            => $selected_parent,
                'field_list_items'      => $parent_ids,
                'field_fmt'             => '',
                'field_instructions'    => '',
                'field_show_fmt'        => 'n',
                'field_fmt_options'     => array(),
                'field_pre_populate'    => 'n',
                'field_text_direction'  => 'ltr',
                'field_type'            => 'select'
            );
        }

        /** -------------------------------------
        /**  Field: Page URI/Slug
        /** -------------------------------------*/
        $selected_uri = array_key_exists($entry_id, $site_pages['uris']) ? $site_pages['uris'][$entry_id] : '';
        $uri = array_key_exists($entry_id, $site_pages['uris']) ? $site_pages['uris'][$entry_id] : '';

        if ($uri == '/') {
            $slug = '/';
        } else {
            $slug = '';
            if ($uri !== null) {
                $slug = trim($uri, '/');
                $slug = explode('/', $slug);
                $slug = end($slug);
            }
        }

        $help_text = $listing_parent ? "<p class='instruction_override'><strong>URL prefix</strong><style>#sub_hold_field_structure__uri .instruction_text p {display:none;} #sub_hold_field_structure__uri .instruction_text p.instruction_override {display:block;}</style>: " . $site_pages['uris'][$parent_id] . "</p>" : '';
        // $uri_override .= '<input type="text" name="structure__uri" value="'.$slug.'" id="structure__uri" dir="ltr" maxlength="100"  />';

        $settings['uri'] = array(
            'field_id'              => 'uri',
            'field_label'           => $listing_parent ? lang('tab_listing_url') : lang('tab_page_url'),
            'field_required'        => 'n',
            'field_data'            => $slug,
            'field_list_items'      => $uri,
            'field_fmt'             => '',
            'field_instructions'    => $help_text,
            'field_show_fmt'        => 'n',
            'field_fmt_options'     => array(),
            'field_pre_populate'    => 'n',
            'field_text_direction'  => 'ltr',
            'field_type'            => 'text',
            'field_maxl'            => 200,
            // 'string_override'        => $uri_override
        );

        /** -------------------------------------
        /**  Field: Template
        /** -------------------------------------*/
        $tmpl_id = isset($site_pages['templates'][$entry_id]) ? $site_pages['templates'][$entry_id] : ''; // get the template_id if it exists. otherwise return an empty string
        $templates = $this->get_template_fields($entry_id, $data, $channel_id, $structure_settings);

        $cached_structure_channels_channel_id = StaticCache::get('publish_tabs__get_structure_channels_channel_id_' . $channel_id);

        if (!empty($cached_structure_channels_channel_id)) {
            $structure_channels = $cached_structure_channels_channel_id;
        } else {
            $structure_channels = $this->structure->get_structure_channels('', $channel_id);
            StaticCache::set('publish_tabs__get_structure_channels_channel_id_' . $channel_id, $structure_channels);
        }

        $selected_template = $entry_id != 0 && array_key_exists($entry_id, $site_pages['templates']) ? array($site_pages['templates'][$entry_id]) : array($structure_channels[$channel_id]['template_id']);

        $settings['template_id'] = array(
            'field_id'              => 'template_id',
            'field_label'           => lang('template'),
            'field_required'        => 'n',
            'field_data'            => $selected_template,
            'field_list_items'      => $templates,
            'field_fmt'             => 'text',
            'field_instructions'    => '',
            'field_show_fmt'        => 'n',
            'field_fmt_options'     => '',
            'field_pre_populate'    => 'n',
            'field_text_direction'  => 'ltr',
            'field_type'            => 'select'
        );

        if ($channel_type != 'listing') {
            /** -------------------------------------
            /**  Field: Hide From Nav
            /** -------------------------------------*/
            $hide_select = array('n' => 'No', 'y' => 'Yes');
            $hide_setting = $this->sql->get_hidden_state($entry_id);

            $settings['hidden'] = array(
                'field_id'              => 'hidden',
                'field_label'           => 'Hide from nav?',
                'field_required'        => 'n',
                'field_data'            => $hide_setting,
                'field_list_items'      => $hide_select,
                'field_fmt'             => 'text',
                'field_instructions'    => '',
                'field_show_fmt'        => 'n',
                'field_fmt_options'     => '',
                'field_pre_populate'    => 'n',
                'field_text_direction'  => 'ltr',
                'field_type'            => 'select'
            );
        }

        /** -------------------------------------
        /**  Field: Listing Channel
        /** -------------------------------------*/
        $listing_cid = $entry_id != 0 && array_key_exists($entry_id, $data) ? $data[$entry_id]['listing_cid'] : false;
        $listing_channels = $this->get_listing_channels($entry_id, $data, $channel_id);

        $result = ee()->db->query("SELECT listing_cid FROM exp_structure WHERE listing_cid != 0");

        $used_listing_ids = array();
        foreach ($result->result_array() as $row) {
            $used_listing_ids[$row['listing_cid']] = $row['listing_cid'];
        }

        unset($used_listing_ids[$listing_cid]);

        $listing_channels = array_diff_key($listing_channels, $used_listing_ids);

        if (! array_key_exists($channel_id, $used_listing_ids)) {
            $settings['listing_channel'] = array(
                'field_id'              => 'listing_channel',
                'field_label'           => lang('listing_channel'),
                'field_required'        => 'n',
                'field_data'            => $listing_cid,
                'field_list_items'      => $listing_channels,
                'field_fmt'             => '',
                'field_instructions'    => '',
                'field_show_fmt'        => 'n',
                'field_fmt_options'     => array(),
                'field_pre_populate'    => 'n',
                'field_text_direction'  => 'ltr',
                'field_type'            => 'select'
            );
        }

        if (ee()->extensions->active_hook('structure_modify_publish_tab_settings') === true) {
            $settings = ee()->extensions->call('structure_modify_publish_tab_settings', $settings, $entry_id);
        }

        return $settings;
    }

    public function delete($params)
    {
        $this->publish_data_delete_db($params);
    }

    public function publish_data_delete_db($params)
    {
        // capture nav history
        $site_id = ee()->config->item('site_id');
        $entry_ids_being_deleted = print_r($params, true);
        // add_structure_nav_revision($site_id, 'Pre Deleting Entries  -- entry_ids'. $entry_ids_being_deleted);

        if (is_array($params) && array_key_exists('entry_ids', $params)) {
            $this->structure->delete_data($params['entry_ids']);
        } else {
            $this->structure->delete_data($params);
        }

        add_structure_nav_revision($site_id, 'Post Deleting Entries  -- entry_ids' . $entry_ids_being_deleted);
    }

    public function validate($channel_entry, $params)
    {
        return $this->validate_publish($params, $channel_entry);
    }

    public function validate_publish($params, $channel_entry = null)
    {
        if (isset($channel_entry->channel_id)) {
            $channel_id = $channel_entry->channel_id;
        } else {
            $channel_id = $params[0]['channel_id'];
        }

        if (isset($channel_entry->entry_id)) {
            $entry_id = $channel_entry->entry_id;
        } elseif (isset($params[0]['entry_id'])) {
            $entry_id = $params[0]['entry_id'];
        } elseif (isset($params['entry_id'])) {
            $entry_id = $params['entry_id'];
        } else {
            $entry_id = 0;
        }

        if (isset($params['parent_id'])) {
            $parent_id = $params['parent_id'];
        } elseif (isset($params[0]) && isset($params[0]['parent_id'])) {
            $parent_id = $params[0]['parent_id'];
        } else {
            $parent_id = 0;
        }

        $structure_channels = $this->structure->get_structure_channels();
        $channel_type = $structure_channels[$channel_id]['type'];

        $this->sql->set_channel_ids($entry_id, $channel_id);

        if ($channel_type == 'page') {
            $adapter = new Structure_Nestedset_Adapter_Ee('exp_structure', 'lft', 'rgt', 'entry_id');
            $this->nset = new Structure_Nestedset($adapter);

            $node = $this->nset->getNode($entry_id);
            $parentNode = $this->nset->getNode($parent_id);

            if ($node !== false && $parentNode !== false && $parentNode['left'] > $node['left'] && $parentNode['right'] < $node['right'] && $entry_id != 0) {
                return array('You can not nest a page below itself.' => 'parent_id');
            }
        }

        return true;
    }

    public function save($channel_entry, $params)
    {
        return $this->publish_data_db($params, $channel_entry);
    }

    public function publish_data_db($params, $channel_entry = null)
    {
        if (!empty($_POST['bulk_action'])) {
            return;
        }

        if (isset($channel_entry->entry_id)) {
            $entry_id = $channel_entry->entry_id;
        } elseif (isset($params['entry_id'])) {
            // EE3
            $entry_id = $params['entry_id'];
        } else {
            // EE2
            $entry_id = 0;
        }

        // This block of code is fixing a really weird bug. When deleting a page, EE was calling a lot of the tab functions (EE3.4.2). It would actually delete the entry from the structure table,
        // and then call the save function, which puts it right back in the structure. This function checks to see if the entry is in the exp_channel_titles table, because if not, it shouldnt save (again)
        if ($entry_id != 0) {
            $result = ee()->db->query("SELECT entry_id FROM exp_channel_titles WHERE entry_id = " . (int) $entry_id);
            if ($result->num_rows == 0) {
                return;
            }
        }

        $site_pages = $this->sql->get_site_pages(true);

        if (isset($channel_entry->channel_id)) {
            $channel_id = $channel_entry->channel_id;
        } elseif (isset($params['meta']['channel_id'])) {
            // EE3
            $channel_id = $params['meta']['channel_id'];
        } else {
            // EE2
            $channel_id = 0;
        }

        if (isset($channel_entry->site_id)) {
            $site_id = $channel_entry->site_id;
        } elseif (isset($params['meta']['site_id'])) {
            // EE3
            $site_id = $params['meta']['site_id'];
        } else {
            // EE2
            $site_id = 0;
        }

        // have to assign variable here to support older versions of PHP
        $structure_parent_id = ee()->input->get_post('structure_parent_id');
        $structure_alt_parent_id = ee()->input->get_post('structure__parent_id');

        // Assign a value to parent_id
        if (!empty($structure_parent_id)) {
            $parent_id = ee()->input->get_post('structure_parent_id');
        } elseif (isset($params['parent_id'])) {
            $parent_id = $params['parent_id']; // EE3
        } elseif (isset($params['mod_data']['parent_id'])) {
            $parent_id = $params['mod_data']['parent_id']; // EE2
        } elseif (!empty($structure_alt_parent_id)) {
            $parent_id = $structure_alt_parent_id;
        } else {
            // do we have an entry id as well as a template?
            // template checks just confirms it's edited
            // and not new...
            if (!empty($entry_id) && array_key_exists($entry_id, $site_pages['templates'])) {
                // get old parent id and set it here
                $parent_id = $this->sql->get_parent_id($entry_id, null);
            } else {
                // new entry and it wasn't set... set it to 0 here
                $parent_id = 0;
            }
        }

        // The data is not always consistant, and sometimes we have an array [0 => 0] instead of a number :facepalm:
        if (is_array($parent_id) && array_key_exists(0, $parent_id)) {
            $parent_id = $parent_id[0];
        }

        // set the template_id here
        if (isset($params['template_id']) && is_array($params['template_id']) && count($params['template_id']) == 1) {
            $template_id = $params['template_id'][0];
        } elseif (isset($params['template_id'])) {
            $template_id = $params['template_id']; // EE3
        } elseif (isset($params['mod_data']['template_id'])) {
            $template_id = $params['mod_data']['template_id']; // EE2
        } else {
            // check to see if the template_id is empty.  When a user
            // submits a entry with a hidden template field it will
            // not submit the field in EE 3.x so we want to pull
            // the old setting or default one for the channel
            // and apply it here

            $structure_channels = $this->structure->get_structure_channels('', $channel_id);

            if (!empty($entry_id) && array_key_exists($entry_id, $site_pages['templates'])) {
                $template_id = $site_pages['templates'][$entry_id];
            } else {
                $template_id = (!empty($structure_channels[$channel_id]['template_id']) ? $structure_channels[$channel_id]['template_id'] : 0);
            }
        }

        // assign the hidden setting
        if (isset($params['hidden'])) {
            $hidden = $params['hidden']; // EE3
        } elseif (isset($params['mod_data']['hidden'])) {
            $hidden = $params['mod_data']['hidden']; // EE2
        } else {
            // do we have an entry id as well as a template?
            // template checks just confirms it's edited
            // and not new...
            if (!empty($entry_id) && array_key_exists($entry_id, $site_pages['templates'])) {
                // get old hidden status
                $hidden = $this->sql->get_hidden_state($entry_id);
            } else {
                // new entry and it wasn't set... set it to 0 here
                $hidden = 'n';
            }

            $hidden = 'n';
        }

        // set the URI now
        if (isset($params['uri'])) {
            $uri = $params['uri']; // EE3
        } elseif (isset($params['mod_data']['uri'])) {
            $uri = $params['mod_data']['uri']; // EE2
        } else {
            // do we have an entry id as well as a template?
            // uris checks just confirms it's edited
            // and not new...
            if (!empty($entry_id) && array_key_exists($entry_id, $site_pages['uris'])) {
                // get old uri
                $site_pages_uri = $site_pages['uris'][$entry_id];
                $uri_pieces = explode('/', $site_pages_uri);
                $uri_pieces = array_filter($uri_pieces);
                $uri = end($uri_pieces);
            } elseif (!empty($channel_entry->url_title)) {
                $uri = $channel_entry->url_title;
            } else {
                // new entry and it wasn't set... set it to blank
                $uri = '';
            }
        }

        if (isset($params['listing_channel'])) {
            if ($params['listing_channel'] == 'n') {
                $listing_channel = 0;
            } else {
                $listing_channel = $params['listing_channel'];
            } // EE3
        } elseif (isset($params['mod_data']['listing_channel'])) {
            if ($params['mod_data']['listing_channel'] == 'n') {
                $listing_channel = 0;
            } else {
                $listing_channel = $params['mod_data']['listing_channel'];
            } // EE2
        } else {
            // get_listing_channel
            // do we have an entry id
            // if so lets see if it's a listing channel
            if (!empty($entry_id)) {
                // get old hidden status
                $listing_channel = $this->sql->get_listing_channel($entry_id);
            } else {
                // new entry and it wasn't set... set it to blank
                $listing_channel = 0;
            }
        }

        $structure_channels = $this->structure->get_structure_channels();
        $channel_type = $structure_channels[$channel_id]['type'] ?? null;
        $allow_dupes = false;

        if ($channel_type == 'page' || $channel_type == 'listing') {
            ee()->load->helper('url');

            $word_separator = ee()->config->item('word_separator');
            $separator = $word_separator != 'dash' ? '_' : '-';

            if (isset($channel_entry->title)) {
                $title = $channel_entry->title;
            } elseif (isset($params['meta']['title'])) {
                // EE3
                $title = $params['meta']['title'];
            } else {
                // EE2
                $title = '';
            }

            // capture nav history
            // add_structure_nav_revision($site_id, 'Pre saving entry "'. $title.'"');

            $structure_uri = $uri; // contents of uri input field

            $uri = $structure_uri == '' ? $this->create_uri($title) : $this->create_uri($structure_uri);

            // If the current channel is not assigned as any sort of Structure channel, then stop
            if ($channel_type == 'page') {
                // get form fields
                $data = array(
                    'site_id'       => intval($site_id),
                    'channel_id'    => intval($channel_id),
                    'entry_id'      => intval($entry_id),
                    'uri'           => $uri,
                    'template_id'   => intval($template_id),
                    'hidden'        => $hidden,
                    'listing_cid'   => intval($listing_channel),
                    'structure_uri' => $structure_uri
                );

                $data['parent_id'] = intval($parent_id);

                $parent_uri = isset($site_pages['uris'][$data['parent_id']]) ? $site_pages['uris'][$data['parent_id']] : '/';
                $data['uri'] = $this->structure->create_page_uri($parent_uri, $data['uri']);

                // Duplicate url check
                if (ee()->extensions->active_hook('structure_allow_dupes') === true) {
                    $allow_dupes = ee()->extensions->call('structure_allow_dupes', $data['uri'], $entry_id);
                }

                $dupe = $this->sql->is_duplicate_page_uri($data['entry_id'], $data['uri']);

                if (! $allow_dupes && $dupe !== false) {
                    $data['uri'] = $dupe;
                }

                $this->structure->set_data($data, true);
            } elseif ($channel_type == 'listing') {
                // get form fields
                $data = array(
                    'site_id'     => intval($site_id),
                    'channel_id'  => intval($channel_id),
                    'entry_id'    => intval($entry_id),
                    'uri'         => $uri,
                    'template_id' => intval($template_id),
                    'listing_cid' => intval($listing_channel)
                );

                $parent_id = $this->sql->get_listing_parent($data['channel_id']);

                if (ee()->extensions->active_hook('structure_listing_parent') === true) {
                    $parent_id = ee()->extensions->call('structure_listing_parent', $parent_id, $data['channel_id'], $data['entry_id'], true);
                }

                $data['parent_id'] = intval($parent_id);

                // If there is no parent id, there is no Structure Page associated with this listing channel.
                if (empty($data['parent_id'])) {
                    return;
                }

                // Duplicate url checks
                $dupe_count = $this->sql->is_duplicate_listing_uri($data['entry_id'], $uri, $data['parent_id']);

                if (ee()->extensions->active_hook('structure_allow_dupes') === true) {
                    $allow_dupes = ee()->extensions->call('structure_allow_dupes', $data['uri'], $data['entry_id']);
                }

                if (! $allow_dupes && $dupe_count !== false) {
                    for ($i = 1;; $i++) {
                        if ($this->sql->is_duplicate_listing_uri($data['entry_id'], $uri . $separator . $i, $data['parent_id']) == false) {
                            $data['uri'] = $uri . $separator . $i;

                            break;
                        }
                    }
                }

                $data['parent_uri'] = $site_pages['uris'][$data['parent_id']];

                $this->sql->set_listing_data($data);
            }

            // clear RTE pages cache
            ee()->cache->delete('/site_pages/', \Cache::GLOBAL_SCOPE);

            // capture nav history
            add_structure_nav_revision($site_id, 'Post saving entry  "' . $title . '"');
        } else {
            return;
        }
    }

    /** -------------------------------------
    /**  Utility functions
    /** -------------------------------------*/
    public function get_template_fields($entry_id, $data, $channel_id, $structure_settings)
    {
        $site_id = ee()->config->item('site_id');

        $template_id = isset($structure_settings['template_channel_' . $channel_id]) ? $structure_settings['template_channel_' . $channel_id] : 0;
        $template_id = ee()->input->get_post('structure__template_id') ? ee()->input->get_post('structure__template_id') : $template_id;

        $cached_templates = StaticCache::get('get_template_fields');

        if (!empty($cached_templates)) {
            $templates = $cached_templates;
        } else {
            $templates = $this->sql->get_templates();
            StaticCache::set('get_template_fields', $templates);
        }

        $options = array();

        foreach ($templates as $template_row) {
            $template_id = $template_row['template_id'];
            $template_group = $template_row['group_name'] . "/" . $template_row['template_name'];
            $options[$template_id] = $template_group;
        }

        return $options;
    }

    public function get_parent_fields($entry_id, $data)
    {
        // Build Parent Entries Select Box
        $parent_id = ee()->input->get_post('structure__parent_id') ? ee()->input->get_post('structure__parent_id') : 0;
        $parent_ids = array();
        $parent_ids['n'] = "NONE";

        // PARENT BUG
        // Update: Changed this to `$entry_id` which may cause some oddity with the Parent dropdown.
        // $data = $this->sql->get_data($entry_id);

        // Create a list of entry_ids to exclude.  Using ids as keys so that we keep a unique list
        $exclude = [$entry_id => null];

        foreach ($data as $eid => $entry) {
            // If we have an entry then this entry and its descendants cannot used as its own parent
            if ($entry_id && (array_key_exists($eid, $exclude) || array_key_exists($entry['parent_id'], $exclude))) {
                $exclude[$eid] = null; // in case we match on parent_id add the entry_id to exclusions
                continue;
            }
            // Add faux indent with "--" double dashes
            $option = str_repeat("--", @$entry['depth']);
            $option .= @$entry['title'];

            $parent_ids[$eid] = $option;
        }

        return $parent_ids;
    }

    public function get_listing_channels($entry_id, $data, $current_channel_id)
    {
        $site_id = ee()->config->item('site_id');
        $structure_data = $this->sql->get_data();

        $cached_structure_channels_listing = StaticCache::get('get_listing_channels__get_structure_channels_listing');

        if (!empty($cached_structure_channels_listing)) {
            if ($cached_structure_channels_listing === 'EMPTY') {
                $listings = array();
            } else {
                $listings = $cached_structure_channels_listing;
            }
        } else {
            $listings = $this->structure->get_structure_channels('listing');

            if (empty($listings)) {
                StaticCache::set('get_listing_channels__get_structure_channels_listing', 'EMPTY');
            } else {
                StaticCache::set('get_listing_channels__get_structure_channels_listing', $listings);
            }
        }

        $count_listings = count($listings);

        // Build Listing Channels Select Box
        $listing_channel = ee()->input->get_post('structure__listing_channel') ? ee()->input->get_post('structure__listing_channel') : 0;
        $listing_channels = array();
        $listing_channels['n'] = "==None Selected==";

        if ($count_listings > 0) {
            foreach ($listings as $channel_id => $row) {
                if ($channel_id == $current_channel_id) {
                    continue;
                }
                $listing_channels[$channel_id] = $row['channel_title'];
            }
        }

        return $listing_channels;
    }

    public function create_uri($str)
    {
        return ee('Format')->make('Text', $str)->urlSlug()->compile();
    }
}
/* END Class */

/* End of file tab.structure.php */
