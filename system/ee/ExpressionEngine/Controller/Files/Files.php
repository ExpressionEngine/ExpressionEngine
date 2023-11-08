<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Files;

use ZipArchive;
use ExpressionEngine\Controller\Files\AbstractFiles as AbstractFilesController;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Library\Data\Collection;
use ExpressionEngine\Model\File\UploadDestination;
use ExpressionEngine\Service\File\ViewType;

/**
 * Files Controller
 */
class Files extends AbstractFilesController
{
    public function index()
    {
        $viewTypeService = new ViewType();
        $view_type = $viewTypeService->determineViewType();

        $this->handleBulkActions(ee('CP/URL')->make('files', ee()->cp->get_url_state()));

        $vars = $this->listingsPage(null, $view_type);
        $vars['viewtype'] = $view_type;

        $this->generateSidebar();
        $headerVars = $this->stdHeader();
        ee()->view->cp_page_title = lang('file_manager');

        $vars['cp_heading'] = lang('all_files');

        $vars['toolbar_items'] = [];

        // Add upload locations to the vars
        $vars['uploadLocationsAndDirectoriesDropdownChoices'] = $headerVars['uploadLocationsAndDirectoriesDropdownChoices'];
        $vars['current_subfolder'] = ee('Request')->get('directory_id');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('files')
        );

        if (AJAX_REQUEST) {
            return array(
                'html' => ee('View')->make('files/index')->render($vars),
                'url' => $vars['form_url']->compile(),
                'viewManager_saveDefaultUrl' => ee('CP/URL')->make('files/views/save-default', ['upload_id' => null, 'viewtype' => $view_type])->compile()
            );
        }

        ee()->cp->render('files/index', $vars);
    }

    public function directory(int $id)
    {
        $dir = ee('Model')->get('UploadDestination', $id)
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->first();

        if (! $dir) {
            show_error(lang('no_upload_destination'));
        }

        if (! $dir->memberHasAccess(ee()->session->getMember())) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! $dir->exists()) {
            $upload_edit_url = ee('CP/URL')->make('files/uploads/edit/' . $dir->id);
            ee('CP/Alert')->makeInline('missing-directory')
                ->asWarning()
                ->cannotClose()
                ->withTitle(sprintf(lang('directory_not_found'), $dir->server_path))
                ->addToBody(sprintf(lang('check_upload_settings'), $upload_edit_url))
                ->now();
        } else {
            // If the directory exists check if it's writable
            if (!$dir->isWritable()) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('dir_not_writable'))
                    ->addToBody(sprintf(lang('dir_not_writable_desc'), $dir->name))
                    ->now();
            }
        }

        $this->handleBulkActions(ee('CP/URL')->make('files/directory/' . $id, ee()->cp->get_url_state()));

        $viewTypeService = new ViewType();
        $view_type = $viewTypeService->determineViewType('dir_' . $id, $dir->default_modal_view);

        $vars = $this->listingsPage($dir, $view_type);
        $vars['viewtype'] = $view_type;

        $vars['dir_id'] = $id;

        $this->generateSidebar($id);
        ee()->view->cp_page_title = lang('file_manager');
        $vars['cp_heading'] = sprintf($dir->name);

        $headerVars = $this->stdHeader();

        $vars['toolbar_items'] = [];
        if (ee('Permission')->can('upload_new_files') && $dir->memberHasAccess(ee()->session->getMember())) {
            $new_folder_modal_name = 'modal_new_folder';

            $vars['toolbar_items']['sync'] = [
                'href' => ee('CP/URL')->make('files/uploads/sync/' . $id),
                'title' => lang('sync'),
                'class' => 'button--secondary icon--sync'
            ];
            if (!bool_config_item('file_manager_compatibility_mode') && $dir->allow_subfolders) {
                $vars['toolbar_items']['new_folder'] = [
                    'href' => '#',
                    'rel' => 'modal-new-folder',
                    'class' => 'm-link button--secondary',
                    'content' => lang('new_folder'),
                ];
            }
            $vars['toolbar_items']['upload'] = [
                'href' => '#',
                'rel' => 'trigger-upload-to-current-location',
                'data-upload_location_id' => $id,
                'data-directory_id' => (int) ee('Request')->get('directory_id'),
                'content' => lang('upload'),
            ];

            // Generate the contents of the new folder modal
            $newFolderModal = ee('View')->make('files/modals/folder')->render([
                'name' => 'modal-new-folder',
                'form_url' => ee('CP/URL')->make('files/createSubdirectory')->compile(),
                'choices' => $headerVars['uploadLocationsAndDirectoriesDropdownChoices'],
                'selected' => $id . '.' . (int) ee('Request')->get('directory_id'),
            ]);

            // Add the modal to the DOM
            ee('CP/Modal')->addModal('modal-new-folder', $newFolderModal);
        }

        // Add upload locations to the vars
        $vars['uploadLocationsAndDirectoriesDropdownChoices'] = $headerVars['uploadLocationsAndDirectoriesDropdownChoices'];
        $vars['current_subfolder'] = ee('Request')->get('directory_id');
        $vars['adapter'] = $dir->adapter;

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('files')->compile() => lang('files'),
            '' => $dir->name
        );

        if (AJAX_REQUEST) {
            return array(
                'html' => ee('View')->make('files/index')->render($vars),
                'url' => $vars['form_url']->compile(),
                'viewManager_saveDefaultUrl' => ee('CP/URL')->make('files/views/save-default', ['upload_id' => $id, 'viewtype' => $view_type])->compile()
            );
        }

        ee()->cp->render('files/index', $vars);
    }

    public function createSubdirectory()
    {
        $dir_ids = explode('.', ee('Request')->post('upload_location'));
        $upload_destination_id = (int) $dir_ids[0];
        $subdirectory_id = isset($dir_ids[1]) ? (int) $dir_ids[1] : 0;

        $subdir_name = ee('Request')->post('folder_name');

        $uploadDirectory = ee('Model')->get('UploadDestination', $upload_destination_id)->first();
        $return_url = ee('CP/URL')->make('files/directory/' . $upload_destination_id);

        if (!ee('Permission')->can('upload_new_files') || !$uploadDirectory->memberHasAccess(ee()->session->getMember()) || bool_config_item('file_manager_compatibility_mode') || !$uploadDirectory->allow_subfolders) {
            show_error(lang('unauthorized_access'), 403);
        }

        if ($subdirectory_id !== 0) {
            $return_url = $return_url->setQueryStringVariable('directory_id', $subdirectory_id);

            $directory = ee('Model')->get('Directory', $subdirectory_id)
                ->filter('upload_location_id', $upload_destination_id)
                ->filter('model_type', 'Directory')
                ->first();

            if (empty($directory)) {
                show_error(lang('unauthorized_access'), 403);
            }

            $filesystem = $directory->getFilesystem();
        } else {
            $filesystem = $uploadDirectory->getFilesystem();
        }

        $subdir = ee('Model')->make('Directory');
        $subdir->file_name = $subdir_name;
        $subdir->upload_location_id = $upload_destination_id;
        $subdir->directory_id = $subdirectory_id;
        $subdir->site_id = $uploadDirectory->site_id;

        //validate before saving on filesystem
        $validation = $subdir->validate();

        if (! $validation->isValid()) {
            $validationErrors = [];
            foreach ($validation->getAllErrors() as $field => $errors) {
                if ($field == 'file_name') {
                    $field = 'folder_name';
                }
                foreach ($errors as $error) {
                    $validationErrors[] = '<b>' . lang($field) . ':</b> ' . $error;
                }
            }
            if (AJAX_REQUEST) {
                ee()->output->send_ajax_response(array('error' => implode('<br>', $validationErrors)));
            }
            ee('CP/Alert')->makeInline('files-form')
                ->asWarning()
                ->withTitle(lang('error_creating_directory'))
                ->addToBody($validationErrors)
                ->defer();

            return ee()->functions->redirect($return_url);
        }

        // Check to see if the directory exists and if it does, return back with an error message
        if ($filesystem->exists($subdir_name)) {
            // Error dir already exists
            if (AJAX_REQUEST) {
                ee()->output->send_ajax_response(array('error' => lang('subfolder_directory_already_exists_desc')));
            }
            ee('CP/Alert')->makeInline('files-form')
                ->asWarning()
                ->withTitle(lang('subfolder_directory_already_exists'))
                ->addToBody(lang('subfolder_directory_already_exists_desc'))
                ->defer();

            return ee()->functions->redirect($return_url);
        }

        // Directory doesnt exist, so attempt to create it
        $created = $filesystem->mkDir($subdir_name);

        // We failed to create the directory, return with an error message
        if (! $created) {
            // Error dir already exists
            if (AJAX_REQUEST) {
                ee()->output->send_ajax_response(array('error' => lang('error_creating_directory')));
            }
            ee('CP/Alert')->makeInline('files-form')
                ->asWarning()
                ->withTitle(lang('error_creating_directory'))
                ->defer();

            return ee()->functions->redirect($return_url);
        }

        // The directory was created, so now lets create it in the DB
        if ($subdir->save()) {
            // Show alert message that we created the directory successfully
            ee('CP/Alert')->makeInline('files-form')
                ->asSuccess()
                ->withTitle(lang('subfolder_directory_created'))
                ->defer();
        }

        if (AJAX_REQUEST) {
            ee()->output->send_ajax_response(['success']);
        }

        ee()->functions->redirect($return_url);
    }

    public function export()
    {
        $files = ee('Model')->get('File')
            ->with('UploadDestination')
            ->fields('file_id')
            ->filter('UploadDestination.module_id', 0)
            ->filter('File.site_id', 'IN', [0, ee()->config->item('site_id')]);

        $this->exportFiles($files->all()->pluck('file_id'));

        // If we got here the download didn't happen due to an error.
        show_error(lang('error_cannot_create_zip'), 500, lang('error_export'));
    }

    public function upload($dir_id)
    {
        if (! ee('Permission')->can('upload_new_files')) {
            show_error(lang('unauthorized_access'), 403);
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
                    ee()->session->set_flashdata('original_name', $result['upload_response']['file_data_orig_name']);
                    ee()->functions->redirect(ee('CP/URL')->make('files/finish-upload/' . $file->file_id));
                }

                $this->saveFileAndRedirect($file, true);
            }
        }

        $vars = array(
            'required' => true,
            'ajax_validate' => true,
            'has_file_input' => true,
            'base_url' => ee('CP/URL')->make('files/upload/' . $dir_id),
            'save_btn_text' => 'btn_upload_file',
            'save_btn_text_working' => 'btn_saving',
            'tabs' => array(
                'file_data' => ee('File')->makeUpload()->getFileDataForm($file, $errors),
                'categories' => ee('File')->makeUpload()->getCategoryForm($file, $errors),
            ),
            'sections' => array(),
        );

        $this->generateSidebar($dir_id);
        $this->stdHeader($dir_id);
        ee()->view->cp_page_title = lang('file_upload');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('files')->compile() => lang('files'),
            ee('CP/URL')->make('files/directory/' . $dir_id)->compile() => ee('Model')->get('UploadDestination', $dir_id)->fields('name')->first()->name,
            '' => lang('upload')
        );

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Generate post re-assignment view if applicable
     *
     * @access public
     * @return void
     */
    public function confirm()
    {
        $vars = array();
        $selected = ee('Request')->post('selection');
        $vars['selected'] = $selected;
        $desc = lang('move_toggle_to_confirm');

        $files = ee('Model')->get('FileSystemEntity', $selected)
            ->with('FileCategories')
            ->with('FileEntries')
            ->all();

        $usageCount = 0;

        if ($files->count() == 1) {
            foreach ($files as $file) {
                $edit_url = ee('CP/URL')->make('files/file/view/' . $file->file_id . '#tab=t-usage');
            }
        }

        foreach ($files as $file) {
            if ($file->model_type == 'Directory') {
                $countFiles = ee('db')->from('files')->where('directory_id', $file->file_id)->count_all_results();
                if ($countFiles > 0) {
                    $title = lang('folder_not_empty');
                    if (ee('Request')->post('bulk_action') == 'move') {
                        $desc = lang('all_files_in_folder_will_be_moved') . BR . $desc;
                    } else {
                        $desc = lang('all_files_in_folder_will_be_deleted') . BR . $desc;
                    }

                    continue;
                }
            }
            $usageCount += $file->FileCategories->count() + $file->FileEntries->count();
        }

        if ($usageCount > 0) {
            if (isset($edit_url)) {
                $title = sprintf(lang('file_is_in_use'), $edit_url, $usageCount);
            } else {
                $title = lang('files_is_in_use');
            }
        }

        if (isset($title)) {
            $vars['fieldset'] = [
                'group' => 'delete-confirm',
                'setting' => [
                    'title' => $title,
                    'desc' => $desc,
                    'fields' => [
                        'confirm' => [
                            'type' => 'toggle',
                            'value' => 0,
                        ]
                    ]
                ]
            ];
        }

        ee()->cp->render('files/delete_confirm', $vars);
    }

    private function overwriteOrRename($file, $original_name)
    {
        $vars = array(
            'required' => true,
            'base_url' => ee('CP/URL')->make('files/finish-upload/' . $file->file_id),
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
        );

        $this->generateSidebar($file->upload_location_id);
        $this->stdHeader();
        ee()->view->cp_page_title = lang('file_upload_stopped');

        ee()->cp->add_js_script(array(
            'file' => array('cp/files/overwrite_rename'),
        ));

        ee()->cp->render('settings/form', $vars);
    }

    public function finishUpload($file_id)
    {
        if (! ee('Permission')->can('upload_new_files')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $file = ee('Model')->get('File', $file_id)
            ->with('UploadDestination')
            ->first();

        if (! $file) {
            show_error(lang('no_file'));
        }

        if (! $file->memberHasAccess(ee()->session->getMember())) {
            show_error(lang('unauthorized_access'), 403);
        }

        $original_name = ee()->session->flashdata('original_name');
        if ($original_name) {
            return $this->overwriteOrRename($file, $original_name);
        }

        $result = ee('File')->makeUpload()->resolveNameConflict($file_id);

        if (isset($result['cancel']) && $result['cancel']) {
            ee()->functions->redirect(ee('CP/URL')->make('files/directory/' . $file->upload_location_id));

            return;
        }

        if ($result['success']) {
            $alert = null;
            if (isset($result['warning'])) {
                $alert = ee('CP/Alert')->makeInline('metadata')
                    ->asWarning()
                    ->addToBody($result['warning']);
            }
            $this->saveFileAndRedirect($result['params']['file'], true, $alert);
        } else {
            $this->overwriteOrRename($result['params']['file'], $result['params']['name']);
        }
    }

    /**
     * Rename a file/folder
     *
     * @access public
     * @return void
     */
    private function rename()
    {
        $selected = ee('Request')->post('selection');
        //can only rename one file at a time
        if (count($selected) != 1) {
            ee('CP/Alert')->makeInline('files-form-errors')
                ->asWarning()
                ->withTitle(lang('could_not_rename'))
                ->addToBody(lang('one_rename_at_a_time'))
                ->defer();
            return false;
        }

        $file = ee('Model')->get('Directory', $selected[0])->first();
        if (empty($file)) {
            ee('CP/Alert')->makeInline('files-form-errors')
                ->asWarning()
                ->withTitle(lang('could_not_rename'))
                ->addToBody(lang('file_not_found'))
                ->defer();
            return false;
        }

        //do they have access to target destination?
        $target = $targetUploadLocation = ee('Model')->get('UploadDestination', $file->upload_location_id)->first();
        if (empty($targetUploadLocation) || ! ee('Permission')->can('edit_files') || ! $targetUploadLocation->memberHasAccess(ee()->session->getMember())) {
            show_error(lang('unauthorized_access'), 403);
        }

        if ($file->directory_id != 0) {
            $target = $targetDirectory = ee('Model')->get('Directory', $file->directory_id)
                ->filter('upload_location_id', $targetUploadLocation->getId())
                ->first();
            if (empty($targetDirectory)) {
                show_error(lang('unauthorized_access'), 403);
            }
        }

        $oldPath = $file->getAbsolutePath();
        $oldName = $file->file_name;
        $file->file_name = ee('Request')->post('new_name');
        $file->title = ee('Request')->post('new_name');

        //validate before saving on filesystem
        $validation = $file->validate();

        if (!$validation->isValid()) {
            $validationErrors = [];
            foreach ($validation->getAllErrors() as $field => $errors) {
                if ($field == 'file_name') {
                    $field = 'folder_name';
                }
                foreach ($errors as $error) {
                    $validationErrors[] = '<b>' . lang($field) . ':</b> ' . $error;
                }
            }
            ee('CP/Alert')->makeInline('files-form')
                ->asWarning()
                ->withTitle(lang('could_not_rename'))
                ->addToBody($validationErrors)
                ->defer();

            return false;
        }

        //does the file with same name already exist?
        if ($target->getFilesystem()->exists($file->file_name)) {
            ee('CP/Alert')->makeInline('files-form')
                ->asWarning()
                ->withTitle(lang('could_not_rename'))
                ->addToBody(lang('error_renaming_already_exists'))
                ->defer();
            return false;
        }

        $renamed = $file->UploadDestination->getFilesystem()->rename(
            $oldPath,
            $file->getAbsolutePath()
        );

        if ($renamed) {
            $file->save();
            ee('CP/Alert')->makeInline('files-form')
                ->asSuccess()
                ->withTitle(lang('rename_success'))
                ->addToBody(sprintf(lang('rename_success_desc'), $oldName, $file->file_name))
                ->defer();
        } else {
            ee('CP/Alert')->makeInline('files-form-errors')
                ->asWarning()
                ->withTitle(lang('could_not_rename'))
                ->addToBody(lang('unexpected_error'))
                ->defer();
        }
    }

    /**
     * Move the file or folder to another subdirectory
     *
     * @return void
     */
    private function move()
    {
        $dir_ids = explode('.', ee('Request')->post('upload_location'));
        $upload_destination_id = (int) $dir_ids[0];
        $subdirectory_id = isset($dir_ids[1]) ? (int) $dir_ids[1] : 0;
        $selected = ee('Request')->post('selection');

        //do they have access to target destination and subfolder exists?
        $target = $targetUploadLocation = ee('Model')->get('UploadDestination', $upload_destination_id)->first();
        if (empty($targetUploadLocation) || ! ee('Permission')->can('edit_files') || ! $targetUploadLocation->memberHasAccess(ee()->session->getMember())) {
            show_error(lang('unauthorized_access'), 403);
        }
        $targetPath = $target->server_path;

        if ($subdirectory_id != 0) {
            $target = $targetDirectory = ee('Model')->get('Directory', $subdirectory_id)
                ->filter('upload_location_id', $targetUploadLocation->getId())
                ->first();
            if (empty($targetDirectory)) {
                show_error(lang('unauthorized_access'), 403);
            }
            $targetPath = $target->getAbsolutePath();
        }

        //directory cannot become child of itself - prepare the data
        $subdirectoryParents = [];
        $parentDirectoryId = $subdirectory_id;
        while ($parentDirectoryId != 0) {
            $parentDirectory = ee('Model')->get('Directory', $parentDirectoryId)->fields('file_id', 'directory_id')->first();
            $parentDirectoryId = $parentDirectory->directory_id;
            $subdirectoryParents[] = $parentDirectoryId;
        }

        $files = ee('Model')->get('FileSystemEntity', $selected)->with('UploadDestination')->all();
        $names = array();
        $errors = array();
        foreach ($files as $file) {
            //are they not in target place already?
            if ($file->upload_location_id == $upload_destination_id && $file->directory_id == $subdirectory_id) {
                $errors[$file->file_name] = lang('error_moving_already_there');
                continue;
            }

            //does the file with same name already exist?
            if ($target->getFilesystem()->exists($file->file_name)) {
                $errors[$file->file_name] = lang('error_moving_already_exists');
                continue;
            }

            //moving to self?
            if ($file->isDirectory() && $file->file_id == $subdirectory_id) {
                $errors[$file->file_name] = lang('error_moving_directory_cannot_be_own_child');
                continue;
            }

            //avoid recursion - the directory cannot become child of itself
            if ($file->isDirectory() && in_array($file->file_id, $subdirectoryParents)) {
                $errors[$file->file_name] = lang('error_moving_directory_cannot_be_own_child');
                continue;
            }

            $targetFilesystem = ($file->UploadDestination->id == $targetUploadLocation->id) ? null : $targetUploadLocation->getFilesystem();
            $success = $file->UploadDestination->getFilesystem()->move(
                $file->getAbsolutePath(),
                rtrim($targetPath, '\\/') . '/' . $file->file_name,
                $targetFilesystem
            );

            if ($success) {
                // Update files within a directory if it is changing upload locations
                if ($file->isDirectory() && !is_null($targetFilesystem) && $childIds = $file->getChildIds()) {
                    ee()->db->where_in('file_id', $childIds);
                    ee()->db->update('files', ['upload_location_id' => $targetUploadLocation->id]);
                }

                // Cleanup any generated files in previous location before updating the location
                if (!$file->isDirectory()) {
                    $file->deleteGeneratedFiles();
                }

                $file->upload_location_id = $targetUploadLocation->id;
                $file->directory_id = $subdirectory_id;
                $file->save();
                $names[] = $file->title;
            } else {
                $errors[$file->file_name] = lang('unexpected_error');
            }
        }

        if (! empty($names)) {
            ee('CP/Alert')->makeInline('files-form')
                ->asSuccess()
                ->withTitle(lang('files_moved'))
                ->addToBody($names)
                ->defer();
        }

        if (! empty($errors)) {
            ee('CP/Alert')->makeInline('files-form-errors')
                ->asWarning()
                ->withTitle(lang('some_files_not_moved'))
                ->addToBody($errors)
                ->defer();
        }

        /*$return_url = ee('CP/URL')->make('files/directory/' . $targetUploadLocation->getId());
        if (! empty($subdirectory_id)) {
            $return_url->setQueryStringVariable('directory_id', $subdirectory_id);
        }
        return ee()->functions->redirect($return_url);*/
    }

    public function rmdir()
    {
        if (! ee('Permission')->can('delete_upload_directories')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $id = ee()->input->post('dir_id');
        $dir = ee('Model')->get('UploadDestination', $id)
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->first();

        if (! $dir) {
            show_error(lang('no_upload_destination'));
        }

        if (! $dir->memberHasAccess(ee()->session->getMember())) {
            show_error(lang('unauthorized_access'), 403);
        }

        $dir->Files->delete(); // @TODO Remove this once cascading works
        $dir->delete();

        ee('CP/Alert')->makeInline('files-form')
            ->asSuccess()
            ->withTitle(lang('upload_directory_deleted'))
            ->addToBody(sprintf(lang('upload_directory_deleted_desc'), $dir->name))
            ->defer();

        $return_url = ee('CP/URL')->make('files');

        if (ee()->input->post('return')) {
            $return_url = ee('CP/URL')->decodeUrl(ee()->input->post('return'));
        }

        ee()->functions->redirect($return_url);
    }

    /**
     * Checks for a bulk_action submission and if present will dispatch the
     * correct action/method.
     *
     * @param string $redirect_url The URL to redirect to once the action has been
     *   performed
     * @return void
     */
    private function handleBulkActions($redirect_url)
    {
        $action = ee()->input->post('bulk_action');

        switch ($action) {
            case 'rename':
                $this->rename(ee()->input->post('selection'));
                break;
            case 'remove':
                $this->remove(ee()->input->post('selection'));
                break;
            case 'move':
                $this->move(ee()->input->post('selection'));
                break;
            case 'download':
                $this->exportFiles(ee()->input->post('selection'));
                break;
            default:
                return;
        }

        ee()->functions->redirect($redirect_url);
    }

    /**
     * Generates a ZipArchive and forces a download
     *
     * @param  array $file_ids An array of file ids
     * @return void If the ZipArchive cannot be created it returns early,
     *   otherwise it exits.
     */
    private function exportFiles($file_ids)
    {
        if (! is_array($file_ids)) {
            $file_ids = array($file_ids);
        }

        // Create the Zip Archive
        $zipfilename = tempnam(sys_get_temp_dir(), '');
        $zip = new ZipArchive();
        if ($zip->open($zipfilename, ZipArchive::OVERWRITE) !== true) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('error_export'))
                ->addToBody(lang('error_cannot_create_zip'))
                ->now();

            return;
        }

        $member = ee()->session->getMember();

        // Loop through the files and add them to the zip
        $files = ee('Model')->get('File', $file_ids)
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->all()
            ->filter(function ($file) use ($member) {
                return $file->memberHasAccess($member);
            });

        foreach ($files as $file) {
            if (! $file->exists()) {
                continue;
            }

            $res = $zip->addFile($file->getAbsolutePath(), $file->file_name);

            if ($res === false) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('error_export'))
                    ->addToBody(sprintf(lang('error_cannot_add_file_to_zip'), $file->title))
                    ->now();

                return;

                $zip->close();
                unlink($zipfilename);
            }
        }

        $zip->close();

        $data = file_get_contents($zipfilename);
        unlink($zipfilename);

        ee()->load->helper('download');
        force_download('ExpressionEngine-files-export.zip', $data);
    }

    private function remove($file_ids)
    {
        if (! ee('Permission')->can('delete_files')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($file_ids)) {
            $file_ids = array($file_ids);
        }

        $member = ee()->session->getMember();

        $files = ee('Model')->get('FileSystemEntity', $file_ids)
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->all()
            ->filter(function ($file) use ($member) {
                return $file->memberHasAccess($member);
            });

        $names = array();
        $message = lang('files_deleted_desc');
        foreach ($files as $file) {
            $names[] = $file->title;
            if ($file->isDirectory()) {
                ee('Model')->get('Directory', $file->getId())->delete();
                $message = lang('folder_deleted_desc'); // directories are only deleted one-by-one
            } else {
                ee('Model')->get('File', $file->getId())->delete();
            }
        }

        ee('CP/Alert')->makeInline('files-form')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody($message)
            ->addToBody($names)
            ->defer();
    }
}

// EOF
