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

use ZipArchive;
use EllisLab\ExpressionEngine\Controller\Files\AbstractFiles as AbstractFilesController;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;
use EllisLab\ExpressionEngine\Library\CP\Table;

use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\File\UploadDestination;

/**
 * Files Controller
 */
class Files extends AbstractFilesController {

	public function index()
	{
		$this->handleBulkActions(ee('CP/URL')->make('files', ee()->cp->get_url_state()));

		$base_url = ee('CP/URL')->make('files');

		$files = ee('Model')->get('File')
			->with('UploadDestination')
			->filter('UploadDestination.module_id', 0)
			->filter('site_id', ee()->config->item('site_id'));

		$vars = $this->listingsPage($files, $base_url);

		$this->generateSidebar(NULL);
		$this->stdHeader();
		ee()->view->cp_page_title = lang('file_manager');

		// Set search results heading
		if ( ! empty($vars['search_terms']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$vars['total_files'],
				$vars['search_terms']
			);
		}
		else
		{
			ee()->view->cp_heading = lang('all_files');
		}

		ee()->cp->render('files/index', $vars);
	}

	public function directory($id)
	{
		$dir = ee('Model')->get('UploadDestination', $id)
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
			ee('CP/Alert')->makeInline('missing-directory')
				->asWarning()
				->cannotClose()
				->withTitle(sprintf(lang('directory_not_found'), $dir->server_path))
				->addToBody(sprintf(lang('check_upload_settings'), $upload_edit_url))
				->now();
		}

		$this->handleBulkActions(ee('CP/URL')->make('files/directory/' . $id, ee()->cp->get_url_state()));

		$base_url = ee('CP/URL')->make('files/directory/' . $id);

		$files = ee('Model')->get('File')
			->with('UploadDestination')
			->filter('upload_location_id', $dir->getId());

		$vars = $this->listingsPage($files, $base_url);

		$vars['form_url'] = $vars['table']['base_url'];
		$vars['dir_id'] = $id;

		$this->generateSidebar($id);
		ee()->view->cp_page_title = lang('file_manager');
		ee()->view->cp_heading = sprintf(lang('files_in_directory'), $dir->name);

		// Check to see if they can sync the directory
		ee()->view->can_sync_directory = ee()->cp->allowed_group('can_upload_new_files')
			&& $dir->memberGroupHasAccess(ee()->session->userdata('group_id'));

		$this->stdHeader(
			ee()->view->can_sync_directory ? $id : NULL
		);

		ee()->cp->render('files/directory', $vars);
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
		if ( ! ee()->cp->allowed_group('can_upload_new_files'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$errors = NULL;

		$result = ee('File')->makeUpload()->uploadTo($dir_id);

		$file = $result['file'];

		if ($result['posted'])
		{
			$errors = $result['validation_result'];

			if ($result['uploaded'])
			{
				// The upload process will automatically rename files in the
				// event of a filename collision. Should that happen we need
				// to ask the user if they wish to rename the file or
				// replace the file
				if ($file->file_name != $result['upload_response']['file_data_orig_name'])
				{
					$file->save();
					ee()->session->set_flashdata('original_name', $result['upload_response']['file_data_orig_name']);
					ee()->functions->redirect(ee('CP/URL')->make('files/finish-upload/' . $file->file_id));
				}

				$this->saveFileAndRedirect($file, TRUE);
			}
		}

		$vars = array(
			'required' => TRUE,
			'ajax_validate' => TRUE,
			'has_file_input' => TRUE,
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

		ee()->cp->render('settings/form', $vars);
	}

	private function overwriteOrRename($file, $original_name)
	{
		$vars = array(
			'required' => TRUE,
			'base_url' => ee('CP/URL')->make('files/finish-upload/' . $file->file_id),
			'sections' => ee('File')->makeUpload()->getRenameOrReplaceform($file, $original_name),
			'buttons' => array(
				array(
					'name'    => 'submit',
					'type'    => 'submit',
					'value'   => 'finish',
					'text'    => 'btn_finish_upload',
					'working' => 'btn_saving'
				),
				array(
					'name'    => 'submit',
					'type'    => 'submit',
					'value'   => 'cancel',
					'class'   => 'draft',
					'text'    => 'btn_cancel_upload',
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
		if ( ! ee()->cp->allowed_group('can_upload_new_files'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

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

		$original_name = ee()->session->flashdata('original_name');
		if ($original_name)
		{
			return $this->overwriteOrRename($file, $original_name);
		}

		$result = ee('File')->makeUpload()->resolveNameConflict($file_id);

		if (isset($result['cancel']) && $result['cancel'])
		{
			ee()->functions->redirect(ee('CP/URL')->make('files/directory/' . $file->upload_location_id));
			return;
		}

		if ($result['success'])
		{
			$alert = NULL;
			if (isset($result['warning']))
			{
				$alert = ee('CP/Alert')->makeInline('metadata')
					->asWarning()
					->addToBody($result['warning']);
			}
			$this->saveFileAndRedirect($result['params']['file'], TRUE, $alert);
		}
		else
		{
			$this->overwriteOrRename($result['params']['file'], $result['params']['name']);
		}
	}

	public function rmdir()
	{
		if ( ! ee()->cp->allowed_group('can_delete_upload_directories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$id = ee()->input->post('dir_id');
		$dir = ee('Model')->get('UploadDestination', $id)
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

		$dir->Files->delete(); // @TODO Remove this once cascading works
		$dir->delete();

		ee('CP/Alert')->makeInline('files-form')
			->asSuccess()
			->withTitle(lang('upload_directory_removed'))
			->addToBody(sprintf(lang('upload_directory_removed_desc'), $dir->name))
			->defer();

		$return_url = ee('CP/URL')->make('files');

		if (ee()->input->post('return'))
		{
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

		if ( ! $action)
		{
			return;
		}
		elseif ($action == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
		}
		elseif ($action == 'download')
		{
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
		if ( ! is_array($file_ids))
		{
			$file_ids = array($file_ids);
		}

		// Create the Zip Archive
		$zipfilename = tempnam(sys_get_temp_dir(), '');
		$zip = new ZipArchive();
		if ($zip->open($zipfilename, ZipArchive::CREATE) !== TRUE)
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('error_export'))
				->addToBody(lang('error_cannot_create_zip'))
				->now();
			return;
		}

		$member_group = ee()->session->userdata['group_id'];

		// Loop through the files and add them to the zip
		$files = ee('Model')->get('File', $file_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all()
			->filter(function($file) use ($member_group) {
				return $file->memberGroupHasAccess($member_group);
			});

		foreach ($files as $file)
		{
			if ( ! $file->exists())
			{
				continue;
			}

			$res = $zip->addFile($file->getAbsolutePath(), $file->file_name);

			if ($res === FALSE)
			{
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
		if ( ! ee()->cp->allowed_group('can_delete_files'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! is_array($file_ids))
		{
			$file_ids = array($file_ids);
		}

		$member_group = ee()->session->userdata['group_id'];

		$files = ee('Model')->get('File', $file_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all()
			->filter(function($file) use ($member_group) {
				return $file->memberGroupHasAccess($member_group);
			});

		$names = array();
		foreach ($files as $file)
		{
			$names[] = $file->title;
			$file->delete();
		}

		ee('CP/Alert')->makeInline('files-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('files_removed_desc'))
			->addToBody($names)
			->defer();
	}
}

// EOF
