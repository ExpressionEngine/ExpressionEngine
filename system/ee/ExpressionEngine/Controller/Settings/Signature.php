<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Settings;

use CP_Controller;

/**
 * signatures Settings Controller
 */
class Signature extends Settings
{
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->hasAll('can_access_members', 'can_admin_design')) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    public function index()
    {
        $vars['sections'] = array(
            'url_path_settings_title' => array(
                array(
                    'title' => 'signature_url',
                    'desc' => 'signature_url_desc',
                    'fields' => array(
                        'signature_url' => array('type' => 'text')
                    )
                ),
                array(
                    'title' => 'signature_path',
                    'desc' => 'signature_path_desc',
                    'fields' => array(
                        'signature_path' => array('type' => 'text')
                    )
                )
            ),
            'signature_file_restrictions' => array(
                array(
                    'title' => 'signature_max_width',
                    'fields' => array(
                        'signature_max_width' => array('type' => 'text')
                    )
                ),
                array(
                    'title' => 'signature_max_height',
                    'fields' => array(
                        'signature_max_height' => array('type' => 'text')
                    )
                ),
                array(
                    'title' => 'signature_max_kb',
                    'fields' => array(
                        'signature_max_kb' => array('type' => 'text')
                    )
                )
            )
        );

        ee()->form_validation->set_rules(array(
            array(
                'field' => 'signature_url',
                'label' => 'lang:signature_url',
                'rules' => 'strip_tags|valid_xss_check'
            ),
            array(
                'field' => 'signature_path',
                'label' => 'lang:signature_path',
                'rules' => 'strip_tags|valid_xss_check|file_exists|writable'
            ),
            array(
                'field' => 'signature_max_width',
                'label' => 'lang:signature_max_width',
                'rules' => 'integer'
            ),
            array(
                'field' => 'signature_max_height',
                'label' => 'lang:signature_max_height',
                'rules' => 'integer'
            ),
            array(
                'field' => 'signature_max_kb',
                'label' => 'lang:signature_max_kb',
                'rules' => 'integer'
            )
        ));

        ee()->form_validation->validateNonTextInputs($vars['sections']);

        $base_url = ee('CP/URL')->make('settings/signature');

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            $directory_settings = array(
                'signature_path' => ee()->input->post('signature_path'),
                'signature_url' => ee()->input->post('signature_url'),
                'signature_max_kb' => ee()->input->post('signature_max_kb'),
                'signature_max_width' => ee()->input->post('signature_max_width'),
                'signature_max_height' => ee()->input->post('signature_max_height')
            );

            if ($this->saveSettings($vars['sections'])
                && $this->updateUploadDirectory($directory_settings)) {
                ee()->view->set_message('success', lang('preferences_updated'), lang('preferences_updated_desc'), true);
            }

            ee()->functions->redirect($base_url);
        } elseif (ee()->form_validation->errors_exist()) {
            ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
        }

        ee()->view->ajax_validate = true;
        ee()->view->base_url = $base_url;
        ee()->view->cp_page_title = lang('signature_settings');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';

        ee()->view->cp_breadcrumbs = array(
            '' => lang('signature_settings')
        );

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Update the upload preferences for the associated upload directory
     *
     * @param mixed $data
     * @access private
     * @return void
     */
    private function updateUploadDirectory($data)
    {
        $directory = ee('Model')->get('UploadDestination')
            ->filter('name', 'Signature Attachments')
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $directory) {
            $directory = ee('Model')->make('UploadDestination');
            $directory->name = 'Signature Attachments';
            $directory->site_id = ee()->config->item('site_id');
            $directory->Module = ee('Model')->get('Module')->filter('module_name', 'Member')->first();
        }
        $directory->server_path = $data['signature_path'];
        $directory->url = $data['signature_url'];
        $directory->max_size = $data['signature_max_kb'];
        $directory->max_width = $data['signature_max_width'];
        $directory->max_height = $data['signature_max_height'];
        $directory->Files = null;
        $directory->save();

        return true;
    }
}
// END CLASS

// EOF
