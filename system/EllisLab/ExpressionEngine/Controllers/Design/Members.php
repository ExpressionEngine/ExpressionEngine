<?php

namespace EllisLab\ExpressionEngine\Controllers\Design;

use EllisLab\ExpressionEngine\Controllers\Design\Design;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;

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
 * ExpressionEngine CP Design\Members Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Members extends Design {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_design', 'can_admin_mbr_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->stdHeader();
	}

	public function index($theme = 'default')
	{
		$path = PATH_MBR_THEMES . ee()->security->sanitize_filename($theme);

		if ( ! is_dir($path))
		{
			show_error(lang('unable_to_find_templates'));
		}

		$this->load->helper('directory');
		$files = directory_map($path, TRUE);

		$vars = array();

		$base_url = new URL('design/members/index/' . $theme, ee()->session->session_id());

		$table = Table::create();
		$table->setColumns(
			array(
				'template',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
			)
		);

		$data = array();
		foreach ($files as $file)
		{
			if (strpos($file, '.') === FALSE)
			{
				continue;
			}

			$human = substr($file, 0, -strlen(strrchr($file, '.')));

			$data[] = array(
				(lang($human) == FALSE) ? $human : lang($human),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('design/members/edit/' . $theme . '/' . $human),
						'title' => lang('edit')
					),
				))
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		ee()->load->model('member_model');

		$themes = array();
		foreach (ee()->member_model->get_profile_templates() as $dir => $name)
		{
			$themes[cp_url('design/members/index/' . $dir)] = $name;
		}

		$vars['themes'] = form_dropdown('theme', $themes, cp_url('design/members/index/' . $theme));

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

		$this->sidebarMenu('members');
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = lang('member_profile_templates');

		ee()->javascript->change("select[name=\'theme\']", 'window.location.href = $(this).val()');

		ee()->cp->render('design/members/index', $vars);
	}

	public function edit($theme, $file)
	{
		$path = PATH_MBR_THEMES
			.ee()->security->sanitize_filename($theme)
			.'/'
			.ee()->security->sanitize_filename($file . '.html');

		if ( ! file_exists($path))
		{
			show_error(lang('unable_to_find_template_file'));
		}

		$template_name = (lang($file) == FALSE) ? $file : lang($file);

		if ( ! empty($_POST))
		{
			if ( ! write_file($path, ee()->input->post('template_data')))
			{
				show_error(lang('error_opening_template'));
			}
			else
			{

				$alert = ee('Alert')->makeInline('template-form')
					->asSuccess()
					->withTitle(lang('update_template_success'))
					->addToBody(sprintf(lang('update_template_success_desc'), $template_name));

				if (ee()->input->post('submit') == 'finish')
				{
					$alert->defer();
					ee()->functions->redirect(cp_url('design/members'));
				}
			}
		}

		if ( ! is_really_writable($path))
		{
			ee('Alert')->makeInline('message-warning')
				->asWarning()
				->cannotClose()
				->withTitle(lang('file_not_writable'))
				->addToBody(lang('file_writing_instructions'));
		}

		$fp = fopen($path, 'r');
		$fstat = fstat($fp);
		fclose($fp);

		$vars = array(
			'form_url'      => cp_url('design/members/edit/' . $theme . '/' . $file),
			'edit_date'     => ee()->localize->human_time($fstat['mtime']),
			'template_data' => file_get_contents($path),
		);

		ee()->view->cp_page_title = sprintf(lang('edit_template'), $template_name);
		ee()->view->cp_breadcrumbs = array(
			cp_url('design') => lang('template_manager'),
			cp_url('design/members/') => sprintf(lang('breadcrumb_group'), lang('members'))
		);

		ee()->cp->render('design/members/edit', $vars);
	}
}
// EOF