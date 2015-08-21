<?php

namespace EllisLab\ExpressionEngine\Controller\Design;

use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use EllisLab\ExpressionEngine\Library\CP\Table;


/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Design\Email Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Email extends AbstractDesignController {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->stdHeader();

		ee()->lang->loadfile('specialty_tmp');
	}

	public function index()
	{
		$templates = ee('Model')->get('SpecialtyTemplate')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('template_type', 'email')
			->all();

		$vars = array();

		$base_url = ee('CP/URL', 'design/email/');

		$table = ee('CP/Table', array('autosort' => TRUE, 'subheadings' => TRUE));
		$table->setColumns(
			array(
				'template' => array(
					'encode' => FALSE
				),
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
			)
		);

		$data = array();
		foreach ($templates as $template)
		{
			$edit_url = ee('CP/URL', 'design/email/edit/' . $template->template_id);
			$template_name = '<a href="' . $edit_url->compile() . '">' . lang($template->template_name) . '</a>';
			$data[$template->template_subtype][] = array(
				$template_name,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					),
				))
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		$this->generateSidebar('email');
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = lang('email_message_templates');

		ee()->cp->render('design/email/index', $vars);
	}

	public function edit($template_id)
	{
		$template = ee('Model')->get('SpecialtyTemplate', $template_id)
			->filter('site_id', ee()->config->item('site_id'))
			->filter('template_type', 'email')
			->first();

		if ( ! $template)
		{
			show_error(lang('error_no_template'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'enable_template',
				'label' => 'lang:enable_template',
				'rules' => 'enum[y,n]'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$template->template_data = ee()->input->post('template_data');
			$template->enable_template = ee()->input->post('enable_template');
			$template->template_notes = ee()->input->post('template_notes');
			$template->edit_date = ee()->localize->now;
			$template->last_author_id = ee()->session->userdata('member_id');
			$template->save();

			$alert = ee('CP/Alert')->makeInline('template-form')
				->asSuccess()
				->withTitle(lang('update_template_success'))
				->addToBody(sprintf(lang('update_template_success_desc'), lang($template->template_name)));

			if (ee()->input->post('submit') == 'finish')
			{
				$alert->defer();
				ee()->functions->redirect(ee('CP/URL', 'design/email'));
			}

			$alert->now();
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('template-form')
				->asIssue()
				->withTitle(lang('update_template_error'))
				->addToBody(lang('update_template_error_desc'))
				->now();
		}

		$author = $template->getLastAuthor();

		$vars = array(
			'form_url' => ee('CP/URL', 'design/email/edit/' . $template->template_id),
			'template' => $template,
			'author' => (empty($author)) ? '-' : $author->getMemberName(),
		);

		$this->loadCodeMirrorAssets();
		ee()->cp->add_js_script(array('file' => 'cp/design/email/edit'));

		ee()->view->cp_page_title = sprintf(lang('edit_template'), lang($template->template_name));
		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL', 'design')->compile() => lang('template_manager'),
			ee('CP/URL', 'design/email/')->compile() => sprintf(lang('breadcrumb_group'), lang('email'))
		);

		ee()->cp->render('design/email/edit', $vars);
	}
}
// EOF
