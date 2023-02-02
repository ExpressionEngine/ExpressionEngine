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
 * Content & Design Settings Controller
 */
class ContentDesign extends Settings
{
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('admin_channels')) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * General Settings
     */
    public function index()
    {
        $vars['sections'] = array(
            array(
                array(
                    'title' => 'new_posts_clear_caches',
                    'desc' => 'new_posts_clear_caches_desc',
                    'fields' => array(
                        'new_posts_clear_caches' => array('type' => 'yes_no')
                    )
                ),
                array(
                    'title' => 'enable_sql_caching',
                    'desc' => 'enable_sql_caching_desc',
                    'fields' => array(
                        'enable_sql_caching' => array('type' => 'yes_no')
                    )
                )
            ),
            array(
                [
                    'title' => 'enable_entry_cloning',
                    'desc' => 'enable_entry_cloning_desc',
                    'fields' => [
                        'enable_entry_cloning' => [
                            'type' => 'yes_no'
                        ]
                    ]
                ],
            ),
            'categories_section' => array(
                array(
                    'title' => 'auto_assign_cat_parents',
                    'desc' => 'auto_assign_cat_parents_desc',
                    'fields' => array(
                        'auto_assign_cat_parents' => array('type' => 'yes_no')
                    )
                )
            ),
            'file_manager' => array(
                array(
                    'title' => 'file_manager_compatibility_mode',
                    'desc' => 'file_manager_compatibility_mode_desc',
                    'fields' => array(
                        'file_manager_compatibility_mode' => array('type' => 'yes_no')
                    )
                )
            ),
            'image_resizing' => array(
                array(
                    'title' => 'image_resize_protocol',
                    'desc' => 'image_resize_protocol_desc',
                    'fields' => array(
                        'image_resize_protocol' => array(
                            'type' => 'radio',
                            'choices' => array(
                                'gd' => lang('gd'),
                                'gd2' => lang('gd2'),
                                'imagemagick' => lang('imagemagick'),
                                'netpbm' => lang('netpbm')
                            )
                        )
                    )
                ),
                array(
                    'title' => 'image_library_path',
                    'desc' => 'image_library_path_desc',
                    'fields' => array(
                        'image_library_path' => array('type' => 'text')
                    )
                ),
            ),
            'emoticons' => array(
                array(
                    'title' => 'enable_emoticons',
                    'desc' => 'enable_emoticons_desc',
                    'fields' => array(
                        'enable_emoticons' => array('type' => 'yes_no')
                    )
                ),
                array(
                    'title' => 'emoticon_url',
                    'desc' => 'emoticon_url_desc',
                    'fields' => array(
                        'emoticon_url' => array('type' => 'text')
                    )
                )
            )
        );

        ee()->form_validation->set_rules(array(
            array(
                'field' => 'image_library_path',
                'label' => 'lang:image_library_path',
                'rules' => 'strip_tags|valid_xss_check|callback__validateResizeLibraryPath'
            ),
            array(
                'field' => 'emoticon_url',
                'label' => 'lang:emoticon_url',
                'rules' => 'strip_tags|valid_xss_check'
            )
        ));

        ee()->form_validation->validateNonTextInputs($vars['sections']);

        $base_url = ee('CP/URL')->make('settings/content-design');

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
        ee()->view->cp_page_title = lang('content_and_design');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';

        ee()->view->cp_breadcrumbs = array(
            '' => lang('content_and_design')
        );

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Custom validator to make sure a path to an image processing library
     * is provided if ImageMagick or NetPBM are selected
     **/
    public function _validateResizeLibraryPath($path)
    {
        $path = (string) $path;
        $protocol = ee()->input->post('image_resize_protocol');

        if (in_array($protocol, array('imagemagick', 'netpbm')) && trim($path) == '') {
            ee()->form_validation->set_message('_validateResizeLibraryPath', lang('invalid_image_library_path'));

            return false;
        }

        ee()->form_validation->set_message('_validateResizeLibraryPath', lang('file_exists'));

        return trim($path) == '' || file_exists($path);
    }
}
// END CLASS

// EOF
