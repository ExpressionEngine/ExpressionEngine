<?php

namespace EllisLab\ExpressionEngine\Controller\Design;

use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use EllisLab\ExpressionEngine\Library\CP\Table;


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
 * ExpressionEngine CP Design\System Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class System extends AbstractDesignController {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->stdHeader();
	}

	public function index()
	{
		$templates = ee('Model')->get('SpecialtyTemplate')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('template_type', 'system')
			->all();

		$vars = array();

		$base_url = ee('CP/URL')->make('design/system/');

		$table = ee('CP/Table', array('autosort' => TRUE, 'limit' => 1024));
		$table->setColumns(
			array(
				'template',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
			)
		);

		$data = array();
		foreach ($templates as $template)
		{
			$edit_url = ee('CP/URL')->make('design/system/edit/' . $template->template_id);
			$data[] = array(
				array(
					'content' => lang($template->template_name),
					'href' => $edit_url
				),
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

		$this->generateSidebar('messages');
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = lang('system_message_templates');

		ee()->cp->render('design/system/index', $vars);
	}

	public function edit($template_id)
	{
		$template = ee('Model')->get('SpecialtyTemplate', $template_id)
			->filter('site_id', ee()->config->item('site_id'))
			->filter('template_type', 'system')
			->first();

		if ( ! $template)
		{
			show_error(lang('error_no_template'));
		}

		if ($template->template_name == 'message_template')
		{
			ee('CP/Alert')->makeInline('message-warning')
				->asWarning()
				->cannotClose()
				->addToBody(lang('message_template_warning'))
				->now();
		}

		if ( ! empty($_POST))
		{
			$template->template_data = ee()->input->post('template_data');
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
				ee()->functions->redirect(ee('CP/URL')->make('design/system'));
			}

			$alert->now();
		}

		$author = $template->getLastAuthor();

		$vars = array(
			'form_url' => ee('CP/URL')->make('design/system/edit/' . $template->template_id),
			'template' => $template,
			'author' => (empty($author)) ? '-' : $author->getMemberName(),
		);

		$this->loadCodeMirrorAssets();

		ee()->view->cp_page_title = sprintf(lang('edit_template'), lang($template->template_name));
		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('design')->compile() => lang('template_manager'),
			ee('CP/URL')->make('design/system/')->compile() => sprintf(lang('breadcrumb_group'), lang('system'))
		);

		// Supress browser XSS check that could cause obscure bug after saving
		ee()->output->set_header("X-XSS-Protection: 0");

		ee()->cp->render('design/system/edit', $vars);
	}
}

// EOF
