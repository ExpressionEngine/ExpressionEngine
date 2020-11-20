<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Design;

use ZipArchive;
use EllisLab\ExpressionEngine\Library\CP\Table;

use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use EllisLab\ExpressionEngine\Library\Template\Template;

/**
 * Design Controller
 */
class Design extends AbstractDesignController {

	public function index()
	{
		$this->manager();
	}

	public function export()
	{
		if (ee()->session->userdata['group_id'] != 1)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->exportTemplates();
	}

	public function manager($group_name = NULL)
	{
		if (is_null($group_name))
		{
			$group = $this->getAssignedTemplateGroup(NULL, TRUE);

			if ( ! $group)
			{
				$group = $this->getAssignedTemplateGroup();
			}

			if ( ! $group)
			{
				ee()->functions->redirect(ee('CP/URL')->make('design/system'));
			}
		}
		else
		{
			$group = $this->getAssignedTemplateGroup($group_name);

			if ( ! $group)
			{
				$group_name = str_replace('_', '.', $group_name);
				$group = $this->getAssignedTemplateGroup($group_name);

				if ( ! $group)
				{
					show_error(sprintf(lang('error_no_template_group'), $group_name));
				}
			}
		}

		if (ee()->input->post('bulk_action') == 'remove')
		{
			if ($this->hasEditTemplatePrivileges($group->group_id))
			{
				$this->removeTemplates(ee()->input->post('selection'));
				ee()->functions->redirect(ee('CP/URL')->make('design/manager/' . $group_name, ee()->cp->get_url_state()));
			}
			else
			{
				show_error(lang('unauthorized_access'), 403);
			}
		}
		elseif (ee()->input->post('bulk_action') == 'export')
		{
			$this->export(ee()->input->post('selection'));
		}

		ee()->load->library('template');
		ee()->template->sync_from_files();

		$base_url = ee('CP/URL')->make('design/manager/' . $group->group_name);
	    $this->base_url = $base_url;

		$templates = ee('Model')->get('Template')->filter('group_id', $group->group_id)->filter('site_id', ee()->config->item('site_id'));

		$vars = $this->buildTableFromTemplateQueryBuilder($templates);

		$vars['show_new_template_button'] = ee()->cp->allowed_group('can_create_new_templates');
		$vars['show_bulk_delete'] = ee()->cp->allowed_group('can_delete_templates');
		$vars['group_id'] = $group->group_name;

		ee()->javascript->set_global('template_settings_url', ee('CP/URL')->make('design/template/settings/###')->compile());
		ee()->javascript->set_global('templage_groups_reorder_url', ee('CP/URL')->make('design/reorder-groups')->compile());
		ee()->javascript->set_global('lang.remove_confirm', lang('template') . ': <b>### ' . lang('templates') . '</b>');
		ee()->cp->add_js_script(array(
			'plugin' => 'ui.touch.punch',
			'file' => array(
				'cp/confirm_remove',
				'cp/design/manager'
			),
		));

		$this->generateSidebar($group->group_id);
		$this->stdHeader();
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = sprintf(lang('templates_in_group'), $group->group_name);

		ee()->cp->render('design/index', $vars);
	}

	private function getAssignedTemplateGroup($group_name = NULL, $site_default = FALSE)
	{
		$assigned_groups = NULL;

		if (ee()->session->userdata['group_id'] != 1)
		{
			$assigned_groups = array_keys(ee()->session->userdata['assigned_template_groups']);

			if (empty($assigned_groups))
			{
				ee()->functions->redirect(ee('CP/URL')->make('design/system'));
			}
		}

		$group = ee('Model')->get('TemplateGroup')
			->fields('group_id', 'group_name')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_name', 'asc');

		if ($group_name)
		{
			$group->filter('group_name', $group_name);
		}

		if ($site_default)
		{
			$group->filter('is_site_default', 'y');
		}

		if ($assigned_groups)
		{
			$group->filter('group_id', 'IN', $assigned_groups);
		}

		return $group->first();
	}

	/**
	 * AJAX end-point for template group reordering
	 */
	public function reorderGroups()
	{
		if ( ! ($group_names = ee()->input->post('groups'))
			OR ! AJAX_REQUEST
			OR ! ee()->cp->allowed_group('can_edit_template_groups'))
		{
			return;
		}

		$groups = ee('Model')->get('TemplateGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->order('group_name', 'asc')
			->all();

		$groups_indexed = $groups->indexBy('group_name');

		$i = 1;
		foreach ($group_names as $name)
		{
			$groups_indexed[$name]->group_order = $i;
			$i++;
		}

		$groups->save();

		return array('success');
	}

}

// EOF
