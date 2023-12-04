<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\File;

use ExpressionEngine\Model\File\File as FileModel;
use ExpressionEngine\Model\Content\FieldFacade;
use ExpressionEngine\Model\Content\Display\FieldDisplay;
use ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 * File Service Upload
 */
class Upload
{
    /**
     * Creates and returns the HTML to add or edit a file.
     *
     * @param obj $file A File Model object
     * @param array $errors An array of errors
     * @return string HTML
     */
    public function getFileDataForm(FileModel $file, $errors)
    {
        $html = '';

        if (! $file->isNew()) {
            $dimensions = explode(" ", $file->file_hw_original);
            $metadata = [
                'name' => $file->file_name,
                'size' => ee('Format')->make('Number', $file->file_size)->bytes(),
                'file_type' => lang('type_' . $file->file_type)
            ];
            if ($file->isImage()) {
                $metadata['dimensions'] = (count($dimensions) > 1) ? $dimensions[0] . 'x' . $dimensions[1] . ' px' : '';
            }
            $metadata = array_merge($metadata, [
                'uploaded_by' => ($file->uploaded_by_member_id && $file->UploadAuthor) ? $file->UploadAuthor->getMemberName() : '',
                'date_added' => ee()->localize->human_time($file->upload_date),
                'modified_by' => ($file->modified_by_member_id && $file->ModifyAuthor) ? $file->ModifyAuthor->getMemberName() : '',
                'date_modified' => ee()->localize->human_time($file->modified_date)
            ]);
        }

        $sections = array(
            array(
                array(
                    'title' => 'file',
                    'desc' => 'file_desc',
                    'fields' => array(
                        'file' => array(
                            'type' => 'file',
                            'required' => true
                        )
                    )
                ),
                array(
                    'fields' => array(
                        'f_metadata' => array(
                            'type' => 'html',
                            'content' => ! $file->isNew() ? ee('View')->make('ee:files/file-data')->render(['data' => $metadata]) : ''
                        )
                    )
                ),
                array(
                    'title' => 'title',
                    'fields' => array(
                        'title' => array(
                            'type' => 'text',
                            'value' => $file->title
                        )
                    )
                ),
                array(
                    'title' => 'description',
                    'fields' => array(
                        'description' => array(
                            'type' => 'textarea',
                            'value' => $file->description
                        )
                    )
                ),
                array(
                    'title' => 'credit',
                    'fields' => array(
                        'credit' => array(
                            'type' => 'text',
                            'value' => $file->credit
                        )
                    )
                ),
                array(
                    'title' => 'location',
                    'fields' => array(
                        'location' => array(
                            'type' => 'text',
                            'value' => $file->location
                        )
                    )
                ),
            )
        );

        // Remove the file field when we are editing
        if (! $file->isNew()) {
            unset($sections[0][0]);
        }

        foreach ($sections as $name => $settings) {
            $html .= ee('View')->make('_shared/form/section')
                ->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
        }

        return $html;
    }

    /**
     * Creates and returns the HTML to add or edit a file's categories.
     *
     * @param obj $file A File Model object
     * @param array $errors An array of errors
     * @return string HTML
     */
    public function getCategoryForm(FileModel $file, $errors)
    {
        ee()->lang->loadfile('content');
        $html = '';

        $sections = array(
            array(
            )
        );

        $cat_groups = $file->UploadDestination->CategoryGroups;

        if (count($cat_groups) == 0) {
            $url = ee('CP/URL', 'files/uploads/edit/' . $file->UploadDestination->getId())->compile();

            return ee('CP/Alert')->makeInline('empty-category-tab')
                ->asWarning()
                ->cannotClose()
                ->withTitle(lang('no_categories_assigned'))
                ->addToBody(sprintf(lang('no_categories_assigned_file_desc'), $url))
                ->render();
        }

        foreach ($cat_groups as $cat_group) {
            $metadata = $cat_group->getFieldMetadata();
            $metadata['categorized_object'] = $file;
            $metadata['field_instructions'] = lang('file_categories_desc');
            $metadata['editable'] = false;

            if ($cat_groups->count() == 1) {
                $metadata['field_label'] = lang('categories');
            }

            $field_id = 'categories[cat_group_id_' . $cat_group->getId() . ']';
            $facade = new FieldFacade($field_id, $metadata);
            $facade->setName($field_id);

            $field = new FieldDisplay($facade);

            $field = array(
                'title' => $field->getLabel(),
                'desc' => $field->getInstructions(),
                'fields' => array(
                    $facade->getId() => array(
                        'type' => 'html',
                        'content' => $field->getForm()
                    )
                )
            );

            $sections[0][] = $field;
        }

        foreach ($sections as $name => $settings) {
            $html .= ee('View')->make('_shared/form/section')
                ->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
        }

        ee('Category')->addCategoryJS();

        return $html;
    }

    /**
     * Creates and returns the HTML to rename or overwrite a file.
     *
     * @param obj $file A File Model object
     * @param string $original_name The original name of the file
     * @return string HTML
     */
    public function getRenameOrReplaceform(FileModel $file, $original_name)
    {
        $alert = ee('CP/Alert')->get('shared-form');

        if (empty($alert)) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('file_conflict'))
                ->addToBody(sprintf(lang('file_conflict_desc'), $original_name))
                ->cannotClose()
                ->now();
        }

        $checked_radio = ee()->input->post('upload_options') ?: 'append';

        $sections = array(
            array(
                array(
                    'title' => 'upload_options',
                    'fields' => array(
                        'original_name' => array(
                            'type' => 'hidden',
                            'value' => $original_name
                        ),
                        'upload_options_1' => array(
                            'type' => 'radio',
                            'name' => 'upload_options',
                            'choices' => array(
                                'append' => sprintf(lang('append'), $file->file_name),
                                'rename' => 'rename'
                            ),
                            'value' => $checked_radio,
                            'encode' => false,
                        ),
                        'rename_custom' => array(
                            'type' => 'text',
                            'placeholder' => $file->file_name,
                            'value' => ee()->input->post('rename_custom'),
                            'attrs' => 'onfocus="$(this).prev().children().prop(\'checked\', true).trigger(\'change\')"'
                        ),
                        'upload_options_2' => array(
                            'type' => 'radio',
                            'name' => 'upload_options',
                            'choices' => array(
                                'replace' => 'replace',
                            ),
                            'value' => $checked_radio,
                            'encode' => false,
                        )
                    )
                )
            )
        );

        return $sections;
    }

    public function uploadTo($upload_location_id, $directory_id = 0)
    {
        $uploadLocation = ee('Model')->get('UploadDestination', $upload_location_id)
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->first();

        if (! $uploadLocation) {
            show_error(lang('no_upload_destination'));
        }

        // check EE permissions on upload location
        if (! $uploadLocation->memberHasAccess(ee()->session->getMember())) {
            show_error(lang('unauthorized_access'), 403);
        }

        // check upload location exists
        if (! $uploadLocation->exists()) {
            if (AJAX_REQUEST) {
                show_error(lang('invalid_upload_destination'), 404);
            }
            $upload_edit_url = ee('CP/URL')->make('files/uploads/edit/' . $uploadLocation->id);
            ee('CP/Alert')->makeStandard()
                ->asIssue()
                ->withTitle(lang('file_not_found'))
                ->addToBody(sprintf(lang('directory_not_found'), $uploadLocation->name))
                ->addToBody(sprintf(lang('check_upload_settings'), $upload_edit_url))
                ->now();

            show_404();
        }

        $posted = false;

        // Check file system permissions on upload location
        if (! $uploadLocation->isWritable()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('dir_not_writable'))
                ->addToBody(sprintf(lang('dir_not_writable_desc'), $uploadLocation->name))
                ->now();
        }

        // check subfolder permissions
        if ($directory_id != 0) {
            $directory = ee('Model')->get('Directory', $directory_id)->filter('upload_location_id', $uploadLocation->id)->first();
            if (! $directory) {
                show_error(lang('invalid_subfolder'));
            }

            if (! $directory->exists()) {
                show_error(lang('subfolder_not_exists'));
            }

            if (! $directory->isWritable()) {
                show_error(lang('subfolder_not_writable'));
            }
        }

        $file = ee('Model')->make('File');
        $file->UploadDestination = $uploadLocation;

        $result = $this->validateFile($file);

        $upload_response = array();
        $uploaded = false;

        if ($result instanceof ValidationResult) {
            $posted = true;

            if ($result->isValid()) {
                // This is going to get ugly...apologies

                // PUNT! @TODO Break away from the old Filemanger Library
                ee()->load->library('filemanager');
                $upload_response = ee()->filemanager->upload_file($upload_location_id, 'file', false, $directory_id);
                if (isset($upload_response['error'])) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asIssue()
                        ->withTitle(lang('upload_filedata_error'))
                        ->addToBody($upload_response['error'])
                        ->now();
                } else {
                    $uploaded = true;
                    $file = ee('Model')->get('File', $upload_response['file_id'])->first();

                    $file->upload_location_id = $upload_location_id;
                    $file->directory_id = $directory_id;
                    $file->site_id = ee()->config->item('site_id');

                    // Validate handles setting properties...
                    $this->validateFile($file);
                }
            }
        }

        return array(
            'file' => $file,
            'posted' => $posted,
            'uploaded' => $uploaded,
            'validation_result' => $result,
            'upload_response' => $upload_response
        );
    }

    public function resolveNameConflict($file_id)
    {
        $file = ee('Model')->get('File', $file_id)
            ->with('UploadDestination')
            ->first();

        if (! $file) {
            show_error(lang('no_file'));
        }

        if (! $file->memberHasAccess(ee()->session->getMember())) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (ee()->input->post('submit') == 'cancel') {
            $file->delete();

            return array('cancel' => true);
        }

        $upload_options = ee()->input->post('upload_options');
        $original_name = ee()->input->post('original_name');

        $result = array(
            'success' => false,
            'params' => array(
                'file' => $file,
                'name' => $original_name
            )
        );

        if ($upload_options == 'rename') {
            $new_name = ee()->input->post('rename_custom');

            if (empty($new_name)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('file_conflict'))
                    ->addToBody(lang('no_filename'))
                    ->now();

                return $result;
            }

            $original_extension = substr($original_name, strrpos($original_name, '.'));
            $new_extension = substr($new_name, strrpos($new_name, '.'));

            if ($new_extension != $original_extension) {
                $new_name .= $original_extension;
            }

            if ($file->UploadDestination->getFilesystem()->exists($new_name)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('file_conflict'))
                    ->addToBody(lang('file_exists_replacement_error'))
                    ->now();

                $result['params']['name'] = $new_name;

                return $result;
            }

            // PUNT! @TODO Break away from the old Filemanger Library
            ee()->load->library('filemanager');
            $rename_file = ee()->filemanager->rename_file($file_id, $new_name, $original_name);

            if (! $rename_file['success']) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('file_conflict'))
                    ->addToBody($rename_file['error'])
                    ->now();

                return $result;
            }

            $title = ($file->title == $file->file_name) ? null : $file->title;

            // The filemanager updated the database, and the saveFileAndRedirect
            // should have fresh data for the alert.
            $file = ee('Model')->get('File', $file_id)->first();

            // The filemanager will, on occasion, alter the title of the file
            // even if we had something set. It's annoying but happens.
            if ($title) {
                $file->title = $title;
                $file->save();
            }

            $result['params']['file'] = $file;
        } elseif ($upload_options == 'replace') {
            $original = ee('Model')->get('File')
                ->filter('file_name', $original_name)
                ->filter('site_id', $file->site_id)
                ->filter('upload_location_id', $file->upload_location_id)
                ->first();

            if (! $original) {
                $src = $file->getAbsolutePath();

                // The default is to use the file name as the title, and if we
                // did that then we should update it since we are replacing.
                if ($file->title == $file->file_name) {
                    $file->title = $original_name;
                }

                $file->file_name = $original_name;
                $file->save();

                $file->getFilesystem()->forceCopy($src, $file->getAbsolutePath());
            } else {
                if (
                    ($file->description && ($file->description != $original->description))
                    || ($file->credit && ($file->credit != $original->credit))
                    || ($file->location && ($file->location != $original->location))
                    || ($file->Categories->count() > 0 && ($file->Categories->count() != $original->Categories->count()))
                ) {
                    $result['warning'] = lang('replace_no_metadata');
                }

                $file->getFilesystem()->forceCopy($file->getAbsolutePath(), $original->getAbsolutePath());

                if ($file->getFilesystem()->exists($file->getAbsoluteThumbnailPath())) {
                    $file->getFilesystem()->forceCopy($file->getAbsoluteThumbnailPath(), $original->getAbsoluteThumbnailPath());
                }

                foreach ($file->UploadDestination->FileDimensions as $fd) {
                    $src = $file->getAbsoluteManipulationPath($fd->short_name);
                    $dest = $original->getAbsoluteManipulationPath($fd->short_name);

                    // non-image files will not have manipulations
                    if ($file->getFilesystem()->exists($src)) {
                        $file->getFilesystem()->forceCopy($src, $dest);
                    }
                }

                $file->delete();

                $result['params']['file'] = $original;
            }
        }

        $result['success'] = true;

        return $result;
    }

    public function validateFile(FileModel $file)
    {
        if (empty($_POST)) {
            return false;
        }

        $action = ($file->isNew()) ? 'upload_filedata' : 'edit_file_metadata';

        $file->set($_POST);
        $file->title = (ee()->input->post('title')) ?: $file->file_name;

        $cats = array_key_exists('categories', $_POST) ? $_POST['categories'] : array();
        $file->setCategoriesFromPost($cats);

        $result = $file->validate();

        if ($response = ee('Validation')->ajax($result)) {
            ee()->output->send_ajax_response($response);
        }

        if ($result->failed()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang($action . '_error'))
                ->addToBody(lang($action . '_error_desc'))
                ->now();
        }

        return $result;
    }

    public function syncUploadDirectory($id, $sizes = [], $db_sync = false) {
        $uploadDestination = ee('Model')->get('UploadDestination', $id)->first();

        if (empty($uploadDestination)) {
            return ee()->output->send_ajax_response([
                'message_type' => 'failure',
                'errors' => lang('unauthorized_access'),
            ]);
        }

        // Final run through, it syncs the db, removing stray records and thumbs
        if ($db_sync == 'y') {
            if (AJAX_REQUEST) {
                $errors = ee()->input->post('errors');
                if (empty($errors)) {
                    ee()->view->set_message('success', lang('directory_synced'), lang('directory_synced_desc'), true);
                }

                return ee()->output->send_ajax_response(array(
                    'message_type' => 'success'
                ));
            }

            return;
        }

        /*ee()->filemanager->xss_clean_off();
        $dir_data['dimensions'] = (is_array($sizes[$id])) ? $sizes[$id] : array();
        ee()->filemanager->set_upload_dir_prefs($id, $dir_data);*/

        // Now for everything NOT forcably replaced

        $missing_only_sizes = (is_array($sizes[$id])) ? $sizes[$id] : array();

        // Check for resize_ids
        $resize_ids = ee()->input->post('resize_ids');

        if (is_array($resize_ids)) {
            foreach ($resize_ids as $resize_id) {
                if (!empty($resize_id)) {
                    $replace_sizes[$resize_id] = $sizes[$id][$resize_id];
                    unset($missing_only_sizes[$resize_id]);
                }
            }
        }

        $mimes = ee()->config->loadFile('mimes');
        $fileTypes = array_filter(array_keys($mimes), 'is_string');

        $filesystem = $uploadDestination->getFilesystem();

        foreach ($current_files as $filePath) {
            $fileInfo = $filesystem->getWithMetadata($filePath);
            if (!isset($fileInfo['basename'])) {
                $fileInfo['basename'] = basename($fileInfo['path']);
            }
            $mime = ($fileInfo['type'] != 'dir') ? $filesystem->getMimetype($filePath) : 'directory';

            if ($mime == 'directory' && (!$uploadDestination->allow_subfolders || bool_config_item('file_manager_compatibility_mode'))) {
                //silently continue on subfolders if those are not allowed
                continue;
            }

            if (empty($mime)) {
                $errors[$fileInfo['basename']] = lang('invalid_mime');

                continue;
            }

            $file = $uploadDestination->getFileByPath($filePath);

            // Clean filename
            $clean_filename = ee()->filemanager->clean_subdir_and_filename($fileInfo['path'], $id, array(
                'convert_spaces' => false,
                'ignore_dupes' => true
            ));

            if ($fileInfo['path'] != $clean_filename) {
                // Make sure clean filename is unique
                $clean_filename = ee()->filemanager->clean_subdir_and_filename($clean_filename, $id, array(
                    'convert_spaces' => false,
                    'ignore_dupes' => false
                ));
                // Rename the file
                if (! $filesystem->rename($fileInfo['path'], $clean_filename)) {
                    $errors[$fileInfo['path']] = lang('invalid_filename');
                    continue;
                }

                $filesystem->delete($fileInfo['path']);
                $fileInfo['basename'] = $filesystem->basename($clean_filename);
            }

            if (! empty($file)) {
                // It exists, but do we need to change sizes or add a missing thumb?

                if (! $file->isEditableImage()) {
                    continue;
                }

                // Note 'Regular' batch needs to check if file exists- and then do something if so
                if (! empty($replace_sizes)) {
                    $thumb_created = ee()->filemanager->create_thumb(
                        $file->getAbsolutePath(),
                        array(
                            'directory' => $uploadDestination,
                            'server_path' => $uploadDestination->server_path,
                            'file_name' => $fileInfo['basename'],
                            'dimensions' => $replace_sizes,
                            'mime_type' => $mime
                        ),
                        true,	// Create thumb
                        false	// Overwrite existing thumbs
                    );

                    if (! $thumb_created) {
                        $errors[$fileInfo['basename']] = lang('thumb_not_created');
                    }
                }

                // Now for anything that wasn't forcably replaced- we make sure an image exists
                $thumb_created = ee()->filemanager->create_thumb(
                    $file->getAbsolutePath(),
                    array(
                        'directory' => $uploadDestination,
                        'server_path' => $uploadDestination->server_path,
                        'file_name' => $fileInfo['basename'],
                        'dimensions' => $missing_only_sizes,
                        'mime_type' => $mime
                    ),
                    true, 	// Create thumb
                    true 	// Don't overwrite existing thumbs
                );

                // Update dimensions
                $image_dimensions = $file->actLocally(function($path) {
                    return ee()->filemanager->get_image_dimensions($path);
                });
                $file->setRawProperty('file_hw_original', $image_dimensions['height'] . ' ' . $image_dimensions['width']);
                $file->file_size = $fileInfo['size'];
                if ($file->file_type === null) {
                    $file->setProperty('file_type', 'other'); // default
                    foreach ($fileTypes as $fileType) {
                        if (in_array($file->getProperty('mime_type'), $mimes[$fileType])) {
                            $file->setProperty('file_type', $fileType);
                            break;
                        }
                    }
                }
                $file->save();

                continue;
            }

            $file = ee('Model')->make('FileSystemEntity');
            $file_data = [
                'upload_location_id' => $uploadDestination->getId(),
                'site_id' => ee()->config->item('site_id'),
                'model_type' => ($mime == 'directory') ? 'Directory' : 'File',
                'mime_type' => $mime,
                'file_name' => $fileInfo['basename'],
                'file_size' => isset($fileInfo['size']) ? $fileInfo['size'] : 0,
                'uploaded_by_member_id' => ee()->session->userdata('member_id'),
                'modified_by_member_id' => ee()->session->userdata('member_id'),
                'upload_date' => $fileInfo['timestamp'],
                'modified_date' => $fileInfo['timestamp']
            ];
            $pathInfo = explode('/', trim(str_replace(DIRECTORY_SEPARATOR, '/', $filePath), '/'));
            //get the subfolder info, but at the same time, skip if no subfolder are allowed
            if (count($pathInfo) > 1) {
                if (!$uploadDestination->allow_subfolders || bool_config_item('file_manager_compatibility_mode')) {
                    continue;
                }
                array_pop($pathInfo);
                $directory = $uploadDestination->getFileByPath(implode('/', $pathInfo));
                $file_data['directory_id'] = $directory->getId();
            }
            $file->set($file_data);
            if ($file->isEditableImage()) {
                $image_dimensions = $file->actLocally(function ($path) {
                    return ee()->filemanager->get_image_dimensions($path);
                });
                $file_data['file_hw_original'] =  $image_dimensions['height'] . ' ' . $image_dimensions['width'];
                $file->setRawProperty('file_hw_original', $file_data['file_hw_original']);
            }
            //$file->save(); need to fallback to old saving because of the checks

            $saved = ee()->filemanager
                ->save_file(
                    $file->getAbsolutePath(),
                    $id,
                    $file_data,
                    false
                );

            if (! $saved['status']) {
                $errors[$fileInfo['basename']] = $saved['message'];
            }
        }

        if (AJAX_REQUEST) {
            if (count($errors)) {
                return ee()->output->send_ajax_response(array(
                    'message_type' => 'failure',
                    'errors' => $errors
                ));
            }

            return ee()->output->send_ajax_response(array(
                'message_type' => 'success'
            ));
        }
    }
}

// EOF
