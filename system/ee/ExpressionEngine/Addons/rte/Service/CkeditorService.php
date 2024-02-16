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

class CkeditorService extends AbstractRteService implements RteService
{
    public $class = 'rte-textarea';
    public $handle;
    protected $settings;
    protected $toolset;
    private static $_includedFieldResources = false;
    private static $_includedConfigs;

    protected function includeFieldResources()
    {
        if (! static::$_includedFieldResources) {
            ee()->load->library('file_field');
            ee()->lang->loadfile('fieldtypes');
            ee()->file_field->loadDragAndDropAssets();

            if (!empty(ee()->config->item('rte_custom_ckeditor_build')) && ee()->config->item('rte_custom_ckeditor_build') === 'y') {
                ee()->cp->load_package_js('ckeditor');
            } else {
                ee()->cp->add_js_script(['file' => 'fields/rte/ckeditor/ckeditor']);
            }
            ee()->cp->add_js_script(['file' => 'fields/rte/rte']);

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
                ee()->cp->add_js_script(['file' => ['fields/rte/ckeditor/translations/' . $lang_code]]);
            }

            $action_id = ee()->db->select('action_id')
                ->where('class', 'Rte')
                ->where('method', 'pages_autocomplete')
                ->get('actions');
            $filedir_urls = ee('Model')->get('UploadDestination')->all()->getDictionary('id', 'url');
            ee()->javascript->set_global([
                'Rte.pages_autocomplete' => ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id->row('action_id') . '&t=' . ee()->localize->now,
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

        // -------------------------------------------
        //  Editor Config
        // -------------------------------------------

        if (!$this->toolset && !empty(ee()->config->item('rte_default_toolset'))) {
            $configId = ee()->config->item('rte_default_toolset');
            $toolsetQuery = ee('Model')->get('rte:Toolset');
            $toolsetQuery->filter('toolset_type', 'ckeditor');
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
            $configHandle = 'default0';
        }

        $this->handle = $configHandle;

        // skip if already included
        if (isset(static::$_includedConfigs) && in_array($configHandle, static::$_includedConfigs)) {
            return $configHandle;
        }

        // CKEditor does not allow specifying language direction implicitely, so we have to fake it by setting language
        $language = isset(ee()->session) ? ee()->session->get_language() : ee()->config->item('deft_lang');
        $config['language'] = (object) [
            'ui' => ee()->lang->code($language),
            'content' => (isset($config['field_text_direction']) && $config['field_text_direction'] == 'rtl') ? 'ar' : ee()->lang->code($language)
        ];

        // toolbar
        $config = array_merge($config, $this->buildToolbarConfig($config));
        if (REQ == 'CP') {
            $config['toolbar']->viewportOffset = (object) ['top' => 59];
        }

        $config['editorClass'] = 'rte_' . $configHandle;

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

        // EE FilePicker is not available on frontend channel forms
        if (stripos($fqcn, 'filepicker_rtefb') !== false && REQ != 'CP') {
            unset($config['image']);
            $filemanager_key = array_search('filemanager', $config['toolbar']->items);
            if ($filemanager_key !== false) {
                $items = $config['toolbar']->items;
                unset($items[$filemanager_key]);
                $config['toolbar']->items = array_values($items);
            }
        }

        $config['toolbar']->shouldNotGroupWhenFull = true;

        if (isset($config['field_text_direction'])) {
            $config['textDirection'] = $config['field_text_direction'];
            unset($config['field_text_direction']);
        }

        unset($config['rte_config_json']);
        unset($config['rte_advanced_config']);

        // -------------------------------------------
        //  JSONify Config and Return
        // -------------------------------------------

        ee()->javascript->set_global([
            'Rte.configs.' . $configHandle => $config
        ]);

        static::$_includedConfigs[] = $configHandle;

        if (isset($config['height']) && !empty($config['height'])) {
            ee()->cp->add_to_head('<style type="text/css">.rte_' . $configHandle . '.ck-editor__editable_inline { min-height: ' . $config['height'] . 'px; }</style>');
        }

        if (isset($config['css_template']) && !empty($config['css_template'])) {
            $this->includeCustomCSS($configHandle, $config['css_template'], '.ck.ck-editor.rte_' . $configHandle);
        }

        if (isset($config['js_template']) && !empty($config['js_template'])) {
            ee()->cp->add_js_script([
                'template' => $config['js_template']
            ]);
        }

        return $configHandle;
    }

    public function buildToolbarConfig($config)
    {
        $toolbarConfig = [];
        if (is_array($config['toolbar'])) {
            $toolbarObject = new \stdClass();
            $toolbarObject->items = $config['toolbar'];
            $toolbarConfig['toolbar'] = $toolbarObject;
            $toolbarConfig['image'] = new \stdClass();
            $toolbarConfig['image']->toolbar = [
                'imageTextAlternative',
                'toggleImageCaption',
                'linkImage'
            ];
            $imageStyles = new \stdClass();
            $imageStyles->name = 'imageStyle:customDropdown';
            $imageStyles->title = lang('alignment_rte');
            $imageStyles->defaultItem = 'imageStyle:inline';
            $imageStyles->items = [
                'imageStyle:inline',
                'imageStyle:block',
                'imageStyle:side',
                'imageStyle:alignLeft',
                'imageStyle:alignBlockLeft',
                'imageStyle:alignCenter',
                'imageStyle:alignBlockRight',
                'imageStyle:alignRight'
            ];
            $toolbarConfig['image']->toolbar[] = $imageStyles;
            $toolbarConfig['image']->styles = [
                'full',
                'side',
                'alignLeft',
                'alignCenter',
                'alignRight'
            ];
            if (in_array('heading', $toolbarConfig['toolbar']->items)) {
                $toolbarConfig['heading'] = new \stdClass();
                $toolbarConfig['heading']->options = [
                    (object) ['model' => 'paragraph', 'title' => lang('paragraph_rte')],
                    (object) ['model' => 'heading1', 'view' => 'h1', 'title' => lang('heading_h1_rte'), 'class' => 'ck-heading_heading1'],
                    (object) ['model' => 'heading2', 'view' => 'h2', 'title' => lang('heading_h2_rte'), 'class' => 'ck-heading_heading2'],
                    (object) ['model' => 'heading3', 'view' => 'h3', 'title' => lang('heading_h3_rte'), 'class' => 'ck-heading_heading3'],
                    (object) ['model' => 'heading4', 'view' => 'h4', 'title' => lang('heading_h4_rte'), 'class' => 'ck-heading_heading4'],
                    (object) ['model' => 'heading5', 'view' => 'h5', 'title' => lang('heading_h5_rte'), 'class' => 'ck-heading_heading5'],
                    (object) ['model' => 'heading6', 'view' => 'h6', 'title' => lang('heading_h6_rte'), 'class' => 'ck-heading_heading6']
                ];
            }

            $tableContentToolbar = [
                'tableColumn',
                'tableRow',
                'mergeTableCells',
                'tableProperties',
                'tableCellProperties',
                'toggleTableCaption'
            ];
            $toolbarConfig['table'] = new \stdClass();
            $toolbarConfig['table']->contentToolbar = $tableContentToolbar;

            //link
            $toolbarConfig['link'] = (object) [
                'decorators' => [
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
        }

        return $toolbarConfig;
    }

    public function toolbarInputHtml($config)
    {
        $selection = [];
        if (is_object($config->settings['toolbar'])) {
            if (isset($config->settings['toolbar']->items)) {
                $selection = $config->settings['toolbar']->items;
            }
        } else {
            $selection = isset($config->settings['toolbar']['buttons']) && is_array($config->settings['toolbar']['buttons']) ? $config->settings['toolbar']['buttons'] : $config->settings['toolbar'];
        }
        $fullToolbar = array_merge($selection, static::defaultToolbars()['CKEditor Full']);//merge to get the right order
        $fullToolset = [];
        foreach ($fullToolbar as $i => $tool) {
            if (in_array($tool, static::defaultToolbars()['CKEditor Full'])) {
                $fullToolset[$tool] = lang($tool . '_rte');
            }
        }

        return ee('View')->make('rte:toolbar')->render(
            [
                'buttons' => $fullToolset,
                'selection' => $selection
            ]
        );
    }

    /**
     * Returns the default config settings.
     *
     * @return array $configSettings
     */
    public static function defaultConfigSettings()
    {
        $toolbars = static::defaultToolbars();

        return array(
            'type' => 'ckeditor',
            'toolbar' => $toolbars['CKEditor Basic'],
            'height' => '200',
            'upload_dir' => 'all',
            'mediaEmbed' => [
                'previewsInData' => true
            ]
        );
    }

    /**
     * Returns the default toolbars.
     *
     * @return array $toolbars
     */
    public static function defaultToolbars()
    {
        return [
            'CKEditor Basic' => [
                "bold",
                "italic",
                "underline",
                "numberedList",
                "bulletedList",
                "link"
            ],
            'CKEditor Full' => [
                "bold",
                "italic",
                "strikethrough",
                "underline",
                "subscript",
                "superscript",
                "blockquote",
                "code",
                "codeBlock",
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
                "htmlEmbed",
                "alignment:left",
                "alignment:right",
                "alignment:center",
                "alignment:justify",
                "horizontalLine",
                "specialCharacters",
                "readMore",
                "fontColor",
                "fontBackgroundColor",
                "showBlocks",
                "sourceEditing"
            ],
        ];
    }

}
