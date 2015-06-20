<?php

namespace EllisLab\ExpressionEngine\Controllers\Design;

use ZipArchive;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Controllers\Design\AbstractDesign as AbstractDesignController;

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
 * ExpressionEngine CP Design Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Design extends AbstractDesignController {

	public function index()
	{
		$this->manager();
	}

	public function export()
	{
		$templates = ee('Model')->get('Template')
			->fields('template_id')
			->filter('site_id', ee()->config->item('site_id'));

		if (ee()->session->userdata['group_id'] != 1)
		{
			$templates->filter('group_id', 'IN', array_keys(ee()->session->userdata['assigned_template_groups']));
		}

		$template_ids = $templates->all()
			->pluck('template_id');

		$this->exportTemplates($template_ids);
	}

	public function manager($group_name = NULL)
	{
		if (is_null($group_name))
		{
			$group = ee('Model')->get('TemplateGroup')
				->filter('is_site_default', 'y')
				->filter('site_id', ee()->config->item('site_id'))
				->first();

			if ( ! $group)
			{
				ee()->functions->redirect(cp_url('design/system'));
			}
		}
		else
		{
			$group = ee('Model')->get('TemplateGroup')
				->filter('group_name', $group_name)
				->filter('site_id', ee()->config->item('site_id'))
				->first();

			if ( ! $group)
			{
				show_error(sprintf(lang('error_no_template_group'), $group_name));
			}
		}

		if ( ! $this->hasEditTemplatePrivileges($group->group_id))
		{
			show_error(lang('unauthorized_access'));
		}

		if (ee()->input->post('bulk_action') == 'remove')
		{
			if ($this->hasEditTemplatePrivileges($group->group_id))
			{
				$this->remove(ee()->input->post('selection'));
				ee()->functions->redirect(cp_url('design/manager/' . $group_name, ee()->cp->get_url_state()));
			}
			else
			{
				show_error(lang('unauthorized_access'));
			}
		}
		elseif (ee()->input->post('bulk_action') == 'export')
		{
			$this->export(ee()->input->post('selection'));
		}

		$vars = array();

		$vars['show_new_template_button'] = TRUE;
		$vars['group_id'] = $group->group_name;

		$base_url = new URL('design/manager/' . $group->group_name, ee()->session->session_id());

		$table = $this->buildTableFromTemplateCollection($group->getTemplates());

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
				->perPage($vars['table']['limit'])
				->currentPage($vars['table']['page'])
				->render($base_url);
		}

		ee()->javascript->set_global('template_settings_url', cp_url('design/template/settings/###'));
		ee()->javascript->set_global('lang.remove_confirm', lang('template') . ': <b>### ' . lang('templates') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/v3/confirm_remove',
				'cp/design/manager'
			),
		));

		$this->sidebarMenu($group->group_id);
		$this->stdHeader();
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = sprintf(lang('templates_in_group'), $group->group_name);

		ee()->cp->render('design/index', $vars);
	}

	private function remove($template_ids)
	{
		if ( ! is_array($template_ids))
		{
			$template_ids = array($template_ids);
		}

		$template_names = array();
		$templates = ee('Model')->get('Template', $template_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		foreach ($templates as $template)
		{
			$template_names[] = $template->getTemplateGroup()->group_name . '/' . $template->template_name;
		}

		$templates->delete();

		ee('Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('templates_removed_desc'))
			->addToBody($template_names)
			->defer();
	}

}
// EOF