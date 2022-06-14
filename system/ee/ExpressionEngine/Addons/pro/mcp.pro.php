<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
*/

 /**
 * Pro Module control panel
 */
class Pro_mcp
{
    private $sidebar;
    private $hasValidLicense = false;
    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        ee()->load->library('file_field');
        ee()->lang->load('settings');
        ee()->lang->load('pro', ee()->session->get_language(), false, true, PATH_ADDONS . 'pro/');
        $this->hasValidLicense = ee('pro:Access')->hasValidLicense(true);
    }

    public function index()
    {
        return $this->general();
    }

    /**
     * Controller method for the branding settings page
     *
     * @access public
     * @return void
     */
    public function branding()
    {
        if (! $this->hasValidLicense) {
            return $this->general();
        }
        $this->build_sidebar('branding');
        $fields = [
            'login_logo',
            'favicon'
        ];

        if (ee('Request')->isPost()) {
            $data = array();
            foreach ($fields as $field) {
                $data[$field] = ee()->file_field->parse_string(ee()->input->post($field));
            }

            $config_update = ee()->config->update_site_prefs($data);

            if (!empty($config_update)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('cp_message_issue'))
                    ->addToBody($config_update)
                    ->defer();
            } else {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody(lang('pro_settings_updated'))
                    ->defer();
            }

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/pro/branding'));
        }

        $settings = [];
        foreach ($fields as $field) {
            $settings[$field] = $this->unparseFiledir(ee()->config->item($field));
        }

        $vars = [
            'sections' => [
                [
                    [
                        'title' => 'login_logo',
                        'desc' => 'login_logo_desc',
                        'fields' => [
                            'login_logo' => [
                                'type' => 'html',
                                'value' => $settings['login_logo'],
                                'required' => false,
                                'maxlength' => 255,
                                'content' => ee()->file_field->dragAndDropField('login_logo', $settings['login_logo'], 'all', 'image')
                            ]
                        ]
                    ],
                    [
                        'title' => 'favicon',
                        'desc' => 'favicon_desc',
                        'fields' => [
                            'favicon' => [
                                'type' => 'html',
                                'value' => $settings['favicon'],
                                'required' => false,
                                'maxlength' => 255,
                                'content' => ee()->file_field->dragAndDropField('favicon', $settings['favicon'], 'all', 'image')
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $vars += array(
            'base_url' => ee('CP/URL')->make('addons/settings/pro/branding'),
            'cp_page_title' => lang('branding'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );

        return array(
            'body'       => ee('View')->make('ee:_shared/form')->render($vars),
            'breadcrumb' => array(
                ee('CP/URL')->make('addons/settings/pro')->compile() => lang('pro_module_name')
            ),
            'heading' => lang('pro_module_name'),
            'sidebar' => $this->sidebar
        );
    }

    /**
     * Controller method for the general settings page
     *
     * @access public
     * @return void
     */
    public function general()
    {
        if ($this->hasValidLicense) {
            $this->build_sidebar('general');
        }
        $fields = [
            'enable_dock',
            'enable_frontedit',
            'automatic_frontedit_links',
            'enable_entry_cloning',
            'enable_mfa',
        ];

        if (ee('Request')->isPost()) {
            $data = array();
            foreach ($fields as $field) {
                $data[$field] = ee('Security/XSS')->clean(ee('Request')->post($field));
            }

            $config_update = ee()->config->update_site_prefs($data);

            if (!empty($config_update)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('cp_message_issue'))
                    ->addToBody($config_update)
                    ->defer();
            } else {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody(lang('pro_settings_updated'))
                    ->defer();
            }

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/pro/general'));
        }

        $settings = [];
        foreach ($fields as $field) {
            $settings[$field] = ee()->config->item($field) === false ? 'y' : ee()->config->item($field);
        }

        $vars = [
            'sections' => [
                [
                    [
                        'title' => 'enable_dock',
                        'desc' => 'enable_dock_desc',
                        'fields' => [
                            'enable_dock' => [
                                'type' => 'yes_no',
                                'value' => $settings['enable_dock'],
                                'group_toggle' => [
                                    'y' => 'enable_dock'
                                ]
                            ]
                        ]
                    ],
                    [
                        'title' => 'enable_frontedit',
                        'desc' => 'enable_frontedit_desc',
                        'group' => 'enable_dock',
                        'fields' => [
                            'enable_frontedit' => [
                                'type' => 'yes_no',
                                'value' => $settings['enable_frontedit'],
                                'group_toggle' => [
                                    'y' => 'enable_frontedit'
                                ]
                            ]
                        ]
                    ],
                    [
                        'title' => 'automatic_frontedit_links',
                        'desc' => 'automatic_frontedit_links_desc',
                        'group' => 'enable_dock|enable_frontedit',
                        'fields' => [
                            'automatic_frontedit_links' => [
                                'type' => 'yes_no',
                                'value' => $settings['automatic_frontedit_links']
                            ]
                        ]
                    ],
                    [
                        'title' => 'enable_entry_cloning',
                        'desc' => 'enable_entry_cloning_desc',
                        'fields' => [
                            'enable_entry_cloning' => [
                                'type' => 'yes_no',
                                'value' => $settings['enable_entry_cloning']
                            ]
                        ]
                    ],
                    [
                        'title' => 'enable_mfa',
                        'desc' => 'enable_mfa_desc',
                        'fields' => [
                            'enable_mfa' => [
                                'type' => 'yes_no',
                                'disabled' => version_compare(PHP_VERSION, 7.1, '<'),
                                'value' => version_compare(PHP_VERSION, 7.1, '<') ? 'n' : $settings['enable_mfa']
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $vars += array(
            'base_url' => ee('CP/URL')->make('addons/settings/pro/general'),
            'cp_page_title' => lang('settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );

        return array(
            'body'       => ee('View')->make('ee:_shared/form')->render($vars),
            'breadcrumb' => array(
                ee('CP/URL')->make('addons/settings/pro')->compile() => lang('pro_module_name')
            ),
            'heading' => lang('pro_module_name'),
            'sidebar' => $this->sidebar
        );
    }

    /**
     * Convert real path back to {filedir_X}
     */
    private function unparseFiledir($path)
    {
        $dirs = ee('Model')->get('UploadDestination')
            ->all()
            ->getDictionary('id', 'url');
        foreach ($dirs as $id => $url) {
            $path = str_replace($url, '{filedir_' . $id . '}', $path);
        }
        return $path;
    }

    /**
     * Build navigation sidebar
     *
     * @return void
     */
    private function build_sidebar($active = null)
    {
        $items = [
            'general' => lang('general'),
            'branding' => lang('branding')
        ];
        $this->sidebar = ee('CP/Sidebar')->make();
        foreach ($items as $nav => $text) {
            $navItem = $this->sidebar->addItem($text, ee('CP/URL', 'addons/settings/pro/' . $nav));
            if ($nav == $active) {
                $navItem->isActive();
            }
        }
    }

    //somewhat hacky redirect to EE settings controller
    //for jump menu
    public function cookies()
    {
        ee()->functions->redirect(ee('CP/URL')->make('settings/pro/cookies'));
    }
}
