<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Rte;

use ExpressionEngine\Library\Rte\RteFilebrowserInterface;
use ExpressionEngine\Library\CP\FileManager\Traits\FileUsageTrait;

class RteHelper
{
    use FileUsageTrait;

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

            ee()->load->model('file_upload_preferences_model');
            $dirs = ee()->file_upload_preferences_model->get_paths();

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
        ee()->load->library('file_field');
        $data = ee()->file_field->parse_string($data);
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
        
        $filedirReplacements = static::getFileUsageReplacements($data);
        if (!empty($filedirReplacements)) {
            foreach ($filedirReplacements as $file_id => $replacements) {
                foreach ($replacements as $from => $to) {
                    $data = str_replace($from, $to, $data);
                }
            }
        }
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
                // Since the arrays being populated are going to be used in
                // replacing URL's we need to sort them by the length
                // of their url.  This prevents shorter segments that
                // are part of longer segments from being replaced
                uasort($pageData, function ($a, $b) {
                    return (strlen($a->uri) < strlen($b->uri)) ? 1 : -1;
                });
                foreach ($pageData as $page) {
                    if (isset($page->entry_id)) {
                        // We want to preserve sorting order, so we'll make sure the key is string
                        $key = '_' . $page->entry_id;
                        $tags[$key] = LD . 'page_' . $page->entry_id . RD;
                        $urls[$key] = $page->uri;
                    }
                }
            }

            static::$_pageTags = array($tags, $urls);
        }

        return static::$_pageTags;
    }

    /**
     * Replaces {page_X} tags with the page URLs.
     * This happens on entry render and when field is loaded for editing
     *
     * @param string &$data
     */
    public static function replacePageTags(&$data, $site_id = null, $buildFullUrls = false)
    {
        // only do the replacement if there's something to replace
        if (!empty($data) && strpos($data, LD . 'page_') !== false) {
            $tags = static::_getPageTags($site_id);

            $hasPageTags = preg_match_all('/' . LD . 'page_(\d+)' . RD . '/', $data, $pageTags);
            if (empty($hasPageTags)) {
                return $data;
            }

            $find = [];
            $replace = [];

            foreach ($pageTags[0] as $key => $pageTag) {
                if (isset($tags[1]['_' . $pageTags[1][$key]])) {
                    $url = $tags[1]['_' . $pageTags[1][$key]];
                    if (empty($url)) {
                        // homepage might be empty string, make sure we add a slash
                        $url = '/';
                    }
                    if ($buildFullUrls) {
                        $url = ee()->functions->create_url($url);
                    }
                    // ensure trailing slash - TODO need to make this an option
                    // $url = rtrim($url, '/') . '/';
                    $find[] = $pageTag;
                    $replace[] = $url;
                }
            }
            $data = str_replace($find, $replace, $data);
        }
    }

    /**
     * Replace page URLs with {page_X} tags.
     * This happens on entry save
     *
     * @param string &$data
     */
    public static function replacePageUrls(&$data)
    {
        $tags = static::_getPageTags();
        $siteUrlSansProtocol = trim(str_replace(['http://', 'https://'], '', ee()->config->item('site_url')), '/');
        // This regular expression is built with consideration that site URL may be present or not present (with or without protocol)),
        // optional index.php and question mark after it,
        // The link might be followed by an anchor or extra GET parameters
        // The capturing groups are:
        // 1. The site URL (with or without protocol)
        // 2. The index.php (with or without question mark)
        // 3. The page URI
        $regex = '/"((?:(?:https?:)?\/\/)?' . str_replace('/', '\/', preg_quote($siteUrlSansProtocol)) . ')?(\/(?:' . str_replace('/', '\/', preg_quote(ee()->config->item('site_index'))) . ')?\??)?(\/__PAGE_URL__\/?)(?:\?[\S]*|&[\S]*|#[\S]*)?"/uU';

        foreach ($tags[1] as $key => $pageUrl) {
            $pageUrl = str_replace('/', '\/', preg_quote(trim($pageUrl, '/')));
            $search = str_replace('__PAGE_URL__', $pageUrl, $regex);
            $hasMatch = preg_match_all($search, $data, $matches);
            if (!empty($hasMatch)) {
                foreach ($matches[0] as $i => $match) {
                    // build the part that we need to replace
                    // we only want to replace the links, so include the quote
                    $find = '"' . $matches[1][$i] . $matches[2][$i] . $matches[3][$i];
                    // do the replacement
                    $data = str_replace($find, '"' . $tags[0][$key], $data);
                }
            }
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
                    $entries->filter('title', 'LIKE', '%' . ee()->db->escape_like_str($search) . '%');
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
                            'uri' => $uri,
                        ];
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
