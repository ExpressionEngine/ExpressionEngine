<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Rte\Service;

use ExpressionEngine\Library\Rte\RteFilebrowserInterface;

class RedactorService extends AbstractRteService implements RteService {

    public $class = 'rte-textarea redactor-box';
    public $handle;
    protected $settings;
    protected $toolset;
    protected static $_includedFieldResources = false;
    protected static $_includedConfigs;
    protected static $type = 'redactor';

    protected function includeFieldResources()
    {
        if (! static::$_includedFieldResources) {
            ee()->load->library('file_field');
            ee()->lang->loadfile('fieldtypes');
            ee()->file_field->loadDragAndDropAssets();

            ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/fields/rte/' . strtolower(static::$type) . '/redactor.min.css" type="text/css" />');
            ee()->cp->add_js_script(['file' => [
                'fields/rte/' . strtolower(static::$type) . '/redactor.min',
                'fields/rte/rte']
            ]);

            if (REQ == 'CP') {
                ee()->cp->add_js_script(['file' => [
                    'fields/file/file_field_drag_and_drop',
                    'fields/file/concurrency_queue',
                    'fields/file/file_upload_progress_table',
                    'fields/file/drag_and_drop_upload',
                    'fields/grid/file_grid']
                ]);
            }

            $language = isset(ee()->session) ? ee()->session->get_language() : ee()->config->item('deft_lang');
            $lang_code = ee()->lang->code($language);
            if ($lang_code != 'en') {
                ee()->cp->add_js_script(['file' => ['fields/rte/redactor/langs/' . $lang_code]]);
            }

            $filedir_urls = ee('Model')->get('UploadDestination')->all()->getDictionary('id', 'url');
            ee()->javascript->set_global([
                'Rte.filedirUrls' => (object) $filedir_urls
            ]);

            static::$_includedFieldResources = true;
        }
    }

    protected function insertConfigJsById()
    {
        ee()->lang->loadfile('rte');

        // starting point
        $baseConfig = static::defaultConfigSettings();

        if (!$this->toolset && !empty(ee()->config->item('rte_default_toolset'))) {
            $configId = ee()->config->item('rte_default_toolset');
            $toolsetQuery = ee('Model')->get('rte:Toolset');
            $toolsetQuery->filter('toolset_type', static::$type);
            if (!empty($configId)) {
                $toolsetQuery->filter('toolset_id', $configId);
            }
            $this->toolset = $toolsetQuery->first();
        }

        if (!empty($this->toolset)) {
            $configHandle = preg_replace('/[^a-z0-9]/i', '_', $this->toolset->toolset_name) . $this->toolset->toolset_id;
            $config = array_merge($baseConfig, $this->toolset->settings);
        } else {
            $config = $baseConfig;
            $configHandle = 'redactordefault0';
        }

        $this->handle = $configHandle;

        // skip if already included
        if (isset(static::$_includedConfigs) && in_array($configHandle, static::$_includedConfigs)) {
            return $configHandle;
        }

        // language
        $language = isset(ee()->session) ? ee()->session->get_language() : ee()->config->item('deft_lang');
        $config['toolbar']['lang'] = ee()->lang->code($language);

        if (!empty(ee()->config->item('site_pages')) && !empty(array_intersect(['rte_definedlinks', 'pages'], $config['toolbar']['plugins']))) {
            $action_id = ee()->db->select('action_id')
                ->where('class', 'Rte')
                ->where('method', 'pages_autocomplete')
                ->get('actions');
            $config['toolbar']['definedlinks'] = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id->row('action_id') . '&t=' . ee()->localize->now;
            $config['toolbar']['handle'] = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id->row('action_id') . '&t=' . ee()->localize->now;
        }

        $config['toolbar']['stylesClass'] = 'redactor-styles rte_' . $configHandle;

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

        // EE FilePicker is not available on frontend channel forms
        if (stripos($fqcn, 'filepicker_rtefb') !== false && REQ != 'CP') {
            $filemanager_key = array_search('filebrowser', $config['toolbar']['plugins']);
            if ($filemanager_key !== false) {
                $items = $config['toolbar']['plugins'];
                unset($items[$filemanager_key]);
                $config['toolbar']['plugins'] = array_values($items);
            }
        }

        if (isset($config['height']) && !empty($config['height']) && is_numeric($config['height'])) {
            $config['toolbar']['minHeight'] = (int) $config['height'] . 'px';
        }
        if (isset($config['max_height']) && !empty($config['max_height']) && is_numeric($config['max_height'])) {
            $config['toolbar']['maxHeight'] = (int) $config['max_height'] . 'px';
        }

        if (isset($config['limiter']) && !empty($config['limiter']) && is_numeric($config['limiter'])) {
            $config['toolbar']['plugins'][] = 'limiter';
            $config['toolbar']['limiter'] = (int) $config['limiter'];
        }

        //link
        $config['toolbar']['linkValidation'] = isset($config['toolbar']['linkValidation']) ? (bool) $config['toolbar']['linkValidation'] : false;
        $config['toolbar']['linkTarget'] = isset($config['toolbar']['linkTarget']) ? (bool) $config['toolbar']['linkTarget'] : true;
        $config['toolbar']['linkNewTab'] = isset($config['toolbar']['linkNewTab']) ? (bool) $config['toolbar']['linkNewTab'] : true;

        if (isset($config['field_text_direction'])) {
            $config['toolbar']['direction'] = $config['field_text_direction'];
            unset($config['field_text_direction']);
        }

        unset($config['rte_config_json']);
        unset($config['rte_advanced_config']);

        // -------------------------------------------
        //  JSONify Config and Return
        // -------------------------------------------
        ee()->javascript->set_global([
            'Rte.configs.' . $configHandle => array_merge(['type' => static::$type], $config['toolbar'])
        ]);

        static::$_includedConfigs[] = $configHandle;

        if (isset($config['css_template'])) {
            $this->includeCustomCSS($configHandle, $config['css_template'], '.redactor-styles.rte_' . $configHandle);
        }

        if (isset($config['js_template']) && !empty($config['js_template'])) {
            ee()->cp->add_js_script([
                'template' => $config['js_template']
            ]);
        }

        return $configHandle;
    }

    /**
     * Returns the default config settings.
     *
     * @return array $configSettings
     */
    public static function defaultConfigSettings()
    {

        return array(
            'type' => static::$type,
            'toolbar' => static::defaultToolbars()['Redactor Basic'],
            'height' => '200',
            'upload_dir' => 'all'
        );
    }

    public function toolbarInputHtml($config)
    {
            ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/fields/rte/' . strtolower(static::$type) . '/redactor.min.css" type="text/css" />');

            $selection = [];
            if (is_object($config->settings['toolbar'])) {
                $selection = (array) $config->settings['toolbar'];
            } else {
                $selection = isset($config->settings['toolbar']['buttons']) && is_array($config->settings['toolbar']['buttons']) ? $config->settings['toolbar']['buttons'] : $config->settings['toolbar'];
            }

            $fullToolbar = array_merge($selection, static::defaultToolbars()['Redactor Full']['buttons']);//merge to get the right order
            $fullToolset = [];
            foreach ($fullToolbar as $i => $tool) {
                if (in_array($tool, static::defaultToolbars()['Redactor Full']['buttons'])) {
                    $fullToolset[$tool] = lang($tool . '_rte');
                }
            }

            return ee('View')->make('rte:redactor-toolbar')->render(
                [
                    'buttons' => $fullToolset,
                    'selection' => $selection,
                    'type' => 'buttons'
                ]
            );
    }

    public function pluginsInputHtml($config)
    {
            $selection = [];
            if (is_object($config->settings['toolbar'])) {
                $selection = (array) $config->settings['toolbar'];
            } else {
                $selection = isset($config->settings['toolbar']['plugins']) ? $config->settings['toolbar']['plugins'] : $config->settings['toolbar'];
            }

            $fullToolbar = array_merge($selection, static::defaultToolbars()['Redactor Full']['plugins']);
            $fullToolset = [];
            foreach ($fullToolbar as $i => $tool) {
                if ($tool == 'limiter') {
                    continue;//this one one is included based on whether setting is provided
                }
                if (in_array($tool, static::defaultToolbars()['Redactor Full']['plugins'])) {
                    $fullToolset[$tool] = lang($tool . '_rte');
                }
            }

            return ee('View')->make('rte:redactor-toolbar')->render(
                [
                    'buttons' => $fullToolset,
                    'selection' => $selection,
                    'type' => 'plugins'
                ]
            );
    }

    public static function defaultToolbars()
    {
        return [
            'Redactor Basic' => [
                'buttons' => [
                    'bold',
                    'italic',
                    'underline',
                    'ol',
                    'ul',
                    'link',
                ],
                'plugins' => [

                ],
            ],
            'Redactor Full' => [
                'buttons' => [
                    'html',
                    'format',
                    'bold',
                    'italic',
                    'deleted',
                    'underline',
                    'redo',
                    'undo',
                    'ol',
                    'ul',
                    'indent',
                    'outdent',
                    'sup',
                    'sub',
                    'link',
                    'line'
                ],
                'plugins' => [
                    'alignment',
                    'rte_definedlinks',
                    'filebrowser',
                    'pages',
                    'inlinestyle',
                    'fontcolor',
                    'limiter',
                    'counter',
                    'properties',
                    'specialchars',
                    'table',
                    'video',
                    'widget',
                    'readmore',
                    'fullscreen',
                ]
            ]
        ];
    }

}
