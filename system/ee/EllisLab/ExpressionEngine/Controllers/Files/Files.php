<?php

namespace EllisLab\ExpressionEngine\Controllers\Files;

use ZipArchive;
use EllisLab\ExpressionEngine\Controllers\Files\AbstractFiles as AbstractFilesController;
use EllisLab\ExpressionEngine\Library\CP\Table;

use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\File\UploadDestination;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class Files extends AbstractFilesController {

	public function index()
	{
		$this->handleBulkActions(ee('CP/URL', 'files', ee()->cp->get_url_state()));

		$base_url = ee('CP/URL', 'files');

		$search_terms = ee()->input->get_post('search');
		if ($search_terms)
		{
			$base_url->setQueryStringVariable('search', $search_terms);
		}

		$files = ee('Model')->get('File')
			->filter('site_id', ee()->config->item('site_id'));

		$filters = ee('Filter')
			->add('Perpage', $files->count(), 'show_all_files');

		$filter_values = $filters->values();
		$base_url->addQueryStringVariables($filter_values);
		$table = $this->buildTableFromFileCollection($files->all(), $filter_values['perpage']);

		$base_url->setQueryStringVariable('sort_col', $table->sort_col);
		$base_url->setQueryStringVariable('sort_dir', $table->sort_dir);

		ee()->view->filters = $filters->render($base_url);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($base_url);

		$upload_destinations = ee('Model')->get('UploadDestination')
			->fields('id', 'name')
			->filter('site_id', ee()->config->item('site_id'));

		$upload_destinations = $upload_destinations->all();

		if (ee()->session->userdata['group_id'] != 1)
		{
			$member_group = ee()->session->userdata['group_id'];
			$upload_destinations->filter(function($dir) use ($member_group){
				return $dir->memberGroupHasAccess($member_group);
			});
		}

		$vars['directories'] = $upload_destinations;

		ee()->javascript->set_global('file_view_url', ee('CP/URL', 'files/file/view/###'));
		ee()->javascript->set_global('lang.remove_confirm', lang('file') . ': <b>### ' . lang('files') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/v3/confirm_remove',
				'cp/files/manager'
			),
		));

		$this->sidebarMenu(NULL);
		$this->stdHeader();
		ee()->view->cp_page_title = lang('file_manager');

		// Set search results heading
		if ( ! empty($vars['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$vars['table']['total_rows'],
				$vars['table']['search']
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
			show_error(lang('unauthorized_access'));
		}

		$this->handleBulkActions(ee('CP/URL', 'files/directory/' . $id, ee()->cp->get_url_state()));

		$base_url = ee('CP/URL', 'files/directory/' . $id);

		$filters = ee('Filter')
			->add('Perpage', $dir->getFiles()->count(), 'show_all_files');

		$filter_values = $filters->values();
		$table = $this->buildTableFromFileCollection($dir->getFiles(), $filter_values['perpage']);

		$base_url->setQueryStringVariable('sort_col', $table->sort_col);
		$base_url->setQueryStringVariable('sort_dir', $table->sort_dir);

		ee()->view->filters = $filters->render($base_url);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];
		$vars['dir_id'] = $id;

		$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($base_url);

		ee()->javascript->set_global('file_view_url', ee('CP/URL', 'files/file/view/###'));
		ee()->javascript->set_global('lang.remove_confirm', lang('file') . ': <b>### ' . lang('files') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/v3/confirm_remove',
				'cp/files/manager'
			),
		));

		$this->sidebarMenu($id);
		$this->stdHeader();
		ee()->view->cp_page_title = lang('file_manager');
		ee()->view->cp_heading = sprintf(lang('files_in_directory'), $dir->name);

		ee()->cp->render('files/directory', $vars);
	}

	public function export()
	{
		$files = ee('Model')->get('File')
			->fields('file_id')
			->filter('site_id', ee()->config->item('site_id'));

		$this->exportFiles($files->all()->pluck('file_id'));

		// If we got here the download didn't happen due to an error.
		show_error(lang('error_cannot_create_zip'), 500, lang('error_export'));
	}

	public function upload($dir_id)
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
			show_error(lang('unauthorized_access'));
		}

		if ( ! $dir->exists())
		{
			$upload_edit_url = ee('CP/URL', 'files/uploads/edit/' . $dir->id);
			ee('Alert')->makeStandard()
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
			ee('Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('dir_not_writable'))
				->addToBody(sprintf(lang('dir_not_writable_desc'), $dir->server_path))
				->now();
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'has_file_input' => TRUE,
			'base_url' => ee('CP/URL', 'files/upload/' . $dir_id),
			'save_btn_text' => 'btn_upload_file',
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
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
						'desc' => 'title_desc',
						'fields' => array(
							'title' => array(
								'type' => 'text',
							)
						)
					),
					array(
						'title' => 'description',
						'desc' => 'description_desc',
						'fields' => array(
							'description' => array(
								'type' => 'textarea',
							)
						)
					),
					array(
						'title' => 'credit',
						'desc' => 'credit_desc',
						'fields' => array(
							'credit' => array(
								'type' => 'text',
							)
						)
					),
					array(
						'title' => 'location',
						'desc' => 'location_desc',
						'fields' => array(
							'location' => array(
								'type' => 'text',
							)
						)
					),
				)
			)
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'title',
				'label' => 'lang:title',
				'rules' => 'strip_tags|trim|valid_xss_check'
			),
			array(
				'field' => 'description',
				'label' => 'lang:description',
				'rules' => 'strip_tags|trim|valid_xss_check'
			),
			array(
				'field' => 'credit',
				'label' => 'lang:credit',
				'rules' => 'strip_tags|trim|valid_xss_check'
			),
			array(
				'field' => 'location',
				'label' => 'lang:location',
				'rules' => 'strip_tags|trim|valid_xss_check'
			),
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			// PUNT! @TODO Break away from the old Filemanger Library
			ee()->load->library('filemanager');
			$upload_response = ee()->filemanager->upload_file($dir_id, 'file');
			if (isset($upload_response['error']))
			{
				ee('Alert')->makeInline('shared-form')
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

				$file->title = (ee()->input->post('title')) ?: $file->file_name;
				$file->description = ee()->input->post('description');
				$file->credit = ee()->input->post('credit');
				$file->location = ee()->input->post('location');

				$file->uploaded_by_member_id = ee()->session->userdata('member_id');
				$file->upload_date = ee()->localize->now;
				$file->modified_by_member_id = ee()->session->userdata('member_id');
				$file->modified_date = ee()->localize->now;

				$file->save();
				ee()->session->set_flashdata('file_id', $upload_response['file_id']);

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('upload_filedata_success'))
					->addToBody(sprintf(lang('upload_filedata_success_desc'), $file->title))
					->defer();

				ee()->functions->redirect(ee('CP/URL', 'files/directory/' . $dir_id));
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('upload_filedata_error'))
				->addToBody(lang('upload_filedata_error_desc'))
				->now();
		}

		$this->sidebarMenu($dir_id);
		$this->stdHeader();
		ee()->view->cp_page_title = lang('file_upload');

		ee()->cp->render('settings/form', $vars);
	}

	public function rmdir()
	{
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
			show_error(lang('unauthorized_access'));
		}

		$dir->delete();

		ee('Alert')->makeInline('files-form')
			->asSuccess()
			->withTitle(lang('upload_directory_removed'))
			->addToBody(sprintf(lang('upload_directory_removed_desc'), $dir->name))
			->defer();

		$return_url = ee('CP/URL', 'files');

		if (ee()->input->post('return'))
		{
			$return = base64_decode(ee()->input->post('return'));
			$uri_elements = json_decode($return, TRUE);

			if ($uri_elements['path'] != 'cp/files/directory/' . $id)
			{
				$return = ee('CP/URL', $uri_elements['path'], $uri_elements['arguments']);
			}
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
			ee('Alert')->makeInline('shared-form')
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
				ee('Alert')->makeInline('shared-form')
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

		ee('Alert')->makeInline('files-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('files_removed_desc'))
			->addToBody($names)
			->defer();
	}
}
// EOF
