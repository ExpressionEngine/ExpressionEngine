<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Files;

use EllisLab\ExpressionEngine\Controller\Files\AbstractFiles as AbstractFilesController;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 * Files\File Controller
 */
class File extends AbstractFilesController {

	public function view($id)
	{
		$file = ee('Model')->get('File', $id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $file)
		{
			show_error(lang('no_file'));
		}

		if ( ! $file->memberGroupHasAccess(ee()->session->userdata['group_id']))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! $file->isImage())
		{
			show_error(lang('not_an_image'));
		}

		ee()->load->library('image_lib');
		$info = ee()->image_lib->get_image_properties($file->getAbsolutePath(), TRUE);

		$vars = array(
			'file' => $file,
			'height' => $info['height'],
			'width' => $info['width'],
			'size' => (string) ee('Format')->make('Number', $file->file_size)->bytes()
		);

		ee()->cp->render('files/view', $vars);
	}

	public function edit($id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_files'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$errors = NULL;

		$file = ee('Model')->get('File', $id)
			->with('UploadDestination')
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $file)
		{
			show_error(lang('no_file'));
		}

		if ( ! $file->memberGroupHasAccess(ee()->session->userdata['group_id']))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$result = $this->validateFile($file);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveFileAndRedirect($file);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('files/file/edit/' . $id),
			'save_btn_text' => 'btn_edit_file_meta',
			'save_btn_text_working' => 'btn_saving',
			'tabs' => array(
				'file_data' => ee('File')->makeUpload()->getFileDataForm($file, $errors),
				'categories' => ee('File')->makeUpload()->getCategoryForm($file, $errors),
			),
			'sections' => array(),
		);

		$this->generateSidebar($file->upload_location_id);
		$this->stdHeader();
		ee()->view->cp_page_title = sprintf(lang('edit_file_metadata'), $file->title);

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('files')->compile() => lang('file_manager'),
		);

		ee()->cp->render('settings/form', $vars);
	}

	public function crop($id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_files'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$file = ee('Model')->get('File', $id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $file)
		{
			show_error(lang('no_file'));
		}

		if ( ! $file->memberGroupHasAccess(ee()->session->userdata['group_id']))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! $file->isImage())
		{
			show_error(lang('not_an_image'));
		}

		if ( ! $file->exists())
		{
			$alert = ee('CP/Alert')->makeStandard()
				->asIssue()
				->withTitle(lang('file_not_found'))
				->addToBody(sprintf(lang('file_not_found_desc'), $file->getAbsolutePath()));

			$dir = $file->getUploadDestination();
			if ( ! $dir->exists())
			{
				$upload_edit_url = ee('CP/URL')->make('files/uploads/edit/' . $dir->id);
				$alert->addToBody(sprintf(lang('directory_not_found'), $dir->server_path))
					->addToBody(sprintf(lang('check_upload_settings'), $upload_edit_url));
			}

			$alert->now();
			show_404();
		}
		else
		{
			// Check permissions on the file
			if ( ! $file->isWritable())
			{
				$alert = ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('file_not_writable'))
					->addToBody(sprintf(lang('file_not_writable_desc'), $file->getAbsolutePath()))
					->now();
			}

			ee()->load->library('image_lib');
			$info = ee()->image_lib->get_image_properties($file->getAbsolutePath(), TRUE);
			ee()->image_lib->error_msg = array(); // Reset any erorrs
		}

		$active_tab = 0;

		ee()->load->library('form_validation');
		if (isset($_POST['crop_width']))
		{
			ee()->form_validation->set_rules('crop_width', 'lang:width', 'trim|is_natural_no_zero|required');
			ee()->form_validation->set_rules('crop_height', 'lang:height', 'trim|is_natural_no_zero|required');
			ee()->form_validation->set_rules('crop_x', 'lang:x_axis', 'trim|numeric|required');
			ee()->form_validation->set_rules('crop_y', 'lang:y_axis', 'trim|numeric|required');
			$action = "crop";
			$action_desc = "cropped";
		}
		else if (isset($_POST['rotate']))
		{
			ee()->form_validation->set_rules('rotate', 'lang:rotate', 'required');
			$action = "rotate";
			$action_desc = "rotated";
			$active_tab = 1;
		}
		else if (isset($_POST['resize_width']))
		{
			ee()->form_validation->set_rules('resize_width', 'lang:width', 'trim|is_natural');
			ee()->form_validation->set_rules('resize_height', 'lang:height', 'trim|is_natural');

			$action = "resize";
			$action_desc = "resized";
			$active_tab = 2;
		}

		if (AJAX_REQUEST)
		{
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
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			// PUNT! (again) @TODO Break away from the old Filemanger Library
			ee()->load->library('filemanager');

			$response = NULL;
			switch ($action)
			{
				case 'crop':
					$response = ee()->filemanager->_do_crop($file->getAbsolutePath());
					break;

				case 'rotate':
					$response = ee()->filemanager->_do_rotate($file->getAbsolutePath());
					break;

				case 'resize':

					// Preserve proportions if either dimention was omitted
					if (empty($_POST['resize_width']) OR empty($_POST['resize_height']))
					{
						$size = explode(" ", $file->file_hw_original);
						// If either h/w unspecified, calculate the other here
						if (empty($_POST['resize_width']))
						{
							$_POST['resize_width'] = ($size[1] / $size[0]) * $_POST['resize_height'];
						}
						elseif (empty($_POST['resize_height']))
						{
							$_POST['resize_height'] = ($size[0] / $size[1]) * $_POST['resize_width'];
						}
					}

					$response = ee()->filemanager->_do_resize($file->getAbsolutePath());
					break;
			}

			if (isset($response['errors']))
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(sprintf(lang('crop_file_error'), lang($action)))
					->addToBody($response['errors'])
					->now();
			}
			else
			{
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
					TRUE, // Regenerate thumbnails
					FALSE // Regenerate all images
				);

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(sprintf(lang('crop_file_success'), lang($action)))
					->addToBody(sprintf(lang('crop_file_success_desc'), $file->title, lang($action_desc)))
					->now();
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(sprintf(lang('crop_file_error'), lang($action)))
				->addToBody(sprintf(lang('crop_file_error_desc'), strtolower(lang($action))))
				->now();
		}

		ee()->view->cp_page_title = sprintf(lang('crop_file'), $file->file_name);

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('files')->compile() => lang('file_manager'),
			ee('CP/URL')->make('files/file/edit/' . $id)->compile() => sprintf(lang('edit_file_name'), $file->file_name)
		);

		$this->stdHeader();

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/files/crop',
			),
		));

		$vars = [
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('files/file/crop/' . $id),
			'tabs' => [
				'crop'   => $this->renderCropForm($file, $info),
				'rotate' => $this->renderRotateForm($file),
				'resize' => $this->renderResizeForm($file, $info)
			],
			'active_tab' => $active_tab,
			'buttons' => [
				[
					'name'    => 'submit',
					'type'    => 'submit',
					'value'   => 'save',
					'text'    => 'save',
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
				->render(array('name' => NULL, 'settings' => $section));
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
				->render(array('name' => NULL, 'settings' => $section));
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
				->render(array('name' => NULL, 'settings' => $section));
	}

	public function download($id)
	{
		$file = ee('Model')->get('File', $id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $file)
		{
			show_error(lang('no_file'));
		}

		if ( ! $file->memberGroupHasAccess(ee()->session->userdata['group_id']))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->load->helper('download');
		force_download($file->file_name, file_get_contents($file->getAbsolutePath()));
	}
}

// EOF
