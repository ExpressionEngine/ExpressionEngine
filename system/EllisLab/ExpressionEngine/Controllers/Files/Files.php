<?php

namespace EllisLab\ExpressionEngine\Controllers\Files;

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Service\Model\Collection;

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
				'url' => cp_url('files/directory/' . $destination->name),
				'edit_url' => cp_url('files/directory/edit/' . $destination->name),
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
					'href' => cp_url('design/export'),
					'title' => lang('export_all')
				)
			),
			'search_button_value' => lang('search_files')
		);
	}

	public function index()
	{
		$base_url = new URL('files', ee()->session->session_id());

		$table = Table::create(array('autosort' => TRUE));
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

		$data = array();

		$files = ee('Model')->get('File')
			->filter('site_id', ee()->config->item('site_id'));

		if (ee()->session->userdata['group_id'] != 1)
		{
			// Add filter to exclude any directories the user's group
			// has been denied access
		}

		foreach ($files->all() as $file)
		{
			$toolbar = array(
				'view' => array(
					'href' => '',
					'title' => lang('view')
				),
				'edit' => array(
					'href' => '',
					'title' => lang('edit')
				),
				'crop' => array(
					'href' => '',
					'title' => lang('crop'),
				),
				'download' => array(
					'href' => '',
					'title' => lang('download'),
				),
			);

			if ( ! $file->isImage())
			{
				unset($toolbar['crop']);
			}

			$data[] = array(
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
		}

		$table->setData($data);

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

		$this->sidebarMenu(NULL);
		$this->stdHeader();
		ee()->view->cp_page_title = lang('file_manager');
		ee()->view->cp_heading = lang('all_files');

		ee()->cp->render('files/index', $vars);
	}
}
// EOF