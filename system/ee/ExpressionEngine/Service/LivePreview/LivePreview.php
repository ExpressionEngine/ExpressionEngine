<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\LivePreview;

/**
 * LivePreview Service
 */
class LivePreview
{
    /**
     * @var obj $session_delegate A Session object
     */
    private $session_delegate;

    /**
     * @var string $cache_class The class name to hand off to the Session cache
     */
    private $cache_class = 'channel_entry';

    /**
     * @var string $key The key to hand off to the Session cache
     */
    private $key = 'live-preview';

    /**
     * Constructor
     *
     * @param obj $session_delegate A Session object
     */
    public function __construct($session_delegate)
    {
        $this->session_delegate = $session_delegate;
    }

    /**
     * Do we have entry data?
     *
     * @return bool TRUE if it is, FALSE if it is not
     */
    public function hasEntryData()
    {
        return ($this->getEntryData() !== false);
    }

    /**
     * Gets the entry data for the live preview.
     *
     * @return array|bool Array of entry data or FALSE if there is no preview data
     */
    public function getEntryData()
    {
        return $this->session_delegate->cache($this->cache_class, $this->key, false);
    }

    /**
     * Sets the live preview data
     *
     * @param array $data The entry data
     * @return void
     */
    public function setEntryData($data)
    {
        $this->session_delegate->set_cache($this->cache_class, $this->key, $data);
    }

    /**
     * generate and display the live preview
     */
    public function preview($channel_id, $entry_id = null, $preview_url = null)
    {
        if (empty($_POST)) {
            return;
        }

        $channel = ee('Model')->get('Channel', $channel_id)
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if ($entry_id) {
            $entry = ee('Model')->get('ChannelEntry', $entry_id)
                ->with('Channel', 'Author')
                ->first();
        } else {
            $entry = ee('Model')->make('ChannelEntry');
            $entry->entry_id = PHP_INT_MAX;
            $entry->Channel = $channel;
            $entry->site_id = ee()->config->item('site_id');
            $entry->author_id = ee()->session->userdata('member_id');
            $entry->ip_address = ee()->session->userdata['ip_address'];
            $entry->versioning_enabled = $channel->enable_versioning;
            $entry->sticky = false;
        }

        $entry->set($_POST);
        $data = $entry->getModChannelResultsArray();
        $data['entry_site_id'] = $entry->site_id;
        if (isset($_POST['categories'])) {
            $data['categories'] = $_POST['categories'];
        }

        ee('LivePreview')->setEntryData($data);

        ee()->load->library('template', null, 'TMPL');

        $template_id = null;

        if (! empty($_POST['pages__pages_uri'])
            && ! empty($_POST['pages__pages_template_id'])) {
            $values = [
                'pages_uri' => $_POST['pages__pages_uri'],
                'pages_template_id' => $_POST['pages__pages_template_id'],
            ];

            $page_tab = new \Pages_tab();
            $site_pages = $page_tab->prepareSitePagesData($entry, $values);

            ee()->config->set_item('site_pages', $site_pages);
            $entry->Site->site_pages = $site_pages;

            $template_id = $_POST['pages__pages_template_id'];
        }

        if (!empty($preview_url)) {
            $site_index = str_ireplace(['http:', 'https:'], '', ee()->functions->fetch_site_index());
            $preview_url = str_ireplace(['http:', 'https:'], '', $preview_url);
            $uri = str_replace($site_index, '', $preview_url);
            $parsed_url = parse_url($uri);
            if ($parsed_url && isset($parsed_url['host'])) {
                $uri = str_ireplace($parsed_url['host'], '', $uri);
            }
            $uri = trim($uri, '/');
        } elseif ($entry->hasPageURI()) {
            $uri = $entry->getPageURI();
            ee()->uri->page_query_string = $entry->entry_id;
            if (! $template_id) {
                $template_id = $entry->getPageTemplateID();
            }
        } else {
            // We want to avoid replacing `{url_title}` with an empty string since that
            // can cause the wrong thing to render (like 404s).
            if (empty($entry->url_title)) {
                $entry->url_title = $entry->entry_id;
            }

            $uri = str_replace(['{url_title}', '{entry_id}'], [$entry->url_title, $entry->entry_id], $channel->preview_url);
        }

        // -------------------------------------------
        // 'publish_live_preview_route' hook.
        //  - Set alternate URI and/or template to use for preview
        //  - Added 4.2.0
        if (ee()->extensions->active_hook('publish_live_preview_route') === true) {
            $route = ee()->extensions->call('publish_live_preview_route', array_merge($_POST, $data), $uri, $template_id);
            $uri = $route['uri'];
            $template_id = $route['template_id'];
        }
        //
        // -------------------------------------------

        ee()->uri->_set_uri_string($uri);

        // Compile the segments into an array
        ee()->uri->segments = [];
        ee()->uri->_explode_segments();

        // Re-index the segment array so that it starts with 1 rather than 0
        ee()->uri->_reindex_segments();

        ee()->core->loadSnippets();

        $template_group = '';
        $template_name = '';

        if ($template_id) {
            $template = ee('Model')->get('Template', $template_id)
                ->with('TemplateGroup')
                ->first();

            $template_group = $template->TemplateGroup->group_name;
            $template_name = $template->template_name;
        }

        ee()->TMPL->run_template_engine($template_group, $template_name);
    }
}

// EOF
