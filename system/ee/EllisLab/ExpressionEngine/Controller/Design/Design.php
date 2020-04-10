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

		$this->_sync_from_files();

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

	protected function _sync_from_files()
	{
		if (ee()->config->item('save_tmpl_files') != 'y')
		{
			return FALSE;
		}

		ee()->load->library('api');
		ee()->legacy_api->instantiate('template_structure');

		// Lazy load templates instead, this was looping the group query with it included

		$groups = ee('Model')->get('TemplateGroup')
			->with('Templates')
			->filter('site_id', ee()->config->item('site_id'))
			->all();
		$group_ids_by_name = $groups->getDictionary('group_name', 'group_id');

		$existing = array();

		foreach ($groups as $group)
		{
			$existing[$group->group_name.'.group'] = array_combine(
				$group->Templates->pluck('template_name'),
				$group->Templates->pluck('template_name')
			);
		}

		$basepath = PATH_TMPL . ee()->config->item('site_short_name');
		ee()->load->helper('directory');
		$files = directory_map($basepath, 0, 1);

		if ($files !== FALSE)
		{
			foreach ($files as $group => $templates)
			{
				if (substr($group, -6) != '.group')
				{
					continue;
				}

				$group_name = substr($group, 0, -6); // remove .group

				// DB column limits template and group name to 50 characters
				if (strlen($group_name) > 50)
				{
					continue;
				}

				$group_id = '';

				if ( ! preg_match("#^[a-zA-Z0-9_\-]+$#i", $group_name))
				{
					continue;
				}

				// if the template group doesn't exist, make it!
				if ( ! isset($existing[$group]))
				{
					if ( ! ee()->legacy_api->is_url_safe($group_name))
					{
						continue;
					}

					if (in_array($group_name, array('act', 'css')))
					{
						continue;
					}

					$data = array(
						'group_name'		=> $group_name,
						'is_site_default'	=> 'n',
						'site_id'			=> ee()->config->item('site_id')
					);

					$new_group = ee('Model')->make('TemplateGroup', $data)->save();
					$group_id = $new_group->group_id;

					$existing[$group] = array();
				}

				// Grab group_id if we still don't have it.
				if ($group_id == '')
				{
					$group_id = $group_ids_by_name[$group_name];
				}

				// if the templates don't exist, make 'em!
				foreach ($templates as $template)
				{
					// Skip subdirectories (such as those created by svn)
					if (is_array($template))
					{
						continue;
					}
					// Skip hidden ._ files
					if (substr($template, 0, 2) == '._')
					{
						continue;
					}
					// If the last occurance is the first position?  We skip that too.
					if (strrpos($template, '.') == FALSE)
					{
						continue;
					}

					$ext = strtolower(ltrim(strrchr($template, '.'), '.'));
					if ( ! in_array('.'.$ext, ee()->api_template_structure->file_extensions))
					{
						continue;
					}

					$ext_length = strlen($ext) + 1;
					$template_name = substr($template, 0, -$ext_length);
					$template_type = array_search('.'.$ext, ee()->api_template_structure->file_extensions);

					if (in_array($template_name, $existing[$group]))
					{
						continue;
					}

					if ( ! ee()->legacy_api->is_url_safe($template_name))
					{
						continue;
					}

					if (strlen($template_name) > 50)
					{
						continue;
					}

					$data = array(
						'group_id'				=> $group_id,
						'template_name'			=> $template_name,
						'template_type'			=> $template_type,
						'template_data'			=> file_get_contents($basepath.'/'.$group.'/'.$template),
						'edit_date'				=> ee()->localize->now,
						'last_author_id'		=> ee()->session->userdata['member_id'],
						'site_id'				=> ee()->config->item('site_id')
					 );

					// do it!
					$template_model = ee('Model')->make('Template', $data)->save();
					$this->saveNewTemplateRevision($template_model);

					// add to existing array so we don't try to create this template again
					$existing[$group][] = $template_name;
				}

				// An index template is required- so we create it if necessary
				if ( ! in_array('index', $existing[$group]))
				{
					$data = array(
						'group_id'				=> $group_id,
						'template_name'			=> 'index',
						'template_data'			=> '',
						'edit_date'				=> ee()->localize->now,
						'save_template_file'	=> 'y',
						'last_author_id'		=> ee()->session->userdata['member_id'],
						'site_id'				=> ee()->config->item('site_id')
					 );

					$template_model = ee('Model')->make('Template', $data)->save();
					$this->saveNewTemplateRevision($template_model);
				}

				unset($existing[$group]);
			}
		}
	}
}

// EOF
