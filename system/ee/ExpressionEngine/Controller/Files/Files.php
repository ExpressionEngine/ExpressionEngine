<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
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
            ->filter('site_id', ee()->config->item('site_id'))
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

        $vars['destinations'] = [];
        $sub_dir_id = (int) ee('Request')->get('directory_id') ?: null;

        // Compile the list of subdirectories for the new folder drop down
        $uploadDestinations = ee('Model')->get('UploadDestination')->filter('module_id', 0)->fields('id', 'name')->all();
        foreach ($uploadDestinations as $uploadDestination) {
            $vars['destinations'][] = [
                'id' => $uploadDestination->id,
                'value' => $uploadDestination->name,
                'selected' => ($id === $uploadDestination->id && is_null($sub_dir_id)),
            ];

            // Get our subfolders from the upload destination
            $subDestinations = $uploadDestination->getSelectFromSubdirectories();

            foreach ($subDestinations as &$subDestination) {
                $subDestination['selected'] = (($id === $uploadDestination->id) && ($sub_dir_id === $subDestination['id']));
                $subDestination['id'] = $uploadDestination->id . '-' . $subDestination['id'];
            }

            $vars['destinations'] = array_merge($vars['destinations'], $subDestinations);
        }

        $vars['toolbar_items'] = [];
        if (ee('Permission')->can('upload_new_files') && $dir->memberHasAccess(ee()->session->getMember())) {
            $new_folder_modal_name = 'modal_new_folder';

            $vars['toolbar_items']['sync'] = [
                'href' => ee('CP/URL')->make('files/uploads/sync/' . $id),
                'title' => lang('sync'),
                'class' => 'button--secondary icon--sync'
            ];
            if ($dir->allow_subfolders) {
                $vars['toolbar_items']['new_folder'] = [
                    'href' => '#',
                    'rel' => 'modal-new-folder',
                    'class' => 'm-link',
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
                'form_url'=> ee('CP/URL')->make('files/createSubdirectory')->compile(),
                'destinations' => $vars['destinations'],
                'choices' => $headerVars['uploadLocationsAndDirectoriesDropdownChoices'],
                'selected' => !empty(ee('Request')->get('directory_id')) ? ee('Request')->get('directory_id') : $uploadDestination->getId(),
                'selected_subfolder' => ee('Request')->get('directory_id')
            ]);

            // Add the modal to the DOM
            ee('CP/Modal')->addModal('modal-new-folder', $newFolderModal);
        }

        // Add upload locations to the vars
        $vars['uploadLocationsAndDirectoriesDropdownChoices'] = $headerVars['uploadLocationsAndDirectoriesDropdownChoices'];
        $vars['current_subfolder'] = ee('Request')->get('directory_id');

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
        $dir_ids = explode('-', ee('Request')->post('upload_location'));
        $upload_destination_id = (int) $dir_ids[0];
        $subdirectory_id = isset($dir_ids[1]) ? (int) $dir_ids[1] : 0;
        // TODO validate this
        $subdir_name = ee('Request')->post('folder_name');

        $uploadDirectory = ee('Model')->get('UploadDestination', $upload_destination_id)->first();
        $return_url = ee('CP/URL')->make('files/directory/' . $upload_destination_id);

        if (!ee('Permission')->can('upload_new_files') || !$uploadDirectory->memberHasAccess(ee()->session->getMember()) || !$uploadDirectory->allow_subfolders) {
            show_error(lang('unauthorized_access'), 403);
        }

        if ($subdirectory_id !== 0) {
            $return_url = $return_url->setQueryStringVariable('directory_id', $subdirectory_id);

            $directory = ee('Model')->get('Directory', $subdirectory_id)
                ->filter('upload_location_id', $upload_destination_id)
                ->filter('model_type', 'Directory')
                ->first();

            $filesystem = $directory->getFilesystem();
        } else {
            $filesystem = $uploadDirectory->getFilesystem();
        }

        $subdir = ee('Model')->make('Directory');
        $subdir->file_name = $subdir_name;
        $subdir->upload_location_id = $upload_destination_id;
        $subdir->directory_id = $subdirectory_id;

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
            ee('CP/Alert')->makeInline('files-form')
                ->asWarning()
                ->withTitle(lang('error_creating_directory'))
                ->defer();

            return ee()->functions->redirect($return_url);
        }

        // The directory was created, so now lets create it in the DB
        $subdir->save();

        // Show alert message that we created the directory successfully
        ee('CP/Alert')->makeInline('files-form')
            ->asSuccess()
            ->withTitle(lang('subfolder_directory_created'))
            ->defer();

        ee()->functions->redirect($return_url);
    }

    public function export()
    {
        $files = ee('Model')->get('File')
            ->with('UploadDestination')
            ->fields('file_id')
            ->filter('UploadDestination.module_id', 0)
            ->filter('site_id', ee()->config->item('site_id'));

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
     * Rename a file/folder
     *
     * @access public
     * @return void
     */
    public function rename()
    {
        echo "<pre>";
        var_dump('rename file/dir');
        exit;

        // TODO:
        // Validate the rename
        // Rename the folder on the filesystem
        // update database
        // redirect
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
        $desc = lang('move_toggle_to_confirm_delete');

        $files = ee('Model')->get('FileSystemEntity', $selected)
            ->with('FileCategories')
            ->with('FileEntries')
            ->all();

        $usageCount = 0;
        foreach ($files as $file) {
            if ($file->model_type == 'Directory') {
                $countFiles = ee('db')->from('files')->where('directory_id', $file->file_id)->count_all_results();
                if ($countFiles > 0) {
                    $title = lang('folder_not_empty');
                    $desc = lang('all_files_in_folder_will_be_deleted') . ' ' . $desc;

                    continue;
                }
            }
            $usageCount += $file->FileCategories->count() + $file->FileEntries->count();
        }

        if ($usageCount > 0) {
            $title = lang('file_is_in_use');
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

    public function move()
    {
        // We are going to move Folder2/test/testing123 to Folder/test2/testing123

        // subdirectory 37 is currently located here:
        // Folder2/test/testing123
        $subdirectory_id = 37;

        // subdirectory 34 is currently located here:
        // Folder/test2
        $move_to_directory_id = 34;
        // $move_to_directory_id = 36; // move it back

        $directory = ee('Model')->get('Directory', $subdirectory_id)
            ->filter('model_type', 'Directory')
            ->first();

        $moveToDir = ee('Model')->get('Directory', $move_to_directory_id)
            ->filter('model_type', 'Directory')
            ->first();

        $return_url = ee('CP/URL')->make('files/directory/' . $directory->upload_location_id)
            ->setQueryStringVariable('directory_id', $directory->file_id);

        if ($moveToDir->getFilesystem()->exists($directory->file_name)) {
            // Error dir already exists
            ee('CP/Alert')->makeInline('files-form')
                ->asWarning()
                ->withTitle(lang('error_moving_directory_directory_already_exists'))
                ->defer();

            return ee()->functions->redirect($return_url);
        }

        // mvoe the folder
        ee('Filesystem')->rename(
            $directory->getAbsolutePath(),
            $moveToDir->getAbsolutePath() . '/' . $directory->file_name
        );

        $directory->directory_id = $moveToDir->file_id;
        $directory->save();

        return ee()->functions->redirect($return_url);
    }

    public function rmdir()
    {
        if (! ee('Permission')->can('delete_upload_directories')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $id = ee()->input->post('dir_id');
        $dir = ee('Model')->get('UploadDestination', $id)
            ->filter('site_id', ee()->config->item('site_id'))
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

        if (! $action) {
            return;
        } elseif ($action == 'remove') {
            $this->remove(ee()->input->post('selection'));
        } elseif ($action == 'download') {
            $this->exportFiles(ee()->input->post('selection'));
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
        if ($zip->open($zipfilename, ZipArchive::CREATE) !== true) {
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
            ->filter('site_id', ee()->config->item('site_id'))
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
            ->filter('site_id', ee()->config->item('site_id'))
            ->all()
            ->filter(function ($file) use ($member) {
                return $file->memberHasAccess($member);
            });

        $names = array();
        foreach ($files as $file) {
            $names[] = $file->title;
            $file->delete();
        }

        ee('CP/Alert')->makeInline('files-form')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(lang('files_deleted_desc'))
            ->addToBody($names)
            ->defer();
    }
}

// EOF
