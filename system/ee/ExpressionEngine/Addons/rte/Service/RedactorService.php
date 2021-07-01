<?php

namespace ExpressionEngine\Addons\Rte\Service;

use ExpressionEngine\Addons\Rte\RteHelper;
use ExpressionEngine\Library\Rte\RteFilebrowserInterface;

class RedactorService {

	public $output;
	public $class = 'rte-textarea redactor-box';
	public $handle;
	protected $settings;
	protected $toolset;
	private static $_includedFieldResources = false;
	private static $_includedConfigs;
	private $_fileTags;
	private $_pageTags;
	private $_extraTags;
	private $_sitePages;
	private $_pageData;

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

			// Styles
			ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THEMES . 'rte/redactor/redactor.css" type="text/css" media="print, projection, screen" />');
			ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THEMES . 'rte/styles/redactor/addon_pbf.css" type="text/css" media="print, projection, screen" />');

			ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THEMES . 'rte/scripts/redactor/rte.js"></script>');
			ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THEMES . 'rte/scripts/redactor/redactor/redactor.min.js"></script>');

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

	public function getClass()
	{
		return $this->class;
	}

	protected function insertConfigJsById()
	{
		ee()->lang->loadfile('rte');

		// starting point
		$baseConfig = RteHelper::defaultRedactorToolbars();

		if (!$this->toolset && !empty(ee()->config->item('rte_default_toolset'))) {
			$configId = ee()->config->item('rte_default_toolset');
			$toolsetQuery = ee('Model')->get('rte:Toolset');
			$toolsetQuery->filter('toolset_type', 'redactor');
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
		$langMap = RteHelper::languageMap();
		$config['lang'] = isset($langMap[$language]) ? $langMap[$language] : 'en';

		if (isset($config['lang']) && $config['lang'] != 'en') {
			$langScript = $config['lang'] . '.js';
			ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THEMES . 'rte/redactor/languages/' . $langScript . '"></script>');
		}

		// toolbar
		if (is_array($config['buttons'])) {
			$toolbarObject = new \stdClass();
			$toolbarObject->items = $config['buttons'];
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

		$allPlugins = $this->getPluginList();

		if (isset($config['plugins'])) {
		    foreach ($config['plugins'] as $plugin) {
		        if (!isset($allPlugins[$plugin])) {
		            continue;
		        }

		        if (
		        	!isset($allPlugins[$plugin]['included'])
		        	|| $allPlugins[$plugin]['included'] == true
		        ) {
		        	ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THEMES . 'rte/redactor/plugins/' . $plugin . '.js"></script>');
		        }
		    }
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
		$config['link'] = (object) [
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

		// -------------------------------------------
		//  JSONify Config and Return
		// -------------------------------------------
		ee()->javascript->set_global([
			'Rte.configs.' . $configHandle => json_encode($config),
		]);

		static::$_includedConfigs[] = $configHandle;

		return $configHandle;
	}

	private function parseConfig($settings)
	{
		$output = [];

		if (isset($settings['config']) && $settings['config'] > 0) {
		    $config = ee('Model')->get('editor:Config', $settings['config'])->first();

		    if (!$config) {
		        return $output;
		    }

		    $config->settings = array_merge(ee('App')->get('editor')->get('redactor_default'), $config->settings);

		    if (isset($config->settings['config']) && !empty($config->settings['config'])) {
		        $output = $config->settings['config'];
		    }

		    $output['buttons'] = $config->settings['buttons'];
		    $output['plugins'] = $config->settings['plugins'];
		} else {
		    return $output;
		}

		foreach ($this->getDefaultAdvancedSettings() as $key => $adv) {
		    if (isset($output[$key]) === false) {
		        unset($output[$key]);

		        continue;
		    }

		    if ($adv['type'] == 'text-array') {
		        $output[$key] = array_map('trim', explode(',', $output[$key]));
		    }

		    if ($adv['type'] == 'bool') {
		        $output[$key] = ($output[$key] == 'yes') ? true : false;
		    }

		    if ($adv['type'] == 'number-bool') {
		        $output[$key] = ($output[$key] > 0) ? $output[$key] : false;
		    }
		}

		if ($config->settings['upload_service'] == 'local') {
		    if ($config->settings['files_upload_location'] > 0) {
		        $uploadUrl  = ee('editor:Helper')->getRouterUrl('url', 'actionFileUpload');
		        $uploadUrl .= '&action=file&upload_location=' . $config->settings['files_upload_location'];
		        $output['fileUpload'] = $uploadUrl;

		        if ($config->settings['files_browse'] == 'yes') {
		            $browse  = ee('editor:Helper')->getRouterUrl('url', 'actionGeneralRouter');
		            $browse .= '&method=browseFiles&upload_location=' . $config->settings['files_upload_location'];

		            $output['fileManagerJson'] = $browse;
		            $output['plugins'][] = 'filemanager';
		        }
		    }

		    if ($config->settings['images_upload_location'] > 0) {
		        $uploadUrl  = ee('editor:Helper')->getRouterUrl('url', 'actionFileUpload');
		        $uploadUrl .= '&action=image&upload_location=' . $config->settings['images_upload_location'];
		        $output['imageUpload'] = $uploadUrl;

		        if ($config->settings['images_browse'] == 'yes') {
		            $browse  = ee('editor:Helper')->getRouterUrl('url', 'actionGeneralRouter');
		            $browse .= '&method=browseImages&upload_location=' . $config->settings['images_upload_location'];

		            $output['imageManagerJson'] = $browse;
		            $output['plugins'][] = 'imagemanager';
		        }
		    }
		} elseif ($config->settings['upload_service'] == 's3') {
		    $uploadUrl  = ee('editor:Helper')->getRouterUrl('url', 'actionFileUpload');
		    $string = base64_encode(ee('editor:Helper')->encryptString(json_encode($config->settings['s3'])));
		    $output['s3'] = "{$uploadUrl}&action=s3_info&s3={$string}";
		    $output['fileUpload'] = true;
		    $output['imageUpload'] = true;
		}

		return $output;
	}

	public function defaultConfigSettings()
	{
		return RteHelper::defaultRedactorToolbars();
	}

	public function toolbarInputHtml($config)
	{
			ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THEMES . 'rte/redactor/redactor.css" type="text/css" media="print, projection, screen" />');

			ee()->cp->add_js_script([
			    'file' => ['cp/form_group'],
			]);

			// $config->settings = null;
			// $config->save();

			if(!isset($config->settings['toolbar']['buttons'])) {
				$settings = $config->settings;
				$settings['toolbar'] = RteHelper::defaultRedactorToolbars();
				$config->settings = $settings;
				$config->save();
			}

			$fullToolset = [
				'buttons' => [],
				'plugins' => [],
				'advanced_settings' => [],
			];

			$buttons = $this->getButtons();
			$allPlugins = $this->getPluginList();

			foreach ($buttons as $key => $button) {
				$button['label'] = lang($key . '_rte');
				$fullToolset['buttons'][$key] = $button;
			}

			foreach ($allPlugins as $key => $plugin) {
				$plugin['label'] = lang($key . '_rte');
				$fullToolset['plugins'][$key] = $plugin;
			}

			$allAdvancedSettings = RteHelper::defaultReactorAdvancedSettings();

			foreach ($allAdvancedSettings as $key => $setting) {
				$setting['label'] = lang('redactor_advanced_' . $key);
				$setting['desc'] = lang('redactor_advanced_' . $key . '_desc');
				$fullToolset['advanced_settings'][] = $setting;
			}

			return ee('View')->make('rte:redactor-toolbar')->render(
				[
					'buttons' => $fullToolset['buttons'],
					'settings' => $config->settings,
					'advanced_settings' => $fullToolset['advanced_settings'],
					'plugins' => $fullToolset['plugins'],
				]
			);
	}

	protected function getDefaultAdvancedSettings()
	{
        $settings = [];
        $settings['air'] = [
            'type' => 'bool',
            'value' => 'no',
        ];
        
        $settings['airWidth'] = [
            'type' => 'number',
            'value' => '',
        ];
        
        $settings['buttonsHide'] = [
            'type' => 'text-array',
            'value' => '',
        ];
        
        $settings['buttonsHideOnMobile'] = [
            'type' => 'text-array',
            'value' => '',
        ];
        
        $settings['focus'] = [
            'type' => 'bool',
            'value' => 'no',
        ];
        
        $settings['focusEnd'] = [
            'type' => 'bool',
            'value' => 'no',
        ];
        
        $settings['formatting'] = [
            'type' => 'text-array',
            'value' => 'p,blockquote,pre,h1,h2,h3,h4,h5,h6',
        ];

        $settings['minHeight'] = [
            'type' => 'number',
            'value' => '300px',
        ];
        $settings['maxHeight'] = [
            'type' => 'number',
            'value' => '800px',
        ];

        $settings['direction'] = [
            'type' => 'radio',
            'value' => 'ltr',
            'options' => [
                'ltr' => 'left-to-right',
                'rtl' => 'right-to-left',
            ],
        ];

        $settings['tabKey'] = [
            'type' => 'bool',
            'value' => 'yes',
        ];

        $settings['tabAsSpaces'] = [
            'type' => 'number-bool',
            'value' => '0',
        ];

        $settings['preSpaces'] = [
            'type' => 'number-bool',
            'value' => '4',
        ];

        $settings['linkNofollow'] = [
            'type' => 'bool',
            'value' => 'no',
        ];

        $settings['linkSize'] = [
            'type' => 'number',
            'value' => '50',
        ];

        $settings['linkTooltip'] = [
            'type' => 'bool',
            'value' => 'yes',
        ];

        $settings['linkify'] = [
            'type' => 'bool',
            'value' => 'yes',
        ];

        $settings['placeholder'] = [
            'type' => 'text',
            'value' => '',
        ];

        $settings['shortcuts'] = [
            'type' => 'bool',
            'value' => 'yes',
        ];

        $settings['script'] = [
            'type' => 'bool',
            'value' => 'yes',
        ];

        $settings['structure'] = [
            'type' => 'bool',
            'value' => 'no',
        ];

        $settings['preClass'] = [
            'type' => 'text',
            'value' => '',
        ];

        $settings['animation'] = [
            'type' => 'bool',
            'value' => 'no',
        ];

        $settings['toolbarFixed'] = [
            'type' => 'bool',
            'value' => 'no',
        ];

        $settings['toolbarFixedTopOffset'] = [
            'type' => 'number',
            'value' => '0',
        ];

        $settings['toolbarFixedTarget'] = [
            'type' => 'text',
            'value' => '',
        ];

        $settings['toolbarOverflow'] = [
            'type' => 'bool',
            'value' => 'no',
        ];

        $settings['lang'] = [
            'type' => 'select',
            'value' => 'en',
            'options' => [
                'ar'    => 'Arabic',
                'de'    => 'German',
                'en'    => 'English',
                'es'    => 'Spanish',
                'fi'    => 'Finnish',
                'fr'    => 'French',
                'ja'    => 'Japanese',
                'ko'    => 'Korean',
                'nl'    => 'Dutch',
                'pl'    => 'Polish',
                'pt_br' => 'Brazilian Portuguese',
                'ru'    => 'Russian',
                'sv'    => 'Swedish',
                'tr'    => 'Turkish',
                'zh_cn' => 'Chinese Simplified',
                'zh_tw' => 'Chinese Traditional',
            ],
        ];

        return $settings;
	}

	protected function getPluginList()
    {
        $plugins = [];
        $plugins['source'] = [
        	'author' => 'Redactor',
        	'desc' => "This plugin allows users to look through and edit text's HTML source code.",
        ];

        $plugins['table'] = [
        	'author' => 'Redactor',
        	'desc' => "Insert and format tables with ease.",
        ];

        $plugins['video'] = [
        	'author' => 'Redactor',
        	'desc' => "Enrich text with embedded video.",
        ];

        $plugins['fullscreen'] = [
        	'author' => 'Redactor',
        	'desc' => "Expand Redactor to fill the whole screen. Also known as 'distraction free' mode.",
        ];

        $plugins['properties'] = [
        	'author' => 'Redactor',
        	'desc' => "This plugin allows you to assign any id or class to any block tag (selected or containing cursor).",
        ];

        $plugins['textdirection'] = [
        	'author' => 'Redactor',
        	'desc' => "Easily change the direction of the text in a block element (paragraph, header, blockquote etc.).",
        ];

        return $plugins;
    }

    protected function getButtons()
    {
    	return [
    	    'format'         => [
    	    	'plugin' => null
    	    ],
    	    'bold'           => [
    	    	'plugin' => null
    	    ],
    	    'italic'         => [
    	    	'plugin' => null
    	    ],
    	    'underline'      => [
    	    	'plugin' => null
    	    ],
    	    'strikethrough' => [
    	    	'plugin' => null
    	    ],
    	    'lists'          => [
    	    	'plugin' => null
    	    ],
    	    'filemanager'    => [
    	    	'plugin' => null
    	    ],
    	    'link'           => [
    	    	'plugin' => null
    	    ],
    	    'horizontalrule' => [
    	    	'plugin' => null
    	    ],
    	];
    }

}
