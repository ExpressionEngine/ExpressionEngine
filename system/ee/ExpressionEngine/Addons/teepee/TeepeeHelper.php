<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Teepee;

use ExpressionEngine\Library\Rte\RteFilebrowserInterface;

class TeepeeHelper
{

    private static $_includedFieldResources = false;
    private static $_includedConfigs;
    private static $_fileTags;
    private static $_pageTags;
    private static $_extraTags;
    private static $_sitePages;
    private static $_pageData;

    private static $_toolbarButtonGroups;
    private static $_toolbarButtonLabelOverrides;


    // --------------------------------------------------------------------

    /**
     * Returns toolbar button groupings, based on CKEditor's default "Full" toolbar.
     *
     * @return array $groups
     */
    public static function toolbarButtonGroups()
    {
        if (!isset(static::$_toolbarButtonGroups)) {
            static::$_toolbarButtonGroups = array(
                array('undo', 'redo'),
                array('bold', 'italic', 'underline', 'strikethrough'),
                array('subscript', 'superscript'),
                array('removeFormat'),
                array('numberedList', 'bulletedList'),
                array('outdent', 'indent'),
                array('alignment'),
                array('blockquote'),
                array('link'),
                array('filemanager', 'insertTable', 'horizontalLine', 'specialCharacters', 'mediaEmbed'),
                array('readMore'),
                array('heading'),
                array('fontColor', 'fontBackgroundColor')
            );

            // -------------------------------------------
            //  'teepee_tb_groups' hook
            //   - Allow extensions to modify the available toolbar groups
            //
            if (ee()->extensions->active_hook('teepee_tb_groups')) {
                static::$_toolbarButtonGroups = ee()->extensions->call('teepee_tb_groups', static::$_toolbarButtonGroups);
            }
            //
            // -------------------------------------------
        }

        return static::$_toolbarButtonGroups;
    }

    // --------------------------------------------------------------------

    /**
     * Returns a map of common EE language folder names to CKEditor language codes.
     *
     * @return array $languageMap
     */
    public static function languageMap()
    {
        return array(
            'arabic'              => 'ar',
            'arabic-utf8'         => 'ar',
            'arabic-windows-1256' => 'ar',
            'czech'               => 'cs',
            'cesky'               => 'cs',
            'danish'              => 'da',
            'german'              => 'de',
            'deutsch'             => 'de',
            'english'             => 'en',
            'spanish'             => 'es',
            'spanish_ee201pb'     => 'es',
            'finnish'             => 'fi',
            'french'              => 'fr',
            'hungarian'           => 'hu',
            'croatian'            => 'hr',
            'italian'             => 'it',
            'japanese'            => 'ja',
            'korean'              => 'ko',
            'dutch'               => 'nl',
            'norwegian'           => 'no',
            'polish'              => 'pl',
            'brazilian'           => 'pt',
            'portuguese'          => 'pt',
            'brasileiro'          => 'pt',
            'brasileiro_160'      => 'pt',
            'russian'             => 'ru',
            'russian_utf8'        => 'ru',
            'russian_win1251'     => 'ru',
            'slovak'              => 'sk',
            'swedish'             => 'sv',
            'swedish_ee20pb'      => 'sv',
            'turkish'             => 'tr',
            'ukrainian'           => 'uk',
            'chinese'             => 'zh',
            'chinese_traditional' => 'zh',
            'chinese_simplified'  => 'zh'
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
            'toolbar'        => $toolbars['Basic'],
            'height'         => '200',
            'upload_dir'     => 'all'
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
            'Full'  => array(
                "bold",
                "italic",
                "strikethrough",
                "underline",
                "blockquote",
                "heading",
                "removeFormat",
                "|",
                "undo",
                "redo",
                "|",
                "numberedList",
                "bulletedList",
                "outdent",
                "indent",
                "|",
                "link",
                "filemanager",
                "insertTable",
                "mediaEmbed",
                "|",
                "alignment:left",
                "alignment:right",
                "alignment:center",
                "alignment:justify",
                "|",
                "horizontalLine",
                "specialCharacters",
                "readMore",
                "|",
                "fontColor",
                "fontBackgroundColor"
            )
        );
    }

    // --------------------------------------------------------------------

    /**
     * Converts flat array of buttons into multi-dimensional
     * array of tool groups and their buttons.
     *
     * @param array $buttons
     * @param bool  $includeMissing should missing buttons be included
     *
     * @return array $result
     */
    public static function createToolbar($buttons, $includeMissing = false)
    {
        $toolbar = array();

        //remap old Wygwam (CKEditor 4) buttons
        /*foreach ($buttons as $i=>$button) {
            $button = self::_convertButton($button);
            if (empty($button)) {
                unset($buttons[$i]);
            } else {
                $buttons[$i] = $button;
            }
        }*/

        // group buttons by toolgroup
        $toolbarButtonGroups = static::toolbarButtonGroups();

        foreach ($toolbarButtonGroups as $groupIndex => &$group) {
            $groupSelectionIndex = null;
            $missing = array();
            foreach ($group as $buttonIndex => &$button) {
                //$button = self::_convertButton($button);
                if (empty($button)) continue;
                // selected?
                if (($buttonSelectionIndex = array_search($button, $buttons)) !== false) {

                    if ($groupSelectionIndex === null) {
                        $groupSelectionIndex = $buttonSelectionIndex;
                    }

                    if (! isset($toolbar[$groupSelectionIndex])) {
                        $toolbar[$groupSelectionIndex] = array();
                    }

                    $toolbar[$groupSelectionIndex]['b'.$buttonIndex] = $button;
                } elseif ($includeMissing) {
                    $missing['b'.$buttonIndex] = '!'.$button;
                }
            }

            if ($groupSelectionIndex !== null) {
                if ($includeMissing) {
                    $toolbar[$groupSelectionIndex] = array_merge($missing, $toolbar[$groupSelectionIndex]);
                }

                ksort($toolbar[$groupSelectionIndex]);
                $toolbar[$groupSelectionIndex] = array_values($toolbar[$groupSelectionIndex]);
            }
        }

        // sort by keys and remove them
        ksort($toolbar);
        $result = array();

        foreach ($toolbar as $toolGroup) {
            if (!empty($toolGroup)) {
                $result = array_merge($result, $toolGroup);
                $result[] = '|';
            }
        }

        array_pop($result);

        $toolbarObject = new \stdClass();
        $toolbarObject->items = $result;

        return $toolbarObject;
    }

    // --------------------------------------------------------------------

    /**
     * Includes the necessary CSS and JS files to get Teepee fields working.
     */
    public static function includeFieldResources()
    {
        if (! static::$_includedFieldResources) {
            ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THEMES . 'teepee/scripts/ckeditor.js"></script>');
            ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THEMES . 'teepee/scripts/teepee.js"></script>');
            ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . URL_THEMES . 'teepee/styles/teepee.css' . '" />');

            $action_id = ee()->db->select('action_id')
                ->where('class', 'Teepee')
                ->where('method', 'pages_autocomplete')
                ->get('actions');

            $filedir_urls = ee('Model')->get('UploadDestination')->all()->getDictionary('id', 'url');

            ee()->javascript->set_global([
                'Teepee.pages_autocomplete' => ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id->row('action_id') . '&t=' . ee()->localize->now,
                'Teepee.filedirUrls' => $filedir_urls
            ]);

            static::$_includedFieldResources = true;
        }
    }

    /**
     * Inserts the Teepee config JS in the page foot by config ID.
     *
     * @param $configId
     *
     * @return $configHandle The handle for config used by Teepee JS
     */
    public static function insertConfigJsById($configId)
    {
        // starting point
        $baseConfig = static::defaultConfigSettings();

        // -------------------------------------------
        //  Editor Config
        // -------------------------------------------

        if ($toolset = ee('Model')->get('teepee:Toolset')->filter('toolset_id', $configId)->first()
        ) {
            $configHandle = preg_replace('/[^a-z0-9]/i', '_', $toolset->toolset_name) . $configId;
            $config = array_merge($baseConfig, $toolset->settings);
        } elseif ($toolset = ee('Model')->get('teepee:Toolset')->filter('toolset_id', ee()->config->item('teepee_default_toolset_id'))->first()
        ) {
            $configHandle = preg_replace('/[^a-z0-9]/i', '_', $toolset->toolset_name) . ee()->config->item('teepee_default_toolset_id');
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
            $config['toolbar'] = static::createToolbar($config['toolbar']);
            $config['image'] = new \stdClass();
            $config['image']->toolbar = [
                'imageTextAlternative',
                '|',
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

        if (!empty(ee()->config->item('site_pages'))) {
            ee()->cp->add_to_foot('<script type="text/javascript">
                EE.Teepee.configs.' . $configHandle . '.mention = {"feeds": [{"marker": "@", "feed": getPages, "itemRenderer": formatPageLinks, "minimumCharacters": 3}]};
            </script>');
        }

        // -------------------------------------------
        //  File Browser Config
        // -------------------------------------------

        $uploadDir = (isset($config['upload_dir']) && !empty($config['upload_dir'])) ? $config['upload_dir'] : 'all';
        unset($config['upload_dir']);

        $fileBrowserOptions = array_unique([ee()->config->item('teepee_file_browser'), 'filepicker']);
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

        // -------------------------------------------
        //  JSONify Config and Return
        // -------------------------------------------
        ee()->javascript->set_global([
            'Teepee.configs.' . $configHandle => $config
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
                $tags[] = LD.'filedir_'.$id.RD;
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
            if ( $fileBrowserAddon !== null && $fileBrowserAddon->hasRteFilebrowser()) {
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
            if ( $fileBrowserAddon !== null && $fileBrowserAddon->hasRteFilebrowser()) {
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

            foreach ($pageData as $page) {
                $tags[] = LD.'page_'.$page->entry_id.RD;
                $urls[] = $page->uri;
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
        if (strpos($data, LD.'page_') !== false) {
            $tags = static::_getPageTags();

            foreach ($tags[0] as $key => $pageTag) {
                $pattern = '/(?!&quot;|\")('.preg_quote($pageTag).')(&quot;|\"|\/)?/u';
                preg_match_all($pattern, $data, $matches);

                if ($matches && count($matches[0]) > 0) {
                    // $matches[2] should either be &quot;, ", / or empty
                    foreach ($matches[2] as $innerKey => $match) {
                        $search = '/('.preg_quote($matches[1][$innerKey]).')/uU';
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
            $search = '/(?!\")('.$pageUrl.')\/?(?=\")/uU';
            $data = preg_replace($search, $tags[0][$key], $data);
        }
    }

    // --------------------------------------------------------------------


    /**
     * Gets the Pages module data.
     *
     * @return array $pagesModule
     */
    private static function _getPagesModuleData()
    {
        if (! isset(static::$_pageData)) {
            static::$_pageData = array();

            if (($pages = static::_getSitePages()) && ($pageIds = array_filter(array_keys($pages['uris'])))) {
                /**
                 * @var \EllisLab\ExpressionEngine\Model\Channel\ChannelEntry|null $entries
                 */

                $query = ee()->db->query('SELECT entry_id, channel_id, title, url_title, status
                                        FROM exp_channel_titles
                                        WHERE entry_id IN ('.implode(',', $pageIds).')
                                        ORDER BY entry_id DESC');

                // index entries by entry_id
                $entryData = array();


                foreach ($query->result_array() as $entry) {
                    $entryData[$entry['entry_id']] = $entry;
                }

                $add_trailing_slash = false;

                // Check if the trailing slash setting in Structure is turned on.
                if (static::isStructureInstalled()) {
                    $slash_result = ee()->db->get_where('structure_settings', array('var'=>'add_trailing_slash'), 1)->row();
                    if ($slash_result && $slash_result->var_value == 'y') {
                        $add_trailing_slash = true;
                    }
                }

                foreach ($pages['uris'] as $entryId => $uri) {
                    if (! isset($entryData[$entryId])) {
                        continue;
                    }

                    $entry = $entryData[$entryId];

                    $url = ee()->functions->create_page_url($pages['url'], $uri);


                    if (!$url || $url == '/') {
                        continue;
                    }

                    if ($add_trailing_slash && substr($url, -1, 1) != '/') {
                        $url .= '/';
                    }

                    static::$_pageData[] = array(
                        $entryId,
                        $entry['channel_id'],
                        $entry['title'],
                        '0',
                        $url
                    );
                }
            }

        }

        return static::$_pageData;
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

        $cache_key = '/site_pages/rte_'.$site_id;
        if (!empty($search)) {
            $cache_key .= '_' . urlencode($search);
        }
        $pages = ee()->cache->get($cache_key, \Cache::GLOBAL_SCOPE);

        if ($pages === FALSE) {

            $break = false;
            /**
             * `teepee_autocomplete_pages` extension hook
             * allows addons to modify (narrow down) the list of pages that can be inserted
             * Expects array of following structure:
             * $pages[] = (object) [
             *			'id' => '@unique-identifier',
             *			'text' => 'main displayed text (e.g. entry title)',
             *			'extra' => 'extra info displayed (e.g. channel name)',
             *			'href' => 'link to the page',
             *          'entry_id' => entry ID,
             *          'uri' => page URI
             *		];
             */
            if (ee()->extensions->active_hook('teepee_autocomplete_pages') === true) {
                $pages = ee()->extensions->call('teepee_autocomplete_pages', $this, $pages, $search, $site_id);
                if (ee()->extensions->end_script === true) {
                    $break = true;
                }
            }

            if (!$break) {
                $site = ee('Model')->get('Site', $site_id)->first();
                $site_pages = $site->site_pages;
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
                foreach($site_pages[$site_id]['uris'] as $entry_id => $uri) {
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
            ee()->cache->save($cache_key, $pages, 0, \Cache::GLOBAL_SCOPE);
        }

        return $pages;
    }



}
