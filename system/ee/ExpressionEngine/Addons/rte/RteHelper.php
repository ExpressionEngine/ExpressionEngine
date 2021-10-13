<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Rte;

use ExpressionEngine\Library\Rte\RteFilebrowserInterface;

class RteHelper
{
    private static $_fileTags;
    private static $_pageTags;
    private static $_extraTags;
    private static $_sitePages;
    private static $_pageData;

    // --------------------------------------------------------------------

    /**
     * Gets all the possible {filedir_X} tags and their replacement URLs.
     *
     * @param bool $sort
     *
     * @return array $list
     */
    private static function _getFileTags()
    {
        if (! isset(static::$_fileTags)) {
            $tags = array();
            $urls = array();

            $dirs = ee('Model')->get('UploadDestination')
                ->all()
                ->getDictionary('id', 'url');

            foreach ($dirs as $id => $url) {
                // ignore "/" URLs
                if ($url == '/') {
                    continue;
                }
                $tags[] = LD . 'filedir_' . $id . RD;
                $urls[] = $url;
            }

            static::$_fileTags = array($tags, $urls);
        }

        return static::$_fileTags;
    }

    /**
     * Replaces {filedir_X} tags with their URLs.
     *
     * @param string &$data
     */
    public static function replaceFileTags(&$data)
    {
        $tags = static::_getFileTags();
        $data = str_replace($tags[0], $tags[1], $data);
    }

    /**
     * Replaces File URLs with {filedir_X} tags.
     *
     * @param string &$data
     */
    public static function replaceFileUrls(&$data)
    {
        $tags = static::_getFileTags();
        $data = str_replace($tags[1], $tags[0], $data);
    }

    /**
     * Replaces {anything_X} tags with their parsed values.
     *
     * @param string &$data
     */
    public static function replaceExtraTags(&$data)
    {
        $addons = ee('Addon')->all();
        foreach ($addons as $fileBrowserAddon) {
            if ($fileBrowserAddon !== null && $fileBrowserAddon->hasRteFilebrowser()) {
                $fqcn = $fileBrowserAddon->getRteFilebrowserClass();
                $fileBrowser = new $fqcn();
                if ($fileBrowser instanceof RteFilebrowserInterface) {
                    $data = $fileBrowser->replaceTags($data);
                }
            }
        }
    }

    /**
     * Replaces {anything_X} tags with their parsed values.
     *
     * @param string &$data
     */
    public static function replaceExtraUrls(&$data)
    {
        $addons = ee('Addon')->all();
        foreach ($addons as $fileBrowserAddon) {
            if ($fileBrowserAddon !== null && $fileBrowserAddon->hasRteFilebrowser()) {
                $fqcn = $fileBrowserAddon->getRteFilebrowserClass();
                $fileBrowser = new $fqcn();
                if ($fileBrowser instanceof RteFilebrowserInterface) {
                    $data = $fileBrowser->replaceUrls($data);
                }
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Gets all the possible {page_X} tags and their replacement URLs
     *
     * @param bool $sort
     *
     * @return array $list
     */
    private static function _getPageTags($site_id = null)
    {
        if (! isset(static::$_pageTags)) {
            $tags = array();
            $urls = array();

            $pageData = static::getSitePages('', $site_id);

            if (!empty($pageData)) {
                foreach ($pageData as $page) {
                    if (isset($page->entry_id)) {
                        $tags[] = LD . 'page_' . $page->entry_id . RD;
                        $urls[] = $page->uri;
                    }
                }
            }

            static::$_pageTags = array($tags, $urls);
        }

        return static::$_pageTags;
    }

    /**
     * Replaces {page_X} tags with the page URLs.
     *
     * @param string &$data
     */
    public static function replacePageTags(&$data)
    {
        if (strpos($data, LD . 'page_') !== false) {
            $tags = static::_getPageTags();

            foreach ($tags[0] as $key => $pageTag) {
                $pattern = '/(?!&quot;|\")(' . preg_quote($pageTag) . ')(&quot;|\"|\/)?/u';
                preg_match_all($pattern, $data, $matches);

                if ($matches && count($matches[0]) > 0) {
                    // $matches[2] should either be &quot;, ", / or empty
                    foreach ($matches[2] as $innerKey => $match) {
                        $search = '/(' . preg_quote($matches[1][$innerKey]) . ')/uU';
                        $replace = $tags[1][$key];

                        // If there is not a trailing quote or slash, we're going to add one.
                        if (empty($match)) {
                            $replace .= '/';
                        }

                        $data = preg_replace($search, $replace, $data);
                    }
                }
            }
        }
    }

    /**
     * Replace page URLs with {page_X} tags.
     *
     * @param string &$data
     */
    public static function replacePageUrls(&$data)
    {
        $tags = static::_getPageTags();

        foreach ($tags[1] as $key => $pageUrl) {
            $pageUrl = str_replace('/', '\/', preg_quote(rtrim($pageUrl, '/')));
            $search = '/(?!\")(' . $pageUrl . ')\/?(?=\")/uU';
            $data = preg_replace($search, $tags[0][$key], $data);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Gets all site page data from the pages module.
     *
     * @param bool $installCheck
     *
     * @return array $pageData
     */
    public static function getSitePages($search = '', $site_id = null)
    {
        if (empty($site_id)) {
            $site_id = ee()->config->item('site_id');
        }

        $cache_key = 'rte_' . $site_id;
        if (!empty($search)) {
            $cache_key .= '_' . urlencode($search);
        }
        $pages = ee()->cache->get('/site_pages/' . md5($cache_key), \Cache::GLOBAL_SCOPE);

        if ($pages === false) {
            $pages = [];
            $break = false;
            /**
             * `rte_autocomplete_pages` extension hook
             * allows addons to modify (narrow down) the list of pages that can be inserted
             * Expects array of following structure:
             * $pages[] = (object) [
             *          'id' => '@unique-identifier',
             *          'text' => 'main displayed text (e.g. entry title)',
             *          'extra' => 'extra info displayed (e.g. channel name)',
             *          'href' => 'link to the page',
             *          'entry_id' => entry ID,
             *          'uri' => page URI
             *      ];
             */
            /*if (ee()->extensions->active_hook('rte_autocomplete_pages') === true) {
                $pages = ee()->extensions->call('rte_autocomplete_pages', $pages, $search, $site_id);
                if (ee()->extensions->end_script === true) {
                    $break = true;
                }
            }*/

            if (!$break) {
                $site = ee('Model')->get('Site', $site_id)->first();
                $site_pages = $site->site_pages;
                if (isset($site_pages[$site_id]['uris'])) {
                    $entry_ids = array_keys($site_pages[$site_id]['uris']);
                    $channels = ee('Model')->get('Channel')
                        ->fields('channel_id', 'channel_title')
                        ->all()
                        ->getDictionary('channel_id', 'channel_title');
                    $entries = ee('Model')->get('ChannelEntry', $entry_ids)
                        ->fields('entry_id', 'title', 'url_title', 'channel_id');
                    if (!empty($search)) {
                        $entries->filter('title', 'LIKE', '%' . $search . '%');
                    }
                    $titles = $entries->all()->getDictionary('entry_id', 'title');
                    $channel_ids = $entries->all()->getDictionary('entry_id', 'channel_id');
                    foreach ($site_pages[$site_id]['uris'] as $entry_id => $uri) {
                        if (isset($titles[$entry_id])) {
                            $pages[] = (object) [
                                'id' => '@' . $entry_id,
                                'text' => $titles[$entry_id],
                                'extra' => $channels[$channel_ids[$entry_id]],
                                'href' => '{page_' . $entry_id . '}',
                                'entry_id' => $entry_id,
                                'uri' => $uri
                            ];
                        }
                    }
                }
            }
            ee()->cache->save('/site_pages/' . md5($cache_key), $pages, 0, \Cache::GLOBAL_SCOPE);
        }

        return $pages;
    }

    /**
     * Just a placeholder
     *
     * @return void
     */
    public static function includeFieldResources()
    {

    }

    /**
     * Backwards compatibility for third-party fieldtypes
     *
     * @param [type] $toolset_id
     * @return void
     */
    public static function insertConfigJsById($toolset_id = null)
    {
        $toolsetId = (!empty($toolset_id)) ? (int) $toolset_id : (!empty(ee()->config->item('rte_default_toolset')) ? (int) ee()->config->item('rte_default_toolset') : null);
        if (!empty($toolsetId)) {
            $toolset = ee('Model')->get('rte:Toolset')->filter('toolset_id', $toolsetId)->first();
        } else {
            $toolset = ee('Model')->get('rte:Toolset')->first();
        }

        // Load proper toolset
        $serviceName = ucfirst($toolset->toolset_type) . 'Service';
        $configHandle = ee('rte:' . $serviceName)->init([], $toolset);

        return $configHandle;
    }
}
