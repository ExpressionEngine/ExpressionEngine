<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Rte;

use ExpressionEngine\Library\Rte\RteFilebrowserInterface;

class RteHelper
{
    private static $_includedFieldResources = false;
    private static $_includedConfigs;
    private static $_fileTags;
    private static $_pageTags;
    private static $_extraTags;
    private static $_sitePages;
    private static $_pageData;

    // --------------------------------------------------------------------

    /**
     * Returns a map of common EE language folder names to CKEditor language codes.
     *
     * @return array $languageMap
     */
    public static function languageMap()
    {
        return array(
            'arabic' => 'ar',
            'arabic-utf8' => 'ar',
            'arabic-windows-1256' => 'ar',
            'czech' => 'cs',
            'cesky' => 'cs',
            'danish' => 'da',
            'german' => 'de',
            'deutsch' => 'de',
            'english' => 'en',
            'spanish' => 'es',
            'spanish_ee201pb' => 'es',
            'finnish' => 'fi',
            'french' => 'fr',
            'hungarian' => 'hu',
            'croatian' => 'hr',
            'italian' => 'it',
            'japanese' => 'ja',
            'korean' => 'ko',
            'dutch' => 'nl',
            'norwegian' => 'no',
            'polish' => 'pl',
            'brazilian' => 'pt',
            'portuguese' => 'pt',
            'brasileiro' => 'pt',
            'brasileiro_160' => 'pt',
            'russian' => 'ru',
            'russian_utf8' => 'ru',
            'russian_win1251' => 'ru',
            'slovak' => 'sk',
            'swedish' => 'sv',
            'swedish_ee20pb' => 'sv',
            'turkish' => 'tr',
            'ukrainian' => 'uk',
            'chinese' => 'zh',
            'chinese_traditional' => 'zh',
            'chinese_simplified' => 'zh'
        );
    }

    // --------------------------------------------------------------------

    /**
     * Returns the default config settings.
     *
     * @return array $configSettings
     */
    public static function defaultConfigSettings()
    {
        $toolbars = static::defaultToolbars();

        return array(
            'toolbar' => $toolbars['Basic'],
            'height' => '200',
            'upload_dir' => 'all'
        );
    }

    /**
     * Returns the default toolbars.
     *
     * @return array $toolbars
     */
    public static function defaultToolbars()
    {
        return array(
            'Basic' => array(
                "bold",
                "italic",
                "underline",
                "numberedList",
                "bulletedList",
                "link"
            ),
            'Full' => array(
                "bold",
                "italic",
                "strikethrough",
                "underline",
                "subscript",
                "superscript",
                "blockquote",
                "code",
                "heading",
                "removeFormat",
                "undo",
                "redo",
                "numberedList",
                "bulletedList",
                "outdent",
                "indent",
                "link",
                "filemanager",
                "insertTable",
                "mediaEmbed",
                "alignment:left",
                "alignment:right",
                "alignment:center",
                "alignment:justify",
                "horizontalLine",
                "specialCharacters",
                "readMore",
                "fontColor",
                "fontBackgroundColor"
            )
        );
    }

    // --------------------------------------------------------------------

    /**
     * Includes the necessary CSS and JS files to get Rte fields working.
     */
    public static function includeFieldResources()
    {
        if (! static::$_includedFieldResources) {
            ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THEMES . 'rte/scripts/ckeditor.js"></script>');
            ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THEMES . 'rte/scripts/rte.js"></script>');
            ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . URL_THEMES . 'rte/styles/rte.css' . '" />');

            $action_id = ee()->db->select('action_id')
                ->where('class', 'Rte')
                ->where('method', 'pages_autocomplete')
                ->get('actions');

            $filedir_urls = ee('Model')->get('UploadDestination')->all()->getDictionary('id', 'url');

            ee()->javascript->set_global([
                'Rte.pages_autocomplete' => ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id->row('action_id') . '&t=' . ee()->localize->now,
                'Rte.filedirUrls' => $filedir_urls
            ]);

            static::$_includedFieldResources = true;
        }
    }

    /**
     * Inserts the Rte config JS in the page foot by config ID.
     *
     * @param $configId
     *
     * @return $configHandle The handle for config used by Rte JS
     */
    public static function insertConfigJsById($configId = null)
    {
        ee()->lang->loadfile('rte');

        // starting point
        $baseConfig = static::defaultConfigSettings();

        // -------------------------------------------
        //  Editor Config
        // -------------------------------------------

        if (empty($configId) && !empty(ee()->config->item('rte_default_toolset'))) {
            $configId = ee()->config->item('rte_default_toolset');
        }
        $toolsetQuery = ee('Model')->get('rte:Toolset');
        if (!empty($configId)) {
            $toolsetQuery->filter('toolset_id', $configId);
        }
        $toolset = $toolsetQuery->first();
        if (!empty($toolset)) {
            $configHandle = preg_replace('/[^a-z0-9]/i', '_', $toolset->toolset_name) . $toolset->toolset_id;
            $config = array_merge($baseConfig, $toolset->settings);
        } else {
            $config = $baseConfig;
            $configHandle = 'default0';
        }

        // skip if already included
        if (isset(static::$_includedConfigs) && in_array($configHandle, static::$_includedConfigs)) {
            return $configHandle;
        }

        // language
        $language = isset(ee()->session) ? ee()->session->get_language() : ee()->config->item('deft_lang');
        $langMap = static::languageMap();
        $config['language'] = isset($langMap[$language]) ? $langMap[$language] : 'en';

        // toolbar
        if (is_array($config['toolbar'])) {
            $toolbarObject = new \stdClass();
            $toolbarObject->items = $config['toolbar'];
            $config['toolbar'] = $toolbarObject;
            $config['image'] = new \stdClass();
            $config['image']->toolbar = [
                'imageTextAlternative',
                'imageStyle:full',
                'imageStyle:side',
                'imageStyle:alignLeft',
                'imageStyle:alignCenter',
                'imageStyle:alignRight'
            ];
            $config['image']->styles = [
                'full',
                'side',
                'alignLeft',
                'alignCenter',
                'alignRight'
            ];
        }

        if (in_array('heading', $config['toolbar']->items)) {
            $config['heading'] = new \stdClass();
            $config['heading']->options = [
                (object) ['model' => 'paragraph', 'title' => lang('paragraph_rte')],
                (object) ['model' => 'heading1', 'view' => 'h1', 'title' => lang('heading_h1_rte'), 'class' => 'ck-heading_heading1'],
                (object) ['model' => 'heading2', 'view' => 'h2', 'title' => lang('heading_h2_rte'), 'class' => 'ck-heading_heading2'],
                (object) ['model' => 'heading3', 'view' => 'h3', 'title' => lang('heading_h3_rte'), 'class' => 'ck-heading_heading3'],
                (object) ['model' => 'heading4', 'view' => 'h4', 'title' => lang('heading_h4_rte'), 'class' => 'ck-heading_heading4'],
                (object) ['model' => 'heading5', 'view' => 'h5', 'title' => lang('heading_h5_rte'), 'class' => 'ck-heading_heading5'],
                (object) ['model' => 'heading6', 'view' => 'h6', 'title' => lang('heading_h6_rte'), 'class' => 'ck-heading_heading6']
            ];
        }

        if (!empty(ee()->config->item('site_pages'))) {
            ee()->cp->add_to_foot('<script type="text/javascript">
                EE.Rte.configs.' . $configHandle . '.mention = {"feeds": [{"marker": "@", "feed": getPages, "itemRenderer": formatPageLinks, "minimumCharacters": 3}]};
            </script>');
        }

        // -------------------------------------------
        //  File Browser Config
        // -------------------------------------------

        $uploadDir = (isset($config['upload_dir']) && !empty($config['upload_dir'])) ? $config['upload_dir'] : 'all';
        unset($config['upload_dir']);

        $fileBrowserOptions = ['filepicker'];
        if (!empty(ee()->config->item('rte_file_browser'))) {
            array_unshift($fileBrowserOptions, ee()->config->item('rte_file_browser'));
        }
        $fileBrowserOptions = array_unique($fileBrowserOptions);
        foreach ($fileBrowserOptions as $fileBrowserName) {
            $fileBrowserAddon = ee('Addon')->get($fileBrowserName);
            if ($fileBrowserAddon !== null && $fileBrowserAddon->isInstalled() && $fileBrowserAddon->hasRteFilebrowser()) {
                $fqcn = $fileBrowserAddon->getRteFilebrowserClass();
                $fileBrowser = new $fqcn();
                if ($fileBrowser instanceof RteFilebrowserInterface) {
                    $fileBrowser->addJs($uploadDir);

                    break;
                }
            }
        }

        if (stripos($fqcn, 'filepicker_rtefb') !== false && REQ != 'CP') {
            unset($config['image']);
            $filemanager_key = array_search('filemanager', $config['toolbar']->items);
            if ($filemanager_key) {
                $items = $config['toolbar']->items;
                unset($items[$filemanager_key]);
                $config['toolbar']->items = array_values($items);
            }
        }

        $config['toolbar']->shouldNotGroupWhenFull = true;

        //link
        $config['link'] = (object) ['decorators' => [
            'openInNewTab' => [
                'mode' => 'manual',
                'label' => lang('open_in_new_tab'),
                'attributes' => [
                    'target' => '_blank',
                    'rel' => 'noopener noreferrer'
                ]
            ]
        ]
        ];

        // -------------------------------------------
        //  JSONify Config and Return
        // -------------------------------------------
        ee()->javascript->set_global([
            'Rte.configs.' . $configHandle => $config
        ]);

        static::$_includedConfigs[] = $configHandle;

        ee()->cp->add_to_head('<style type="text/css">.ck-editor__editable_inline { min-height: ' . $config['height'] . 'px; }</style>');

        return $configHandle;
    }

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
                    $tags[] = LD . 'page_' . $page->entry_id . RD;
                    $urls[] = $page->uri;
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

        $cache_key = '/site_pages/rte_' . $site_id;
        if (!empty($search)) {
            $cache_key .= '_' . urlencode($search);
        }
        $pages = ee()->cache->get($cache_key, \Cache::GLOBAL_SCOPE);

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
            if (ee()->extensions->active_hook('rte_autocomplete_pages') === true) {
                $pages = ee()->extensions->call('rte_autocomplete_pages', $pages, $search, $site_id);
                if (ee()->extensions->end_script === true) {
                    $break = true;
                }
            }

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
                        ->fields('entry_id', 'title', 'url_title', 'channel_id')
                        ->all();
                    $titles = $entries->getDictionary('entry_id', 'title');
                    $channel_ids = $entries->getDictionary('entry_id', 'channel_id');
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
            ee()->cache->save($cache_key, $pages, 0, \Cache::GLOBAL_SCOPE);
        }

        return $pages;
    }
}
