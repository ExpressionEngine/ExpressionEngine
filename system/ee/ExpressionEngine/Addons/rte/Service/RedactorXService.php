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

class RedactorXService extends RedactorService implements RteService {

    protected static $type = 'redactorX';
    protected static $_includedFieldResources = false;

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
            $configHandle = 'redactorxdefault0';
        }

        $this->handle = $configHandle;

        // skip if already included
        if (isset(static::$_includedConfigs) && in_array($configHandle, static::$_includedConfigs)) {
            return $configHandle;
        }

        if (!isset($config['toolbar']['editor']) || !is_object($config['toolbar']['editor'])) {
            $config['toolbar']['editor'] = new \stdClass();
        }

        // language
        $language = isset(ee()->session) ? ee()->session->get_language() : ee()->config->item('deft_lang');
        $config['toolbar']['editor']->lang = ee()->lang->code($language);
        if (isset($config['field_text_direction']) && $config['field_text_direction'] == 'rtl') {
            $config['toolbar']['editor']->direction = 'rtl';
        }

        $config['toolbar']['editor']->focus = false;
        $config['toolbar']['editor']->drop = false;
        $config['toolbar']['reorder'] = true;

        // toolbars
        if (!isset($config['toolbar']['toolbar']) || !is_object($config['toolbar']['toolbar'])) {
            $config['toolbar']['toolbar'] = new \stdClass();
        }
        if (isset($config['toolbar']['sticky'])) {
            $config['toolbar']['toolbar']->sticky = get_bool_from_string($config['toolbar']['sticky']);
            unset($config['toolbar']['sticky']);
        }
        $config['toolbar']['toolbar']->stickyTopOffset = 60;

        if (isset($config['toolbar']['hide'])) {
            $config['toolbar']['toolbar']->hide = $config['toolbar']['hide'];
        }

        if (isset($config['toolbar']['toolbar_hide'])) {
            if ($config['toolbar']['toolbar_hide'] != 'y') {
                $config['toolbar']['toolbar'] = false;
            }
        }

        if (!isset($config['toolbar']['buttons']) || !is_object($config['toolbar']['buttons'])) {
            $config['toolbar']['buttons'] = new \stdClass();
        }
        if (isset($config['toolbar']['topbar'])) {
            $config['toolbar']['buttons']->topbar = $config['toolbar']['topbar'];
        }
        unset($config['toolbar']['topbar']);
        if (isset($config['toolbar']['toolbar_topbar'])) {
            if ($config['toolbar']['toolbar_topbar'] != 'y') {
                $config['toolbar']['topbar'] = false;
            }
        }

        if (isset($config['toolbar']['addbar'])) {
            $config['toolbar']['buttons']->addbar = $config['toolbar']['addbar'];
        }
        unset($config['toolbar']['addbar']);
        if (isset($config['toolbar']['toolbar_addbar'])) {
            if ($config['toolbar']['toolbar_addbar'] != 'y') {
                $config['toolbar']['addbar'] = false;
            }
        }

        if (isset($config['toolbar']['context'])) {
            $config['toolbar']['buttons']->context = $config['toolbar']['context'];
        }
        unset($config['toolbar']['context']);
        if (isset($config['toolbar']['toolbar_context'])) {
            if ($config['toolbar']['toolbar_context'] != 'y') {
                $config['toolbar']['context'] = false;
            } else {
                $config['toolbar']['context'] = true;
            }
        }

        if (!isset($config['toolbar']['plugins'])) {
            $config['toolbar']['plugins'] = [];
        }

        if (isset($config['toolbar']['toolbar_control'])) {
            $config['toolbar']['control'] = ($config['toolbar']['toolbar_control'] == 'y');
        }

        if (isset($config['toolbar']['spellcheck'])) {
            switch ($config['toolbar']['spellcheck']) {
                case 'browser':
                    $config['toolbar']['editor']->spellcheck = true;
                    break;
                case 'grammarly':
                    $config['toolbar']['editor']->spellcheck = false;
                    $config['toolbar']['editor']->grammarly = true;
                    break;
                case 'none':
                default:
                    $config['toolbar']['editor']->spellcheck = false;
                    break;
            }
            unset($config['toolbar']['spellcheck']);
        }

        // Structure / Pages linking
        if (!empty(ee()->config->item('site_pages')) && !empty(array_intersect(['rte_definedlinks', 'pages'], $config['toolbar']['plugins']))) {
            $action_id = ee()->db->select('action_id')
                ->where('class', 'Rte')
                ->where('method', 'pages_autocomplete')
                ->get('actions');
            $config['toolbar']['definedlinks'] = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id->row('action_id') . '&structured=y&t=' . ee()->localize->now;
            $config['toolbar']['handle'] = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id->row('action_id') . '&t=' . ee()->localize->now;
        }

        $config['toolbar']['editor']->classname = 'content redactor-styles rte_' . $configHandle;

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
            $config['toolbar']['editor']->minHeight = (int) $config['height'] . 'px';
        }
        if (isset($config['max_height']) && !empty($config['max_height']) && is_numeric($config['max_height'])) {
            $config['toolbar']['editor']->maxHeight = (int) $config['max_height'] . 'px';
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
            'toolbar' => static::defaultToolbars()['RedactorX Basic'],
            'height' => '200',
            'upload_dir' => 'all'
        );
    }

    public function toolbarInputHtml($config, $toolbar = 'buttons')
    {
        ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/fields/rte/' . strtolower(static::$type) . '/redactor.min.css" type="text/css" />');

        $selection = [];
        if (is_object($config->settings['toolbar'])) {
            $selection = (array) $config->settings['toolbar'];
        } else {
            $selection = isset($config->settings['toolbar'][$toolbar]) ? $config->settings['toolbar'][$toolbar] : $config->settings['toolbar'];
        }

        if ($toolbar == 'hide') {
            $allButtons = static::defaultToolbars()['RedactorX Full']['editor'];
        } elseif ($toolbar == 'plugins' || $toolbar == 'format') {
            $allButtons = static::defaultToolbars()['RedactorX Full'][$toolbar];
        } else {
            $allButtons = array_merge(
                static::defaultToolbars()['RedactorX Full']['editor'],
                static::defaultToolbars()['RedactorX Full']['addbar'],
                static::defaultToolbars()['RedactorX Full']['context'],
                static::defaultToolbars()['RedactorX Full']['topbar']
            );
        }
        $allButtons = array_unique($allButtons);
        if ($toolbar == 'addbar') {
            unset($allButtons[array_search('addbar', $allButtons)]);
            unset($allButtons[array_search('link', $allButtons)]);
            unset($allButtons[array_search('paragraph', $allButtons)]);
            unset($allButtons[array_search('shortcut', $allButtons)]);
        }
        if (empty($config->toolset_id)) {
            $selection = ($toolbar != 'hide') ? static::defaultToolbars()['RedactorX Full'][$toolbar] : [];
        }
        $fullToolbar = array_merge($selection, $allButtons);//merge to get the right order
        $fullToolset = [];
        foreach ($fullToolbar as $i => $tool) {
            if (in_array($tool, $allButtons)) {
                $fullToolset[$tool] = lang($tool . '_rte');
            }
        }

        return ee('View')->make('rte:redactorX-toolbar')->render(
            [
                'buttons' => $fullToolset,
                'selection' => $selection,
                'type' => $toolbar,
                'reverse' => ($toolbar == 'hide')
            ]
        );
    }

    public static function defaultToolbars()
    {
        return [
            'RedactorX Basic' => [
                'toolbar_hide' => 'y',
                'toolbar_topbar' => 'n',
                'toolbar_addbar' => 'n',
                'toolbar_context' => 'n',
                'toolbar_control' => 'n',
                'hide' => [],
                'topbar' => [
                    'shortcut',
                    'undo',
                    'redo'
                ],
                'addbar' => [
                    'paragraph',
                    //'image',
                    'embed',
                    'table',
                    'quote',
                    'pre',
                    'line'
                ],
                'context' => [
                    'bold',
                    'italic',
                    'deleted',
                    'code',
                    'link',
                    'mark',
                    'sub',
                    'sup',
                    'kbd'
                ],
                'editor' => [
                    'format',
                    'bold',
                    'italic',
                    'link'
                ],
                'format' =>  [
                    'p',
                    'ul',
                    'ol'
                ],
                'plugins' => [
                    'underline',
                    'filebrowser',
                    'rte_definedlinks',
                    'pages',
                ]
            ],
            'RedactorX Full' => [
                'toolbar_hide' => 'y',
                'toolbar_topbar' => 'y',
                'toolbar_addbar' => 'y',
                'toolbar_context' => 'y',
                'toolbar_control' => 'y',
                'hide' => [],
                'topbar' => [
                    'undo',
                    'redo',
                    'shortcut'
                ],
                'addbar' => [
                    'paragraph',
                    //'image',
                    'embed',
                    'table',
                    'quote',
                    'pre',
                    'line'
                ],
                'context' => [
                    'bold',
                    'italic',
                    'deleted',
                    'code',
                    'link',
                    'mark',
                    'sub',
                    'sup',
                    'kbd'
                ],
                'editor' => [
                    'add',
                    'html',
                    'format',
                    'bold',
                    'italic',
                    'deleted',
                    'link'
                ],
                'format' =>  [
                    'p',
                    'h1',
                    'h2',
                    'h3',
                    'h4',
                    'h5',
                    'h6',
                    'ul',
                    'ol'
                ],
                'plugins' => [
                    'underline',
                    'alignment',
                    'blockcode',
                    'rte_definedlinks',
                    'pages',
                    'filebrowser',
                    'imageposition',
                    'imageresize',
                    'inlineformat',
                    'removeformat',
                    'counter',
                    'selector',
                    'specialchars',
                    'textdirection',
                    'readmore',
                ]
            ]
        ];
    }

}
