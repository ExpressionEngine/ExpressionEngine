<?php

namespace EllisLab\ExpressionEngine\Controllers\Settings;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

use EllisLab\ExpressionEngine\Library\CP;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Uploads Directories Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Uploads extends Settings {

	public function index()
	{
		if ( ! $this->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->model('file_upload_preferences_model');

		$upload_dirs = ee()->file_upload_preferences_model->get_file_upload_preferences(
			ee()->session->userdata('group_id')
		);

		$data = array();
		foreach ($upload_dirs as $id => $dir)
		{
			$data[] = array(
				$dir['id'],
				$dir['name'],
				array('toolbar_items' => array(
					'view' => array(
						'href' => cp_url(''),
						'title' => lang('upload_btn_view')
					),
					'edit' => array(
						'href' => cp_url(''),
						'title' => lang('upload_btn_edit')
					),
					'sync' => array(
						'href' => cp_url(''),
						'title' => lang('upload_btn_sync')
					)
				)),
				array(
					'name' => 'uploads[]',
					'value' => $dir['id']
				)
			);
		}

		$table = CP\Table::create(array('autosort' => TRUE));
		$table->setColumns(
			array(
				'upload_id' => array(
					'type'	=> CP\Table::COL_ID
				),
				'upload_name',
				'upload_manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_tables_match');
		$table->setData($data);

		$base_url = new CP\URL('settings/uploads', ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		$pagination = new CP\Pagination(
			$vars['table']['limit'],
			$vars['table']['total_rows'],
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->view->cp_page_title = lang('upload_directories');
		ee()->view->table_heading = lang('all_upload_dirs');

		ee()->cp->set_breadcrumb(cp_url('files'), lang('file_manager'));

		ee()->cp->render('settings/uploads', $vars);
	}
}
// END CLASS

/* End of file Uploads.php */
/* Location: ./system/expressionengine/controllers/cp/Settings/Uploads.php */