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
 * Word Censoring Settings Controller
 */
class WordCensor extends Settings
{
    /**
     * General Settings
     */
    public function index()
    {
        ee()->load->model('admin_model');

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'enable_censoring',
                    'desc' => 'enable_censoring_desc',
                    'fields' => array(
                        'enable_censoring' => array('type' => 'yes_no')
                    )
                ),
                array(
                    'title' => 'censor_replacement',
                    'desc' => 'censor_replacement_desc',
                    'fields' => array(
                        'censor_replacement' => array('type' => 'text')
                    )
                ),
                array(
                    'title' => 'censored_words',
                    'desc' => 'censored_words_desc',
                    'fields' => array(
                        'censored_words' => array(
                            'type' => 'textarea',
                            'kill_pipes' => true
                        )
                    )
                )
            )
        );

        $base_url = ee('CP/URL')->make('settings/word-censor');

        ee()->form_validation->set_rules(array(
            array(
                'field' => 'censor_replacement',
                'label' => 'lang:censor_replacement',
                'rules' => 'strip_tags|valid_xss_check'
            )
        ));

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
        ee()->view->cp_page_title = lang('word_censoring');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';
        ee()->view->cp_breadcrumbs = array(
            '' => lang('word_censoring')
        );
        ee()->cp->render('settings/form', $vars);
    }
}
// END CLASS

// EOF
