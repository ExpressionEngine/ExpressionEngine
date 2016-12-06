<?php

namespace EllisLab\ExpressionEngine\Controller\Files;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controller\Files\AbstractFiles as AbstractFilesController;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Watermarks extends AbstractFilesController {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_create_upload_directories'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->generateSidebar('watermark');
		$this->stdHeader();

		ee()->load->library('form_validation');
	}

	/**
	 * Watermarks listing
	 */
	public function index()
	{
		$table = ee('CP/Table');
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
		$table->setNoResultsText(sprintf(lang('no_found'), lang('watermarks')), 'create_watermark', ee('CP/URL')->make('files/watermarks/create'));

		$watermarks = ee('Model')->get('Watermark');
		$total_rows = $watermarks->count();

		$sort_map = array(
			'name' => 'wm_name',
			'type' => 'wm_type'
		);

		$watermarks = $watermarks->order($sort_map[$table->sort_col], $table->sort_dir)
			->limit($table->config['limit'])
			->offset(($table->config['page'] - 1) * $table->config['limit'])
			->all();

		$data = array();
		foreach ($watermarks as $watermark)
		{
			$edit_url = ee('CP/URL')->make('files/watermarks/edit/'.$watermark->getId());
			$columns = array(
				array(
					'content' => $watermark->wm_name,
					'href' => $edit_url
				),
				$watermark->wm_type,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'watermarks[]',
					'value' => $watermark->getId(),
					'data'	=> array(
						'confirm' => lang('watermark') . ': <b>' . htmlentities($watermark->wm_name, ENT_QUOTES, 'UTF-8') . '</b>'
					),
					// Cannot delete default group
					'disabled' => ($watermark->wm_name == 'Default') ? 'disabled' : NULL
				)
			);

			$attrs = array();
			if (ee()->session->flashdata('highlight_id') == $watermark->getId())
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => $columns
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData(ee('CP/URL')->make('files/watermarks'));

		$vars['pagination'] = ee('CP/Pagination', $total_rows)
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->view->cp_page_title = lang('watermarks');

		ee()->javascript->set_global('lang.remove_confirm', lang('watermarks') . ': <b>### ' . lang('watermarks') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
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

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('watermarks_removed'))
					->addToBody(sprintf(lang('watermarks_removed_desc'), count($watermarks)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->functions->redirect(ee('CP/URL')->make('files/watermarks', ee()->cp->get_url_state()));
	}

	/**
	 * New watermark form
	 */
	public function create()
	{
		return $this->form();
	}

	/**
	 * Edit watermark form
	 */
	public function edit($watermark_id)
	{
		return $this->form($watermark_id);
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
			$alert_key = 'created';
			ee()->view->cp_page_title = lang('create_watermark');
			ee()->view->base_url = ee('CP/URL')->make('files/watermarks/create');
			$watermark = ee('Model')->make('Watermark');
		}
		else
		{
			$watermark = ee('Model')->get('Watermark', $watermark_id)->first();

			if ( ! $watermark)
			{
				show_error(lang('unauthorized_access'), 403);
			}

			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('edit_watermark');
			ee()->view->base_url = ee('CP/URL')->make('files/watermarks/edit/'.$watermark_id);
		}

		ee()->load->library('filemanager');

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
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
					'fields' => array(
						'wm_type' => array(
							'type' => 'select',
							'choices' => array(
								'text' => lang('text'),
								'image' => lang('image')
							),
							'group_toggle' => array(
								'text' => 'text_options',
								'image' => 'image_options'
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
							'type' => 'select',
							'choices' => array(
								'top' => lang('top'),
								'middle' => lang('middle'),
								'bottom' => lang('bottom')
							),
							'value' => $watermark->wm_vrt_alignment,
						),
						'wm_hor_alignment' => array(
							'type' => 'select',
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
							'value' => ($watermark->isNew()) ? 10 : $watermark->wm_padding
						)
					)
				),
				array(
					'title' => 'watermark_offset',
					'desc' => 'watermark_offset_desc',
					'fields' => array(
						'wm_hor_offset' => array(
							'type' => 'short-text',
							'value' => $watermark->wm_hor_offset ?: 0,
							'label' => 'horizontal'
						),
						'wm_vrt_offset' => array(
							'type' => 'short-text',
							'value' => $watermark->wm_vrt_offset ?: 0,
							'label' => 'vertical'
						)
					)
				)
			),
			'text_options' => array(
				'group' => 'text_options',
				'settings' => array(
					array(
						'title' => 'watermarks_true_type',
						'desc' => 'watermarks_true_type_desc',
						'fields' => array(
							'wm_use_font' => array(
								'type' => 'yes_no',
								'value' => ! is_null($watermark->wm_use_font) ? $watermark->wm_use_font : 'y'
							)
						)
					),
					array(
						'title' => 'watermark_text',
						'desc' => 'watermark_text_desc',
						'fields' => array(
							'wm_text' => array(
								'type' => 'text',
								'value' => $watermark->wm_text ?: 'Copyright '.date('Y', ee()->localize->now)
							)
						)
					),
					array(
						'title' => 'watermark_text_font',
						'fields' => array(
							'wm_font' => array(
								'type' => 'select',
								'choices' => ee()->filemanager->fetch_fontlist(),
								'value' => $watermark->wm_font ?: 'texb.ttf'
							)
						)
					),
					array(
						'title' => 'watermark_text_size',
						'fields' => array(
							'wm_font_size' => array(
								'type' => 'text',
								'value' => $watermark->wm_font_size ?: 16
							)
						)
					),
					array(
						'title' => 'watermark_text_color',
						'fields' => array(
							'wm_font_color' => array(
								'type' => 'text',
								'value' => $watermark->wm_font_color ?: 'FFFF00'
							)
						)
					),
					array(
						'title' => 'watermark_text_dropshadow',
						'desc' => 'watermark_text_dropshadow_desc',
						'fields' => array(
							'wm_use_drop_shadow' => array(
								'type' => 'yes_no',
								'value' => ! is_null($watermark->wm_use_drop_shadow) ? $watermark->wm_use_drop_shadow : 'y'
							)
						)
					),
					array(
						'title' => 'watermark_text_dropshadow_distance',
						'fields' => array(
							'wm_shadow_distance' => array(
								'type' => 'text',
								'value' => ($watermark->isNew()) ? 1 : $watermark->wm_shadow_distance
							)
						)
					),
					array(
						'title' => 'watermark_text_dropshadow_color',
						'fields' => array(
							'wm_shadow_color' => array(
								'type' => 'text',
								'value' => $watermark->wm_shadow_color ?: '999999'
							)
						)
					)
				)
			),
			'image_options' => array(
				'group' => 'image_options',
				'settings' => array(
					array(
						'title' => 'watermark_image_path',
						'desc' => 'watermark_image_path_desc',
						'fields' => array(
							'wm_image_path' => array(
								'type' => 'text',
								'value' => $watermark->getRawProperty('wm_image_path')
							)
						)
					),
					array(
						'title' => 'watermark_image_opacity',
						'desc' => 'watermark_image_opacity_desc',
						'fields' => array(
							'wm_opacity' => array(
								'type' => 'text',
								'value' => ($watermark->isNew()) ? 50 : $watermark->wm_opacity
							)
						)
					),
					array(
						'title' => 'watermark_image_transparency_map',
						'desc' => 'watermark_image_transparency_map_desc',
						'fields' => array(
							'wm_x_transp' => array(
								'type' => 'short-text',
								'value' => ($watermark->isNew()) ? 2 : $watermark->wm_x_transp,
								'label' => 'x_axis'
							),
							'wm_y_transp' => array(
								'type' => 'short-text',
								'value' => ($watermark->isNew()) ? 2 : $watermark->wm_y_transp,
								'label' => 'y_axis'
							)
						)
					)
				)
			)
		);

		if ( ! empty($_POST))
		{
			$watermark->set($_POST);
			$result = $watermark->validate();

			if ($response = $this->ajaxValidation($result))
			{
				return $response;
			}

			if ($result->isValid())
			{
				$watermark->save();

				if (is_null($watermark_id))
				{
					ee()->session->set_flashdata('highlight_id', $watermark->getId());
				}

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('watermark_'.$alert_key))
					->addToBody(sprintf(lang('watermark_'.$alert_key.'_desc'), $watermark->wm_name))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('files/watermarks'));
			}
			else
			{
				$vars['errors'] = $result;

				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('watermark_not_'.$alert_key))
					->addToBody(lang('watermark_not_'.$alert_key.'_desc'))
					->now();
			}
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('watermark'));
		ee()->view->save_btn_text_working = 'btn_saving';

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('files'), lang('file_manager'));
		ee()->cp->set_breadcrumb(ee('CP/URL')->make('files/watermarks'), lang('watermarks'));

		ee()->cp->add_js_script(array(
			'file' => array('cp/form_group'),
		));

		ee()->cp->render('settings/form', $vars);
	}
}

// EOF
