<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\File;

use EllisLab\ExpressionEngine\Model\File\File as FileModel;
use EllisLab\ExpressionEngine\Model\Content\FieldFacade;
use EllisLab\ExpressionEngine\Model\Content\Display\FieldDisplay;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 * File Service Upload
 */
class Upload {

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

		$sections = array(
			array(
				array(
					'title' => 'file',
					'desc' => 'file_desc',
					'fields' => array(
						'file' => array(
							'type' => 'file',
							'required' => TRUE
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
		if ( ! $file->isNew())
		{
			unset($sections[0][0]);
		}

		foreach ($sections as $name => $settings)
		{
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

		$cat_groups = ee('Model')->get('CategoryGroup')
			->filter('group_id', 'IN', explode('|', $file->UploadDestination->cat_group))
			->all();

		if (count($cat_groups) == 0)
		{
			$url = ee('CP/URL', 'files/uploads/edit/' . $file->UploadDestination->getId())->compile();
			return ee('CP/Alert')->makeInline('empty-category-tab')
				->asWarning()
				->cannotClose()
				->withTitle(lang('no_categories_assigned'))
				->addToBody(sprintf(lang('no_categories_assigned_file_desc'), $url))
				->render();
		}

		foreach ($cat_groups as $cat_group)
		{
			$metadata = $cat_group->getFieldMetadata();
			$metadata['categorized_object'] = $file;
			$metadata['field_instructions'] = lang('file_categories_desc');
			$metadata['editable'] = FALSE;

			if ($cat_groups->count() == 1)
			{
				$metadata['field_label'] = lang('categories');
			}

			$field_id = 'categories[cat_group_id_'.$cat_group->getId().']';
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

		foreach ($sections as $name => $settings)
		{
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

		if (empty($alert))
		{
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
							'encode' => FALSE,
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
							'encode' => FALSE,
						)
					)
				)
			)
		);

		return $sections;
	}

	public function uploadTo($dir_id)
	{
		$dir = ee('Model')->get('UploadDestination', $dir_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $dir)
		{
			show_error(lang('no_upload_destination'));
		}

		if ( ! $dir->memberGroupHasAccess(ee()->session->userdata['group_id']))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! $dir->exists())
		{
			$upload_edit_url = ee('CP/URL')->make('files/uploads/edit/' . $dir->id);
			ee('CP/Alert')->makeStandard()
				->asIssue()
				->withTitle(lang('file_not_found'))
				->addToBody(sprintf(lang('directory_not_found'), $dir->server_path))
				->addToBody(sprintf(lang('check_upload_settings'), $upload_edit_url))
				->now();

			show_404();
		}

		$posted = FALSE;

		// Check permissions on the directory
		if ( ! $dir->isWritable())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('dir_not_writable'))
				->addToBody(sprintf(lang('dir_not_writable_desc'), $dir->server_path))
				->now();
		}

		$file = ee('Model')->make('File');
		$file->UploadDestination = $dir;

		$result = $this->validateFile($file);

		$upload_response = array();
		$uploaded = FALSE;

		if ($result instanceOf ValidationResult)
		{
			$posted = TRUE;

			if ($result->isValid())
			{
				// This is going to get ugly...apologies

				// PUNT! @TODO Break away from the old Filemanger Library
				ee()->load->library('filemanager');
				$upload_response = ee()->filemanager->upload_file($dir_id, 'file');
				if (isset($upload_response['error']))
				{
					ee('CP/Alert')->makeInline('shared-form')
						->asIssue()
						->withTitle(lang('upload_filedata_error'))
						->addToBody($upload_response['error'])
						->now();
				}
				else
				{
					$uploaded = TRUE;
					$file = ee('Model')->get('File', $upload_response['file_id'])->first();

					$file->upload_location_id = $dir_id;
					$file->site_id = ee()->config->item('site_id');

					// Validate handles setting properties...
					$this->validateFile($file);
				}
			}
		}

		return array(
			'file'              => $file,
			'posted'            => $posted,
			'uploaded'          => $uploaded,
			'validation_result' => $result,
			'upload_response'   => $upload_response
		);
	}

	public function resolveNameConflict($file_id)
	{
		$file = ee('Model')->get('File', $file_id)
			->with('UploadDestination')
			->first();

		if ( ! $file)
		{
			show_error(lang('no_file'));
		}

		if ( ! $file->memberGroupHasAccess(ee()->session->userdata['group_id']))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if (ee()->input->post('submit') == 'cancel')
		{
			$file->delete();
			return array('cancel' => TRUE);
		}

		$upload_options = ee()->input->post('upload_options');
		$original_name  = ee()->input->post('original_name');

		$result = array(
			'success' => FALSE,
			'params' => array(
				'file' => $file,
				'name' => $original_name
			)
		);

		if ($upload_options == 'rename')
		{
			$new_name = ee()->input->post('rename_custom');

			if (empty($new_name))
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('file_conflict'))
					->addToBody(lang('no_filename'))
					->now();
				return $result;
			}

			$original_extension = substr($original_name, strrpos($original_name, '.'));
			$new_extension = substr($new_name, strrpos($new_name, '.'));

			if ($new_extension != $original_extension)
			{
				$new_name .= $original_extension;
			}

			if ($file->UploadDestination->getFilesystem()->exists($new_name))
			{
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

			if ( ! $rename_file['success'])
			{
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('file_conflict'))
					->addToBody($rename_file['error'])
					->now();
				return $result;
			}

			$title = ($file->title == $file->file_name) ? NULL : $file->title;

			// The filemanager updated the database, and the saveFileAndRedirect
			// should have fresh data for the alert.
			$file = ee('Model')->get('File', $file_id)->first();

			// The filemanager will, on occasion, alter the title of the file
			// even if we had something set. It's annoying but happens.
			if ($title)
			{
				$file->title = $title;
				$file->save();
			}

			$result['params']['file'] = $file;
		}
		elseif ($upload_options == 'replace')
		{
			$original = ee('Model')->get('File')
				->filter('file_name', $original_name)
				->filter('site_id', $file->site_id)
				->filter('upload_location_id', $file->upload_location_id)
				->first();

			if ( ! $original)
			{
				$src = $file->getAbsolutePath();

				// The default is to use the file name as the title, and if we
				// did that then we should update it since we are replacing.
				if ($file->title == $file->file_name)
				{
					$file->title = $original_name;
				}

				$file->file_name = $original_name;
				$file->save();

				ee('Filesystem')->copy($src, $file->getAbsolutePath());
			}
			else
			{
				if (($file->description && ($file->description != $original->description))
					|| ($file->credit && ($file->credit != $original->credit))
					|| ($file->location && ($file->location != $original->location))
					|| ($file->Categories->count() > 0 && ($file->Categories->count() != $file->Categories->count())))
				{
					$result['warning'] = lang('replace_no_metadata');
				}

				ee('Filesystem')->copy($file->getAbsolutePath(), $original->getAbsolutePath());

				if (file_exists($file->getAbsoluteThumbnailPath()))
				{
					ee('Filesystem')->copy($file->getAbsoluteThumbnailPath(), $original->getAbsoluteThumbnailPath());
				}

				foreach ($file->UploadDestination->FileDimensions as $fd)
				{
					$src  = $fd->getAbsolutePath() . $file->file_name;
					$dest = $fd->getAbsolutePath() . $original->file_name;

					// non-image files will not have manipulations
					if (ee('Filesystem')->exists($src))
					{
						ee('Filesystem')->copy($src, $dest);
					}
				}

				$file->delete();

				$result['params']['file'] = $original;
			}
		}

		$result['success'] = TRUE;
		return $result;
	}

	public function validateFile(FileModel $file)
	{
		if (empty($_POST))
		{
			return FALSE;
		}

		$action = ($file->isNew()) ? 'upload_filedata' : 'edit_file_metadata';

		$file->set($_POST);
		$file->title = (ee()->input->post('title')) ?: $file->file_name;

		$cats = array_key_exists('categories', $_POST) ? $_POST['categories'] : array();
		$file->setCategoriesFromPost($cats);

		$result = $file->validate();

		if ($response = ee('Validation')->ajax($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang($action . '_error'))
				->addToBody(lang($action . '_error_desc'))
				->now();
		}

		return $result;
	}
}

// EOF
