<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\Rte\RteHelper;
use ExpressionEngine\Addons\Rte\Model\Toolset;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Library\Rte\RteFilebrowserInterface;

class Rte_mcp
{
    public function __construct()
    {
        $this->base_url = ee('CP/URL')->make('addons/settings/rte');
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
                'rte_file_browser' => 'required|enum[' . implode(',', array_keys($file_browser_choices)) . ']'
            );
            $validationResult = ee('Validation')->make($rules)->validate($_POST);

            if ($validationResult->passed()) {
                $prefs = [
                    'rte_default_toolset' => ee()->input->post('rte_default_toolset'),
                    'rte_file_browser' => ee()->input->post('rte_file_browser')
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
                'rte_file_browser' => ee()->config->item('rte_file_browser') ? ee()->config->item('rte_file_browser') : reset($file_browser_choices)
            ];
        }

        $toolsets = ee('Model')->get('rte:Toolset')->all();

        // prep the Default Toolset dropdown
        $toolset_opts = array();

        $data = array();
        $toolset_id = ee()->session->flashdata('toolset_id');

        foreach ($toolsets as $t) {
            $toolset_name = htmlentities($t->toolset_name, ENT_QUOTES, 'UTF-8');
            $toolset_opts[$t->toolset_id] = $toolset_name;
            $url = ee('CP/URL')->make('addons/settings/rte/edit_toolset', array('toolset_id' => $t->toolset_id));
            $checkbox = array(
                'name' => 'selection[]',
                'value' => $t->toolset_id,
                'data' => array(
                    'confirm' => lang('toolset') . ': <b>' . $toolset_name . '</b>'
                )
            );

            $toolset_name = '<a href="' . $url->compile() . '">' . $toolset_name . '</a>';
            if ($prefs['rte_default_toolset'] == $t->toolset_id) {
                $toolset_name = '<span class="default">' . $toolset_name . ' ✱</span>';
                $checkbox['disabled'] = 'disabled';
            }
            $toolset = array(
                'tool_set' => $toolset_name,
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
        if (ee('Request')->isPost()) {
            $settings = ee('Request')->post('settings');

            // -------------------------------------------
            //  Save and redirect to Index
            // -------------------------------------------

            $toolset_id = ee('Request')->post('toolset_id');
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
            $config->settings = $settings;

            $validate = $config->validate();

            if ($validate->isValid()) {
                $config->save();

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle($toolset_id ? lang('toolset_updated') : lang('toolset_created'))
                    ->addToBody(sprintf($toolset_id ? lang('toolset_updated_desc') : lang('toolset_created_desc'), $configName))
                    ->defer();

                ee()->functions->redirect($this->base_url);
            } else {
                $variables['errors'] = $validate;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('toolset_error'))
                    ->addToBody(lang('toolset_error_desc'))
                    //->addToBody($validate->getAllErrors())
                    ->now();
            }
        }

        ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . URL_THEMES . 'rte/styles/settings.css' . '" />');

        ee()->cp->add_js_script(array(
            'ui' => 'draggable',
            'fp_module' => 'rte'
        ));

        $request = ee('Request');

        $defaultConfigSettings = RteHelper::defaultConfigSettings();

        $headingTitle = lang('rte_create_config');

        if (
            ($toolset_id = $request->get('toolset_id'))
            && ($config = ee('Model')->get('rte:Toolset')->filter('toolset_id', '==', $toolset_id)->first())
        ) {
            $config->settings = array_merge($defaultConfigSettings, $config->settings);

            // Clone a config?
            if ($request->get('clone') == 'y') {
                $config->toolset_id = '';
                $config->toolset_name .= ' ' . lang('rte_clone');
                $headingTitle = lang('rte_create_config');
            } else {
                $headingTitle = lang('rte_edit_config') . ' - ' . $config->toolset_name;
            }
        } elseif (!isset($config) || empty($config)) {
            $config = ee('Model')->make('rte:Toolset', array(
                'toolset_id' => '',
                'toolset_name' => '',
                'settings' => $defaultConfigSettings
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

        $fullToolbar = RteHelper::defaultToolbars()['Full'];
        $fullToolset = array();
        foreach ($fullToolbar as $i => $tool) {
            $fullToolset[$tool] = lang($tool . '_rte');
        }

        $toolbarInputHtml = ee('View')->make('rte:toolbar')->render(
            ['buttons' => $fullToolset, 'selection' => $config->settings['toolbar']]
        );

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
                    'title' => lang('rte_toolbar'),
                    'wide' => true,
                    'fields' => array(
                        'settings[toolbar]' => array(
                            'type' => 'html',
                            'content' => $toolbarInputHtml
                        )
                    )
                ),
                array(
                    'title' => lang('rte_height'),
                    'fields' => array(
                        'settings[height]' => array(
                            'type' => 'short-text',
                            'value' => $config->settings['height'],
                            'label' => ''
                        )
                    )
                ),
            ),
        );

        $variables['sections'] = $sections;
        $variables['base_url'] = ee('CP/URL')->make('addons/settings/rte/edit_toolset');
        $variables['cp_page_title'] = $headingTitle;

        $variables['save_btn_text'] = lang('save');
        $variables['save_btn_text_working'] = lang('saving');

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
        $toolset_id = ee('Request')->post('selection');

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
}
