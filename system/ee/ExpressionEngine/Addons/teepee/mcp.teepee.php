<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\Teepee\TeepeeHelper;
use ExpressionEngine\Addons\Teepee\Model\Toolset;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Library\Rte\RteFilebrowserInterface;

/**
 * Teepee Module Control Panel Class
 *
 * @package   Teepee
 * @author    EEHarbor <help@eeharbor.com>
 * @copyright Copyright (c) Copyright (c) 2016 EEHarbor
 */

class Teepee_mcp
{

    public function __construct()
    {
        $this->base_url = ee('CP/URL')->make('addons/settings/teepee');
    }


    /**
     * Homepage
     *
     * @access	public
     * @return	string The page
     */
    public function index()
    {
        $toolsets = ee('Model')->get('teepee:Toolset')->all();
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
                'teepee_default_toolset_id' => 'required|enum[' . implode(',', $toolset_ids) . ']',
                'teepee_file_browser' => 'required|enum[' . implode(',', array_keys($file_browser_choices)) . ']'
            );
            $validationResult = ee('Validation')->make($rules)->validate($_POST);

            if ($validationResult->passed()) {
                $prefs = [
                    'teepee_default_toolset_id' => ee()->input->post('teepee_default_toolset_id'),
                    'teepee_file_browser' => ee()->input->post('teepee_file_browser')
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
                'teepee_default_toolset_id' => ee()->config->item('teepee_default_toolset_id') ? ee()->config->item('teepee_default_toolset_id') : reset($toolset_ids),
                'teepee_file_browser' => ee()->config->item('teepee_file_browser') ? ee()->config->item('teepee_file_browser') : reset($file_browser_choices)
            ];
        }

        $toolsets = ee('Model')->get('teepee:Toolset')->all();

        // prep the Default Toolset dropdown
        $toolset_opts = array();

        $data = array();
        $toolset_id = ee()->session->flashdata('toolset_id');

        $default_toolset_id = ee()->config->item('rte_default_toolset_id');

        foreach ($toolsets as $t) {
            $toolset_name = htmlentities($t->toolset_name, ENT_QUOTES, 'UTF-8');
            $toolset_opts[$t->toolset_id] = $toolset_name;
            $url = ee('CP/URL')->make('addons/settings/teepee/edit_toolset', array('toolset_id' => $t->toolset_id));
            $checkbox = array(
                'name' => 'selection[]',
                'value' => $t->toolset_id,
                'data'	=> array(
                    'confirm' => lang('toolset') . ': <b>' . $toolset_name . '</b>'
                )
            );

            $toolset_name = '<a href="' . $url->compile() . '">' . $toolset_name . '</a>';
            if ($default_toolset_id == $t->toolset_id) {
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
                            'href' => ee('CP/URL')->make('addons/settings/teepee/edit_toolset', array('toolset_id' => $t->toolset_id, 'clone' => 'y')),
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
                'attrs'		=> $attrs,
                'columns'	=> $toolset
            );
        }

        $vars = array(
            'cp_page_title' => lang('teepee_module_name') . ' ' . lang('configuration'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'sections' => array(
                array(
                    array(
                        'title' => 'default_toolset',
                        'desc' => '',
                        'fields' => array(
                            'teepee_default_toolset_id' => array(
                                'type' => 'radio',
                                'choices' => $toolset_opts,
                                'value' => $prefs['teepee_default_toolset_id'],
                                'no_results' => [
                                    'text' => sprintf(lang('no_found'), lang('toolsets'))
                                ]
                            )
                        )
                    ),
                    array(
                        'title' => 'teepee_file_browser',
                        'desc' => 'teepee_file_browser_desc',
                        'fields' => array(
                            'teepee_file_browser' => array(
                                'required' => true,
                                'type'     => 'select',
                                'value' => $prefs['teepee_file_browser'],
                                'choices'  => $file_browser_choices
                            )
                        )
                    )
                )
            )
        );

        $table = ee('CP/Table', array('autosort' => TRUE, 'autosearch' => FALSE, 'limit' => 20));
        $table->setColumns(
            array(
                'tool_set' => array(
                    'encode' => FALSE
                ),
                'manage' => array(
                    'type'	=> Table::COL_TOOLBAR
                ),
                array(
                    'type'	=> Table::COL_CHECKBOX
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
        return ee('View')->make('teepee:index')->render($vars);
    }


    /**
     * Edit Config
     */
    public function edit_toolset()
    {
        /**
         * @var $request EllisLab\ExpressionEngine\Core\Request;
         */
        $request = ee('Request');

        $defaultConfigSettings = TeepeeHelper::defaultConfigSettings();

        if (($toolset_id = $request->get('toolset_id'))
            && ($config = ee('Model')->get('teepee:Toolset')->filter('toolset_id', '==', $toolset_id)->first())
        ) {
            /**
             * @var $config Config
             */
            $config->settings = array_merge($defaultConfigSettings, $config->settings);

            // Clone a config?
            if ($request->get('clone') == 'y') {
                $config->toolset_id = '';
                $config->toolset_name .= ' '.lang('teepee_clone');
                $headingTitle = lang('teepee_create_config');
            } else {
                $headingTitle = lang('teepee_edit_config').' - '.$config->toolset_name;
            }
        } else {
            $config = ee('Model')->make('teepee:Toolset', array(
                'toolset_id' => '',
                'toolset_name' => '',
                'settings' => $defaultConfigSettings
            ));

            $headingTitle = lang('teepee_create_config');
        }

        $variables['config'] = $config;

        // -------------------------------------------
        //  Upload Directory
        // -------------------------------------------

        $uploadDirs = array('' => lang('all'));

        $fileBrowserOptions = array_unique([ee()->config->item('teepee_file_browser'), 'filepicker']);
        foreach ($fileBrowserOptions as $fileBrowserName) {
            $fileBrowserAddon = ee('Addon')->get($fileBrowserName);
            if ( $fileBrowserAddon !== null && $fileBrowserAddon->isInstalled() && $fileBrowserAddon->hasRteFilebrowser()) {
                $fqcn = $fileBrowserAddon->getRteFilebrowserClass();
                $fileBrowser = new $fqcn();
                if ($fileBrowser instanceof RteFilebrowserInterface) {
                    $uploadDirs = array_merge($uploadDirs, $fileBrowser->getUploadDestinations());
                    break;
                }
            }
        }

        $variables['uploadDestinations'] = $uploadDirs;

        // -------------------------------------------
        //  Advanced Settings
        // -------------------------------------------

        $fullToolset = TeepeeHelper::defaultToolbars()['Full'];

        $sections = array(
            'teepee_basic_settings' => array(
                array(
                    'fields' => array(
                        'toolset_id' => array(
                            'type' => 'hidden', 'value' => $config->toolset_id
                        )
                    )
                ),
                array(
                    'title'  => lang('toolset_name'),
                    'fields' => array(
                        'toolset_name' => array(
                            'type'     => 'text',
                            'value'    => $config->toolset_name
                        )
                    )
                ),
                array(
                    'title'  => lang('teepee_upload_dir'),
                    'fields' => array(
                        'settings[upload_dir]' => array(
                            'type' => 'select',
                            'choices' => $uploadDirs,
                            'value' => $config->settings['upload_dir']
                        )
                    )
                ),
                array(
                    'title'   => lang('teepee_toolbar'),
                    'wide'    => true,
                    'fields'  => array(
                        'settings[toolbar]' => array(
							'type' => 'checkbox',
							'choices' => $fullToolset,
							'value' => $config->settings['toolbar'],
							'no_results' => ['text' => sprintf(lang('no_found'), lang('tools'))]
						)
                    )
                        ),
                array(
                    'title' => lang('teepee_height'),
                    'fields' => array(
                        'settings[height]' => array(
                            'type'  => 'short-text',
                            'value' => $config->settings['height'],
                            'label' => ''
                        )
                    )
                ),
            ),
        );

        $variables['sections'] = $sections;
        $variables['base_url'] = ee('CP/URL')->make('addons/settings/teepee/saveConfig');
        $variables['cp_page_title'] = $headingTitle;

        $variables['save_btn_text'] = lang('save');
        $variables['save_btn_text_working'] = lang('saving');


        return ee('View')->make('teepee:form')->render($variables);
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

        // -------------------------------------------
        //  Save and redirect to Index
        // -------------------------------------------

        $toolset_id = $request->post('toolset_id');
        $configName = $request->post('toolset_name');

        if (!$configName) {
            $configName = 'Untitled';
        }

        // Existing configuration
        if ($toolset_id) {
            $config = ee('Model')->get('teepee:Toolset')->filter('toolset_id', '==', $toolset_id)->first();
        }

        // New config
        if (empty($config)) {
            $config = ee('Model')->make('teepee:Toolset');
        }

        /**
         * @var $config \EEHarbor\Teepee\Model\Config
         */
        $config->toolset_name = $configName;
        $config->settings = $settings;

        $config->save();

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('teepee_config_saved'))
            ->addToBody(sprintf(lang('teepee_config_saved_desc'), $configName))
            ->defer();

        ee()->functions->redirect($this->base_url);
    }

    // --------------------------------------------------------------------

    /**
     * Delete Config
     */
    public function deleteConfig()
    {
        $toolset_id = ee('Request')->post('deletetoolset_id');

        if (!empty($toolset_id)) {
            /**
             * @var $config \EEHarbor\Teepee\Model\Config
             */
            $config = ee('Model')->get('teepee:Toolset')->filter('toolset_id', '==', $toolset_id)->first();

            if ($config) {
                $config->delete();

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('teepee_config_deleted'))
                    ->addToBody(sprintf(lang('teepee_config_deleted_desc'), $config->toolset_name))
                    ->defer();
            }
        }

        ee()->functions->redirect($this->base_url);
    }

    // --------------------------------------------------------------------

}
