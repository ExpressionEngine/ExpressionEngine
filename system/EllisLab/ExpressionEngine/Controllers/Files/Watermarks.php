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
			$watermark = ee('Model')->get('Watermark', $watermark_id)->first();

			if ( ! $watermark)
			{
				show_error(lang('unauthorized_access'));
			}

			ee()->view->cp_page_title = lang('edit_watermark');
			ee()->view->base_url = cp_url('files/watermarks/edit/'.$watermark_id);
			ee()->view->save_btn_text = 'edit_watermark';
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'watermark_name_desc',
					'fields' => array(
						'wm_name' => array(
							'type' => 'text',
							'value' => $watermark->wm_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'type',
					'desc' => 'watermark_type_desc',
					'fields' => array(
						'wm_type' => array(
							'type' => 'dropdown',
							'choices' => array(
								'text' => lang('text'),
								'image' => lang('image')
							),
							'value' => $watermark->wm_type,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'watermark_alignment',
					'desc' => 'watermark_alignment_desc',
					'fields' => array(
						'wm_vrt_alignment' => array(
							'type' => 'dropdown',
							'choices' => array(
								'top' => lang('top'),
								'middle' => lang('middle'),
								'bottom' => lang('bottom')
							),
							'value' => $watermark->wm_vrt_alignment,
						),
						'wm_hor_alignment' => array(
							'type' => 'dropdown',
							'choices' => array(
								'left' => lang('left'),
								'center' => lang('center'),
								'right' => lang('right')
							),
							'value' => $watermark->wm_hor_alignment,
						)
					)
				),
				array(
					'title' => 'watermark_padding',
					'desc' => 'watermark_padding_desc',
					'fields' => array(
						'wm_padding' => array(
							'type' => 'text',
							'value' => $watermark->wm_padding
						)
					)
				),
				array(
					'title' => 'watermark_offset',
					'desc' => 'watermark_offset_desc',
					'fields' => array(
						'wm_hor_offset' => array(
							'type' => 'short-text',
							'value' => $watermark->wm_hor_offset,
							'label' => 'horizontal'
						),
						'wm_vrt_offset' => array(
							'type' => 'short-text',
							'value' => $watermark->wm_vrt_offset,
							'label' => 'vertical'
						)
					)
				)
			),
			'text_options' => array(
				array(
					'title' => 'watermarks_true_type',
					'desc' => 'watermarks_true_type_desc',
					'fields' => array(
						'wm_use_font' => array(
							'type' => 'yes_no',
							'value' => $watermark->wm_use_font
						)
					)
				),
				array(
					'title' => 'watermark_text',
					'desc' => 'watermark_text_desc',
					'fields' => array(
						'wm_text' => array(
							'type' => 'text',
							'value' => $watermark->wm_text
						)
					)
				),
				array(
					'title' => 'watermark_text_font',
					'desc' => 'watermark_text_font_desc',
					'fields' => array(
						'wm_font' => array(
							'type' => 'dropdown',
							'choices' => array(), // TODO
							'value' => $watermark->wm_font
						)
					)
				),
				array(
					'title' => 'watermark_text_size',
					'desc' => 'watermark_text_size_desc',
					'fields' => array(
						'wm_font_size' => array(
							'type' => 'text',
							'value' => $watermark->wm_font_size
						)
					)
				),
				array(
					'title' => 'watermark_text_color',
					'desc' => 'watermark_text_color_desc',
					'fields' => array(
						'wm_font_color' => array(
							'type' => 'text',
							'value' => $watermark->wm_font_color
						)
					)
				),
				array(
					'title' => 'watermark_text_dropshadow',
					'desc' => 'watermark_text_dropshadow_desc',
					'fields' => array(
						'wm_use_drop_shadow' => array(
							'type' => 'yes_no',
							'value' => $watermark->wm_use_drop_shadow
						)
					)
				),
				array(
					'title' => 'watermark_text_dropshadow_distance',
					'desc' => 'watermark_text_dropshadow_distance_desc',
					'fields' => array(
						'wm_shadow_distance' => array(
							'type' => 'text',
							'value' => $watermark->wm_shadow_distance
						)
					)
				),
				array(
					'title' => 'watermark_text_dropshadow_color',
					'desc' => 'watermark_text_dropshadow_color_desc',
					'fields' => array(
						'wm_shadow_distance' => array(
							'type' => 'text',
							'value' => $watermark->wm_shadow_distance
						)
					)
				)
			),
			'image_options' => array(
				array(
					'title' => 'watermark_image_path',
					'desc' => 'watermark_image_path_desc',
					'fields' => array(
						'wm_image_path' => array(
							'type' => 'text',
							'value' => $watermark->wm_image_path
						)
					)
				),
				array(
					'title' => 'watermark_image_opacity',
					'desc' => 'watermark_image_opacity_desc',
					'fields' => array(
						'wm_opacity' => array(
							'type' => 'text',
							'value' => $watermark->wm_opacity
						)
					)
				),
				array(
					'title' => 'watermark_image_transparency_map',
					'desc' => 'watermark_image_transparency_map_desc',
					'fields' => array(
						'wm_x_transp' => array(
							'type' => 'short-text',
							'value' => $watermark->wm_x_transp,
							'label' => 'x_axis'
						),
						'wm_y_transp' => array(
							'type' => 'short-text',
							'value' => $watermark->wm_y_transp,
							'label' => 'y_axis'
						)
					)
				)
			)
		);

		if ( ! empty($_POST))
		{
			$watermark->set($_POST);
			$result = $watermark->validate();

			if (AJAX_REQUEST)
			{
				$field = ee()->input->post('ee_fv_field');

				if ($result->hasErrors($field))
				{
					ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
				}
				else
				{
					ee()->output->send_ajax_response('success');
				}
				exit;
			}

			if ($result->isValid())
			{
				$watermark_id = $watermark->save()->getId();

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('watermark_saved'))
					->addToBody(lang('watermark_saved_desc'))
					->defer();

				ee()->functions->redirect(cp_url('files/watermarks/edit/' . $watermark_id));
			}
			else
			{
				ee()->load->library('form_validation');
				ee()->form_validation->_error_array = $result->renderErrors();
				ee('Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('watermark_not_saved'))
					->addToBody(lang('watermark_not_saved_desc'))
					->now();
			}
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(cp_url('files'), lang('file_manager'));
		ee()->cp->set_breadcrumb(cp_url('files/watermarks'), lang('watermarks'));

		ee()->cp->render('settings/form', $vars);
	}
}
// EOF