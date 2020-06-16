<?php

use ExpressionEngine\Addons\Wygwam\Helper;
use ExpressionEngine\Addons\Wygwam\Model\Config;

/**
 * Wygwam Module Control Panel Class
 *
 * @package   Wygwam
 * @author    EEHarbor <help@eeharbor.com>
 * @copyright Copyright (c) Copyright (c) 2016 EEHarbor
 */

class Wygwam_mcp
{
    private $_heading = "";



    /**
     * Config list
     */
    public function index()
    {
        /**
         * @var $configModels EllisLab\ExpressionEngine\Service\Model\Collection
         */
        $configModels = ee('Model')->get('wygwam:Config')->all();
        $configs = array();

        foreach ($configModels as $model) {
            $configs[] = array(
                'id' => $model->config_id,
                'name' => $model->config_name,
                'edit' => Helper::getMcpUrl('editConfig', array('configId' => $model->config_id)),
                'clone' => Helper::getMcpUrl('editConfig', array('configId' => $model->config_id, 'clone' => 'y')),
                'delete' => Helper::getMcpUrl('deleteConfig'),
            );
        }

        $variables = array(
            'configs'   => $configs,
            'newConfig' => Helper::getMcpUrl('editConfig'),
            'pageTitle' => lang('wygwam_configs'),
            'deleteUrl' => Helper::getMcpUrl('deleteConfig')
        );

        $this->_setHeader(lang('wygwam_configs'));
        return $this->_render('config_list', $variables);
    }

    /**
     * Edit Config
     */
    public function editConfig()
    {
        /**
         * @var $request EllisLab\ExpressionEngine\Core\Request;
         */
        $request = ee('Request');

        $defaultConfigSettings = Helper::defaultConfigSettings();

        if (($configId = $request->get('configId'))
            && ($config = ee('Model')->get('wygwam:Config')->filter('config_id', '==', $configId)->first())
        ) {
            /**
             * @var $config Config
             */
            $config->settings = array_merge($defaultConfigSettings, $config->settings);

            // Clone a config?
            if ($request->get('clone') == 'y') {
                $config->config_id = '';
                $config->config_name .= ' '.lang('wygwam_clone');
                $headingTitle = lang('wygwam_create_config');
            } else {
                $headingTitle = lang('wygwam_edit_config').' - '.$config->config_name;
            }
        } else {
            $config = ee('Model')->make('wygwam:Config', array(
                'config_id' => '',
                'config_name' => '',
                'settings' => $defaultConfigSettings
            ));

            $headingTitle = lang('wygwam_create_config');
        }

        $variables['config'] = $config;

        // -------------------------------------------
        //  Upload Directory
        // -------------------------------------------

        $wygwamSettings = Helper::getGlobalSettings();
        $msm = ee('Config')->getFile()->getBoolean('multiple_sites_enabled');

        // If we're using Assets and it is installed, let's show Assets sources instead of just EE filedirs.
        if (isset($wygwamSettings['file_browser']) && $wygwamSettings['file_browser'] == 'assets' && Helper::isAssetsInstalled()) {
            $siteMap = array();

            if ($msm) {
                $sites = ee('Model')->get('Site')->all();

                foreach ($sites as $site) {
                    $siteMap[$site->site_id] = $site->site_label;
                }
            }

            $uploadDirs = array('' => '--');

            // How does one lib?
            ee()->load->add_package_path(PATH_THIRD . 'assets/');
            ee()->load->library('assets_lib');

            $all_sources = array();

            if (method_exists('Assets_helper', 'get_all_sources')) {
                $all_sources = \Assets_helper::get_all_sources();
            } else {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('wygwam_error_compatibility_title'))
                    ->addToBody(lang('wygwam_error_assets_ver'))
                    ->now();
            }

            //$all_sources = ee('assets:Assets')->getAllSources();

            foreach ($all_sources as $source) {
                $uploadDirs[$source->type.':'.$source->id] = ($msm && $source->type == 'ee' ? $siteMap[$source->site_id] . ' - ' : '') . $source->name;
            }
        } else {
            $uploadDirs = array('' => '--');
            $uploadDestinations = ee('Model')
                ->get('UploadDestination')
                ->with('Site')
                ->order('Site.site_label', 'asc')
                ->order('UploadDestination.name', 'asc')
                ->all();

            foreach ($uploadDestinations as $destination) {
                $uploadDirs[$destination->id] = ($msm ? $destination->Site->site_label.' - ' : '') . $destination->name;
            }
        }

        if (!empty($uploadDirs)) {
            $variables['uploadDestinations'] = $uploadDirs;
        }

        // -------------------------------------------
        //  Advanced Settings
        // -------------------------------------------

        // which settings have we already shown?
        $skip = array_keys($defaultConfigSettings);

        // get settings that should be treated as lists
        $configLists = Helper::configLists();

        // sort settings by key
        $settings = $config->settings;
        ksort($settings);

        $js = '';

        foreach ($settings as $setting => $value) {
            // skip?
            if (in_array($setting, $skip)) {
                continue;
            }

            // format_tags?
            if ($setting == 'format_tags') {
                $value = explode(';', $value);
            }

            // list?
            if (in_array($setting, $configLists)) {
                $value = implode("\n", $value);
            }

            $json = json_encode($value);
            $js .= 'new wygwam_addSettingRow("'.$setting.'", '.$json.');' . NL;
        }

        $js .= 'new wygwam_addSettingRow();' . NL;

        $helperVariables = array(
            'tbGroups'         => Helper::toolbarButtonGroups(),
            'tbCombos'         => Helper::toolbarButtonCombos(),
            'tbLabelOverrides' => Helper::toolbarLabelOverrides()
        );

        $selectionVariables = array(
            'variables'       => $helperVariables,
            'id'              => 'selections',
            'groups'          => Helper::createToolbar($config->settings['toolbar'], true),
            'selected_groups' => array(),
            'selections_pane' => true
        );

        $optionVariables = array(
            'variables'       => $helperVariables,
            'id'              => 'options',
            'groups'          => Helper::toolbarButtonGroups(),
            'selected_groups' => $config->settings['toolbar'],
            'selections_pane' => false
        );
        var_dump($optionVariables);

        $toolbarInputHtml = $this->_renderPartial('_partial/config_edit_toolbar', $selectionVariables);
        $toolbarInputHtml .= $this->_renderPartial('_partial/config_edit_toolbar', $optionVariables);
        $toolbarInputHtml = $this->_renderPartial('_partial/toolbar_wrapper', array('html' => $toolbarInputHtml));

        $sections = array(
            'wygwam_basic_settings' => array(
                array(
                    'fields' => array(
                        'config_id' => array(
                            'type' => 'hidden', 'value' => $config->config_id
                        )
                    )
                ),
                array(
                    'title'  => lang('wygwam_config_name'),
                    'fields' => array(
                        'config_name' => array(
                            'type'     => 'text',
                            'value'    => $config->config_name
                        )
                    )
                ),
                array(
                    'title'  => lang('wygwam_upload_dir'),
                    'fields' => array(
                        'settings[upload_dir]' => array(
                            'type' => 'select',
                            'choices' => $uploadDirs,
                            'value' => $config->settings['upload_dir']
                        )
                    )
                ),
                array(
                    'title'   => lang('wygwam_toolbar'),
                    'wide'    => true,
                    'fields'  => array(
                        'settings[toolbar]' => array(
                            'type'    => 'html',
                            'content' => $toolbarInputHtml
                        )
                    )
                )
            ),
            'wygwam_appearance_settings' => array(
                array(
                    'title' => lang('wygwam_height'),
                    'fields' => array(
                        'settings[height]' => array(
                            'type'  => 'short-text',
                            'value' => $config->settings['height'],
                            'label' => ''
                        )
                    )
                ),
                /*array(
                    'title' => lang('wygwam_resizable'),
                    'fields' => array(
                        'settings[resize_enabled]' => array(
                            'type'  => 'yes_no',
                            'value' => $config->settings['resize_enabled'],
                        )
                    )
                )*/
            ),
            /*'wygwam_custom_styles' => array(
                array(
                    'title' => lang('wygwam_css_file'),
                    'desc'  => lang('wygwam_css_desc'),
                    'fields' => array(
                        'settings[contentsCss]' => array(
                            'type'  => 'text',
                            'value' => implode($config->settings['contentsCss'])
                        )
                    )
                ),
                array(
                    'title' => lang('wygwam_parse_css'),
                    'desc'  => lang('wygwam_parse_css_desc'),
                    'fields' => array(
                        'settings[parse_css]' => array(
                            'type' => 'yes_no',
                            'value' => $config->settings['parse_css']
                        )
                    )
                )
            ),*/
            /*'wygwam_advanced_settings' => array(
                array(
                    'title' => lang('wygwam_restrict_html'),
                    'desc'  => lang('wygwam_restrict_html_desc'),
                    'fields' => array(
                        'settings[restrict_html]' => array(
                            'type' => 'yes_no',
                            'value' => $config->settings['restrict_html']
                        )
                    )
                ),
                array(
                    'wide' => true,
                    'fields' => array(
                        'settings[advanced]' => array(
                            'type' => 'html',
                            'content' => $this->_renderPartial('_partial/config_advanced')
                        )
                    )
                )
            )*/
        );

        $variables['sections'] = $sections;
        $variables['base_url'] = Helper::getMcpUrl('saveConfig');
        $variables['cp_page_title'] = $headingTitle;

        $variables['save_btn_text'] = lang('save');
        $variables['save_btn_text_working'] = lang('saving');

        // Resources
        Helper::includeThemeCss('lib/ckeditor/skins/wygwam/editor.css');
        Helper::includeThemeCss('styles/config_edit.css');
        ee()->cp->add_js_script(array('ui' => 'draggable'));
        Helper::includeThemeJs('lib/ckeditor/ckeditor.js');
        Helper::includeThemeJs('scripts/config_edit_toolbar.js');
        Helper::includeThemeJs('scripts/config_edit_advanced.js');
        Helper::insertJs('jQuery(document).ready(function(){' . NL . $js . '});');

        $this->_setHeader($headingTitle);

        return $this->_render('form', $variables);
    }

    // --------------------------------------------------------------------

    /**
     * Save Config
     */
    public function saveConfig()
    {
        // -------------------------------------------
        //  Advanced Settings
        // -------------------------------------------

        /**
         * @var $request EllisLab\ExpressionEngine\Core\Request;
         */
        $request = ee('Request');

        $settings = $request->post('settings');

        // empty toolbar
        if (empty($settings['toolbar']) || $settings['toolbar'] === 'n') {
            $settings['toolbar'] = array();
        }

        // format_tags
        if (isset($settings['format_tags'])) {
            $settings['format_tags'] = implode(';', $settings['format_tags']);
        }

        // lists
        foreach (Helper::configLists() as $list) {
            if (isset($settings[$list])) {
                $settings[$list] = array_filter(preg_split('/[\r\n]+/', $settings[$list]));
            }
        }

        // -------------------------------------------
        //  Save and redirect to Index
        // -------------------------------------------

        $configId = $request->post('config_id');
        $configName = $request->post('config_name');

        if (!$configName) {
            $configName = 'Untitled';
        }

        // Existing configuration
        if ($configId) {
            $config = ee('Model')->get('wygwam:Config')->filter('config_id', '==', $configId)->first();
        }

        // New config
        if (empty($config)) {
            $config = ee('Model')->make('wygwam:Config');
        }

        /**
         * @var $config \EEHarbor\Wygwam\Model\Config
         */
        $config->config_name = $configName;
        $config->settings = $settings;

        $config->save();

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('wygwam_config_saved'))
            ->addToBody(sprintf(lang('wygwam_config_saved_desc'), $configName))
            ->defer();

        ee()->functions->redirect(Helper::getMcpUrl('index'));
    }

    // --------------------------------------------------------------------

    /**
     * Delete Config
     */
    public function deleteConfig()
    {
        $configId = ee('Request')->post('deleteConfigId');

        if (!empty($configId)) {
            /**
             * @var $config \EEHarbor\Wygwam\Model\Config
             */
            $config = ee('Model')->get('wygwam:Config')->filter('config_id', '==', $configId)->first();

            if ($config) {
                $config->delete();

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('wygwam_config_deleted'))
                    ->addToBody(sprintf(lang('wygwam_config_deleted_desc'), $config->config_name))
                    ->defer();
            }
        }

        ee()->functions->redirect(Helper::getMcpUrl('index'));
    }

    // --------------------------------------------------------------------

    /**
     * Settings
     */
    public function settings($validationResult = null)
    {
        $variables = Helper::getGlobalSettings();

        if ($validationResult) {
            $variables['errors'] = $validationResult;
            $request = ee('Request');
            // $variables['license_key'] = $request->post('license_key');
            $variables['file_browser'] = $request->post('file_browser');
        }

        $fields = array();
        // $fields[] = array(
        //     'title' => 'wygwam_license_key',
        //     'desc' => '',
        //     'fields' => array(
        //         'license_key' => array(
        //             'required' => true,
        //             'type'     => 'text',
        //             'value'    => $variables['license_key'],
        //         )
        //     )
        // );

        $choices = array(
            'ee' => 'EE File Manager'
        );

        if (Helper::isAssetsInstalled()) {
            $choices['assets'] = 'Assets';
        }

        $fields[] = array(
            'title' => 'wygwam_file_browser',
            'desc' => 'wygwam_file_browser_desc',
            'fields' => array(
                'file_browser' => array(
                    'required' => true,
                    'type'     => 'select',
                    'value'    => $variables['file_browser'],
                    'choices'  => $choices
                )
            )
        );

        $variables['sections'] = array($fields);
        $variables['base_url'] = Helper::getMcpUrl('saveSettings');
        $variables['cp_page_title'] = lang('wygwam_settings');

        $variables['save_btn_text'] = lang('save');
        $variables['save_btn_text_working'] = lang('saving');

        $this->_setHeader(lang('wygwam_settings'));
        return $this->_render('form', $variables);
    }

    /**
     * Save Module Settings
     */
    public function saveSettings()
    {
        /**
         * @var $request EllisLab\ExpressionEngine\Core\Request;
         */
        $request = ee('Request');

        $settings = array(
            // 'license_key' => $request->post('license_key'),
            'file_browser' => $request->post('file_browser')
        );

        $rules = array(
            // 'license_key' => 'required',
            'file_browser' => 'required',
        );

        /**
         * @var $validationResult EllisLab\ExpressionEngine\Service\Validation\Result
         */
        $validationResult = ee('Validation')->make($rules)->validate($_POST);

        if ($validationResult->failed()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('settings_save_error'))
                ->addToBody(lang('settings_save_error_desc'))
                ->now();

            return $this->settings($validationResult);
        } else {
            $model = Helper::getFieldtypeModel();
            $model->setProperty('settings', $settings);
            $model->save();

            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('settings_saved'))
                ->addToBody(sprintf(lang('settings_saved_desc'), Helper::getInfo()->getName()))
                ->defer();

            ee()->functions->redirect(Helper::getMcpUrl('settings'));
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Set the header information for a Wygwam page.
     *
     * @param $title
     */
    private function _setHeader($title)
    {
        $headerInfo = array(
            'title' => $title,
            'toolbar_items' => array(
                'settings' => array(
                    'href' => Helper::getMcpUrl('settings'),
                    'title' => lang('settings')
                )
            )
        );

        $this->_heading = $title;
        ee()->view->header = $headerInfo;
    }

    // /**
    //  * Make the sidebar for a Wygwam page.
    //  *
    //  * @param string $selected The selected link.
    //  */
    // private function _makeSidebar($selected = '')
    // {
    //     $links = array(
    //         'configs'  => array(
    //             'link'   => Helper::getMcpUrl(),
    //             'button' => array(
    //                 'title' => lang('new'),
    //                 'link'  => Helper::getMcpUrl('editConfig')
    //             )
    //         ),
    //         'settings' => array(
    //             'link' => Helper::getMcpUrl('settings')
    //         )
    //     );

    //     /**
    //      * @var $sidebar \EllisLab\ExpressionEngine\Service\Sidebar\Sidebar
    //      */
    //     $sidebar = ee('CP/Sidebar')->make();

    //     foreach ($links as $name => $link) {
    //         $header = $sidebar->addHeader(lang('wygwam_'.$name), $link['link']);

    //         if ($selected == $name) {
    //             $header->isActive();
    //         }

    //         if (!empty($link['button'])) {
    //             $header->withButton($link['button']['title'], $link['button']['link']);
    //         }
    //     }
    // }

    /**
     * Return a rendered view, ready to be used as a module page.
     *
     * @param       $viewFile
     * @param array $variables
     *
     * @return array $viewData
     */
    private function _render($viewFile, $variables = array())
    {
        return array(
            'heading' => $this->_heading,
            'breadcrumb' => array(Helper::getMcpUrl() => 'Wygwam'),
            'body' => $this->_renderPartial($viewFile, $variables)
        );
    }

    /**
     * Render a viewfile and return it as a string.
     *
     * @param       $viewFile
     * @param array $variables
     *
     * @return string The rendered html.
     */
    private function _renderPartial($viewFile, $variables = array())
    {
        /**
         * @var $view EllisLab\ExpressionEngine\Service\View\View
         */
        $view = ee('View')->make('wygwam:'.$viewFile);
        return $view->render($variables);
    }
}
