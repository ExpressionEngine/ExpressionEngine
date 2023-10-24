<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Model\File\UploadDestination;
use ExpressionEngine\Addons\FilePicker\FilePicker as Picker;
use ExpressionEngine\Service\File\ViewType;
use ExpressionEngine\Library\CP\FileManager\Traits\FileManagerTrait;
use ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 * File Picker Module control panel
 */
class Filepicker_mcp
{
    use FileManagerTrait;

    private $images = false;

    public $picker;
    public $base_url;
    public $access;

    public function __construct()
    {
        $this->picker = new Picker();
        $this->base_url = 'addons/settings/filepicker';
        $this->access = false;

        if (ee('Permission')->can('access_files')) {
            $this->access = true;
        }

        ee()->lang->loadfile('filemanager');
    }

    protected function getUserUploadDirectories()
    {
        $dirs = ee('Model')->get('UploadDestination')
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->filter('module_id', 0)
            ->order('name', 'asc')
            ->all();

        $member = ee()->session->getMember();

        return $dirs->filter(function ($dir) use ($member) {
            return $dir->memberHasAccess($member);
        });
    }

    protected function getSystemUploadDirectories()
    {
        $dirs = ee('Model')->get('UploadDestination')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('module_id', '!=', 0)
            ->all();

        return $dirs;
    }

    public function index()
    {
        // check if we have a request for a specific file id
        $file = ee()->input->get('file');
        if (! empty($file)) {
            return $this->fileInfo($file);
        }

        if ($this->access === false) {
            show_error(lang('unauthorized_access'), 403);
        }

        // directory filter
        $field_upload_locations = ee()->input->get('field_upload_locations') ?: (ee()->input->get('directory') ?: 'all');

        // directories we were asked to list
        $requested_directory = ee()->input->get('requested_directory') ?: ee()->input->get('directories');
        $requested_directory = empty($requested_directory) ? $field_upload_locations : $requested_directory;

        $dirs = $this->getUserUploadDirectories();
        if ($requested_directory != 'all') {
            $dirs = $dirs->filter('id', (int) $requested_directory);
        }

        // only have one? use it
        if ($dirs->count() == 1) {
            $field_upload_locations = $dirs->first()->id;
        }
        $directories = $dirs->indexBy('id');

        if ($field_upload_locations == 'all') {
            $viewTypeService = new ViewType();
            $type = $viewTypeService->determineViewType();
        } else {
            // selected something but we don't have that directory? check
            // the system dirs, just in case
            if (!isset($directories[$field_upload_locations]) || empty($directories[$field_upload_locations])) {
                $system_dirs = $this->getSystemUploadDirectories()->indexBy('id');
                if (empty($system_dirs[$field_upload_locations])) {
                    show_error(lang('no_upload_destination'));
                }
                $dir = $system_dirs[$field_upload_locations];
            } else {
                $dir = $directories[$field_upload_locations];
            }
            $viewTypeService = new ViewType();
            $type = $viewTypeService->determineViewType('dir_' . $field_upload_locations, $dir->default_modal_view);
        }

        // show a slightly different message if we have no upload directories
        /*if ($nodirs) {
            if (ee('Permission')->can('create_upload_directories')) {
                $table->setNoResultsText(
                    lang('zero_upload_directories_found'),
                    lang('create_new'),
                    ee('CP/URL')->make('files/uploads/create'),
                    true
                );
            } else {
                $table->setNoResultsText(lang('zero_upload_directories_found'));
            }
        }*/

        $vars = $this->listingsPage($field_upload_locations != 'all' ? $dir : null, $type, true);
        $vars['viewtype'] = $type;
        $vars['toolbar_items'] = [];
        $vars['cp_heading'] = $field_upload_locations == 'all' ? lang('all_files') : sprintf(lang('files_in_directory'), $dir->name);

        if ($requested_directory != 'all' && ee('Request')->get('hasUpload') == '1') {
            /*if (!bool_config_item('file_manager_compatibility_mode') && $dir->allow_subfolders) {
                $vars['toolbar_items']['new_folder'] = [
                    'href' => '#',
                    'rel' => 'modal-new-folder',
                    'class' => 'm-link',
                    'content' => lang('new_folder'),
                ];
            }*/
            if (ee('Permission')->can('upload_new_files') && isset($dir)) {
                $vars['toolbar_items']['upload'] = [
                    'href' => '#',
                    'rel' => 'trigger-upload-to-current-location',
                    'data-upload_location_id' => $dir->getId(),
                    'data-directory_id' => (int) ee('Request')->get('directory_id'),
                    'content' => lang('upload'),
                ];
            }
        }

        // Generate the contents of the new folder modal
        $newFolderModal = ee('View')->make('files/modals/folder')->render([
            'name' => 'modal-new-folder',
            'form_url' => ee('CP/URL')->make('files/createSubdirectory')->compile(),
            'choices' => $this->getUploadLocationsAndDirectoriesDropdownChoices(),
            'selected' => (int) ee('Request')->get('directory_id'),
        ]);

        $html = ee('View')->make('ee:files/index')->render($vars) . $newFolderModal;

        if (!empty(ee('Request')->header('ACCEPT')) && strpos(ee('Request')->header('ACCEPT'), '/json') !== false) {
            return json_encode([
                'html' => $html,
                'url' => $vars['form_url']->compile(),
                'viewManager_saveDefaultUrl' => ee('CP/URL')->make('files/views/save-default', ['upload_id' => null, 'viewtype' => $vars['viewtype']])->compile()
            ]);
        }

        return $html;
    }

    /**
     * Applies a search filter to a Files builder object
     */
    private function search($files)
    {
        if ($search = ee()->input->get('filter_by_keyword')) {
            $files
                ->filterGroup()
                ->filter('title', 'LIKE', '%' . ee()->db->escape_like_str($search) . '%')
                ->orFilter('file_name', 'LIKE', '%' . ee()->db->escape_like_str($search) . '%')
                ->orFilter('mime_type', 'LIKE', '%' . ee()->db->escape_like_str($search) . '%')
                ->endFilterGroup();
        }
    }

    /**
     * Applies sort to a Files builder object
     */
    private function sort($files)
    {
        $sort_col = ee()->input->get('sort_col');

        $sort_map = array(
            'title_or_name' => 'file_name',
            'file_type' => 'mime_type',
            'date_added' => 'upload_date'
        );

        if (array_key_exists((string) $sort_col, $sort_map)) {
            $files->order($sort_map[$sort_col], ee()->input->get('sort_dir'));
        } else {
            $files->order('upload_date', 'desc');
        }
    }

    public function modal()
    {
        $this->base_url = $this->picker->controller;
        ee()->output->_display($this->index());
        exit();
    }

    public function images()
    {
        $this->images = true;
        $this->base_url = $this->picker->base_url . 'images';
        ee()->output->_display($this->index());
        exit();
    }

    /**
     * Return an AJAX response for a particular file ID
     *
     * @param mixed $id
     * @access private
     * @return void
     */
    private function fileInfo($id)
    {
        $file = ee('Model')->get('File', $id)
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->first();

        if (! $file || ! $file->exists()) {
            ee()->output->send_ajax_response(lang('file_not_found'), true);
        }

        $member = ee()->session->getMember();

        if ($file->memberHasAccess($member) === false || $this->access === false) {
            ee()->output->send_ajax_response(lang('unauthorized_access'), true);
        }

        $result = $file->getValues();

        $result['path'] = $file->getAbsoluteURL();
        $result['thumb_path'] = ee('Thumbnail')->get($file)->url;
        $result['isImage'] = $file->isImage();
        $result['isSVG'] = $file->isSVG();

        ee()->output->send_ajax_response($result);
    }

    public function upload()
    {
        $dir_id = ee()->input->get('directory');

        if (empty($dir_id)) {
            show_404();
        }

        $errors = null;

        $result = ee('File')->makeUpload()->uploadTo($dir_id);

        $file = $result['file'];

        if ($result['posted']) {
            $errors = $result['validation_result'];

            if ($result['uploaded']) {
                // The upload process will automatically rename files in the
                // event of a filename collision. Should that happen we need
                // to ask the user if they wish to rename the file or
                // replace the file
                if ($file->file_name != $result['upload_response']['file_data_orig_name']) {
                    $file->save();

                    return $this->overwriteOrRename($file, $result['upload_response']['file_data_orig_name']);
                }

                return $this->saveAndReturn($file);
            }
        }

        $vars = array(
            'required' => true,
            'ajax_validate' => true,
            'has_file_input' => true,
            'base_url' => ee('CP/URL')->make($this->picker->base_url . 'upload', array('directory' => $dir_id)),
            'save_btn_text' => 'btn_upload_file',
            'save_btn_text_working' => 'btn_uploading',
            'sections' => array(),
            'tabs' => array(
                'file_data' => ee('File')->makeUpload()->getFileDataForm($file, $errors),
                'categories' => ee('File')->makeUpload()->getCategoryForm($file, $errors),
            ),
            'cp_page_title' => lang('file_upload')
        );

        $out = ee()->cp->render('_shared/form', $vars, true);
        $out = ee()->cp->render('filepicker:UploadView', array('content' => $out));
        ee()->output->enable_profiler(false);
        ee()->output->_display($out);
        exit();
    }

    public function ajaxUpload()
    {
        $dir_id = ee('Request')->post('upload_location_id') ?: ee('Request')->post('directory');
        $subfolder_id = (int) ee('Request')->post('directory_id');

        if (empty($dir_id)) {
            show_404();
        }

        $errors = null;

        $result = ee('File')->makeUpload()->uploadTo($dir_id, $subfolder_id);

        $file = $result['file'];

        if (isset($result['upload_response']['error'])) {
            return [
                'ajax' => true,
                'body' => [
                    'status' => 'error',
                    'error' => $result['upload_response']['error']
                ]
            ];
        }

        if ($result['posted']) {
            $errors = $result['validation_result'];

            if ($result['uploaded']) {
                // mark the file as newly uploaded in file picker
                ee()->session->set_flashdata('file_id', $file->getId());

                if ($file->file_name != $result['upload_response']['file_data_orig_name']) {
                    $file->save();

                    return [
                        'ajax' => true,
                        'body' => [
                            'status' => 'duplicate',
                            'duplicate' => true,
                            'fileId' => $file->getId(),
                            'originalFileName' => $result['upload_response']['file_data_orig_name']
                        ]
                    ];
                }

                return [
                    'ajax' => true,
                    'body' => [
                        // Inconsistent casing for backwards compatibility
                        'status' => 'success',
                        'title' => $file->file_name,
                        'file_id' => $file->getId(),
                        'file_name' => $file->file_name,
                        'isImage' => $file->isImage(),
                        'isSVG' => $file->isSVG(),
                        'thumb_path' => $file->getAbsoluteThumbnailURL(),
                        'upload_location_id' => $file->upload_location_id
                    ]
                ];
            }
        }

        return [
            'ajax' => true,
            'body' => [
                'status' => 'error',
                'error' => $errors
            ]
        ];
    }

    public function ajaxOverwriteOrRename()
    {
        $file_id = ee('Request')->get('file_id');
        $original_name = ee('Request')->get('original_name');

        $file = ee('Model')->get('File', $file_id)->first();

        return $this->overwriteOrRename($file, $original_name);
    }

    protected function overwriteOrRename($file, $original_name)
    {
        $vars = array(
            'required' => true,
            'base_url' => ee('CP/URL')->make($this->picker->base_url . 'finish-upload/' . $file->file_id),
            'sections' => ee('File')->makeUpload()->getRenameOrReplaceform($file, $original_name),
            'buttons' => array(
                array(
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'finish',
                    'text' => 'btn_finish_upload',
                    'working' => 'btn_saving'
                ),
                array(
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'cancel',
                    'class' => 'draft',
                    'text' => 'btn_cancel_upload',
                    'working' => 'btn_canceling'
                ),
            ),
            'cp_page_title' => lang('file_upload_stopped')
        );

        $out = ee()->cp->render('_shared/form', $vars, true);
        $out = ee()->cp->render('filepicker:UploadView', array('content' => $out));
        ee()->output->enable_profiler(false);
        ee()->output->_display($out);
        exit();
    }

    public function finishUpload($file_id)
    {
        $result = ee('File')->makeUpload()->resolveNameConflict($file_id);

        if (isset($result['cancel']) && $result['cancel']) {
            ee()->output->send_ajax_response($result);
        }

        if ($result['success']) {
            return $this->saveAndReturn($result['params']['file']);
        } else {
            return $this->overwriteOrRename($result['params']['file'], $result['params']['name']);
        }
    }

    protected function saveAndReturn($file)
    {
        if ($file->isNew()) {
            $file->uploaded_by_member_id = ee()->session->userdata('member_id');
            $file->upload_date = ee()->localize->now;
        }

        $file->modified_by_member_id = ee()->session->userdata('member_id');
        $file->modified_date = ee()->localize->now;

        $file->save();

        return $this->fileInfo($file->getId());
    }

    protected function ajaxValidation(ValidationResult $result)
    {
        return ee('Validation')->ajax($result);
    }
}

// EOF
