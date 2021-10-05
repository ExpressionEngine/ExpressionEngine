<?php

namespace ExpressionEngine\Addons\Rte\Service;

use ExpressionEngine\Library\Rte\RteFilebrowserInterface;

class CkeditorService implements RteService
{
    public $class = 'rte-textarea rte-ckeditor';
    public $handle;
    protected $settings;
    protected $toolset;
    private static $_includedFieldResources = false;
    private static $_includedConfigs;

    public function init($settings, $toolset = null)
    {
        $this->settings = $settings;
        $this->toolset = $toolset;
        $this->includeFieldResources();
        $this->insertConfigJsById();
        return $this->handle;
    }

    protected function includeFieldResources()
    {
        if (! static::$_includedFieldResources) {
            //would rather prefer this in combo loader, but that's for CP only
            ee()->cp->add_js_script(['file' => [
                'fields/rte/ckeditor/ckeditor',
                'fields/rte/rte']
            ]);

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

    public function getClass()
    {
        return $this->class;
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

        // language
        $language = isset(ee()->session) ? ee()->session->get_language() : ee()->config->item('deft_lang');
        $config['language'] = ee()->lang->code($language);

        // toolbar
        if (is_array($config['toolbar'])) {
            $toolbarObject = new \stdClass();
            $toolbarObject->items = $config['toolbar'];
            $toolbarObject->viewportTopOffset = 59;
            $config['toolbar'] = $toolbarObject;
            $config['image'] = new \stdClass();
            $config['image']->toolbar = [
                'imageTextAlternative',
                'linkImage',
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

        if (isset($config['height']) && !empty($config['height'])) {
            ee()->cp->add_to_head('<style type="text/css">.ck-editor__editable_inline { min-height: ' . $config['height'] . 'px; }</style>');
        }

        return $configHandle;
    }

    public function toolbarInputHtml($config)
    {
        $selection = isset($config->settings['toolbar']['buttons']) ? $config->settings['toolbar']['buttons'] : $config->settings['toolbar'];
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
                "fontBackgroundColor"
            ],
        ];
    }

}
