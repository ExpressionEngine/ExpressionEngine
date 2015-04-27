<?php

namespace EllisLab\ExpressionEngine\Controllers\Files;

use CP_Controller;

use EllisLab\ExpressionEngine\Model\File\UploadDestination;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Library\CP\Table;

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
 * ExpressionEngine CP Abstract Files Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
abstract class AbstractFiles extends CP_Controller {

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
				'edit_url' => cp_url('files/uploads/edit/' . $destination->id),
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

}
// EOF