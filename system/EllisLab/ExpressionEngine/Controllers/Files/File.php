<?php

namespace EllisLab\ExpressionEngine\Controllers\Files;

use EllisLab\ExpressionEngine\Controllers\Files\Files;

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
 * ExpressionEngine CP Files\File Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class File extends Files {

	public function view($id)
	{
		$file = ee('Model')->get('File', $id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $file)
		{
			show_error(lang('no_file'));
		}

		if ( ! $this->hasFileGroupAccessPrivileges($file->getUploadDestination()))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $file->isImage())
		{
			show_error(lang('not_an_image'));
		}

		ee()->load->library('image_lib');
		$info = ee()->image_lib->get_image_properties($file->getAbsolutePath(), TRUE);

		// Adapted from http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
		$size   = array('b', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb');
	    $factor = floor((strlen($file->file_size) - 1) / 3);

		$vars = array(
			'file' => $file,
			'height' => $info['height'],
			'width' => $info['width'],
			'size' => sprintf("%d", $file->file_size / pow(1024, $factor)) . lang('size_' . @$size[$factor])
		);

		ee()->cp->render('files/view', $vars);
	}

	public function edit($id)
	{
		$file = ee('Model')->get('File', $id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $file)
		{
			show_error(lang('no_file'));
		}

		if ( ! $this->hasFileGroupAccessPrivileges($file->getUploadDestination()))
		{
			show_error(lang('unauthorized_access'));
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => cp_url('files/file/edit/' . $id),
			'save_btn_text' => 'btn_edit_file_meta',
			'save_btn_text_working' => 'btn_edit_file_meta_working',
			'sections' => array(
				array(
					array(
						'title' => 'title',
						'desc' => 'title_desc',
						'fields' => array(
							'title' => array(
								'type' => 'text',
								'value' => $file->title
							)
						)
					),
					array(
						'title' => 'description',
						'desc' => 'description_desc',
						'fields' => array(
							'description' => array(
								'type' => 'textarea',
								'value' => $file->description
							)
						)
					),
					array(
						'title' => 'credit',
						'desc' => 'credit_desc',
						'fields' => array(
							'credit' => array(
								'type' => 'text',
								'value' => $file->credit
							)
						)
					),
					array(
						'title' => 'location',
						'desc' => 'location_desc',
						'fields' => array(
							'location' => array(
								'type' => 'text',
								'value' => $file->location
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
			$file->title = ee()->input->post('title');
			$file->description = ee()->input->post('description');
			$file->credit = ee()->input->post('credit');
			$file->location = ee()->input->post('location');
			$file->modified_by_member_id = ee()->session->userdata('member_id');
			$file->modified_date = ee()->localize->now;

			$file->save();

			ee('Alert')->makeInline('settings-form')
				->asSuccess()
				->withTitle(lang('edit_file_metadata_success'))
				->addToBody(sprintf(lang('edit_file_metadata_success_desc'), $file->title))
				->defer();

			ee()->functions->redirect(cp_url('files/directory/' . $file->upload_location_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('settings-form')
				->asIssue()
				->withTitle(lang('edit_file_metadata_error'))
				->addToBody(lang('edit_file_metadata_error_desc'));
		}

		$this->sidebarMenu($file->upload_location_id);
		ee()->view->cp_page_title = sprintf(lang('edit_file_metadata'), $file->title);

		ee()->view->cp_breadcrumbs = array(
			cp_url('files') => lang('file_manager'),
		);

		ee()->cp->render('settings/form', $vars);
	}

	public function crop($id)
	{
		$file = ee('Model')->get('File', $id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $file)
		{
			show_error(lang('no_file'));
		}

		if ( ! $this->hasFileGroupAccessPrivileges($file->getUploadDestination()))
		{
			show_error(lang('unauthorized_access'));
		}

	}

	public function download($id)
	{
		$file = ee('Model')->get('File', $id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $file)
		{
			show_error(lang('no_file'));
		}

		if ( ! $this->hasFileGroupAccessPrivileges($file->getUploadDestination()))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->helper('download');
		force_download($file->file_name, file_get_contents($file->getAbsolutePath()));
	}
}
// EOF