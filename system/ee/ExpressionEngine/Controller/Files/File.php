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

use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Controller\Files\AbstractFiles as AbstractFilesController;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use ExpressionEngine\Model\File\File as FileModel;

/**
 * Files\File Controller
 */
class File extends AbstractFilesController
{
    public function view($id)
    {
        if (! ee('Permission')->can('edit_files')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $file = ee('Model')->get('File', $id)
            ->with('UploadDestination', 'UploadAuthor', 'ModifyAuthor')
            ->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
            ->all()
            ->first();

        if (! $file) {
            show_error(lang('no_file'));
        }

        if (! $file->memberHasAccess(ee()->session->getMember())) {
            show_error(lang('unauthorized_access'), 403);
        }

        //if this is specific file manipulation action, do that
        if (in_array(ee('Request')->post('action'), ['resize', 'rotate', 'crop'])) {
            $this->modify($file, ee('Request')->post('action'));
        }

        $result = ee('File')->makeUpload()->validateFile($file);
        $errors = null;

        // Save any changes made to the file
        if ($result instanceof ValidationResult) {
            $errors = $result;

            if ($result->isValid()) {
                $this->saveFileAndRedirect($file);
            }
        }

        if (! $file->exists()) {
            $alert = ee('CP/Alert')->makeStandard()
                ->asIssue()
                ->withTitle(lang('file_not_found'))
                ->addToBody(sprintf(lang('file_not_found_desc'), $file->getAbsolutePath()));

            $dir = $file->getUploadDestination();
            if (! $dir->exists()) {
                $upload_edit_url = ee('CP/URL')->make('files/uploads/edit/' . $dir->id);
                $alert->addToBody(sprintf(lang('directory_not_found'), $dir->server_path))
                    ->addToBody(sprintf(lang('check_upload_settings'), $upload_edit_url));
            }

            $alert->now();
            show_404();
        } else {
            // Check permissions on the file
            if (! $file->isWritable()) {
                $alert = ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('file_not_writable'))
                    ->addToBody(sprintf(lang('file_not_writable_desc'), $file->getAbsolutePath()))
                    ->now();
            }
        }

        $tabs = array(
            'file_data' => ee('File')->makeUpload()->getFileDataForm($file, $errors),
            'categories' => ee('File')->makeUpload()->getCategoryForm($file, $errors),
        );
        if ($file->isEditableImage() && ee('Request')->get('modal_form') != 'y') {
            ee()->load->library('image_lib');
            // we should really be storing the image properties in the db during file upload
            $info = $file->actLocally(function ($path) {
                return ee()->image_lib->get_image_properties($path, true);
            });
            ee()->image_lib->error_msg = array(); // Reset any erorrs

            $tabs['crop'] = $this->renderCropForm($file, $info);
            $tabs['rotate'] = $this->renderRotateForm($file);
            $tabs['resize'] = $this->renderResizeForm($file, $info);
            if (!empty($file->UploadDestination->FileDimensions->count())) {
                $tabs['manipulations'] = $this->renderManipulationsForm($file);
            }
        }
        if (! bool_config_item('file_manager_compatibility_mode')) {
            $tabs['usage'] = $this->renderUsageForm($file);
        }

        $vars = [
            'file' => $file,
            'is_image' => $file->isImage(),
            'size' => (string) ee('Format')->make('Number', $file->file_size)->bytes(),
            'download_url' => ee('CP/URL')->make('files/file/download/' . $file->file_id),
            'modal_form' => ee('Request')->get('modal_form') === 'y',
            'ajax_validate' => true,
            'base_url' => ee('CP/URL')->make('files/file/view/' . $id),
            'save_btn_text' => 'btn_edit_file_meta',
            'save_btn_text_working' => 'btn_saving',
            'tabs' => $tabs,
            'usage_count' => $file->total_records,
            'buttons' => [
                [
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'save',
                    'text' => 'btn_edit_file_meta',
                    'working' => 'btn_saving'
                ],
            ],
            'sections' => array(),
            'hide_top_buttons' => true
        ];

        if (ee('Request')->get('modal_form') !== 'y') {
            $vars['buttons'][] = [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_close',
                'text' => 'save_and_close',
                'working' => 'btn_saving'
            ];
        }

        ee()->view->cp_page_title = lang('edit_file_metadata');

        ee()->view->header = array(
            'title' => lang('edit_file'),
        );

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('files')->compile() => lang('files'),
            ee('CP/URL')->make('files/directory/' . $file->UploadDestination->getId())->compile() => $file->UploadDestination->name,
            '' => lang('edit_file')
        );

        ee()->cp->add_js_script(array(
            'file' => array('cp/files/copy-url'),
        ));

        ee()->cp->render('files/edit', $vars);
    }

    private function modify(FileModel $file, $action = '')
    {
        if (! in_array($action, ['resize', 'rotate', 'crop'])) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! $file->isImage()) {
            show_error(lang('not_an_image'));
        }

        if (! $file->exists()) {
            $alert = ee('CP/Alert')->makeStandard()
                ->asIssue()
                ->withTitle(lang('file_not_found'))
                ->addToBody(sprintf(lang('file_not_found_desc'), $file->getAbsolutePath()));

            $dir = $file->getUploadDestination();
            if (! $dir->exists()) {
                $upload_edit_url = ee('CP/URL')->make('files/uploads/edit/' . $dir->id);
                $alert->addToBody(sprintf(lang('directory_not_found'), $dir->server_path))
                    ->addToBody(sprintf(lang('check_upload_settings'), $upload_edit_url));
            }

            $alert->now();
            show_404();
        } else {
            // Check permissions on the file
            if (! $file->isWritable()) {
                $alert = ee('CP/Alert')->makeInline('file-modify')
                    ->asIssue()
                    ->withTitle(lang('file_not_writable'))
                    ->addToBody(sprintf(lang('file_not_writable_desc'), $file->getAbsolutePath()))
                    ->defer();
            }

            ee()->load->library('image_lib');
            $info = $file->actLocally(function ($path) {
                return ee()->image_lib->get_image_properties($path, true);
            });
            ee()->image_lib->error_msg = array(); // Reset any erorrs
        }

        $active_tab = 0;

        ee()->load->library('form_validation');
        switch ($action) {
            case 'crop':
                ee()->form_validation->set_rules('crop_width', 'lang:width', 'trim|is_natural_no_zero|required');
                ee()->form_validation->set_rules('crop_height', 'lang:height', 'trim|is_natural_no_zero|required');
                ee()->form_validation->set_rules('crop_x', 'lang:x_axis', 'trim|numeric|required');
                ee()->form_validation->set_rules('crop_y', 'lang:y_axis', 'trim|numeric|required');
                $action_desc = "cropped";

                break;
            case 'rotate':
                ee()->form_validation->set_rules('rotate', 'lang:rotate', 'required');
                $action_desc = "rotated";
                $active_tab = 1;

                break;
            case 'resize':
                ee()->form_validation->set_rules('resize_width', 'lang:width', 'trim|is_natural');
                ee()->form_validation->set_rules('resize_height', 'lang:height', 'trim|is_natural');
                $action_desc = "resized";
                $active_tab = 2;

                break;
        }

        if (AJAX_REQUEST) {
            // If it is an AJAX request, then we did not have POST data to
            // specify the rules, so we'll do it here. Note: run_ajax() removes
            // rules for all fields but the one submitted.
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            // PUNT! (again) @TODO Break away from the old Filemanger Library
            ee()->load->library('filemanager');

            $response = null;
            switch ($action) {
                case 'crop':
                    $response = ee()->filemanager->_do_crop($file->getAbsolutePath(), $file->getFilesystem());

                    break;

                case 'rotate':
                    $response = ee()->filemanager->_do_rotate($file->getAbsolutePath(), $file->getFilesystem());

                    break;

                case 'resize':
                    // Preserve proportions if either dimention was omitted
                    if (empty($_POST['resize_width']) or empty($_POST['resize_height'])) {
                        $size = explode(" ", $file->file_hw_original);
                        // If either h/w unspecified, calculate the other here
                        if (empty($_POST['resize_width'])) {
                            $_POST['resize_width'] = ($size[1] / $size[0]) * $_POST['resize_height'];
                        } elseif (empty($_POST['resize_height'])) {
                            $_POST['resize_height'] = ($size[0] / $size[1]) * $_POST['resize_width'];
                        }
                    }

                    $response = ee()->filemanager->_do_resize($file->getAbsolutePath(), $file->getFilesystem());

                    break;
            }

            if (isset($response['errors'])) {
                ee('CP/Alert')->makeInline('file-modify')
                    ->asIssue()
                    ->withTitle(sprintf(lang('crop_file_error'), lang($action)))
                    ->addToBody($response['errors'])
                    ->defer();
            } else {
                $file->file_hw_original = $response['dimensions']['height'] . ' ' . $response['dimensions']['width'];
                $file->file_size = $response['file_info']['size'];
                $file->save();

                // Regenerate thumbnails
                $dir = $file->getUploadDestination();
                $dimensions = $dir->getFileDimensions();

                ee()->filemanager->create_thumb(
                    $file->getAbsolutePath(),
                    array(
                        'directory' => $dir,
                        'server_path' => $dir->server_path,
                        'file_name' => $file->file_name,
                        'dimensions' => $dimensions->asArray()
                    ),
                    true, // Regenerate thumbnails
                    false // Regenerate all images
                );

                ee('CP/Alert')->makeInline('file-modify')
                    ->asSuccess()
                    ->withTitle(sprintf(lang('crop_file_success'), lang($action)))
                    ->addToBody(sprintf(lang('crop_file_success_desc'), $file->title, lang($action_desc)))
                    ->defer();
            }
        } elseif (ee()->form_validation->errors_exist()) {
            ee('CP/Alert')->makeInline('file-modify')
                ->asIssue()
                ->withTitle(sprintf(lang('crop_file_error'), lang($action)))
                ->addToBody(sprintf(lang('crop_file_error_desc'), strtolower(lang($action))))
                ->defer();
        }
    }

    protected function renderUsageForm($file)
    {
        $entriesTable = ee('CP/Table', array(
            'class' => 'tbl-fixed',
        ));
        $entriesTable->setColumns(
            array(
                'title' => array(
                    'encode' => false,
                    'attrs' => array(
                        'width' => '40%'
                    ),
                ),
                //'date_added',
                'channel',
                'status' => [
                    'type' => Table::COL_STATUS
                ]
            )
        );
        //$entriesTable->setNoResultsText(lang('no_uploaded_files'));
        $data = array();
        foreach ($file->FileEntries as $entry) {
            $title = ee('Format')->make('Text', $entry->title)->convertToEntities();
            if (
                $entry->site_id == ee()->config->item('site_id') && (
                    ee('Permission')->can('edit_other_entries_channel_id_' . $entry->channel_id) ||
                    (ee('Permission')->can('edit_self_entries_channel_id_' . $entry->channel_id) && $entry->author_id == ee()->session->userdata('member_id'))
                )
            ) {
                $title = '<a href="' . ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id) . '">' . $title . '</a>';
            }
            $attrs = [];
            $columns = [
                $title,
                //ee()->localize->human_time($entry->entry_date),
                $entry->Channel->channel_title,
                !empty($entry->getStatus()) ? $entry->getStatus()->renderTag() : $entry->status
            ];
            $data[] = array(
                'attrs' => $attrs,
                'columns' => $columns
            );
        }
        $entriesTable->setData($data);

        $categoriesTable = ee('CP/Table', array(
            'class' => 'tbl-fixed',
        ));
        $categoriesTable->setColumns(
            array(
                'name' => array(
                    'encode' => false,
                    'attrs' => array(
                        'width' => '40%'
                    ),
                ),
                'category_group',
            )
        );
        $data = array();
        foreach ($file->FileCategories as $category) {
            $title = ee('Format')->make('Text', $category->cat_name)->convertToEntities();
            $can_edit = explode('|', rtrim((string) $category->CategoryGroup->can_edit_categories, '|'));
            if (
                ee('Permission')->isSuperAdmin() ||
                (ee('Permission')->can('edit_categories') && ee('Permission')->hasAnyRole($can_edit))
            ) {
                $title = '<a href="' . ee('CP/URL')->make('categories/edit/' . $category->group_id . '/' . $category->cat_id) . '">' . $title . '</a>';
            }
            $attrs = [];
            $columns = [
                $title,
                $category->CategoryGroup->group_name,
            ];
            $data[] = array(
                'attrs' => $attrs,
                'columns' => $columns
            );
        }
        $categoriesTable->setData($data);

        $section = [
            [
                'title' => 'usage_desc',
                'desc' => '',
                'fields' => [
                    'usage_tables' => [
                        'type' => 'html',
                        'content' => ee('View')->make('ee:_shared/file/usage-tab')->render([
                            'entries' => $entriesTable->viewData(),
                            'categories' => $categoriesTable->viewData(),
                        ]),
                    ],
                ],
            ],
        ];

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section));
    }

    protected function renderManipulationsForm($file)
    {
        $manipulationsTable = ee('CP/Table', array(
            'class' => 'tbl-fixed',
        ));
        $manipulationsTable->setColumns(
            array(
                'short_name' => array(
                    'encode' => false,
                    'attrs' => array(
                        'width' => '40%'
                    ),
                ),
                'type',
                'watermark',
                'view' => array(
                    'encode' => false,
                ),
            )
        );
        $data = array();
        foreach ($file->UploadDestination->FileDimensions as $manipulation) {
            $attrs = [];
            $columns = [
                $manipulation->short_name,
                lang($manipulation->resize_type) . ', ' . $manipulation->width . 'px ' . lang('by') . ' ' . $manipulation->height . 'px',
                !empty($manipulation->watermark_id) ? $manipulation->Watermark->wm_name : '',
                '<a href="' . $file->getAbsoluteManipulationURL($manipulation->short_name) . '" target="_blank"><i class="fal fa-eye"></i></a>'
            ];
            $data[] = array(
                'attrs' => $attrs,
                'columns' => $columns
            );
        }
        $manipulationsTable->setData($data);

        $section = [
            [
                'title' => '',
                'desc' => lang('existing_file_manipulations_desc'),
                'fields' => [
                    'usage_tables' => [
                        'type' => 'html',
                        'content' => ee('View')->make('ee:_shared/table')->render($manipulationsTable->viewData()),
                    ],
                ],
            ],
        ];

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section));
    }

    protected function renderCropForm($file, $info)
    {
        $section = [
            [
                'title' => 'constraints',
                'desc' => 'crop_constraints_desc',
                'fields' => [
                    'crop_width' => [
                        'type' => 'short-text',
                        'label' => 'crop_width',
                        'value' => ee('Request')->post('crop_width', $info['width'])
                    ],
                    'crop_height' => [
                        'type' => 'short-text',
                        'label' => 'crop_height',
                        'value' => ee('Request')->post('crop_height', $info['height'])
                    ]
                ]
            ],
            [
                'title' => 'coordinates',
                'desc' => 'coordiantes_desc',
                'fields' => [
                    'crop_x' => [
                        'type' => 'short-text',
                        'label' => 'x_axis',
                        'value' => ee('Request')->post('crop_x', 0)
                    ],
                    'crop_y' => [
                        'type' => 'short-text',
                        'label' => 'y_axis',
                        'value' => ee('Request')->post('crop_y', 0)
                    ]
                ]
            ],
        ];

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section));
    }

    protected function renderRotateForm($file)
    {
        $section = [
            [
                'title' => 'rotation',
                'desc' => 'rotation_desc',
                'fields' => [
                    'rotate' => [
                        'type' => 'radio',
                        'choices' => [
                            '270' => lang('90_degrees_right'),
                            '90' => lang('90_degrees_left'),
                            'vrt' => lang('flip_vertically'),
                            'hor' => lang('flip_horizontally'),
                        ],
                        'value' => ee('Request')->post('rotate')
                    ],
                ]
            ],
        ];

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section));
    }

    protected function renderResizeForm($file, $info)
    {
        $section = [
            [
                'title' => 'constraints',
                'desc' => 'crop_constraints_desc',
                'fields' => [
                    'resize_width' => [
                        'type' => 'short-text',
                        'label' => 'resize_width',
                        'value' => ee('Request')->post('resize_width', $info['width'])
                    ],
                    'resize_height' => [
                        'type' => 'short-text',
                        'label' => 'resize_height',
                        'value' => ee('Request')->post('resize_height', $info['height'])
                    ]
                ]
            ],
        ];

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section));
    }

    public function download($id)
    {
        $file = ee('Model')->get('File', $id)
            ->with('UploadDestination')
            ->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
            ->first();

        if (! $file) {
            show_error(lang('no_file'));
        }

        if (! $file->memberHasAccess(ee()->session->getMember())) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->load->helper('download');
        force_download($file->file_name, $file->UploadDestination->getFilesystem()->read($file->getAbsolutePath()));
    }
}

// EOF
