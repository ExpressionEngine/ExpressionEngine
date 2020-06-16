<?php

namespace ExpressionEngine\Addons\Wygwam;

use ExpressionEngine\Addons\FilePicker\FilePicker;

if (file_exists(PATH_THIRD.'assets/helper.php')) {
    include_once(PATH_THIRD .'assets/helper.php');
}

/**
 * Wygwam Helper Class
 *
 * @package   Wygwam
 * @author    EEHarbor <help@eeharbor.com>
 * @copyright Copyright (c) Copyright (c) 2016 EEHarbor
 */

class Helper
{
    public static $entrySiteId;

    private static $_globalSettings;
    private static $_themeUrl;
    private static $_includedFieldResources = false;
    private static $_includedConfigs;
    private static $_fileTags;
    private static $_pageTags;
    private static $_sitePages;
    private static $_pageData;

    private static $_toolbarButtonGroups;
    private static $_toolbarButtonCombos;
    private static $_toolbarButtonLabelOverrides;


    /**
     * Return Info model.
     *
     * @return \EllisLab\ExpressionEngine\Core\Provider $providerObject
     */
    public static function getInfo()
    {
        return ee('App')->get('wygwam');
    }

    /*
     * Return a generated URL to a Wygwam action
     *
     * @param string $action
     * @param array  $params
     *
     * @return string $url
     */
    public static function getMcpUrl($action = 'index', $params = array())
    {
        return ee('CP/URL')->make('addons/settings/wygwam/'.$action, $params)->compile();
    }

    /**
     * Gets Wygwam's global settings.
     *
     * @return array $globalSettings
     */
    public static function getGlobalSettings()
    {
        if (! isset(static::$_globalSettings)) {
            $model = static::getFieldtypeModel();

            if (!$model) {
                return array();
            }

            $defaults = array(
                'license_key' => '',
                'file_browser' => 'ee'
            );

            static::$_globalSettings = array_merge($defaults, $model->settings);
        }

        return static::$_globalSettings;
    }

    /**
     * Sets Wygwam's global settings.
     *
     * @param array $globalSettings
     */
    public static function setGlobalSettings($globalSettings)
    {
        static::$_globalSettings = $globalSettings;
    }

    /**
     * Get the Wygwam fieldtype model.
     *
     * @return \EllisLab\ExpressionEngine\Model\Addon\Fieldtype|null $fieldType
     */
    public static function getFieldtypeModel()
    {
        return ee('Model')->get('Fieldtype')->filter('name', '==', 'wygwam')->first();
    }

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
            //  'wygwam_tb_groups' hook
            //   - Allow extensions to modify the available toolbar groups
            //
            if (ee()->extensions->active_hook('wygwam_tb_groups')) {
                static::$_toolbarButtonGroups = ee()->extensions->call('wygwam_tb_groups', static::$_toolbarButtonGroups);
            }
            //
            // -------------------------------------------
        }

        return static::$_toolbarButtonGroups;
    }

    // --------------------------------------------------------------------

    /**
     * Returns which toolbar items are combos.
     *
     * @return array $combos
     */
    public static function toolbarButtonCombos()
    {
        if (!isset(static::$_toolbarButtonCombos)) {
            static::$_toolbarButtonCombos = array('Styles', 'Format');

            // -------------------------------------------
            //  'wygwam_tb_combos' hook
            //   - Allow extensions to modify which toolbar items should be considered selects.
            //
            if (ee()->extensions->active_hook('wygwam_tb_combos')) {
                static::$_toolbarButtonCombos = ee()->extensions->call('wygwam_tb_combos', static::$_toolbarButtonCombos);
            }
            //
            // -------------------------------------------
        }

        return static::$_toolbarButtonCombos;
    }

    /**
     * Returns the real toolbar button names.
     *
     * @return array $overrides
     */
    public static function toolbarLabelOverrides()
    {
        if (!isset(static::$_toolbarButtonLabelOverrides)) {
            static::$_toolbarButtonLabelOverrides = array(
                'PasteText'      => 'Paste As Plain Text',
                'PasteFromWord'  => 'Paste from Word',
                'Scayt'          => 'Spell Check As You Type',
                'RemoveFormat'   => 'Remove Format',
                'Strike'         => 'Strike Through',
                'NumberedList'   => 'Insert/Remove Numbered List',
                'BulletedList'   => 'Insert/Remove Bulleted List',
                'Outdent'        => 'Decrease Indent',
                'Indent'         => 'Increase Indent',
                'CreateDiv'      => 'Create Div Container',
                'HorizontalRule' => 'Insert Horizontal Line',
                'About'          => 'About CKEditor',
                'MediaEmbed'     => 'Embed Media',
                'ReadMore'       => 'Read More',
                'ShowBlocks'     => 'Show Blocks',
            );

            // -------------------------------------------
            //  'wygwam_tb_label_overrides' hook
            //   - Allow extensions to modify which toolbar items should be considered selects.
            //
            if (ee()->extensions->active_hook('wygwam_tb_label_overrides')) {
                static::$_toolbarButtonLabelOverrides = ee()->extensions->call('wygwam_tb_label_overrides', static::$_toolbarButtonLabelOverrides);
            }
            //
            // -------------------------------------------
        }

        return static::$_toolbarButtonLabelOverrides;
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
            'resize_enabled' => 'y',
            'contentsCss'    => array(),
            'parse_css'      => false,
            'restrict_html'  => 'y',
            'upload_dir'     => ''
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
            'Basic' => array('Bold','Italic','Underline','NumberedList','BulletedList','Link','Unlink','Anchor'),
            'Full'  => array('Source','Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord','Undo', 'Redo','Scayt','Bold', 'Italic', 'Strike','RemoveFormat','NumberedList', 'BulletedList','Outdent', 'Indent','Blockquote','Link', 'Unlink', 'Anchor','Image', 'Table', 'HorizontalRule', 'SpecialChar','ReadMore','Styles','Format','Maximize')
        );
    }

    /**
     * Returns the default Wygwam field settings.
     *
     * @return array $settings
     */
    public static function defaultSettings()
    {
        return array(
            'config_id' => '',
            'defer'     => 'n'
        );
    }

    /**
     * Returns the base CKEditor config.
     *
     * @return array $config
     */
    public static function baseConfig()
    {
        return array_merge(array(
            'skin'                          => 'wygwam',
            'toolbarCanCollapse'            => 'n',
            'dialog_backgroundCoverOpacity' => 0,
            'entities_processNumerical'     => 'y',
            'forcePasteAsPlainText'         => 'y'
        ), static::defaultConfigSettings());
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

        //remap old (CKEditor 4) buttons
        foreach ($buttons as $i=>$button) {
            $button = self::_convertButton($button);
            if (empty($button)) {
                unset($buttons[$i]);
            } else {
                $buttons[$i] = $button;
            }
        }

        // group buttons by toolgroup
        $toolbarButtonGroups = static::toolbarButtonGroups();

        foreach ($toolbarButtonGroups as $groupIndex => &$group) {
            $groupSelectionIndex = null;
            $missing = array();
            foreach ($group as $buttonIndex => &$button) {
                $button = self::_convertButton($button);
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
     * Returns the boolean config settings.
     *
     * @return array $list
     */
    public static function configBooleans()
    {
        return array(
            'autoGrow_onStartup',
            'autoParagraph',
            'colorButton_enableMore',
            'dialog_noConfirmCancel',
            'disableNativeSpellChecker',
            'disableObjectResizing',
            'disableReadonlyStyling',
            'editingBlock',
            'entities',
            'entities_greek',
            'entities_latin',
            'entities_processNumerical',
            'fillEmptyBlocks',
            'forceEnterMode',
            'forcePasteAsPlainText',
            'forceSimpleAmpersand',
            'fullPage',
            'htmlEncodeOutput',
            'ignoreEmptyParagraph',
            'image_removeLinkByEmptyURL',
            'pasteFromWordNumberedHeadingToList',
            'pasteFromWordPromptCleanup',
            'pasteFromWordRemoveFontStyles',
            'pasteFromWordRemoveStyles',
            'readOnly',
            'resize_enabled',
            'scayt_autoStartup',
            'startupFocus',
            'startupOutlineBlocks',
            'templates_replaceContent',
            'toolbarCanCollapse',
            'toolbarGroupCycling',
            'toolbarStartupExpanded'
        );
    }

    /**
     * Returns the config settings that are lists.
     *
     * @return array $list
     */
    public static function configLists()
    {
        return array(
            'contentsCss',
            'templates_files'
        );
    }

    /**
     * Returns the config settings that are literals.
     *
     * @return array $list
     */
    public static function configLiterals()
    {
        return array(
            'enterMode',
            'on',
            'stylesheetParser_skipSelectors',
            'stylesheetParser_validSelectors',
            'filebrowserBrowseFunc',
            'filebrowserLinkBrowseFunc',
            'filebrowserImageBrowseFunc',
            'filebrowserFlashBrowseFunc',
        );
    }

    // --------------------------------------------------------------------

    /**
     * Returns Wygwam's themes folder URL.
     *
     * @return string $url
     */
    public static function themeUrl()
    {
        if (! isset(static::$_themeUrl)) {
            static::$_themeUrl = URL_THEMES . 'wygwam/';
        }
        return static::$_themeUrl;
    }

    /**
     * Includes a CSS file in the page head.
     *
     * @param string $file
     */
    public static function includeThemeCss($file)
    {
        ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.static::themeUrl().$file.'" />');
    }

    /**
     * Includes a JS file in the page foot.
     *
     * @param string $file
     */
    public static function includeThemeJs($file)
    {
        ee()->cp->add_to_foot('<script type="text/javascript" src="'.static::themeUrl().$file.'"></script>');
    }

    /**
     * Insert CSS in the page head.
     *
     * @param string $css
     */
    public static function insertCss($css)
    {
        ee()->cp->add_to_head('<style type="text/css">'.$css.'</style>');
    }

    /**
     * Insert JS in the page foot.
     *
     * @param string $js
     */
    public static function insertJs($js)
    {
        ee()->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
    }

    /**
     * Includes the necessary CSS and JS files to get Wygwam fields working.
     */
    public static function includeFieldResources()
    {
        if (! static::$_includedFieldResources) {
            static::includeThemeJs('scripts/ckeditor.js?v=202006151430');
            //static::includeThemeJs('lib/ckeditor/config.js');
            static::includeThemeJs('scripts/wygwam.js');
            static::includeThemeCss('styles/wygwam.css');
            static::insertCss('.content_elements_icon_wygwam { background: url('.static::themeUrl().'images/ce_icon.png); background-size: 16px; }');

            $action_id = ee()->db->select('action_id')
				->where('class', 'Wygwam')
				->where('method', 'pages_autocomplete')
				->get('actions');

            $js = 'Wygwam.themeUrl = "'.static::themeUrl().'";'
                . 'Wygwam.pages_autocomplete = "' . ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id->row('action_id') . '&t=' . ee()->localize->now . '";'
                . 'Wygwam.ee2plus = '.(version_compare(APP_VER, '2.2', '>=') ? 'true' : 'false').';';

            $filedirs = static::getUploadDestinations(1);

            if ($filedirs) {
                $filedir_urls = array();
                foreach ($filedirs as $filedir) {
                    $filedir_urls[$filedir['id']] = $filedir['url'];
                }

                $js .= 'Wygwam.filedirUrls = '.json_encode($filedir_urls).';';
            }

            static::insertJs($js);

            static::$_includedFieldResources = true;
        }
    }

    /**
     * Inserts the Wygwam config JS in the page foot by config ID.
     *
     * @param $configId
     *
     * @return $configHandle The handle for config used by Wygwam JS
     */
    public static function insertConfigJsById($configId)
    {
        $globalSettings = static::getGlobalSettings();

        // starting point
        $baseConfig = static::baseConfig();

        // -------------------------------------------
        //  Editor Config
        // -------------------------------------------


        if (ee()->db->table_exists('wygwam_configs')
            && is_numeric($configId)
            && $config = ee('Model')->get('wygwam:Config')->filter('config_id', '==', $configId)->first()
        ) {
            /**
             * @var $config \EEHarbor\Wygwam\Model\Config
             */
            // merge custom settings into config
            $customSettings = $config->settings;
            $configHandle = preg_replace('/[^a-z0-9]/i', '_', $config->config_name).$configId;
            $config = array_merge($baseConfig, $customSettings);
        } else {
            $customSettings = array();
            $config = $baseConfig;
            $configHandle = 'default0';
        }

        // skip if already included
        if (isset(static::$_includedConfigs) && in_array($configHandle, static::$_includedConfigs)) {
            return $configHandle;
        }

        // language
        if (! isset($config['language']) || ! $config['language']) {
            $langMap = static::languageMap();
            $language = ee()->session->userdata('language');
            $config['language'] = isset($langMap[$language]) ? $langMap[$language] : 'en';
        }

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

        if (static::_isPagesModuleInstalled()) {
            $config['mention'] = new \stdClass();
            $pagesFeed = new \stdClass();
            $pagesFeed->marker = '@';
            $pagesFeed->feed = 'getPages()';
            $pagesFeed->itemRenderer = 'formatPageLinks()';
            $pagesFeed->minimumCharacters = 3;
            $config['mention']->feeds = [
                $pagesFeed
            ];
        }

        // css
        if (! $config['contentsCss']) {
            unset($config['contentsCss']);
        }

        // set the autoGrow_minHeight to the height
        $config['autoGrow_minHeight'] = $config['height'];

        // allowedContent
        if ($config['restrict_html'] == 'n') {
            $config['allowedContent'] = true;
        }

        unset($config['restrict_html']);

        // extraPlugins
        if (!empty($config['extraPlugins'])) {
            $extraPlugins = array_map('trim', explode(',', $config['extraPlugins']));
        } else {
            $extraPlugins = array();
        }

        //$extraPlugins[] = 'autosave';
        //$extraPlugins[] = 'wygwam';
        //$extraPlugins[] = 'readmore';

        if ($config['parse_css'] === 'y') {
            if (!in_array('stylesheetparser', $extraPlugins)) {
                $extraPlugins[] = 'stylesheetparser';
            }

            unset($config['parse_css']);
        }

        //$config['plugins'] = implode(',', $extraPlugins);

        // -------------------------------------------
        //  File Browser Config
        // -------------------------------------------

        $userGroup = ee()->session->userdata('group_id');
        $uploadDir = isset($config['upload_dir']) ? $config['upload_dir'] : null;
        $uploadDestination = static::getUploadDestinations($userGroup, $uploadDir);

        $config['fileBrowser'] = isset($globalSettings['file_browser']) ? $globalSettings['file_browser'] : 'ee';
        static::insertJs(NL."\t"."Wygwam.fileBrowser = '" . $config['fileBrowser'] ."';".NL);

        switch ($config['fileBrowser']) {
            case 'assets':

                // make sure Assets is actually installed
                // (otherwise, just use the EE File Manager)
                if (static::isAssetsInstalled()) {
                    // include sheet resources
                    \Assets_helper::include_sheet_resources();

                    // if no upload directory was set, just default to "all"
                    if (! $uploadDir) {
                        $uploadDir = '"all"';
                    }

                    // If this has a source type passed in as well, wrap it in quotes.
                    if (strpos($uploadDir, ":")) {
                        $uploadDir = '"'.$uploadDir.'"';
                    }

                    static::insertJs(NL."\t"."function Wygwam_browseImages(params) { Wygwam.loadAssetsSheet(params, ".$uploadDir.", 'image'); }".NL);

                    $config['filebrowserBrowseFunc']      = 'function(params) { Wygwam.loadAssetsSheet(params, '.$uploadDir.', "any"); }';
                    $config['filebrowserImageBrowseFunc'] = 'function(params) { Wygwam.loadAssetsSheet(params, '.$uploadDir.', "image"); }';
                    $config['filebrowserFlashBrowseFunc'] = 'function(params) { Wygwam.loadAssetsSheet(params, '.$uploadDir.', "flash"); }';

                    break;
                }

                // no break
            default:
                if (! $uploadDestination) {
                    break;
                }

                // load the file browser
                // pass in the uploadDir to limit the directory to the one choosen
                $fp = new FilePicker();
				$fp->inject(ee()->view);
                static::insertJs(NL."\t"."Wygwam.fpUrl = '" . ee('CP/FilePicker')->make($uploadDir)->getUrl() ."';".NL);

                // if no upload directory was set, just default to "all"
                if (! $uploadDir) {
                    $uploadDir = '"all"';
                }

                static::insertJs(NL."\t"."function Wygwam_browseImages(params) { Wygwam.loadEEFileBrowser(params, ".$uploadDir.", 'image'); }".NL);

                $config['filebrowserBrowseFunc']      = 'function(params) { Wygwam.loadEEFileBrowser(params, '.$uploadDir.', "any"); }';
                $config['filebrowserImageBrowseFunc'] = 'function(params) { Wygwam.loadEEFileBrowser(params, '.$uploadDir.', "image"); }';
        }

        // add any site page data to wygwam config
        if ($pages = static::getAllPageData()) {
            ee()->lang->loadfile('wygwam');
            $sitePageString = lang('wygwam_site_page');

            foreach ($pages as $page) {
                $config['link_types'][$sitePageString][] = array(
                    'label' => $page[2],
                    'url'   => $page[4]
                );
            }
        }

        // -------------------------------------------
        //  'wygwam_config' hook
        //   - Override any of the config settings
        //
        if (ee()->extensions->active_hook('wygwam_config')) {
            $config = ee()->extensions->call('wygwam_config', $config, $customSettings);
        }
        //
        // -------------------------------------------

        unset($config['upload_dir']);

        // -------------------------------------------
        //  JSONify Config and Return
        // -------------------------------------------

        $configLiterals = static::configLiterals();
        $configBooleans = static::configBooleans();

        $js = '';

        foreach ($config as $setting => $value) {
            if (! in_array($setting, $configLiterals)) {
                if (in_array($setting, $configBooleans)) {
                    $value = ($value == 'y' ? true : false);
                }

                $value = json_encode($value);
                $value = preg_replace_callback('/\"([a-zA-Z_{1}][a-zA-Z0-9_]+)\(\)\"/i', function ($match) {
                    return str_replace('"', '', $match[1]);
                }, $value);

                // Firefox gets an "Unterminated string literal" error if this line gets too long,
                // so let's put each new value on its own line
                if ($setting == 'link_types') {
                    $value = str_replace('","', "\",\n\t\t\t\"", $value);
                }
            }

            $js .= ($js ? ','.NL : '')
                . "\t\t".'"'.$setting.'": '.$value;
        }

        // Strip out any non-space whitespace chars
        $js = str_replace(array(chr(10), chr(11), chr(12), chr(13)), ' ', $js);

        static::insertJs(NL."\t".'Wygwam.configs["'.$configHandle.'"] = {'.NL.$js.NL."\t".'};'.NL);
        static::$_includedConfigs[] = $configHandle;

        static::insertCss('.ck-editor__editable_inline { min-height: ' . $config['height'] . 'px; }');

        return $configHandle;
    }

    // --------------------------------------------------------------------

    /**
     * Gets the system upload preferences
     *
     * @param  int $groupId Member group ID specified when returning allowed upload directories only for that member group
     * @param  int $id      Specific ID of upload destination to return
     *
     * @return array $destinations Result array of DB object, merged with custom file upload settings (if on EE 2.4+)
     */
    public static function getUploadDestinations($groupId = null, $id = null)
    {
        ee()->load->model('file_upload_preferences_model');
        return ee()->file_upload_preferences_model->get_file_upload_preferences($groupId, $id);
    }

    // --------------------------------------------------------------------

    /**
     * Gets all the possible {filedir_X} tags and their replacement URLs.
     *
     * @param bool $sort
     *
     * @return array $list
     */
    private static function _getFileTags($sort = false)
    {
        if (! isset(static::$_fileTags)) {
            $tags = array();
            $urls = array();

            ee()->load->model('file_upload_preferences_model');

            if ($filePaths = ee()->file_upload_preferences_model->get_paths()) {
                if ($sort) {
                    uasort($filePaths, function ($a, $b) {
                        return -(strcmp(strlen($a), strlen($b)));
                    });
                }

                foreach ($filePaths as $id => $url) {
                    // ignore "/" URLs
                    if ($url == '/') {
                        continue;
                    }

                    $tags[] = LD.'filedir_'.$id.RD;
                    $urls[] = $url;
                }
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
     * Replaces Asset URLs with {assets_X} tags.
     *
     * @param $data
     * @param $assetIds
     * @param $assetUrls
     */
    public static function replaceAssetUrls(&$data, $assetIds, $assetUrls)
    {
        foreach ($assetUrls as $key => $assetUrl) {
            $replace = '{assets_'.$assetIds[$key].':'.$assetUrl.'}';
            $search = str_replace('/', '\/', preg_quote(rtrim($assetUrl, '/')));
            $search = '/(?!\")('.$search.')\/?(?=\")/uU';

            $data = preg_replace($search, $replace, $data);
        }
    }

    /**
     * Replaces Asset URLs with {assets_X} tags.
     *
     * @param $data
     *
     * @return array $list
     */
    public static function replaceAssetTags(&$data)
    {
        preg_match_all("/\\{assets_(\\d*):((.*)(\\}))/uU", $data, $matches);

        if ($matches && !empty($matches[0])) {
            $assetIds = $matches[1];
            $assetUrls = $matches[3];

            if (static::isAssetsInstalled()) {
                $EE = ee();
                $EE->load->add_package_path(PATH_THIRD.'assets/');
                $EE->load->library('assets_lib');
                $files = $EE->assets_lib->get_file_by_id($assetIds);
            }

            for ($counter = 0; $counter < count($matches[1]); $counter++) {
                $fileId = $matches[1][$counter];

                // The file has been deleted or Assets is not installed.
                if ((isset($files[$fileId]) && $files[$fileId] === false) || !isset($files[$fileId])) {
                    $replace = $matches[3][$counter];
                } else {
                    $replace = $files[$fileId]->url();
                }

                $data = str_replace('{assets_'.$fileId.':'.$matches[3][$counter].'}', $replace, $data);
            }

            return array('ids' => $assetIds, 'urls' => $assetUrls);
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
    private static function _getPageTags($sort = false)
    {
        if (! isset(static::$_pageTags)) {
            $tags = array();
            $urls = array();

            $pageData = static::getAllPageData(false);

            if ($sort) {
                usort($pageData, function ($a, $b) {
                    return -(strcmp(strlen($a[4]), strlen($b[4])));
                });
            }

            foreach ($pageData as $page) {
                $tags[] = LD.'page_'.$page[0].RD;
                $urls[] = $page[4];
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
        $tags = static::_getPageTags(true);

        foreach ($tags[1] as $key => $pageUrl) {
            $pageUrl = str_replace('/', '\/', preg_quote(rtrim($pageUrl, '/')));
            $search = '/(?!\")('.$pageUrl.')\/?(?=\")/uU';
            $data = preg_replace($search, $tags[0][$key], $data);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Returns whether the Pages module is installed.
     *
     * @return bool $isInstalled
     */
    private static function _isPagesModuleInstalled()
    {
        return !empty(ee()->config->item('site_pages'));
    }

    // --------------------------------------------------------------------

    /**
     * Gets the current site's pages.
     *
     * @return array $sitePages
     */
    private static function _getSitePages()
    {
        $siteId = ee()->config->item('site_id');

        // Is this entry from a different site?
        $differentSite = (static::$entrySiteId && $siteId != static::$entrySiteId);
        $entrySiteId = $differentSite ? static::$entrySiteId : $siteId;

        if (! isset(static::$_sitePages[$entrySiteId])) {
            // Temporarily swap the site config over to the entry's site
            if ($differentSite) {
                ee()->config->site_prefs('', $entrySiteId);
            }

            $pages = ee()->config->item('site_pages');

            if (is_array($pages) && !empty($pages[$entrySiteId]['uris'])) {
                // grab a copy of this site's pages
                $site_pages = array_merge($pages[$entrySiteId]);

                // sort by uris
                natcasesort($site_pages['uris']);

                static::$_sitePages[$entrySiteId] = $site_pages;
            } else {
                static::$_sitePages[$entrySiteId] = array();
            }

            // Return the config to the actual site
            if ($differentSite) {
                ee()->config->site_prefs('', $siteId);
            }
        }

        return static::$_sitePages[$entrySiteId];
    }

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

            // sort by entry title
            if (count(static::$_pageData) > 0) {
                static::$_pageData = static::_subvalSort(static::$_pageData, 2);
            }
        }

        return static::$_pageData;
    }

    /**
     * Sorts a multidimensional array on an internal array's key.
     *
     * @param array $initialArray
     * @param string $subKey
     *
     * @return array $sortedArray
     */
    private static function _subvalSort($initialArray, $subKey)
    {
        $sortedArray = array();

        foreach ($initialArray as $key => $value) {
            $tempArray[$key] = strtolower($value[$subKey]);
        }

        asort($tempArray);

        foreach ($tempArray as $key => $value) {
            $sortedArray[] = $initialArray[$key];
        }

        return $sortedArray;
    }

    // --------------------------------------------------------------------

    /**
     * Gets all site page data from the pages module.
     *
     * @param bool $installCheck
     *
     * @return array $pageData
     */
    public static function getAllPageData($installCheck = true)
    {
        $pageData = array();

        if ($installCheck) {
            if (static::_isPagesModuleInstalled()) {
                $pageData = static::_getPagesModuleData();
            }
        } else {
            $pageData = static::_getPagesModuleData();
        }

        return $pageData;
    }

    /**
     * Returns whether Assets is installed or not.
     *
     * @return bool $isInstalled
     */
    public static function isAssetsInstalled()
    {
        return array_key_exists('assets', ee('Addon')->installed());
    }

    /**
     * Returns whether Structure is installed or not.
     *
     * @return bool $isInstalled
     */
    public static function isStructureInstalled()
    {
        return array_key_exists('structure', ee('Addon')->installed());
    }

    private static function _convertButton($button) {
        switch ($button) {
            case 'Source':
            case 'Cut':
            case 'Copy':
            case 'Paste':
            case 'PasteText':
            case 'PasteFromWord':
            case 'Unlink':
            case 'Anchor':
            case 'Maximize': //will bring this back once available
            case 'Scayt': //this is now third-party paid solution: webspellchecker.com
            case 'ShowBlocks':
            case 'Templates':
            case 'CreateDiv':
            case 'Styles':
                $button = null;
                break;
            case 'Strike':
                $button = 'strikethrough';
                break;
            case 'Table':
                $button = 'insertTable';
                break;
            case 'HorizontalRule':
                $button = 'horizontalLine';
                break;
            case 'SpecialChar':
                $button = 'specialCharacters';
                break;
            case 'Format':
                $button = 'heading';
                break;
            case 'JustifyLeft':
                $button = 'alignment:left';
                break;
            case 'JustifyRight':
                $button = 'alignment:right';
                break;
            case 'JustifyCenter':
                $button = 'alignment:center';
                break;
            case 'JustifyBlock':
                $button = 'alignment:justify';
                break;
            case 'TextColor':
                $button = 'fontColor';
                break;
            case 'BGColor':
                $button = 'fontBackgroundColor';
                break;
            case 'EmbedMedia':
                $button = 'mediaEmbed';
                break;
            case 'Image':
                $button = 'filemanager';
                break;
            default:
                $button = lcfirst($button);
                break;
        }
        return $button;
    }
}
