<?php

namespace EllisLab\ExpressionEngine\Controllers\Files;

use ZipArchive;
use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
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
class Files extends CP_Controller {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_content', 'can_access_files'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('filemanager');

		ee()->view->can_admin_upload_prefs = ee()->cp->allowed_group('can_admin_upload_prefs');
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
			'can_admin_upload_prefs' => ee()->cp->allowed_group('can_admin_upload_prefs'),
			'upload_directories' => array()
		);

		$upload_destinations = ee('Model')->get('UploadDestination')
			->filter('site_id', ee()->config->item('site_id'));

		foreach ($upload_destinations->all() as $destination)
		{
			if ($this->hasFileGroupAccessPrivileges($destination) === FALSE)
			{
				continue;
			}

			$class = ($active_id == $destination->id) ? 'act' : '';

			$data = array(
				'name' => $destination->name,
				'id' => $destination->id,
				'url' => cp_url('files/directory/' . $destination->id),
				'edit_url' => cp_url('settings/uploads/edit/' . $destination->id),
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
			if ( ! $file->getUploadDestination()
				|| $this->hasFileGroupAccessPrivileges($file->getUploadDestination()) === FALSE)
			{
				continue;
			}

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
				$file->title . '<br><em class="faded">' . $file->file_name . '</em>',
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
		// 2 = Banned
		// 3 = Guests
		// 4 = Pending
		$hardcoded_disallowed_groups = array('2', '3', '4');

		$member_group_id = ee()->session->userdata['group_id'];
		// If the user is a Super Admin, return true
		if ($member_group_id == 1)
		{
			return TRUE;
		}

		if (in_array($member_group_id, $hardcoded_disallowed_groups))
		{
			return FALSE;
		}

		if ( ! $dir)
		{
			return FALSE;
		}

		if (in_array($member_group_id, $dir->getNoAccess()->pluck('group_id')))
		{
			return FALSE;
		}

		return TRUE;
	}

	public function index()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(cp_url('files', ee()->cp->get_url_state()));
		}
		elseif (ee()->input->post('bulk_action') == 'download')
		{
			$this->exportFiles(ee()->input->post('selection'));
		}

		$base_url = new URL('files', ee()->session->session_id());

		$files = ee('Model')->get('File')
			->filter('site_id', ee()->config->item('site_id'));

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

		ee()->javascript->set_global('file_view_url', cp_url('files/file/view/###'));
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
		ee()->view->cp_heading = lang('all_files');

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

		if ( ! $this->hasFileGroupAccessPrivileges($dir))
		{
			show_error(lang('unauthorized_access'));
		}

		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(cp_url('files/directory/' . $id, ee()->cp->get_url_state()));
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

		ee()->javascript->set_global('file_view_url', cp_url('files/file/view/###'));
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

	public function picker()
	{
		$directories = array();
		$dirs = ee('Model')->get('UploadDestination')
			->filter('site_id', ee()->config->item('site_id'))
			->all();
		
		foreach($dirs as $dir)
		{
			$directories[$dir->id] = $dir;
		}

		if ( ! empty(ee()->input->get('directory')))
		{
			$id = ee()->input->get('directory');
		}

		if (empty($id) || $id == 'all')
		{
			$id = 'all';
			$files = ee('Model')->get('File')
				->filter('site_id', ee()->config->item('site_id'))->all();
		}
		else
		{
			$dir = $directories[$id];
			$files = $dir->getFiles();
		}

		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(cp_url('files/directory/' . $id, ee()->cp->get_url_state()));
		}
		elseif (ee()->input->post('bulk_action') == 'download')
		{
			$this->exportFiles(ee()->input->post('selection'));
		}

		$base_url = new URL('files/picker', ee()->session->session_id());

		$filters = ee('Filter')->add('Perpage', $files->count(), 'show_all_files');

		$directories = array_map(function($dir) {return $dir->name;}, $directories);
		$directories = array('all' => lang('all')) + $directories;
		$dirFilter = ee('Filter')->make('directory', lang('directory'), $directories);
		$dirFilter->disableCustomValue();

		$filters = $filters->add($dirFilter);

		$table = $this->buildTableFromFileCollection($files, $filters->values()['perpage']);

		$base_url->setQueryStringVariable('sort_col', $table->sort_col);
		$base_url->setQueryStringVariable('sort_dir', $table->sort_dir);
		$base_url->setQueryStringVariable('directory', $id);

		ee()->view->filters = $filters->render($base_url);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];
		$vars['dir'] = $id;

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

		ee()->view->cp_heading = $id == 'all' ? lang('all_files') : sprintf(lang('files_in_directory'), $dir->name);

		ee()->cp->render('_shared/modal_file_picker', $vars);
	}

	public function avatars()
	{
		if ( ! empty(ee()->input->get('directory')))
		{
			$id = ee()->input->get('directory');
		}

		$avatar_path = $this->config->slash_item('avatar_path') . ee()->security->sanitize_filename($dir).'/';
		$avatar_url = $this->config->slash_item('avatar_url') . ee()->security->sanitize_filename($dir).'/';

		// Is this a valid avatar folder?

		$extensions = array('.gif', '.jpg', '.jpeg', '.png');

		if ( ! @is_dir($avatar_path) OR ! $fp = @opendir($avatar_path))
		{
			return array();
		}

		// Grab the image names

		$avatars = array();

		while (FALSE !== ($file = readdir($fp)))
		{
			if (FALSE !== ($pos = strpos($file, '.')))
			{
				if (in_array(substr($file, $pos), $extensions))
				{
					$avatars[] = $file;
				}
			}
		}

		closedir($fp);
		$total_count = count($avatars);

		// Did we succeed?

		if (count($avatars) == 0)
		{
			show_error(lang('avatars_not_found'));
		}

		$filters = ee('Filter')->add('Perpage', $total_count, 'show_all_files');

		$table = Table::create(array('autosort' => TRUE, 'limit' => $filters->values()['perpage']));
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

		foreach ($avatars as $avatar)
		{
			$file = $avatar_path . $avatar;
			$toolbar = array(
				'view' => array(
					'href' => '',
					'rel' => 'modal-view-file',
					'class' => 'm-link',
					'title' => lang('view'),
				)
			);

			$column = array(
				$avatar . '<br><em class="faded">' . $avatar . '</em>',
				filetype($file),
				ee()->localize->human_time(filemtime($file)),
				array('toolbar_items' => $toolbar),
				array(
					'name' => 'selection[]',
					'value' => $avatar,
					'data' => array(
						'confirm' => lang('file') . ': <b>' . htmlentities($avatar, ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);
		$base_url = new URL('files/avatars', ee()->session->session_id());
		$base_url->setQueryStringVariable('sort_col', $table->sort_col);
		$base_url->setQueryStringVariable('sort_dir', $table->sort_dir);

		ee()->view->filters = $filters->render($base_url);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];
		$vars['dir'] = $dir;

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

		ee()->view->cp_heading = sprintf(lang('files_in_directory'), lang($dir));

		ee()->cp->render('_shared/modal_file_picker', $vars);
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

	public function upload($dir_id)
	{
		$dir = ee('Model')->get('UploadDestination', $dir_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $dir)
		{
			show_error(lang('no_upload_destination'));
		}

		if ( ! $this->hasFileGroupAccessPrivileges($dir))
		{
			show_error(lang('unauthorized_access'));
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'has_file_input' => TRUE,
			'base_url' => cp_url('files/upload/' . $dir_id),
			'save_btn_text' => 'btn_upload_file',
			'save_btn_text_working' => 'btn_upload_file_working',
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
				ee('Alert')->makeInline('settings-form')
					->asIssue()
					->withTitle(lang('upload_filedata_error'))
					->addToBody($upload_response['error']);
				break 2;
			}

			$file = ee('Model')->make('File', $upload_response['file_id']);
			$file->upload_location_id = $dir_id;
			$file->site_id = ee()->config->item('site_id');

			$file->mime_type = $upload_response['mime_type'];
			$file->rel_path = $upload_response['rel_path'];
			$file->file_name = $upload_response['file_name'];
			$file->file_size = $upload_response['file_size'];

			$file->title = ee()->input->post('title');
			$file->description = ee()->input->post('description');
			$file->credit = ee()->input->post('credit');
			$file->location = ee()->input->post('location');

			$file->uploaded_by_member_id = ee()->session->userdata('member_id');
			$file->upload_date = ee()->localize->now;
			$file->modified_by_member_id = ee()->session->userdata('member_id');
			$file->modified_date = ee()->localize->now;

			$file->save();

			ee()->session->set_flashdata('file_id', $file->file_id);

			ee('Alert')->makeInline('settings-form')
				->asSuccess()
				->withTitle(lang('upload_filedata_success'))
				->addToBody(sprintf(lang('upload_filedata_success_desc'), $file->title))
				->defer();

			ee()->functions->redirect(cp_url('files/directory/' . $file->upload_location_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('settings-form')
				->asIssue()
				->withTitle(lang('upload_filedata_error'))
				->addToBody(lang('upload_filedata_error_desc'));
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

		if ( ! $this->hasFileGroupAccessPrivileges($dir))
		{
			show_error(lang('unauthorized_access'));
		}

		$dir->delete();

		ee('Alert')->makeInline('files-form')
			->asSuccess()
			->withTitle(lang('upload_directory_removed'))
			->addToBody(sprintf(lang('upload_directory_removed_desc'), $dir->name))
			->defer();

		$return_url = cp_url('files');

		if (ee()->input->post('return'))
		{
			$return = base64_decode(ee()->input->post('return'));
			$uri_elements = json_decode($return, TRUE);

			if ($uri_elements['path'] != 'cp/files/directory/' . $id)
			{
				$return = cp_url($uri_elements['path'], $uri_elements['arguments']);
			}
		}

		ee()->functions->redirect($return_url);
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

	private function remove($file_ids)
	{
		if ( ! is_array($file_ids))
		{
			$file_ids = array($file_ids);
		}

		$files = ee('Model')->get('File', $file_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$names = array();
		foreach ($files as $file)
		{
			if ($this->hasFileGroupAccessPrivileges($file->getUploadDestination()))
			{
				$names[] = $file->title;
				$file->delete();
			}
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
