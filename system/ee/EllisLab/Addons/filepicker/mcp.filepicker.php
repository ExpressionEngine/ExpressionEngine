<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use EllisLab\ExpressionEngine\Model\File\UploadDestination;
use EllisLab\Addons\FilePicker\FilePicker as Picker;

/**
 * File Picker Module control panel
 */
class Filepicker_mcp {

	private $images = FALSE;

	public function __construct()
	{
		$this->picker = new Picker();
		$this->base_url = 'addons/settings/filepicker';
		$this->access = FALSE;

		if (ee()->cp->allowed_group('can_access_files'))
		{
			$this->access = TRUE;
		}

		ee()->lang->loadfile('filemanager');
	}

	protected function getUserUploadDirectories()
	{
		$dirs = ee('Model')->get('UploadDestination')
			->with('NoAccess')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('module_id', 0)
			->order('name', 'asc')
			->all();

		$member_group = ee()->session->userdata['group_id'];

		return $dirs->filter(function($dir) use ($member_group)
		{
			return $dir->memberGroupHasAccess($member_group);
		});
	}

	protected function getSystemUploadDirectories()
	{
		$dirs = ee('Model')->get('UploadDestination')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('module_id', '!=', 0)
			->all();

		return $dirs;
	}

	public function index()
	{
		// check if we have a request for a specific file id
		$file = ee()->input->get('file');

		if ( ! empty($file))
		{
			return $this->fileInfo($file);
		}

		if ($this->access === FALSE)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$dirs = $this->getUserUploadDirectories();

		// directories we were asked to list
		$show = ee()->input->get('directories');

		// directory filter
		$requested = ee()->input->get('directory') ?: 'all';

		$show = (empty($show) && $requested == 'all') ? 'all' : $show;

		if ($show != 'all')
		{
			$dirs = $dirs->filter('id', (int) $show);
		}

		// only have one? use it
		if ($dirs->count() == 1)
		{
			$requested = $dirs->first()->id;
		}

		$directories = $dirs->indexBy('id');
		$files = NULL;
		$nodirs = FALSE;

		$vars['search_allowed'] = FALSE;

		if ($requested == 'all')
		{
			$files = ee('Model')->get('File')
				->filter('upload_location_id', 'IN', $dirs->getIds())
				->filter('site_id', ee()->config->item('site_id'));

			$dir_ids = $dirs->getIds();

			if (empty($dir_ids))
			{
				$nodirs = TRUE;
				$files->markAsFutile();
			}

			$this->search($files);
			$this->sort($files);
			$vars['search_allowed'] = TRUE;

			$total_files = $files->count();

			$type = ee()->input->get('type') ?: 'list';
		}
		else
		{
			// selected something but we don't have that directory? check
			// the system dirs, just in case
			if (empty($directories[$requested]))
			{
				$system_dirs = $this->getSystemUploadDirectories()->indexBy('id');

				if (empty($system_dirs[$requested]))
				{
					show_error(lang('no_upload_destination'));
				}

				$dir = $system_dirs[$requested];
				$files = $dir->getFilesystem()->all();
				$total_files = iterator_count($files);
			}
			else
			{
				$dir = $directories[$requested];

				$files = ee('Model')->get('File')
					->filter('upload_location_id', $dir->getId())
					->filter('site_id', ee()->config->item('site_id'));

				$this->search($files);
				$this->sort($files);
				$vars['search_allowed'] = TRUE;

				$total_files = $files->count();
			}

			$type = ee()->input->get('type') ?: $dir->default_modal_view;
		}

		$has_filters = ee()->input->get('hasFilters');

		$base_url = ee('CP/URL', $this->base_url);
		$base_url->setQueryStringVariable('directories', $show);
		$base_url->setQueryStringVariable('directory', $requested);
		$base_url->setQueryStringVariable('type', $type);

		$vars['search'] = ee()->input->get('search');
		$base_url->setQueryStringVariable('search', $vars['search']);

		if ($has_filters !== '0')
		{
			$vars['type'] = $type;
			$filters = ee('CP/Filter');

			if (count($directories) > 1)
			{
				$directories = array_map(function($dir) {return $dir->name;}, $directories);
				$directories = array('all' => lang('all')) + $directories;

				$dirFilter = ee('CP/Filter')->make('directory', lang('directory'), $directories)
					->disableCustomValue()
					->setDefaultValue($requested);

				$filters = ee('CP/Filter')->add($dirFilter);
			}

			$imgOptions = array(
				'thumb' => 'thumbnails',
				'list' => 'list'
			);

			$imgFilter = ee('CP/Filter')->make('type', lang('picker_type'), $imgOptions)
				->disableCustomValue()
				->setDefaultValue($type);

			$filters = $filters->add('Perpage', $total_files, 'show_all_files', $imgFilter->value() == 'list');

			$filters = $filters->add($imgFilter);

			$perpage = $filters->values();
			$perpage = $perpage['perpage'];
			$base_url->setQueryStringVariable('perpage', $perpage);

			$page = ((int) ee()->input->get('page')) ?: 1;
			$offset = ($page - 1) * $perpage; // Offset is 0 indexed

			$vars['filters'] = $filters->render($base_url);
		}
		else
		{
			$base_url->setQueryStringVariable('hasFilters', $has_filters);

			$perpage = 25;
			$page = ((int) ee()->input->get('page')) ?: 1;
			$offset = ($page - 1) * $perpage; // Offset is 0 indexed
		}


		if ( ! $files instanceOf \Iterator)
		{
			$files = $files->limit($perpage)->offset($offset)->all();
			$files = $files->getIterator();
		}
		else
		{
			$files = new \LimitIterator($files, $offset, $perpage);
		}

		if (ee()->input->get('hasUpload') !== '0')
		{
			$vars['upload'] = ee('CP/URL', $this->picker->base_url."upload");
			$vars['upload']->setQueryStringVariable('directory', $requested);
		}

		$vars['dir'] = $requested;

		if (($this->images || $type == 'thumb') && $total_files > 0)
		{
			$vars['type'] = 'thumb';
			$vars['files'] = $files;
			$vars['form_url'] = $base_url;
			$vars['data_url_base'] = $this->base_url;
		}
		else
		{
			$table = $this->picker->buildTableFromFileCollection($files, $perpage, ee()->input->get_post('selected'));

			// Display Upload button if we can
			if (isset($vars['upload']) && is_numeric($vars['dir']))
			{
				$table->addActionButton($vars['upload'], lang('upload_new_file'));
			}

			// show a slightly different message if we have no upload directories
			if ($nodirs)
			{
				if (ee()->cp->allowed_group('can_create_upload_directories'))
				{
					$table->setNoResultsText(
						lang('zero_upload_directories_found'),
						lang('create_new'),
						ee('CP/URL')->make('files/uploads/create'),
						TRUE
					);
				}
				else
				{
					$table->setNoResultsText(lang('zero_upload_directories_found'));
				}
			}

			$base_url->setQueryStringVariable('sort_col', $table->sort_col);
			$base_url->setQueryStringVariable('sort_dir', $table->sort_dir);

			$vars['type'] = $type;
			$vars['files'] = array();
			$vars['table'] = $table->viewData($base_url);
			$vars['form_url'] = $vars['table']['base_url'];
		}

		$vars['pagination'] = ee('CP/Pagination', $total_files)
			->perPage($perpage)
			->currentPage($page)
			->render($base_url);

		$vars['cp_heading'] = $requested == 'all' ? lang('all_files') : sprintf(lang('files_in_directory'), $dir->name);

		return ee('View')->make('filepicker:ModalView')->render($vars);
	}

	/**
	 * Applies a search filter to a Files builder object
	 */
	private function search($files)
	{
		if ($search = ee()->input->get('search'))
		{
			$files
				->filterGroup()
				->filter('title', 'LIKE', '%' . $search . '%')
				->orFilter('file_name', 'LIKE', '%' . $search . '%')
				->orFilter('mime_type', 'LIKE', '%' . $search . '%')
				->endFilterGroup();
		}
	}

	/**
	 * Applies sort to a Files builder object
	 */
	private function sort($files)
	{
		$sort_col = ee()->input->get('sort_col');

		$sort_map = array(
			'title_or_name' => 'file_name',
			'file_type' => 'mime_type',
			'date_added' => 'upload_date'
		);

		if (array_key_exists((string) $sort_col, $sort_map))
		{
			$files->order($sort_map[$sort_col], ee()->input->get('sort_dir'));
		}
		else
		{
			$files->order('upload_date', 'desc');
		}
	}

	public function modal()
	{
		$this->base_url = $this->picker->controller;
		ee()->output->_display($this->index());
		exit();
	}

	public function images()
	{
		$this->images = TRUE;
		$this->base_url = $this->picker->base_url . 'images';
		ee()->output->_display($this->index());
		exit();
	}

	/**
	 * Return an AJAX response for a particular file ID
	 *
	 * @param mixed $id
	 * @access private
	 * @return void
	 */
	private function fileInfo($id)
	{
		$file = ee('Model')->get('File', $id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $file || ! $file->exists())
		{
			ee()->output->send_ajax_response(lang('file_not_found'), TRUE);
		}

		$member_group = ee()->session->userdata['group_id'];

		if ($file->memberGroupHasAccess($member_group) === FALSE || $this->access === FALSE)
		{
			ee()->output->send_ajax_response(lang('unauthorized_access'), TRUE);
		}

		$result = $file->getValues();

		$result['path'] = $file->getAbsoluteURL();
		$result['thumb_path'] = ee('Thumbnail')->get($file)->url;
		$result['isImage'] = $file->isImage();
		$result['isSVG'] = $file->isSVG();

		ee()->output->send_ajax_response($result);
	}

	public function upload()
	{
		$dir_id = ee()->input->get('directory');

		if (empty($dir_id))
		{
			show_404();
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
					return $this->overwriteOrRename($file, $result['upload_response']['file_data_orig_name']);
				}

				return $this->saveAndReturn($file);
			}
		}

		$vars = array(
			'required' => TRUE,
			'ajax_validate' => TRUE,
			'has_file_input' => TRUE,
			'base_url' => ee('CP/URL')->make($this->picker->base_url . 'upload', array('directory' => $dir_id)),
			'save_btn_text' => 'btn_upload_file',
			'save_btn_text_working' => 'btn_uploading',
			'sections' => array(),
			'tabs' => array(
				'file_data' => ee('File')->makeUpload()->getFileDataForm($file, $errors),
				'categories' => ee('File')->makeUpload()->getCategoryForm($file, $errors),
			),
			'cp_page_title' => lang('file_upload')
		);

		$out = ee()->cp->render('_shared/form', $vars, TRUE);
		$out = ee()->cp->render('filepicker:UploadView', array('content' => $out));
		ee()->output->enable_profiler(FALSE);
		ee()->output->_display($out);
		exit();
	}

	public function ajaxUpload()
	{
		$dir_id = ee('Request')->post('directory');

		if (empty($dir_id))
		{
			show_404();
		}

		$errors = NULL;

		$result = ee('File')->makeUpload()->uploadTo($dir_id);

		$file = $result['file'];

		if (isset($result['upload_response']['error']))
		{
			return [
				'ajax' => TRUE,
				'body' => [
					'status' => 'error',
					'error' => $result['upload_response']['error']
				]
			];
		}

		if ($result['posted'])
		{
			$errors = $result['validation_result'];

			if ($result['uploaded'])
			{
				if ($file->file_name != $result['upload_response']['file_data_orig_name'])
				{
					$file->save();
					return [
						'ajax' => TRUE,
						'body' => [
							'status'           => 'duplicate',
							'duplicate'        => TRUE,
							'fileId'           => $file->getId(),
							'originalFileName' => $result['upload_response']['file_data_orig_name']
						]
					];
				}

				return [
					'ajax' => TRUE,
					'body' => [
						// Inconsistent casing for backwards compatibility
						'status'             => 'success',
						'title'              => $file->file_name,
						'file_name'          => $file->file_name,
						'isImage'            => $file->isImage(),
						'isSVG'              => $file->isSVG(),
						'thumb_path'         => $file->getAbsoluteThumbnailURL(),
						'upload_location_id' => $file->upload_location_id
					]
				];
			}
		}

		return [
			'ajax' => TRUE,
			'body' => [
				'status' => 'error',
				'error' => $errors
			]
		];
	}

	public function ajaxOverwriteOrRename()
	{
		$file_id = ee('Request')->get('file_id');
		$original_name = ee('Request')->get('original_name');

		$file = ee('Model')->get('File', $file_id)->first();

		return $this->overwriteOrRename($file, $original_name);
	}

	protected function overwriteOrRename($file, $original_name)
	{
		$vars = array(
			'required' => TRUE,
			'base_url' => ee('CP/URL')->make($this->picker->base_url . 'finish-upload/' . $file->file_id),
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
			'cp_page_title' => lang('file_upload_stopped')
		);

		$out = ee()->cp->render('_shared/form', $vars, TRUE);
		$out = ee()->cp->render('filepicker:UploadView', array('content' => $out));
		ee()->output->enable_profiler(FALSE);
		ee()->output->_display($out);
		exit();
	}

	public function finishUpload($file_id)
	{
		$result = ee('File')->makeUpload()->resolveNameConflict($file_id);

		if (isset($result['cancel']) && $result['cancel'])
		{
			ee()->output->send_ajax_response($result);
		}

		if ($result['success'])
		{
			return $this->saveAndReturn($result['params']['file']);
		}
		else
		{
			return $this->overwriteOrRename($result['params']['file'], $result['params']['name']);
		}
	}

	protected function saveAndReturn($file)
	{
		if ($file->isNew())
		{
			$file->uploaded_by_member_id = ee()->session->userdata('member_id');
			$file->upload_date = ee()->localize->now;
		}

		$file->modified_by_member_id = ee()->session->userdata('member_id');
		$file->modified_date = ee()->localize->now;

		$file->save();

		return $this->fileInfo($file->getId());
	}

	protected function ajaxValidation(ValidationResult $result)
	{
		return ee('Validation')->ajax($result);
	}
}

// EOF
