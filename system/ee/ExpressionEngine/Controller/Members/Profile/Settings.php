<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members\Profile;

use CP_Controller;
use ExpressionEngine\Addons\FilePicker\FilePicker as FilePicker;

/**
 * Member Profile Personal Settings Controller
 */
class Settings extends Profile
{
    private $base_url = 'members/profile/settings';

    protected function permissionCheck()
    {
        $id = ee()->input->get('id');

        if ($id != $this->session->userdata['member_id'] && !empty($id)) {
            parent::permissionCheck();
        }
    }

    /**
     * Personal Settings
     */
    public function index()
    {
        $this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

        $this->load->helper('html');
        $this->load->helper('directory');

        $vars['has_file_input'] = true;
        $vars['sections'] = [];

        $settings = [];

        if ($this->member->parse_smileys == 'y') {
            $settings[] = 'parse_smileys';
        }

        if ($this->member->accept_messages == 'y') {
            $settings[] = 'accept_messages';
        }

        // Member settings
        $vars['sections'][] = array(
            array(
                'title' => 'language',
                'desc' => 'language_desc',
                'fields' => array(
                    'language' => array(
                        'type' => 'radio',
                        'choices' => ee()->lang->language_pack_names(),
                        'value' => $this->member->language ?: ee()->config->item('deft_lang')
                    )
                )
            ),
            array(
                'title' => 'preferences',
                'desc' => 'preferences_desc',
                'fields' => array(
                    'preferences' => array(
                        'type' => 'checkbox',
                        'choices' => array(
                            'accept_messages' => lang('allow_messages'),
                            'parse_smileys' => lang('parse_smileys')
                        ),
                        'value' => $settings
                    ),
                )
            )
        );

        //Signature Settings To update the signature text
        if (ee()->config->item('allow_signatures') === "y") {
            $desc = $this->member->signature;
            if (!$desc) {
                $desc = '<b>'. lang('update_sig_text_desc_null') . '<b>';
            } else {
                $desc = sprintf(lang('update_sig_text_desc'), '<b>'. $this->member->signature .'<b>');
            }
            $vars['sections'][] = array(
                array(
                    'title' => 'signature_settings_text',
                    'desc' => $desc,
                    'fields' => array(
                        'signature' => array(
                            'type' => 'text',
                        )
                    )
                ),
            );
        }

        //Signature Settings To update the signature image
        if (ee()->config->item('allow_signatures') === "y" && ee()->config->item('sig_allow_img_upload') === "y") {
            $signature_directory = ee('Model')->get('UploadDestination')
                ->filter('name', 'Signature Attachments')
                ->filter('site_id', ee()->config->item('site_id'))
                ->first();

            if (empty($signature_directory) || !$signature_directory->exists()) {
                $vars['sections']['signature_settings'] = [
                    array(
                        'title' => 'change_signature',
                        'desc' => sprintf(lang('avatar_path_does_not_exist'), ee('CP/URL', 'settings/avatars')),
                        'fields' => []
                    ),
                ];
            } else {
                // Make sure the filename is not an empty string, as that will cause filesystem->exists() to return true
                $signature_exists = ($this->member->sig_img_filename != '' && $signature_directory->getFilesystem()->exists($this->member->sig_img_filename));

                $vars['sections']['signature_settings'] = array(
                    array(
                        'title' => 'current_signature',
                        'desc' => 'current_signature_desc',
                        'fields' => array(
                            'signature_filename' => array(
                                'type' => 'image',
                                'id' => 'signature',
                                'edit' => false,
                                'image' => $signature_exists ? $signature_directory->url . $this->member->sig_img_filename : '',
                                'value' => $this->member->sig_img_filename
                            )
                        )
                    ),
                    array(
                        'title' => 'change_signature',
                        'desc' => sprintf(lang('change_signature_desc'), $signature_directory->max_size),
                        'fields' => [
                            'upload_signature' => [
                                'type' => 'html',
                                'content' => form_upload('upload_signature')
                            ]
                        ]
                    )
                );

                // Hide the current sig section if the member doesn't have one
                if (!$signature_exists) {
                    $vars['sections']['signature_settings'][0]['hide'] = true;
                }
            }
        }

        if (ee()->config->item('allow_avatar_uploads') === "y") {
            // Avatar settings
            $avatar_directory = ee('Model')->get('UploadDestination')
                ->filter('name', 'Avatars')
                ->filter('site_id', ee()->config->item('site_id'))
                ->first();

            if (empty($avatar_directory) || !$avatar_directory->exists()) {
                $vars['sections']['avatar_settings'] = [
                    array(
                        'title' => 'change_avatar',
                        'desc' => sprintf(lang('avatar_path_does_not_exist'), ee('CP/URL', 'settings/avatars')),
                        'fields' => []
                    ),
                ];
            } else {
                // Make sure the filename is not an empty string, as that will cause filesystem->exists() to return true
                $avatar_exists = ($this->member->avatar_filename != '' && $avatar_directory->getFilesystem()->exists($this->member->avatar_filename));

                $vars['sections']['avatar_settings'] = array(
                    array(
                        'title' => 'current_avatar',
                        'desc' => 'current_avatar_desc',
                        'fields' => array(
                            'avatar_filename' => array(
                                'type' => 'image',
                                'id' => 'avatar',
                                'edit' => false,
                                'image' => $avatar_exists ? $avatar_directory->url . $this->member->avatar_filename : '',
                                'value' => $this->member->avatar_filename
                            )
                        )
                    ),
                    array(
                        'title' => 'change_avatar',
                        'desc' => sprintf(lang('change_avatar_desc'), $avatar_directory->max_size),
                        'fields' => [
                            'upload_avatar' => [
                                'type' => 'html',
                                'content' => form_upload('upload_avatar')
                            ]
                        ]
                    )
                );

                // Hide the current avatar section if the member doesn't have one
                if (!$avatar_exists) {
                    $vars['sections']['avatar_settings'][0]['hide'] = true;
                }
            }
        }



        // Date fields need some lang values from the content lang
        ee()->lang->loadfile('content');

        // Display custom member fields
        foreach ($this->member->getDisplay()->getFields() as $field) {
            $vars['sections']['custom_fields'][] = array(
                'title' => $field->getLabel(),
                'desc' => '',
                'fields' => array(
                    $field->getName() => array(
                        'type' => 'html',
                        'content' => $field->getForm(),
                        'required' => $field->isRequired(),
                    )
                )
            );
        }

        // Save settings
        if (!empty($_POST)) {
            $result = $this->saveSettings($vars['sections']);

            if (!is_bool($result)) {
                return $result;
            }

            if ($result) {
                // Show a success message
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('member_updated'))
                    ->addToBody(lang('member_updated_desc'))
                    ->defer();

                ee()->functions->redirect($this->base_url);
            }
        }

        ee()->cp->add_js_script('file', 'cp/members/avatar');

        ee()->view->base_url = $this->base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('personal_settings');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';

        ee()->view->cp_breadcrumbs = array_merge($this->breadcrumbs, [
            '' => lang('personal_settings')
        ]);

        ee()->cp->render('settings/form', $vars);
    }

    protected function saveSettings($settings)
    {
        unset($settings['avatar_settings']);
        unset($settings['signatrue_settings']);

        // Save the avatar and Signature images
        $success = $this->uploadAvatar();
        $success2 = $this->uploadSignature();

        if (!$success && !$success2) {
            parent::saveSettings($settings);

            return false;
        }

        $saved = parent::saveSettings($settings);

        return $saved;
    }

    protected function uploadAvatar()
    {
        // If nothing was chosen, keep the current avatar.
        if (!isset($_FILES['upload_avatar']) || empty($_FILES['upload_avatar']['name'])) {
            $this->member->avatar_filename = ee()->security->sanitize_filename(ee()->input->post('avatar_filename'));

            return true;
        }

        $existing = ee()->config->item('avatar_path') . $this->member->avatar_filename;

        // Remove the member's existing avatar
        if (file_exists($existing) && is_file($existing)) {
            unlink($existing);
        }

        ee()->load->library('filemanager');

        $directory = ee('Model')->get('UploadDestination')
            ->filter('name', 'Avatars')
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        $upload_response = ee()->filemanager->upload_file($directory->id, 'upload_avatar');

        if (isset($upload_response['error'])) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('upload_filedata_error'))
                ->addToBody($upload_response['error'])
                ->now();

            return false;
        }

        // We don't have the suffix, so first we explode to avoid passed by reference error
        // Then we grab our suffix
        $name_array = explode('.', $_FILES['upload_avatar']['name']);
        $suffix = array_pop($name_array);

        $name = $_FILES['upload_avatar']['name'];
        $name = 'avatar_' . $this->member->member_id . '.' . $suffix;

        $file_path = ee()->filemanager->clean_filename(
            basename($name),
            $directory->id,
            array('ignore_dupes' => false)
        );
        $filename = basename($file_path);

        // Upload the file
        ee()->load->library('upload', array('upload_path' => dirname($file_path)));
        ee()->upload->do_upload('file');
        $original = ee()->upload->upload_path . ee()->upload->file_name;

        if (!@copy($original, $file_path)) {
            if (!@move_uploaded_file($original, $file_path)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('upload_filedata_error'))
                    ->now();

                return false;
            }
        }

        unlink($original);
        $result = (array) ee()->upload;

        // Save the new avatar filename
        $this->member->avatar_filename = $filename;

        return true;
    }

    protected function uploadSignature()
    {
        // If nothing was chosen, keep the current
        if (!isset($_FILES['upload_signature']) || empty($_FILES['upload_signature']['name'])) {
            $this->member->sig_img_filename = ee()->security->sanitize_filename(ee()->input->post('signature_filename'));

            return true;
        }

        $existing = ee()->config->item('sig_img_path') . $this->member->sig_img_filename;

        // Remove the member's existing signature
        if (file_exists($existing) && is_file($existing)) {
            unlink($existing);
        }

        ee()->load->library('filemanager');

        $directory = ee('Model')->get('UploadDestination')
            ->filter('name', 'Signature Attachments')
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        $upload_response = ee()->filemanager->upload_file($directory->id, 'upload_signature');

        if (isset($upload_response['error'])) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('upload_filedata_error'))
                ->addToBody($upload_response['error'])
                ->now();

            return false;
        }

        // We don't have the suffix, so first we explode to avoid passed by reference error
        // Then we grab our suffix
        $name_array = explode('.', $_FILES['upload_signature']['name']);
        $suffix = array_pop($name_array);

        $name = $_FILES['upload_signature']['name'];
        $name = 'signature_' . $this->member->member_id . '.' . $suffix;

        $file_path = ee()->filemanager->clean_filename(
            basename($name),
            $directory->id,
            array('ignore_dupes' => false)
        );
        $filename = basename($file_path);

        // Upload the file
        ee()->load->library('upload', array('upload_path' => dirname($file_path)));
        ee()->upload->do_upload('file');
        $original = ee()->upload->upload_path . ee()->upload->file_name;

        if (!@copy($original, $file_path)) {
            if (!@move_uploaded_file($original, $file_path)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('upload_filedata_error'))
                    ->now();

                return false;
            }
        }

        unlink($original);
        $result = (array) ee()->upload;

        // Save the new signature filename
        $this->member->sig_img_filename = $filename;

        return true;
    }
}
// END CLASS

// EOF
