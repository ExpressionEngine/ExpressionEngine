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
 * URLs Settings Controller
 */
class Urls extends Settings
{
    /**
     * General Settings
     */
    public function index()
    {
        $vars['sections'] = array(
            array(
                array(
                    'title' => 'base_url',
                    'desc' => 'base_url_desc',
                    'fields' => array(
                        'base_url' => array('type' => 'text')
                    )
                ),
                array(
                    'title' => 'base_path',
                    'desc' => 'base_path_desc',
                    'fields' => array(
                        'base_path' => array('type' => 'text')
                    )
                ),
                array(
                    'title' => 'site_index',
                    'desc' => 'site_index_desc',
                    'fields' => array(
                        'site_index' => array('type' => 'text')
                    )
                ),
                array(
                    'title' => 'site_url',
                    'desc' => 'site_url_desc',
                    'fields' => array(
                        'site_url' => array('type' => 'text', 'required' => true)
                    )
                ),
                array(
                    'title' => 'cp_url',
                    'desc' => 'cp_url_desc',
                    'fields' => array(
                        'cp_url' => array('type' => 'text', 'required' => true)
                    )
                ),
                array(
                    'title' => 'themes_url',
                    'desc' => 'themes_url_desc',
                    'fields' => array(
                        'theme_folder_url' => array('type' => 'text', 'required' => true)
                    )
                ),
                array(
                    'title' => 'themes_path',
                    'desc' => 'themes_path_desc',
                    'fields' => array(
                        'theme_folder_path' => array('type' => 'text', 'required' => true)
                    )
                ),
                ee('Config')->getFile()->getBoolean('legacy_member_templates') ?
                array(
                    'title' => 'member_segment_trigger',
                    'desc' => 'member_segment_trigger_desc',
                    'fields' => array(
                        'profile_trigger' => array('type' => 'text')
                    )
                ) : null,
                array(
                    'title' => 'category_segment_trigger',
                    'desc' => 'category_segment_trigger_desc',
                    'fields' => array(
                        'reserved_category_word' => array('type' => 'text')
                    )
                ),
                array(
                    'title' => 'category_url',
                    'desc' => 'category_url_desc',
                    'fields' => array(
                        'use_category_name' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'y' => lang('category_url_opt_titles'),
                                'n' => lang('category_url_opt_ids')
                            )
                        )
                    )
                ),
                array(
                    'title' => 'url_title_separator',
                    'desc' => 'url_title_separator_desc',
                    'fields' => array(
                        'word_separator' => array(
                            'type' => 'radio',
                            'choices' => array(
                                'dash' => lang('url_title_separator_opt_hyphen'),
                                'underscore' => lang('url_title_separator_opt_under')
                            )
                        )
                    )
                ),
            )
        );

        ee()->form_validation->set_rules(array(
            array(
                'field' => 'base_path',
                'label' => 'lang:base_path',
                'rules' => 'file_exists'
            ),
            array(
                'field' => 'site_index',
                'label' => 'lang:site_index',
                'rules' => 'strip_tags|valid_xss_check'
            ),
            array(
                'field' => 'site_url',
                'label' => 'lang:site_url',
                'rules' => 'required|strip_tags|valid_xss_check'
            ),
            array(
                'field' => 'cp_url',
                'label' => 'lang:cp_url',
                'rules' => 'required|strip_tags|valid_xss_check'
            ),
            array(
                'field' => 'theme_folder_url',
                'label' => 'lang:themes_url',
                'rules' => 'required|strip_tags|valid_xss_check'
            ),
            array(
                'field' => 'theme_folder_path',
                'label' => 'lang:themes_path',
                'rules' => 'required|strip_tags|valid_xss_check|file_exists|callback__validateThemeFolderPath'
            ),
            array(
                'field' => 'profile_trigger',
                'label' => 'lang:member_segment_trigger',
                'rules' => 'alpha_dash'
            ),
        ));

        ee()->form_validation->validateNonTextInputs($vars['sections']);

        $base_url = ee('CP/URL')->make('settings/urls');

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
        ee()->view->cp_page_title = lang('url_path_settings');
        ee()->view->cp_page_title_alt = lang('url_path_settings_title');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';
        ee()->view->cp_breadcrumbs = array(
            '' => lang('url_path_settings')
        );
        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Custom validator to make sure Theme Folder Path does exist
     * and seemingly has correct folder structure
     **/
    public function _validateThemeFolderPath($path)
    {
        if (!ee()->form_validation->file_exists(rtrim((string) $path, '\\/') . '/ee/cp')) {
            ee()->form_validation->set_message('_validateThemeFolderPath', lang('invalid_theme_folder_path'));

            return false;
        }
        return true;
    }
}
// END CLASS

// EOF
