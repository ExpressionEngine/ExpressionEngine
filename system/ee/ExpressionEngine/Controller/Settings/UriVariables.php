<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Settings;

use CP_Controller;

/**
 * URI Variables Settings Controller
 */
class UriVariables extends Settings
{
    /**
     * General Settings
     */
    public function index()
    {
        $categoryGroups = ee('Model')
            ->get('CategoryGroup')
            ->filter('site_id', ee()->config->item('site_id'))
            ->all()
            ->sortBy('group_name')
            ->getDictionary('group_id', 'group_name');
        $vars['sections'] = array(
            array(
                array(
                    'title' => 'enable_request_variables',
                    'desc' => 'enable_request_variables_desc',
                    'fields' => array(
                        'enable_request_variables' => array(
                            'type' => 'yes_no',
                        ),
                    )
                ),
            ),
            array(
                array(
                    'title' => 'enable_category_uri_variables',
                    'desc' => 'enable_category_uri_variables_desc',
                    'fields' => array(
                        'enable_category_uri_variables' => array(
                            'type' => 'yes_no',
                            'group_toggle' => array(
                                'y' => 'category_uri_variables_settings'
                            )
                        ),
                    )
                ),
            ),
            'category_uri_variables_settings' => array(
                'group' => 'category_uri_variables_settings',
                'settings' => array(
                    array(
                        'title' => 'category_uri_variables_category_groups',
                        'desc' => 'category_uri_variables_category_groups_desc',
                        'fields' => array(
                            'category_uri_variables_category_groups' => array(
                                'type' => 'checkbox',
                                'choices' => $categoryGroups,
                            ),
                        )
                    ),
                    array(
                        'title' => 'category_uri_variables_uri_pattern',
                        'desc' => 'category_uri_variables_uri_pattern_desc',
                        'fields' => array(
                            'category_uri_variables_uri_pattern' => array(
                                'type' => 'text',
                            ),
                        )
                    ),
                    array(
                        'title' => 'category_uri_variables_set_all_segments',
                        'desc' => 'category_uri_variables_set_all_segments_desc',
                        'fields' => array(
                            'category_uri_variables_set_all_segments' => array(
                                'type' => 'yes_no',
                            ),
                        )
                    ),
                    array(
                        'title' => 'category_uri_variables_ignore_pagination',
                        'desc' => 'category_uri_variables_ignore_pagination_desc',
                        'fields' => array(
                            'category_uri_variables_ignore_pagination' => array(
                                'type' => 'yes_no',
                            ),
                        )
                    ),
                    array(
                        'title' => 'category_uri_variables_parse_file_paths',
                        'desc' => 'category_uri_variables_parse_file_paths_desc',
                        'fields' => array(
                            'category_uri_variables_parse_file_paths' => array(
                                'type' => 'yes_no',
                            ),
                        )
                    ),
                ),
            )
        );

        $base_url = ee('CP/URL')->make('settings/uri-variables');

        /*ee()->form_validation->set_rules(array(
            array(
                'field' => 'dynamic_tracking_disabling',
                'label' => 'lang:dynamic_tracking_disabling',
                'rules' => 'is_numeric'
            )
        ));*/

        ee()->form_validation->validateNonTextInputs($vars['sections']);

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            if ($this->saveSettings($vars['sections'])) {
                ee()->view->set_message('success', lang('preferences_updated'), lang('preferences_updated_desc'), true);
            }

            ee()->functions->redirect($base_url);
        } elseif (ee()->form_validation->errors_exist()) {
            ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
        }

        ee()->view->base_url = $base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('uri_variables');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';

        ee()->view->cp_breadcrumbs = array(
            '' => lang('uri_variables')
        );

        ee()->cp->render('settings/form', $vars);
    }
}
// END CLASS

// EOF
