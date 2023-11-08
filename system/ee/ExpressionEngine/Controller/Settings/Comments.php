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
 * Comment Settings Controller
 */
class Comments extends Settings
{
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->hasAll('can_access_addons', 'can_admin_addons')) {
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
                    'title' => 'enable_comments',
                    'desc' => 'enable_comments_desc',
                    'fields' => array(
                        'enable_comments' => array('type' => 'yes_no')
                    ),
                )
            ),
            'options' => array(
                array(
                    'title' => 'comment_word_censoring',
                    'desc' => sprintf(lang('comment_word_censoring_desc'), ee('CP/URL')->make('settings/word-censor')),
                    'fields' => array(
                        'comment_word_censoring' => array('type' => 'yes_no')
                    ),
                ),
                array(
                    'title' => 'comment_moderation_override',
                    'desc' => 'comment_moderation_override_desc',
                    'fields' => array(
                        'comment_moderation_override' => array('type' => 'yes_no')
                    ),
                ),
                array(
                    'title' => 'comment_edit_time_limit',
                    'desc' => 'comment_edit_time_limit_desc',
                    'fields' => array(
                        'comment_edit_time_limit' => array('type' => 'text')
                    )
                )
            )
        );

        ee()->form_validation->set_rules(array(
            array(
                'field' => 'comment_edit_time_limit',
                'label' => 'lang:comment_edit_time_limit',
                'rules' => 'integer'
            )
        ));

        ee()->form_validation->validateNonTextInputs($vars['sections']);

        $base_url = ee('CP/URL')->make('settings/comments');

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
        ee()->view->cp_page_title = lang('comment_settings');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';

        ee()->view->cp_breadcrumbs = array(
            '' => lang('comment_settings')
        );

        ee()->cp->render('settings/form', $vars);
    }
}
// END CLASS

// EOF
