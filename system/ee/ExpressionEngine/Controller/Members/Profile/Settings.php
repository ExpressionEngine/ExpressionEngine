<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members\Profile;

use ExpressionEngine\Service\Model\Collection AS FileCollection;
use ExpressionEngine\Model\File\File AS FileModel;

/**
 * Member Profile Personal Settings Controller
 */
class Settings extends Profile
{
    private $base_url = 'members/profile/settings';

    protected function permissionCheck()
    {
        $id = ee()->input->get('id');

        if ($id != ee()->session->userdata['member_id'] && ! empty($id)) {
            parent::permissionCheck();
        }
    }

    /**
     * Personal Settings
     */
    public function index()
    {
        $this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

        ee()->load->helper('html');
        ee()->load->helper('directory');

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
            if (! $avatar_exists) {
                $vars['sections']['avatar_settings'][0]['hide'] = true;
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
        if (! empty($_POST)) {
            $result = $this->saveSettings($vars['sections']);

            if (! is_bool($result)) {
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

        $vars['base_url'] = $this->base_url;
        $vars['ajax_validate'] = true;
        $vars['cp_page_title'] = lang('personal_settings');
        $vars['header'] = ee()->view->header;
        $vars['save_btn_text'] = 'btn_save_settings';
        $vars['save_btn_text_working'] = 'btn_saving';

        $vars['cp_breadcrumbs'] = array_merge($this->breadcrumbs, [
            '' => lang('personal_settings')
        ]);

        if (ee('Request')->get('modal_form') == 'y') {
            $sidebar = ee('CP/Sidebar')->render();
            if (! empty($sidebar)) {
                $vars['left_nav'] = $sidebar;
                $vars['left_nav_collapsed'] = ee('CP/Sidebar')->collapsedState;
            }
            return ee('View')->make('settings/modal-form')->render($vars);
        }

        ee()->cp->render('settings/form', $vars);
    }

    protected function saveSettings($settings)
    {
        unset($settings['avatar_settings']);

        // Save the avatar
        $success = $this->uploadAvatar();

        if (! $success) {
            parent::saveSettings($settings);

            return false;
        }

        $saved = parent::saveSettings($settings);

        if ($saved === true && ee('Request')->get('modal_form') == 'y') {
            $result = [
                'saveId' => $this->member->getId(),
                'item' => [
                    'value' => $this->member->getId(),
                    'label' => $this->member->screen_name,
                    'instructions' => $this->member->username
                ]
            ];
            return $result;
        }

        return $saved;
    }

    protected function uploadAvatar()
    {
        ee()->load->library('filemanager');

        $directory = ee('Model')->get('UploadDestination')
            ->filter('name', 'Avatars')
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        // If nothing was chosen, keep the current avatar.
        if (! isset($_FILES['upload_avatar']) || empty($_FILES['upload_avatar']['name'])) {
            if (empty(ee()->input->post('avatar_filename'))) {
                $this->removeAvatarFiles($directory->id);
            }
            $this->member->avatar_filename = ee()->security->sanitize_filename(ee()->input->post('avatar_filename'));
            return true;
        }

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

        //$name = $_FILES['upload_avatar']['name'];
        $name = 'avatar_' . $this->member->member_id . '.' . $suffix;

        $file_path = ee()->filemanager->clean_filename(
            $name,
            $directory->id,
            array('ignore_dupes' => false)
        );

        $filename = basename($file_path);

        $original = $upload_response['upload_directory_prefs']['server_path'] . $upload_response['file_name'];

        if (! @copy($original, $file_path)) {
            if (! @move_uploaded_file($original, $file_path)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('upload_filedata_error'))
                    ->now();

                return false;
            }
        }

        @unlink($original);
        $this->removeAvatarFiles($directory->id); //removes old avatar files

        // Save the new avatar filename
        $this->member->avatar_filename = $filename;
        $this->member->avatar_width = $upload_response['file_width'];
        $this->member->avatar_height = $upload_response['file_height'];

        return true;
    }

    /**
     * Removes the existing avatar files and data for current member
     *
     * @param [type] $dir_id
     * @return void
     */
    protected function removeAvatarFiles($dir_id)
    {
        //check if we have to delete an image
        if ($this->member->avatar_filename) {
            $existing = realpath(ee()->config->item('avatar_path') . $this->member->avatar_filename);

            // Remove the member's existing avatar
            if ($existing && file_exists($existing) && is_file($existing)) {
                unlink($existing);
            }

            $thumb = realpath(ee()->config->item('avatar_path') . '/_thumbs/' . $this->member->avatar_filename);
            if ($thumb && file_exists($thumb) && is_file($thumb)) {
                unlink($thumb);
            }
        }

        $this->member->avatar_filename = $this->member->avatar_width = $this->member->avatar_height = null;

        //now cleanup the saved File objects
        $files = $this->member->UploadedFiles->filter('upload_location_id', $dir_id);

        if ($files instanceof FileCollection) {
            if ($files->count() >= 1) {
                foreach ($files->getIds() as $file_id) {
                    $file = ee('Model')->get('File', $file_id)->first();
                    if ($file instanceof FileModel) {
                        if ($file->upload_location_id == $dir_id) {
                            $file->delete();
                        }
                    }
                }
            }
        }
    }
}
// END CLASS

// EOF
