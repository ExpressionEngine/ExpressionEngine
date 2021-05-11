<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Files;

use ExpressionEngine\Controller\Files\AbstractFiles as AbstractFilesController;
use ExpressionEngine\Service\Validation\Result as ValidationResult;

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
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $file) {
            show_error(lang('no_file'));
        }

        if (! $file->memberHasAccess(ee()->session->getMember())) {
            show_error(lang('unauthorized_access'), 403);
        }

        $result = $this->validateFile($file);
        $errors = null;

        // Save any changes made to the file
        if ($result instanceof ValidationResult) {
            $errors = $result;

            if ($result->isValid()) {
                $this->saveFileAndRedirect($file);
            }
        }

        $is_image = $file->isImage();
        $image_info = [];

        if ($is_image) {
            ee()->load->library('image_lib');
            $image_info = ee()->image_lib->get_image_properties($file->getAbsolutePath(), true);
        }

        $vars = [
            'file' => $file,
            'is_image' => $is_image,
            'image_info' => $image_info,
            'size' => (string) ee('Format')->make('Number', $file->file_size)->bytes(),
            'download_url' => ee('CP/URL')->make('files/file/download/' . $file->file_id),

            'ajax_validate' => true,
            'base_url' => ee('CP/URL')->make('files/file/view/' . $id),
            'save_btn_text' => 'btn_edit_file_meta',
            'save_btn_text_working' => 'btn_saving',
            'tabs' => array(
                'file_data' => ee('File')->makeUpload()->getFileDataForm($file, $errors),
                'categories' => ee('File')->makeUpload()->getCategoryForm($file, $errors),
            ),
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

        ee()->view->cp_page_title = lang('edit_file_metadata');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('files')->compile() => lang('file_manager'),
        );

        ee()->cp->render('files/edit', $vars);
    }

    public function crop($id)
    {
        if (! ee('Permission')->can('edit_files')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $file = ee('Model')->get('File', $id)
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $file) {
            show_error(lang('no_file'));
        }

        if (! $file->memberHasAccess(ee()->session->getMember())) {
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
                $alert = ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('file_not_writable'))
                    ->addToBody(sprintf(lang('file_not_writable_desc'), $file->getAbsolutePath()))
                    ->now();
            }

            ee()->load->library('image_lib');
            $info = ee()->image_lib->get_image_properties($file->getAbsolutePath(), true);
            ee()->image_lib->error_msg = array(); // Reset any erorrs
        }

        $active_tab = 0;

        ee()->load->library('form_validation');
        if (isset($_POST['crop_width'])) {
            ee()->form_validation->set_rules('crop_width', 'lang:width', 'trim|is_natural_no_zero|required');
            ee()->form_validation->set_rules('crop_height', 'lang:height', 'trim|is_natural_no_zero|required');
            ee()->form_validation->set_rules('crop_x', 'lang:x_axis', 'trim|numeric|required');
            ee()->form_validation->set_rules('crop_y', 'lang:y_axis', 'trim|numeric|required');
            $action = "crop";
            $action_desc = "cropped";
        } elseif (isset($_POST['rotate'])) {
            ee()->form_validation->set_rules('rotate', 'lang:rotate', 'required');
            $action = "rotate";
            $action_desc = "rotated";
            $active_tab = 1;
        } elseif (isset($_POST['resize_width'])) {
            ee()->form_validation->set_rules('resize_width', 'lang:width', 'trim|is_natural');
            ee()->form_validation->set_rules('resize_height', 'lang:height', 'trim|is_natural');

            $action = "resize";
            $action_desc = "resized";
            $active_tab = 2;
        }

        if (AJAX_REQUEST) {
            // If it is an AJAX request, then we did not have POST data to
            // specify the rules, so we'll do it here. Note: run_ajax() removes
            // rules for all fields but the one submitted.

            ee()->form_validation->set_rules('crop_width', 'lang:width', 'trim|is_natural_no_zero|required');
            ee()->form_validation->set_rules('crop_height', 'lang:height', 'trim|is_natural_no_zero|required');
            ee()->form_validation->set_rules('crop_x', 'lang:x_axis', 'trim|numeric|required');
            ee()->form_validation->set_rules('crop_y', 'lang:y_axis', 'trim|numeric|required');
            ee()->form_validation->set_rules('rotate', 'lang:rotate', 'required');
            ee()->form_validation->set_rules('resize_width', 'lang:width', 'trim|is_natural');
            ee()->form_validation->set_rules('resize_height', 'lang:height', 'trim|is_natural');

            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            // PUNT! (again) @TODO Break away from the old Filemanger Library
            ee()->load->library('filemanager');

            $response = null;
            switch ($action) {
                case 'crop':
                    $response = ee()->filemanager->_do_crop($file->getAbsolutePath());

                    break;

                case 'rotate':
                    $response = ee()->filemanager->_do_rotate($file->getAbsolutePath());

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

                    $response = ee()->filemanager->_do_resize($file->getAbsolutePath());

                    break;
            }

            if (isset($response['errors'])) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(sprintf(lang('crop_file_error'), lang($action)))
                    ->addToBody($response['errors'])
                    ->now();
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
                        'server_path' => $dir->server_path,
                        'file_name' => $file->file_name,
                        'dimensions' => $dimensions->asArray()
                    ),
                    true, // Regenerate thumbnails
                    false // Regenerate all images
                );

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(sprintf(lang('crop_file_success'), lang($action)))
                    ->addToBody(sprintf(lang('crop_file_success_desc'), $file->title, lang($action_desc)))
                    ->now();
            }
        } elseif (ee()->form_validation->errors_exist()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(sprintf(lang('crop_file_error'), lang($action)))
                ->addToBody(sprintf(lang('crop_file_error_desc'), strtolower(lang($action))))
                ->now();
        }

        ee()->view->cp_page_title = sprintf(lang('crop_file'), $file->file_name);

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('files')->compile() => lang('file_manager'),
            '' => lang('btn_crop')
        );

        $this->stdHeader();

        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/files/crop',
            ),
        ));

        $vars = [
            'ajax_validate' => true,
            'base_url' => ee('CP/URL')->make('files/file/crop/' . $id),
            'tabs' => [
                'crop' => $this->renderCropForm($file, $info),
                'rotate' => $this->renderRotateForm($file),
                'resize' => $this->renderResizeForm($file, $info)
            ],
            'active_tab' => $active_tab,
            'buttons' => [
                [
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'save',
                    'text' => 'save',
                    'working' => 'btn_saving'
                ]
            ],
            'sections' => []
        ];

        ee()->cp->render('settings/form', $vars);
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
            [
                'title' => '',
                'fields' => [
                    'img_preview' => [
                        'type' => 'html',
                        'content' => '<figure class="img-preview"><img src="' . $file->getAbsoluteURL() . '?v=' . time() . '"></figure>'
                    ]
                ]
            ]
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
            [
                'title' => '',
                'fields' => [
                    'img_preview' => [
                        'type' => 'html',
                        'content' => '<figure class="img-preview"><img src="' . $file->getAbsoluteURL() . '?v=' . time() . '"></figure>'
                    ]
                ]
            ]
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
            [
                'title' => '',
                'fields' => [
                    'img_preview' => [
                        'type' => 'html',
                        'content' => '<figure class="img-preview"><img src="' . $file->getAbsoluteURL() . '?v=' . time() . '"></figure>'
                    ]
                ]
            ]
        ];

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section));
    }

    public function download($id)
    {
        $file = ee('Model')->get('File', $id)
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $file) {
            show_error(lang('no_file'));
        }

        if (! $file->memberHasAccess(ee()->session->getMember())) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->load->helper('download');
        force_download($file->file_name, file_get_contents($file->getAbsolutePath()));
    }
}

// EOF
