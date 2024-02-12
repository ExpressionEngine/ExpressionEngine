<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\Rte\RteHelper;
use ExpressionEngine\Addons\Rte\Model\Toolset;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Library\Rte\RteFilebrowserInterface;

class Rte_mcp
{
    private $base_url;

    public function __construct()
    {
        $this->base_url = ee('CP/URL')->make('addons/settings/rte');
        ee()->lang->load('admin_content');
    }

    /**
     * Homepage
     *
     * @access public
     * @return string The page
     */
    public function index()
    {
        $toolsets = ee('Model')->get('rte:Toolset')->all();
        $toolset_ids = $toolsets->pluck('toolset_id');

        $file_browser_choices = [];
        //get addons that have rte file
        $addons = ee('Addon')->installed();
        foreach ($addons as $addon) {
            if ($addon->hasRteFilebrowser() === true) {
                $file_browser_choices[$addon->getProvider()->getPrefix()] = $addon->getName();
            }
        }

        $prefs = [];

        if (ee('Request')->isPost()) {
            $rules = array(
                'rte_default_toolset' => 'required|enum[' . implode(',', $toolset_ids) . ']',
                'rte_file_browser' => 'required|enum[' . implode(',', array_keys($file_browser_choices)) . ']',
                'rte_custom_ckeditor_build' => 'required|enum[y,n]'
            );
            $validationResult = ee('Validation')->make($rules)->validate($_POST);

            if ($validationResult->passed()) {
                $prefs = [
                    'rte_default_toolset' => ee()->input->post('rte_default_toolset', true),
                    'rte_file_browser' => ee()->input->post('rte_file_browser', true),
                    'rte_custom_ckeditor_build' => ee()->input->post('rte_custom_ckeditor_build', true) === 'y' ? 'y' : 'n'
                ];
                ee()->config->update_site_prefs($prefs);

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('settings_saved'))
                    ->addToBody(lang('settings_saved_desc'))
                    ->now();
            } else {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('settings_error'))
                    ->addToBody(lang('settings_error_desc'))
                    ->now();
            }
        }

        if (empty($prefs)) {
            $prefs = [
                'rte_default_toolset' => ee()->config->item('rte_default_toolset') ? ee()->config->item('rte_default_toolset') : reset($toolset_ids),
                'rte_file_browser' => ee()->config->item('rte_file_browser') ? ee()->config->item('rte_file_browser') : reset($file_browser_choices),
                'rte_custom_ckeditor_build' => ee()->config->item('rte_custom_ckeditor_build') ? ee()->config->item('rte_custom_ckeditor_build') : 'n'
            ];
        }

        $toolsets = ee('Model')->get('rte:Toolset')->all();

        // prep the Default Toolset dropdown
        $toolset_opts = array();

        $data = array();
        $toolset_id = ee()->session->flashdata('toolset_id');

        foreach ($toolsets as $t) {
            $toolset_opts[$t->toolset_id] = ee('Security/XSS')->clean($t->toolset_name);
            $url = ee('CP/URL')->make('addons/settings/rte/edit_toolset', array('toolset_id' => $t->toolset_id));
            $checkbox = array(
                'name' => 'selection[]',
                'value' => $t->toolset_id,
                'data' => array(
                    'confirm' => lang('toolset') . ': <b>' . ee('Security/XSS')->clean($t->toolset_name) . '</b>'
                )
            );

            $toolset_name = '<a href="' . $url->compile() . '">' . ee('Security/XSS')->clean($t->toolset_name) . '</a>';
            if ($prefs['rte_default_toolset'] == $t->toolset_id) {
                $toolset_name = '<span class="default">' . $toolset_name . ' ✱</span>';
                $checkbox['disabled'] = 'disabled';
            }
            $toolset = array(
                'tool_set' => $toolset_name,
                'tool_type' => $t->toolset_type,
                array('toolbar_items' => array(
                    'edit' => array(
                        'href' => $url,
                        'title' => lang('edit'),
                    ),
                    'copy' => array(
                        'href' => ee('CP/URL')->make('addons/settings/rte/edit_toolset', array('toolset_id' => $t->toolset_id, 'clone' => 'y')),
                        'title' => lang('clone'),
                    )
                )
                ),
                $checkbox
            );

            $attrs = array();

            if ($toolset_id && $t->toolset_id == $toolset_id) {
                $attrs = array('class' => 'selected');
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => $toolset
            );
        }

        $vars = array(
            'cp_page_title' => lang('rte_module_name') . ' ' . lang('configuration'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'sections' => array(
                array(
                    array(
                        'title' => 'default_toolset',
                        'desc' => '',
                        'fields' => array(
                            'rte_default_toolset' => array(
                                'type' => 'radio',
                                'choices' => $toolset_opts,
                                'value' => $prefs['rte_default_toolset'],
                                'no_results' => [
                                    'text' => sprintf(lang('no_found'), lang('toolsets'))
                                ]
                            )
                        )
                    ),
                    array(
                        'title' => 'rte_file_browser',
                        'desc' => 'rte_file_browser_desc',
                        'fields' => array(
                            'rte_file_browser' => array(
                                'required' => true,
                                'type' => 'select',
                                'value' => $prefs['rte_file_browser'],
                                'choices' => $file_browser_choices
                            )
                        )
                    ),
                    array(
                        'title' => 'rte_custom_ckeditor_build',
                        'desc' => 'rte_custom_ckeditor_build_desc',
                        'fields' => array(
                            'rte_custom_ckeditor_build' => array(
                                'type' => 'yes_no',
                                'value' => $prefs['rte_custom_ckeditor_build'],
                            )
                        )
                    )
                )
            )
        );

        $table = ee('CP/Table', array('autosort' => true, 'autosearch' => false, 'limit' => 20));
        $table->setColumns(
            array(
                'tool_set' => array(
                    'encode' => false
                ),
                'tool_type' => array(
                    'encode' => false
                ),
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                ),
                array(
                    'type' => Table::COL_CHECKBOX
                )
            )
        );

        $table->setNoResultsText('no_tool_sets');
        $table->setData($data);

        $vars['base_url'] = $this->base_url;
        $vars['table'] = $table->viewData($this->base_url);

        ee()->javascript->set_global('lang.remove_confirm', lang('toolset') . ': <b>### ' . lang('toolsets') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array('cp/confirm_remove'),
        ));

        // return the page
        return ee('View')->make('rte:index')->render($vars);
    }

    /**
     * Edit Config
     */
    public function edit_toolset()
    {
        $toolsetType = ee('Security/XSS')->clean(ee('Request')->post('toolset_type', 'ckeditor'));
        if (ee('Request')->isPost()) {
            $settings = ee('Security/XSS')->clean(ee('Request')->post('settings'));
            if (isset($settings['rte_config_json'])) {
                // need to allow some extra stuff here
                $settings['rte_config_json'] = ee('Request')->post('settings')['rte_config_json'];
            }

            // -------------------------------------------
            //  Save and redirect to Index
            // -------------------------------------------

            $toolset_id = ee('Security/XSS')->clean(ee('Request')->post('toolset_id'));
            $configName = ee('Request')->post('toolset_name');

            if (!$configName) {
                $configName = 'Untitled';
            }

            // Existing configuration
            if ($toolset_id) {
                $config = ee('Model')->get('rte:Toolset', $toolset_id)->first();
            }

            // New config
            if (!isset($config) || empty($config)) {
                $config = ee('Model')->make('rte:Toolset');
            }

            $config->toolset_name = $configName;
            $config->toolset_type = $toolsetType;
            $jsonError = false;
            if ($settings['rte_advanced_config'] == 'y' && !empty($settings['rte_config_json'])) {
                //override with JSON
                $json = json_decode($settings['rte_config_json']);
                if (empty($json)) {
                    $jsonError = true;
                    $settings['toolbar'] = $settings[$toolsetType . '_toolbar'];
                } elseif ($toolsetType == 'redactor' || $toolsetType == 'redactorX') {
                    $settings['toolbar'] = (array) $json;
                } else {
                    $settings = array_merge($settings, (array) $json);
                }
            } else {
                $settings['toolbar'] = $settings[$toolsetType . '_toolbar'];
            }
            if ($toolsetType == 'redactor') {
                if (!isset($settings['toolbar']['buttons'])) {
                    $settings['toolbar']['buttons'] = [];
                }
                if (!isset($settings['toolbar']['plugins'])) {
                    $settings['toolbar']['plugins'] = [];
                }
            }
            unset($settings['ckeditor_toolbar']);
            unset($settings['redactorX_toolbar']);
            unset($settings['redactor_toolbar']);
            $config->settings = $settings;

            $validate = $config->validate();

            if (!$jsonError && $validate->isValid()) {
                $config->save();

                $alert = ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle($toolset_id ? lang('toolset_updated') : lang('toolset_created'))
                    ->addToBody(sprintf($toolset_id ? lang('toolset_updated_desc') : lang('toolset_created_desc'), $configName));

                if (ee('Request')->post('submit') == 'save_and_close') {
                    $alert->defer();
                    ee()->functions->redirect($this->base_url);
                }
                $alert->now();
            } else {
                $variables['errors'] = $validate;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('toolset_error'))
                    ->addToBody($jsonError ? lang('toolset_json_error_desc') : lang('toolset_error_desc'))
                    ->now();
            }
        }

        ee()->cp->add_js_script(array(
            'ui' => 'draggable',
            'fp_module' => 'rte'
        ));

        $headingTitle = lang('rte_create_config');

        if (
            ($toolset_id = (int) ee('Request')->get('toolset_id'))
            && ($config = ee('Model')->get('rte:Toolset')->filter('toolset_id', '==', $toolset_id)->first())
        ) {
            $config->settings = array_merge(ee('rte:' . ucfirst($config->toolset_type) . 'Service')->defaultConfigSettings(), $config->settings);

            // Clone a config?
            if (ee('Request')->get('clone') == 'y') {
                $config->toolset_id = '';
                $config->toolset_name .= ' ' . lang('rte_clone');
                $headingTitle = lang('rte_create_config');
            } else {
                $headingTitle = lang('rte_edit_config') . ' - ' . ee('Security/XSS')->clean($config->toolset_name);
            }
        } elseif (!isset($config) || empty($config)) {
            $config = ee('Model')->make('rte:Toolset', array(
                'toolset_id' => '',
                'toolset_type' => $toolsetType,
                'toolset_name' => '',
                'settings' => ee('rte:' . ucfirst($toolsetType) . 'Service')->defaultConfigSettings(),
            ));
        }

        $variables['config'] = $config;

        // -------------------------------------------
        //  Upload Directory
        // -------------------------------------------

        $uploadDirs = array('' => lang('all'));

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
                    $uploadDirs = $uploadDirs + $fileBrowser->getUploadDestinations();

                    break;
                }
            }
        }

        $variables['uploadDestinations'] = $uploadDirs;

        // -------------------------------------------
        //  Advanced Settings
        // -------------------------------------------

        $toolbarInputHtml['ckeditor'] = ee('rte:CkeditorService')->toolbarInputHtml($config);
        $toolbarInputHtml['redactor'] = ee('rte:RedactorService')->toolbarInputHtml($config);

        if (isset($config->settings['rte_advanced_config']) && !empty($config->settings['rte_advanced_config']) && $config->settings['rte_advanced_config'] == 'y') {
            $rte_config_json = $config->settings['rte_config_json'];
        } elseif ($config->toolset_type == 'redactor' || $config->toolset_type == 'redactorX') {
            $rte_config_json = json_encode($config->settings['toolbar'], JSON_PRETTY_PRINT);
        } else {
            $rte_config_json = json_encode(ee('rte:CkeditorService')->buildToolbarConfig($config->settings), JSON_PRETTY_PRINT);
        }

        $sections = array(
            'rte_basic_settings' => array(
                array(
                    'fields' => array(
                        'toolset_id' => array(
                            'type' => 'hidden', 'value' => $config->toolset_id
                        )
                    )
                ),
                array(
                    'title' => lang('toolset_name'),
                    'fields' => array(
                        'toolset_name' => array(
                            'type' => 'text',
                            'value' => $config->toolset_name
                        )
                    )
                ),
                array(
                    'title' => lang('tool_type'),
                    'fields' => array(
                        'toolset_type' => array(
                            'type' => 'select',
                            'choices' => [
                                'ckeditor'  => 'CKEditor',
                                'redactorX'  => 'RedactorX',
                                'redactor'  => 'Redactor (deprecated)',
                            ],
                            'group_toggle' => [
                                'ckeditor' => 'ckeditor_toolbar',
                                'redactorX' => 'redactorX_toolbar',
                                'redactor' => 'redactor_toolbar',
                            ],
                            'value' => $config->toolset_type
                        )
                    )
                ),
                array(
                    'title' => lang('rte_upload_dir'),
                    'fields' => array(
                        'settings[upload_dir]' => array(
                            'type' => 'select',
                            'choices' => $uploadDirs,
                            'value' => $config->settings['upload_dir']
                        )
                    )
                ),
                array(
                    'title' => 'field_text_direction',
                    'fields' => array(
                        'settings[field_text_direction]' => array(
                            'type' => 'radio',
                            'choices' => array(
                                'ltr' => lang('field_text_direction_ltr'),
                                'rtl' => lang('field_text_direction_rtl')
                            ),
                            'value' => isset($config->settings['field_text_direction']) ? $config->settings['field_text_direction'] : 'ltr'
                        )
                    )
                ),
                array(
                    'title' => lang('rte_toolbar'),
                    'group' => 'ckeditor_toolbar',
                    'wide' => true,
                    'fields' => array(
                        'settings[ckeditor_toolbar]' => array(
                            'type' => 'html',
                            'content' => $toolbarInputHtml['ckeditor']
                        )
                    )
                ),
                array(
                    'title' => lang('rte_toolbar'),
                    'group' => 'redactor_toolbar',
                    'wide' => true,
                    'fields' => array(
                        'settings[redactor_toolbar]' => array(
                            'type' => 'html',
                            'content' => $toolbarInputHtml['redactor']
                        )
                    )
                ),
                array(
                    'title' => lang('rte_plugins'),
                    'group' => 'redactor_toolbar',
                    'wide' => true,
                    'fields' => array(
                        'settings[redactor_toolbar]' => array(
                            'type' => 'html',
                            'content' => ee('rte:RedactorService')->pluginsInputHtml($config)
                        )
                    )
                ),
                array(
                    'title' => 'rte_show_main_toolbar',
                    'desc' => 'rte_show_main_toolbar_desc',
                    'group' => 'redactorX_toolbar',
                    'fields' => array(
                        'settings[redactorX_toolbar][toolbar_hide]' => array(
                            'type' => 'yes_no',
                            'group_toggle' => array(
                                'y' => 'redactorX_toolbar_hide',
                            ),
                            'value' => isset($config->settings['toolbar']['toolbar_hide']) && !empty($config->settings['toolbar']['toolbar_hide']) ? $config->settings['toolbar']['toolbar_hide'] : 'y',
                        )
                    )
                ),
                array(
                    'title' => 'rte_main_toolbar',
                    'group' => 'redactorX_toolbar|redactorX_toolbar_hide',
                    'wide' => true,
                    'fields' => array(
                        'settings[redactorX_toolbar][hide]' => array(
                            'type' => 'html',
                            'content' => ee('rte:RedactorXService')->toolbarInputHtml($config, 'hide')
                        )
                    )
                ),
                array(
                    'title' => 'rte_toolbar_sticky',
                    'desc' => 'rte_show_main_toolbar_desc',
                    'group' => 'redactorX_toolbar|redactorX_toolbar_hide',
                    'fields' => array(
                        'settings[redactorX_toolbar][sticky]' => array(
                            'type' => 'yes_no',
                            'value' => isset($config->settings['toolbar']['sticky']) && !empty($config->settings['toolbar']['sticky']) ? $config->settings['toolbar']['sticky'] : 'y',
                        )
                    )
                ),
                array(
                    'title' => 'rte_show_topbar',
                    'desc' => 'rte_show_topbar_desc',
                    'group' => 'redactorX_toolbar',
                    'fields' => array(
                        'settings[redactorX_toolbar][toolbar_topbar]' => array(
                            'type' => 'yes_no',
                            'group_toggle' => array(
                                'y' => 'redactorX_toolbar_topbar',
                            ),
                            'value' => isset($config->settings['toolbar']['toolbar_topbar']) && !empty($config->settings['toolbar']['toolbar_topbar']) ? $config->settings['toolbar']['toolbar_topbar'] : 'y',
                        )
                    )
                ),
                array(
                    'title' => 'rte_topbar',
                    'group' => 'redactorX_toolbar|redactorX_toolbar_topbar',
                    'wide' => true,
                    'fields' => array(
                        'settings[redactorX_toolbar][topbar]' => array(
                            'type' => 'html',
                            'content' => ee('rte:RedactorXService')->toolbarInputHtml($config, 'topbar')
                        )
                    )
                ),
                array(
                    'title' => 'rte_show_addbar',
                    'desc' => 'rte_show_addbar_desc',
                    'group' => 'redactorX_toolbar',
                    'fields' => array(
                        'settings[redactorX_toolbar][toolbar_addbar]' => array(
                            'type' => 'yes_no',
                            'group_toggle' => array(
                                'y' => 'redactorX_toolbar_addbar',
                            ),
                            'value' => isset($config->settings['toolbar']['toolbar_addbar']) && !empty($config->settings['toolbar']['toolbar_addbar']) ? $config->settings['toolbar']['toolbar_addbar'] : 'y',
                        )
                    )
                ),
                array(
                    'title' => 'rte_addbar',
                    'group' => 'redactorX_toolbar|redactorX_toolbar_addbar',
                    'wide' => true,
                    'fields' => array(
                        'settings[redactorX_toolbar][addbar]' => array(
                            'type' => 'html',
                            'content' => ee('rte:RedactorXService')->toolbarInputHtml($config, 'addbar')
                        )
                    )
                ),
                array(
                    'title' => 'rte_show_context',
                    'desc' => 'rte_show_context_desc',
                    'group' => 'redactorX_toolbar',
                    'fields' => array(
                        'settings[redactorX_toolbar][toolbar_context]' => array(
                            'type' => 'yes_no',
                            'group_toggle' => array(
                                'y' => 'redactorX_toolbar_context',
                            ),
                            'value' => isset($config->settings['toolbar']['toolbar_context']) && !empty($config->settings['toolbar']['toolbar_context']) ? $config->settings['toolbar']['toolbar_context'] : 'y',
                        )
                    )
                ),
                array(
                    'title' => 'rte_context',
                    'group' => 'redactorX_toolbar|redactorX_toolbar_context',
                    'wide' => true,
                    'fields' => array(
                        'settings[redactorX_toolbar][addbar]' => array(
                            'type' => 'html',
                            'content' => ee('rte:RedactorXService')->toolbarInputHtml($config, 'context')
                        )
                    )
                ),
                array(
                    'title' => 'rte_format',
                    'desc' => 'rte_format_desc',
                    'group' => 'redactorX_toolbar',
                    'wide' => true,
                    'fields' => array(
                        'settings[redactorX_toolbar][format]' => array(
                            'type' => 'html',
                            'content' => ee('rte:RedactorXService')->toolbarInputHtml($config, 'format')
                        )
                    )
                ),
                array(
                    'title' => lang('rte_plugins'),
                    'group' => 'redactorX_toolbar',
                    'wide' => true,
                    'fields' => array(
                        'settings[redactorX_toolbar][plugins]' => array(
                            'type' => 'html',
                            'content' => ee('rte:RedactorXService')->toolbarInputHtml($config, 'plugins')
                        )
                    )
                ),
                array(
                    'title' => 'rte_control_bar',
                    'desc' => 'rte_control_bar_desc',
                    'group' => 'redactorX_toolbar',
                    'fields' => array(
                        'settings[redactorX_toolbar][toolbar_control]' => array(
                            'type' => 'yes_no',
                            'value' => isset($config->settings['toolbar']['toolbar_control']) && !empty($config->settings['toolbar']['toolbar_control']) ? $config->settings['toolbar']['toolbar_control'] : 'y',
                        )
                    )
                ),
                array(
                    'title' => 'rte_spellcheck',
                    'desc' => 'rte_spellcheck_desc',
                    'group' => 'redactorX_toolbar',
                    'fields' => array(
                        'settings[redactorX_toolbar][spellcheck]' => array(
                            'type' => 'dropdown',
                            'choices' => [
                                'none' => lang('none'),
                                'browser' => lang('browser'),
                                'grammarly' => lang('grammarly')
                            ],
                            'value' => isset($config->settings['toolbar']['spellcheck']) && !empty($config->settings['toolbar']['spellcheck']) ? $config->settings['toolbar']['spellcheck'] : 'browser'
                        )
                    )
                ),
                array(
                    'title' => lang('custom_stylesheet'),
                    'desc' => lang('custom_stylesheet_desc'),
                    'fields' => array(
                        'settings[css_template]' => array(
                            'type' => 'dropdown',
                            'choices' => $this->getTemplates('css'),
                            'value' => isset($config->settings['css_template']) && !empty($config->settings['css_template']) ? (int) $config->settings['css_template'] : '',
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('templates'))
                            ]
                        )
                    )
                ),
                array(
                    'title' => lang('rte_min_height'),
                    'desc' => lang('rte_min_height_desc'),
                    'fields' => array(
                        'settings[height]' => array(
                            'type' => 'short-text',
                            'value' => isset($config->settings['height']) && !empty($config->settings['height']) ? (int) $config->settings['height'] : '',
                            'label' => ''
                        )
                    )
                ),
                array(
                    'title' => lang('rte_max_height'),
                    'desc' => lang('rte_max_height_desc'),
                    'group' => 'redactor_toolbar|redactorX_toolbar',
                    'fields' => array(
                        'settings[max_height]' => array(
                            'type' => 'short-text',
                            'value' => isset($config->settings['max_height']) && !empty($config->settings['max_height']) ? (int) $config->settings['max_height'] : '',
                            'label' => ''
                        )
                    )
                ),
                array(
                    'title' => lang('rte_limiter'),
                    'desc' => lang('rte_limiter_desc'),
                    'group' => 'redactor_toolbar',
                    'fields' => array(
                        'settings[limiter]' => array(
                            'type' => 'short-text',
                            'value' => isset($config->settings['limiter']) && !empty($config->settings['limiter']) ? (int) $config->settings['limiter'] : '',
                            'label' => ''
                        )
                    )
                ),
                array(
                    'title' => 'rte_advanced_config',
                    'desc' => 'rte_advanced_config_desc',
                    'fields' => array(
                        'settings[rte_advanced_config]' => array(
                            'type' => 'yes_no',
                            'group_toggle' => array(
                                'y' => 'rte_advanced_config',
                            ),
                            'value' => isset($config->settings['rte_advanced_config']) && !empty($config->settings['rte_advanced_config']) ? $config->settings['rte_advanced_config'] : 'n',
                        )
                    )
                ),
                array(
                    'title' => 'rte_config_json',
                    'desc' => 'rte_config_json_desc',
                    'group' => 'rte_advanced_config',
                    'fields' => array(
                        'rte_advanced_config_warning' => array(
                            'type' => 'html',
                            'content' => ee('CP/Alert')->makeInline('rte_advanced_config_warning')
                                ->asImportant()
                                ->addToBody(lang('rte_advanced_config_warning'))
                                ->cannotClose()
                                ->render()
                        ),
                        'settings[rte_config_json]' => array(
                            'type' => 'textarea',
                            'value' => $rte_config_json
                        )
                    )
                ),
                array(
                    'title' => lang('custom_javascript'),
                    'desc' => lang('custom_javascript_rte_desc'),
                    'group' => 'rte_advanced_config',
                    'fields' => array(
                        'settings[js_template]' => array(
                            'type' => 'dropdown',
                            'choices' => $this->getTemplates('js'),
                            'value' => isset($config->settings['js_template']) && !empty($config->settings['js_template']) ? (int) $config->settings['js_template'] : '',
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('templates'))
                            ]
                        )
                    )
                ),
            ),
        );

        $variables['sections'] = $sections;
        $variables['base_url'] = ee('CP/URL')->make('addons/settings/rte/edit_toolset');
        $variables['cp_page_title'] = $headingTitle;

        $variables['buttons'] = [
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save',
                'text' => 'save',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_close',
                'text' => 'save_and_close',
                'working' => 'btn_saving'
            ]
        ];

        ee()->cp->add_js_script([
            'plugin' => 'ee_codemirror',
            'ui' => 'resizable',
            'file' => array(
                'vendor/codemirror/codemirror',
                'vendor/codemirror/closebrackets',
                'vendor/codemirror/comment',
                'vendor/codemirror/lint',
                'vendor/codemirror/active-line',
                'vendor/codemirror/overlay',
                'vendor/codemirror/xml',
                'vendor/codemirror/css',
                'vendor/codemirror/javascript',
                'vendor/codemirror/htmlmixed',
                'ee-codemirror-mode',
                'vendor/codemirror/dialog',
                'vendor/codemirror/searchcursor',
                'vendor/codemirror/search',
            )
        ]);
        ee()->javascript->set_global(
            'editor.height',
            ee()->config->item('codemirror_height') !== false ? ee()->config->item('codemirror_height') : 400
        );
        $fontSize = ee()->config->item('codemirror_fontsize');
        if ($fontSize !== false) {
            ee()->cp->add_to_head('<style type="text/css">.CodeMirror-scroll {font-size: ' . $fontSize . '}</style>');
        }
        if (isset($config->settings['rte_advanced_config']) && $config->settings['rte_advanced_config'] == 'y') {
            //json editor is visible, initialize immediately
            ee()->javascript->output("
                $('textarea[name=\"settings[rte_config_json]\"]').toggleCodeMirror({name: 'javascript', json: true});
                $('fieldset[data-group^=ckeditor_toolbar]').hide();
                $('fieldset[data-group^=redactorX_toolbar]').hide();
                $('fieldset[data-group^=redactor_toolbar]').hide();
            ");
        }

        ee()->javascript->output("
            window.document.addEventListener('formFields:toggle', (event) => {
                if (event.detail.for == 'settings[rte_advanced_config]') {
                    if (event.detail.state == 'y') {
                        $('fieldset[data-group^=ckeditor_toolbar]').hide();
                        $('fieldset[data-group^=redactorX_toolbar]').hide();
                        $('fieldset[data-group^=redactor_toolbar]').hide();
                    } else {
                        $('fieldset[data-group^=' + $('select[name=toolset_type]').children('option:selected').val() + '_toolbar]').show();
                    }
                    $('textarea[name=\"settings[rte_config_json]\"]').toggleCodeMirror({name: 'javascript', json: true});
                }
            });
        ");

        return [
            'body' => ee('View')->make('ee:_shared/form')->render($variables),
            'breadcrumb' => [
                '' => !empty($toolset_id) ? lang('edit_tool_set') : lang('create_tool_set')
            ]
        ];
    }

    // --------------------------------------------------------------------

    /**
     * Delete Config
     */
    public function delete_toolset()
    {
        $toolset_id = ee('Security/XSS')->clean(ee('Request')->post('selection'));

        if (!empty($toolset_id)) {
            $config = ee('Model')->get('rte:Toolset')->filter('toolset_id', 'IN', $toolset_id);

            if ($config) {
                $removed = $config->all()->pluck('toolset_name');
                $config->delete();

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('toolsets_removed'))
                    ->addToBody(lang('remove_success_desc'))
                    ->addToBody($removed)
                    ->defer();
            }
        }

        ee()->functions->redirect($this->base_url);
    }

    /**
     * Gets a list of the templates for the current site that do not already
     * have a route, grouped by their template group name:
     *   array(
     *     'news' => array(
     *       1 => 'index',
     *       3 => 'about',
     *     )
     *   )
     *
     * @return array An associative array of templates
     */
    private function getTemplates($type = 'css')
    {
        $templates = ee('Model')->get('Template')
            ->with('TemplateGroup')
            ->order('TemplateGroup.group_name')
            ->filter('template_type', $type)
            ->order('template_name')
            ->all();

        $results = [''];
        foreach ($templates as $template) {
            $results[$template->getId()] = $template->getPath();
        }

        return $results;
    }
}
