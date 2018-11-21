<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Files;

use CP_Controller;

use EllisLab\ExpressionEngine\Model\File\UploadDestination;
use EllisLab\ExpressionEngine\Model\File\File;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Model\Content\FieldFacade;
use EllisLab\ExpressionEngine\Model\Content\Display\FieldDisplay;

/**
 * Abstract Files Controller
 */
abstract class AbstractFiles extends CP_Controller {

	protected $no_access;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_files'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('filemanager');

		ee()->view->can_edit_upload_directories = ee()->cp->allowed_group('can_edit_upload_directories');
	}

	protected function getNoAccess()
	{
		if ( ! is_array($this->no_access))
		{
			$this->no_access = [];

			if (ee()->session->userdata('group_id') != 1)
			{
				$query = ee('Model/Datastore')->rawQuery();
				$query->where('member_group', ee()->session->userdata('group_id'));
				$query->from('upload_no_access');
				$result = $query->get();

				foreach ($result->result_array() as $row)
				{
					$this->no_access[] = $row['upload_id'];
				}
			}
		}

		return $this->no_access;
	}

	protected function generateSidebar($active = NULL)
	{
		$active_id = NULL;
		if (is_numeric($active))
		{
			$active_id = (int) $active;
		}

		$sidebar = ee('CP/Sidebar')->make();

		$header = $sidebar->addHeader(lang('upload_directories'));

		$list = $header->addFolderList('directory')
			->withNoResultsText(lang('zero_directories_found'));

		if (ee()->cp->allowed_group('can_create_upload_directories'))
		{
			$header->withButton(lang('new'), ee('CP/URL')->make('files/uploads/create'));

			$list->withRemoveUrl(ee('CP/URL')->make('files/rmdir', array('return' => ee('CP/URL')->getCurrentUrl()->encode())))
				->withRemovalKey('dir_id');

			$watermark_header = $sidebar->addHeader(lang('watermarks'), ee('CP/URL')->make('files/watermarks'))
				->withButton(lang('new'), ee('CP/URL')->make('files/watermarks/create'));

			if ($active == 'watermark')
			{
				$watermark_header->isActive();
			}
		}

		$upload_destinations = ee('Model')->get('UploadDestination')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('module_id', 0)
			->order('name', 'asc');

		$no_access = $this->getNoAccess();

		if ( ! empty($no_access))
		{
			$upload_destinations->filter('id', 'NOT IN', $no_access);
		}

		foreach ($upload_destinations->all() as $destination)
		{
			$display_name = htmlspecialchars($destination->name, ENT_QUOTES, 'UTF-8');

			$item = $list->addItem($display_name, ee('CP/URL')->make('files/directory/' . $destination->id))
				->withEditUrl(ee('CP/URL')->make('files/uploads/edit/' . $destination->id))
				->withRemoveConfirmation(lang('upload_directory') . ': <b>' . $display_name . '</b>')
				->identifiedBy($destination->id);

			if ( ! ee()->cp->allowed_group('can_edit_upload_directories'))
			{
				$item->cannotEdit();
			}

			if ( ! ee()->cp->allowed_group('can_delete_upload_directories'))
			{
				$item->cannotRemove();
			}

			if ($active_id == $destination->id)
			{
				$item->isActive();
			}
		}

		ee()->cp->add_js_script(array(
			'file' => array('cp/files/menu'),
		));
	}

	protected function stdHeader($active = NULL)
	{
		$upload_destinations = [];
		if (ee()->cp->allowed_group('can_upload_new_files'))
		{
			$upload_destinations = ee('Model')->get('UploadDestination')
				->fields('id', 'name')
				->filter('site_id', ee()->config->item('site_id'))
				->filter('module_id', 0)
				->all();

			$no_access = $this->getNoAccess();

			if ( ! empty($no_access))
			{
				$upload_destinations->filter('id', 'NOT IN', $no_access);
			}

			$choices = [];
			foreach ($upload_destinations as $upload)
			{
				$choices[ee('CP/URL')->make('files/upload/' . $upload->getId())->compile()] = $upload->name;
			}
		}

		$toolbar_items = [];

		if (ee('Model')->get('File')->count())
		{
			$toolbar_items['export'] = [
				'href'  => ee('CP/URL')->make('files/export'),
				'title' => lang('export_all')
			];
		}

		if ($active !== NULL)
		{
			$toolbar_items['sync'] = [
				'href'  => ee('CP/URL')->make('files/uploads/sync/' . $active),
				'title' => lang('sync')
			];
		}

		ee()->view->header = array(
			'title' => lang('file_manager'),
			'toolbar_items' => $toolbar_items,
			'action_button' => ee()->cp->allowed_group('can_upload_new_files') && $upload_destinations->count() ? [
				'text' => lang('upload_file'),
				'filter_placeholder' => lang('filter_upload_directories'),
				'choices' => count($choices) > 1 ? $choices : NULL,
				'href' => ee('CP/URL')->make('files/upload/' . $upload_destinations->first()->getId())->compile()
			] : NULL
		);
	}

	protected function buildTable($files, $limit, $offset)
	{
		$table = ee('CP/Table', array(
			'sort_col'   => 'date_added',
			'sort_dir'   => 'desc',
			'class'      => 'tbl-fixed'
		));

		$table->setColumns(
			array(
				'title_or_name' => array(
					'encode' => FALSE,
					'attrs' => array(
						'width' => '40%'
					),
				),
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

		$table->setNoResultsText(sprintf(lang('no_found'), lang('files')));

		$sort_col = $table->sort_col;

		$sort_map = array(
			'title_or_name' => 'title',
			'file_type' => 'mime_type',
			'date_added' => 'upload_date'
		);

		if ( ! array_key_exists($sort_col, $sort_map))
		{
			throw new \Exception("Invalid sort column: ".htmlentities($sort_col));
		}

		$files = $files->order($sort_map[$sort_col], $table->sort_dir)
			->limit($limit)
			->offset($offset)
			->all();

		$data = array();
		$missing_files = FALSE;

		$file_id = ee()->session->flashdata('file_id');
		$member_group = ee()->session->userdata['group_id'];

		foreach ($files as $file)
		{
			if ( ! $file->memberGroupHasAccess($member_group))
			{
				continue;
			}

			$edit_link =  ee('CP/URL')->make('files/file/edit/' . $file->file_id);
			$toolbar = array(
				'view' => array(
					'href' => '',
					'rel' => 'modal-view-file',
					'class' => 'm-link',
					'title' => lang('view'),
					'data-file-id' => $file->file_id
				),
				'edit' => array(
					'href' => $edit_link,
					'title' => lang('edit')
				),
				'crop' => array(
					'href' => ee('CP/URL')->make('files/file/crop/' . $file->file_id),
					'title' => lang('crop'),
				),
				'download' => array(
					'href' => ee('CP/URL')->make('files/file/download/' . $file->file_id),
					'title' => lang('download'),
				),
			);

			if ( ! ee()->cp->allowed_group('can_edit_files'))
			{
				unset($toolbar['view']);
				unset($toolbar['edit']);
				unset($toolbar['crop']);
			}

			if ( ! $file->isImage())
			{
				unset($toolbar['view']);
				unset($toolbar['crop']);
			}

			$file_description = $file->title;

			if (ee()->cp->allowed_group('can_edit_files'))
			{
				$file_description = '<a href="'.$edit_link.'">'.$file->title.'</a>';
			}

			$column = array(
				$file_description.'<br><em class="faded">' . $file->file_name . '</em>',
				$file->mime_type,
				ee()->localize->human_time($file->upload_date),
				array('toolbar_items' => $toolbar),
				array(
					'name' => 'selection[]',
					'value' => $file->file_id,
					'data' => array(
						'confirm' => lang('file') . ': <b>' . htmlentities($file->title, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			$attrs = array();

			if ( ! $file->exists())
			{
				$attrs['class'] = 'missing';
				$missing_files = TRUE;
			}

			if ($file_id && $file->file_id == $file_id)
			{
				if (array_key_exists('class', $attrs))
				{
					$attrs['class'] .= ' selected';
				}
				else
				{
					$attrs['class'] = 'selected';
				}
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);

		if ($missing_files)
		{
			ee('CP/Alert')->makeInline('missing-files')
				->asWarning()
				->cannotClose()
				->withTitle(lang('files_not_found'))
				->addToBody(lang('files_not_found_desc'))
				->now();
		}

		return $table;
	}

	protected function validateFile(File $file)
	{
		return ee('File')->makeUpload()->validateFile($file);
	}

	protected function saveFileAndRedirect(File $file, $is_new = FALSE, $sub_alert = NULL)
	{
		$action = ($is_new) ? 'upload_filedata' : 'edit_file_metadata';

		if ($file->isNew())
		{
			$file->uploaded_by_member_id = ee()->session->userdata('member_id');
			$file->upload_date = ee()->localize->now;
		}

		$file->modified_by_member_id = ee()->session->userdata('member_id');
		$file->modified_date = ee()->localize->now;

		$file->save();

		$alert = ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang($action . '_success'))
			->addToBody(sprintf(lang($action . '_success_desc'), $file->title));

		if ($sub_alert)
		{
			$alert->setSubAlert($sub_alert);
		}

		$alert->defer();

		if ($action == 'upload_filedata')
		{
			ee()->session->set_flashdata('file_id', $file->file_id);
		}

		ee()->functions->redirect(ee('CP/URL')->make('files/directory/' . $file->upload_location_id));
	}

	protected function listingsPage($files, $base_url)
	{
		$vars = array();
		$search_terms = ee()->input->get_post('filter_by_keyword');

		if ($search_terms)
		{
			$base_url->setQueryStringVariable('fliter_by_keyword', $search_terms);
			$files->search(['title', 'file_name', 'mime_type'], $search_terms);
			$vars['search_terms'] = htmlentities($search_terms, ENT_QUOTES, 'UTF-8');
		}

		$total_files = $files->count();
		$vars['total_files'] = $total_files;

		$filters = ee('CP/Filter')
			->add('Keyword')
			->add('Perpage', $total_files, 'show_all_files');

		$filter_values = $filters->values();

		$perpage = $filter_values['perpage'];
		$page = ((int) ee()->input->get('page')) ?: 1;
		$offset = ($page - 1) * $perpage;

		$base_url->addQueryStringVariables($filter_values);
		$table = $this->buildTable($files, $perpage, $offset);

		$base_url->setQueryStringVariable('sort_col', $table->sort_col);
		$base_url->setQueryStringVariable('sort_dir', $table->sort_dir);

		ee()->view->filters = $filters->render($base_url);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		$vars['pagination'] = ee('CP/Pagination', $total_files)
			->perPage($perpage)
			->currentPage($page)
			->render($base_url);

		ee()->javascript->set_global('file_view_url', ee('CP/URL')->make('files/file/view/###')->compile());
		ee()->javascript->set_global('lang.remove_confirm', lang('file') . ': <b>### ' . lang('files') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
				'cp/files/manager'
			),
		));

		return $vars;
	}
}

// EOF
