<?php

namespace EllisLab\ExpressionEngine\Controllers\Files;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controllers\Files\AbstractFiles as AbstractFilesController;

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
 * ExpressionEngine CP Watermarks Controller Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Watermarks extends AbstractFilesController {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_admin_upload_prefs'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->sidebarMenu(NULL);
		$this->stdHeader();

		ee()->load->library('form_validation');
	}

	/**
	 * Watermarks listing
	 */
	public function index()
	{
		$table = CP\Table::create();
		$table->setColumns(
			array(
				'name',
				'type',
				'manage' => array(
					'type'	=> CP\Table::COL_TOOLBAR
				),
				array(
					'type'	=> CP\Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_watermarks', 'create_watermark', cp_url('files/watermarks/create'));

		$watermarks = ee('Model')->get('Watermark');
		$total_rows = $watermarks->all()->count();

		$sort_map = array(
			'name' => 'wm_name',
			'type' => 'wm_type'
		);

		$watermarks = $watermarks->order($sort_map[$table->sort_col], $table->sort_dir)
			->limit(20)
			->offset(($table->config['page'] - 1) * 20)
			->all();

		$data = array();
		foreach ($watermarks as $watermark)
		{
			$data[] = array(
				htmlentities($watermark->wm_name, ENT_QUOTES),
				htmlentities($watermark->wm_type, ENT_QUOTES),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('files/watermarks/edit/'.$watermark->getId()),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'watermarks[]',
					'value' => $watermark->getId(),
					'data'	=> array(
						'confirm' => lang('watermark') . ': <b>' . htmlentities($watermark->wm_name, ENT_QUOTES) . '</b>'
					),
					// Cannot delete default group
					'disabled' => ($watermark->wm_name == 'Default') ? 'disabled' : NULL
				)
			);
		}

		$table->setData($data);

		$base_url = new CP\URL('files/watermarks', ee()->session->session_id());
		$vars['table'] = $table->viewData($base_url);

		$pagination = new CP\Pagination(
			$vars['table']['limit'],
			$total_rows,
			$vars['table']['page']
		);
		$vars['pagination'] = $pagination->cp_links($vars['table']['base_url']);

		ee()->view->cp_page_title = lang('watermarks');

		ee()->javascript->set_global('lang.remove_confirm', lang('watermarks') . ': <b>### ' . lang('watermarks') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		ee()->cp->render('files/watermarks', $vars);
	}

	/**
	 * Remove watermarks handler
	 */
	public function remove()
	{
		$watermarks = ee()->input->post('watermarks');

		if ( ! empty($watermarks) && ee()->input->post('bulk_action') == 'remove')
		{
			// Filter out junk
			$watermarks = array_filter($watermarks, 'is_numeric');

			if ( ! empty($watermarks))
			{
				ee('Model')->get('Watermark', $watermarks)->delete();

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('watermarks_removed'))
					->addToBody(sprintf(lang('watermarks_removed_desc'), count($watermarks)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect(cp_url('files/watermarks', ee()->cp->get_url_state()));
	}

	/**
	 * New watermark form
	 */
	public function create()
	{
		$this->form();
	}

	/**
	 * Edit watermark form
	 */
	public function edit($watermark_id)
	{
		$this->form($watermark_id);
	}

	/**
	 * Watermark creation/edit form
	 *
	 * @param	int	$watermark_id	ID of watermark to edit
	 */
	private function form($watermark_id = NULL)
	{
		if (is_null($watermark_id))
		{
			ee()->view->cp_page_title = lang('create_watermark');
			ee()->view->base_url = cp_url('files/watermarks/create');
			ee()->view->save_btn_text = 'create_watermark';
			$watermark = ee('Model')->make('Watermark');
		}
		else
		{
			$watermark = ee('Model')->get('StatusGroup', $watermark_id)->first();

			if ( ! $watermark)
			{
				show_error(lang('unauthorized_access'));
			}

			ee()->view->cp_page_title = lang('edit_watermark');
			ee()->view->base_url = cp_url('files/watermarks/edit/'.$watermark_id);
			ee()->view->save_btn_text = 'edit_watermark';
		}

		// ...

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(cp_url('files'), lang('file_manager'));
		ee()->cp->set_breadcrumb(cp_url('files/watermarks'), lang('watermarks'));

		ee()->cp->render('settings/form', $vars);
	}
}
// EOF