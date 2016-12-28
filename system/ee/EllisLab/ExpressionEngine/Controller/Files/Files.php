<?php

namespace EllisLab\ExpressionEngine\Controller\Files;

use ZipArchive;
use EllisLab\ExpressionEngine\Controller\Files\AbstractFiles as AbstractFilesController;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;
use EllisLab\ExpressionEngine\Library\CP\Table;

use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\File\UploadDestination;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Files Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
		$vars['can_upload_files'] = ee()->cp->allowed_group('can_upload_new_files');

		$this->generateSidebar($id);
		$this->stdHeader();
		ee()->view->cp_page_title = lang('file_manager');
		ee()->view->cp_heading = sprintf(lang('files_in_directory'), $dir->name);

		// Check to see if they can sync the directory
		ee()->view->can_sync_directory = ee()->cp->allowed_group('can_upload_new_files')
			&& $dir->memberGroupHasAccess(ee()->session->userdata('group_id'));

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

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

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
					$file = ee('Model')->get('File', $upload_response['file_id'])->first();
					$file->upload_location_id = $dir_id;
					$file->site_id = ee()->config->item('site_id');

					// Validate handles setting properties...
					$this->validateFile($file);

					$this->saveFileAndRedirect($file, TRUE);
				}
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
		$this->stdHeader();
		ee()->view->cp_page_title = lang('file_upload');

		ee()->cp->render('settings/form', $vars);
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

			$res = $zip->addFile($file->getAbsolutePath());

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
