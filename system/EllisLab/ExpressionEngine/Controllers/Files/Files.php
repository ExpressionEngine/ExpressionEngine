<?php

namespace EllisLab\ExpressionEngine\Controllers\Files;

use ZipArchive;
use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Service\Model\Collection;
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
class Files extends CP_Controller {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_content', 'can_access_files'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('filemanager');
	}

	protected function sidebarMenu($active = NULL)
	{
		$active_id = NULL;
		if (is_numeric($active))
		{
			$active_id = (int) $active;
		}

		// Register our menu
		$vars = array(
			'upload_directories' => array()
		);

		// Template Groups
		$is_admin = ee()->session->userdata['group_id'] == 1;

		$upload_destinations = ee('Model')->get('UploadDestination')
			->filter('site_id', ee()->config->item('site_id'));

		if (ee()->session->userdata['group_id'] != 1)
		{
			// Add filter to exclude any directories the user's group
			// has been denied access
		}

		foreach ($upload_destinations->all() as $destination)
		{
			$class = ($active_id == $destination->id) ? 'act' : '';

			$data = array(
				'name' => $destination->name,
				'id' => $destination->id,
				'url' => cp_url('files/directory/' . $destination->id),
				'edit_url' => cp_url('settings/upload/edit/' . $destination->id),
			);

			if ( ! empty($class))
			{
				$data['class'] = $class;
			}

			$vars['upload_directories'][] = $data;
		}

		ee()->view->left_nav = ee('View')->make('files/menu')->render($vars);
		ee()->cp->add_js_script(array(
			'file' => array('cp/files/menu'),
		));
	}

	protected function stdHeader()
	{
		ee()->view->header = array(
			'title' => lang('file_manager'),
			'form_url' => cp_url('files/search'),
			'toolbar_items' => array(
				'download' => array(
					'href' => cp_url('files/export'),
					'title' => lang('export_all')
				)
			),
			'search_button_value' => lang('search_files')
		);
	}

	protected function buildTableFromFileCollection(Collection $files, $limit = 20)
	{
		$table = Table::create(array('autosort' => TRUE, 'limit' => $limit));
		$table->setColumns(
			array(
				'title_or_name',
				'file_type',
				'date_added',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText(lang('no_uploaded_files'));

		$data = array();

		$file_id = ee()->session->flashdata('file_id');

		foreach ($files as $file)
		{
			$toolbar = array(
				'view' => array(
					'href' => '',
					'rel' => 'modal-view-file',
					'class' => 'm-link',
					'title' => lang('view'),
					'data-file-id' => $file->file_id
				),
				'edit' => array(
					'href' => cp_url('files/file/edit/' . $file->file_id),
					'title' => lang('edit')
				),
				'crop' => array(
					'href' => cp_url('files/file/crop/' . $file->file_id),
					'title' => lang('crop'),
				),
				'download' => array(
					'href' => cp_url('files/file/download/' . $file->file_id),
					'title' => lang('download'),
				),
			);

			if ( ! $file->isImage())
			{
				unset($toolbar['view']);
				unset($toolbar['crop']);
			}

			$column = array(
				$file->title . '<br><em class="faded">' . $file->rel_path . '</em>',
				$file->mime_type,
				ee()->localize->human_time($file->upload_date),
				array('toolbar_items' => $toolbar),
				array(
					'name' => 'selection[]',
					'value' => $file->file_id,
					'data' => array(
						'confirm' => lang('file') . ': <b>' . htmlentities($file->title, ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();

			if ($file_id && $file->file_id == $file_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);

		return $table;
	}

	protected function hasFileGroupAccessPrivileges(UploadDestination $dir)
	{
		$member_group_id = ee()->session->userdata['group_id'];
		// If the user is a Super Admin, return true
		if ($member_group_id == 1)
		{
			return TRUE;
		}

		if ( ! $file)
		{
			return FALSE;
		}

		// if $member_group_id not in $dir->getNoAccess()
		// return TRUE;

		return FALSE;
	}

	public function index()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
		}
		elseif (ee()->input->post('bulk_action') == 'download')
		{
			$this->exportFiles(ee()->input->post('selection'));
		}

		$base_url = new URL('files', ee()->session->session_id());

		$files = ee('Model')->get('File')
			->filter('site_id', ee()->config->item('site_id'));

		if (ee()->session->userdata['group_id'] != 1)
		{
			// Add filter to exclude any directories the user's group
			// has been denied access
		}

		$filters = ee('Filter')
			->add('Perpage', $files->count(), 'show_all_files');

		$table = $this->buildTableFromFileCollection($files->all(), $filters->values()['perpage']);

		$base_url->setQueryStringVariable('sort_col', $table->sort_col);
		$base_url->setQueryStringVariable('sort_dir', $table->sort_dir);

		ee()->view->filters = $filters->render($base_url);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$pagination = new Pagination(
				$vars['table']['limit'],
				$vars['table']['total_rows'],
				$vars['table']['page']
			);
			$vars['pagination'] = $pagination->cp_links($base_url);
		}

		$upload_destinations = ee('Model')->get('UploadDestination')
			->fields('id', 'name')
			->filter('site_id', ee()->config->item('site_id'));

		if (ee()->session->userdata['group_id'] != 1)
		{
			// Add filter to exclude any directories the user's group
			// has been denied access
		}

		$vars['directories'] = $upload_destinations->all();

		$this->sidebarMenu(NULL);
		$this->stdHeader();
		ee()->view->cp_page_title = lang('file_manager');
		ee()->view->cp_heading = lang('all_files');

		ee()->cp->render('files/index', $vars);
	}

	public function directory($id)
	{
		$dir = ee('Model')->get('UploadDestination', $id)->first();

		if ( ! $dir)
		{
			show_error(lang('no_upload_destination'));
		}

		if ( ! $this->hasFileGroupAccessPrivileges($dir))
		{
			show_error(lang('unauthorized_access'));
		}

		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
		}
		elseif (ee()->input->post('bulk_action') == 'download')
		{
			$this->exportFiles(ee()->input->post('selection'));
		}

		$base_url = new URL('files/directory/' . $id, ee()->session->session_id());

		$filters = ee('Filter')
			->add('Perpage', $dir->getFiles()->count(), 'show_all_files');

		$table = $this->buildTableFromFileCollection($dir->getFiles(), $filters->values()['perpage']);

		$base_url->setQueryStringVariable('sort_col', $table->sort_col);
		$base_url->setQueryStringVariable('sort_dir', $table->sort_dir);

		ee()->view->filters = $filters->render($base_url);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];
		$vars['dir_id'] = $id;

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$pagination = new Pagination(
				$vars['table']['limit'],
				$vars['table']['total_rows'],
				$vars['table']['page']
			);
			$vars['pagination'] = $pagination->cp_links($base_url);
		}

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

		if (ee()->session->userdata['group_id'] != 1)
		{
			// Add filter to exclude any directories the user's group
			// has been denied access
		}

		$this->exportFiles($files->all()->pluck('file_id'));
	}

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
			ee('Alert')->makeInline('settings-form')
				->asIssue()
				->withTitle(lang('error_export'))
				->addToBody(lang('error_cannot_create_zip'));
			return;
		}

		// Loop through the files and add them to the zip
		$files = ee('Model')->get('File', $file_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		foreach ($files as $file)
		{
			if ($this->hasFileGroupAccessPrivileges($file->getUploadDestination()))
			{
				$res = $zip->addFile($file->getAbsolutePath());

				if ($res === FALSE)
				{
					ee('Alert')->makeInline('settings-form')
						->asIssue()
						->withTitle(lang('error_export'))
						->addToBody(sprintf(lang('error_cannot_add_file_to_zip'), $file->title));
					return;

					$zip->close();
					unlink($zipfilename);
				}
			}
		}

		$zip->close();

		$data = file_get_contents($zipfilename);
		unlink($zipfilename);

		ee()->load->helper('download');
		force_download('ExpressionEngine-files-export.zip', $data);
	}
}
// EOF