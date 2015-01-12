<?php

namespace EllisLab\ExpressionEngine\Controllers\Design;

use CP_Controller;
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
 * ExpressionEngine CP Design Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Design extends CP_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! $this->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('design');
	}

	protected function sidebarMenu($active_group_id = NULL)
	{
		// Register our menu
		$vars = array(
			'template_groups' => array(),
			'system_templates' => array(
				array(
					'name' => lang('messages'),
					'url' => cp_url(''),
					'edit_url' => cp_url('')
				),
				array(
					'name' => lang('email'),
					'url' => cp_url(''),
					'edit_url' => cp_url('')
				)
			)
		);

		// Template Groups
		$is_admin = ee()->session->userdata['group_id'] == 1;
		$assigned_template_groups = ee()->session->userdata['assigned_template_groups'];

		foreach (ee('Model')->get('TemplateGroup')->all() as $group)
		{
			if ($is_admin OR array_key_exists($group->group_id, $assigned_template_groups))
			{
				$class = ($active_group_id == $group->group_id) ? 'act' : '';

				$data = array(
					'name' => $group->group_name,
					'url' => cp_url('design/manager/' . $group->group_name),
					'edit_url' => cp_url('design/group/edit/' . $group->group_name),
				);

				if ($group->is_site_default)
				{
					$class .= ' default';
					$data['name'] = '<b>' . $group->group_name . '</b>';
				}

				if ( ! empty($class))
				{
					$data['class'] = $class;
				}

				$vars['template_groups'][] = $data;
			}
		}

		// System Templates
		if (ee('Model')->get('Module')->filter('module_name', 'Member')->first())
		{
			$vars['system_templates'][] = array(
				'name' => lang('members'),
				'url' => cp_url(''),
				'edit_url' => cp_url('')
			);
		}

		if (ee()->config->item('forum_is_installed') == "y")
		{
			$vars['system_templates'][] = array(
				'name' => lang('forums'),
				'url' => cp_url(''),
				'edit_url' => cp_url('')
			);
		}

		ee()->view->left_nav = ee('View')->make('design/menu')->render($vars);
		ee()->cp->add_js_script(array(
			'file' => array('cp/design/menu'),
		));
	}

	protected function stdHeader()
	{
		ee()->view->header = array(
			'title' => lang('template_manager'),
			'form_url' => cp_url('design'),
			'toolbar_items' => array(
				'settings' => array(
					'href' => cp_url('settings/template'),
					'title' => lang('settings')
				),
				'sync' => array(
					'href' => cp_url('design/sync'),
					'title' => lang('sync_all_templates')
				),
				'download' => array(
					'href' => cp_url('design/export'),
					'title' => lang('export_all')
				)
			),
			'search_button_value' => lang('search_templates')
		);
	}

	/**
	 * Determines if the logged in user has edit privileges for a given template
	 * group. We need either a group's unique id or a template's unique id to
	 * determine access.
	 *
	 * @param  int  $group_id    The id of the template group in question (optional)
	 * @param  int  $template_id The id of the tempalte in question (optional)
	 * @return bool TRUE if the user has edit privileges, FALSE if not
	 */
	protected function hasEditTemplatePrivileges($group_id = NULL, $template_id = NULL)
	{
		// If the user is a Super Admin, return true
		if (ee()->session->userdata['group_id'] == 1)
		{
			return TRUE;
		}

		if ( ! $group_id)
		{
			if ( ! $template_id)
			{
				return FALSE;
			}
			else
			{
				$group_id = ee('Model')->get('Template', $template_id)
					->fields('group_id')
					->first()
					->group_id;
			}
		}

		return array_key_exists($group_id, ee()->session->userdata['assigned_template_groups']);
	}

	public function index()
	{
		$this->manager();
	}

	public function manager($group_name = NULL)
	{
		$vars = array();

		$table = Table::create();
		$table->setColumns(
			array(
				'template',
				'hits',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$data = array();
		if (is_null($group_name))
		{
			$group = ee('Model')->get('TemplateGroup')
				->filter('is_site_default', 'y')
				->first();
		}
		else
		{
			$group = ee('Model')->get('TemplateGroup')
				->filter('group_name', $group_name)
				->first();

			if ( ! $group)
			{
				show_error(sprintf(lang('error_no_template_group'), $group_name));
			}
		}

		$vars['group_id'] = $group->group_name;

		$base_url = new URL('design/manager/' . $group->group_name, ee()->session->session_id());

		$templates = $group->getTemplates();

		$hidden_indicator = ($this->config->item('hidden_template_indicator') != '') ? $this->config->item('hidden_template_indicator') : '_';
		$hidden_indicator_length = strlen($hidden_indicator);

		foreach ($templates as $template)
		{
			$template_name = htmlentities($template->template_name, ENT_QUOTES);

			if (strncmp($template->template_name, $hidden_indicator, $hidden_indicator_length) == 0)
			{
				$template_name = '<span class="hidden">' . $template_name . '</span>';
			}

			if ($template->template_name == 'index')
			{
				$template_name = '<span class="index">' . $template_name . '</span>';
			}

			$data[] = array(
				$template_name,
				$template->hits,
				array('toolbar_items' => array(
					'view' => array(
						'href' => cp_url('design/template/view/' . $template->template_id),
						'title' => lang('view')
					),
					'edit' => array(
						'href' => cp_url('design/template/edit/' . $template->template_id),
						'title' => lang('edit')
					),
					'settings' => array(
						'href' => '',
						'rel' => 'modal-template-' . $template->template_id,
						'class' => 'm-link',
						'title' => lang('settings')
					),
					'sync' => array(
						'href' => cp_url('design/template/sync/' . $template->template_id),
						'title' => lang('sync')
					),
				)),
				array(
					'name' => 'selection[]',
					'value' => $template->template_id,
					'data'	=> array(
						'confirm' => lang('temlate') . ': <b>' . htmlentities($template->template_name, ENT_QUOTES) . '</b>'
					)
				)

			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

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

		// Set search results heading
		if ( ! empty($vars['table']['search']))
		{
			ee()->view->cp_heading = sprintf(
				lang('search_results_heading'),
				$vars['table']['total_rows'],
				$vars['table']['search']
			);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('template') . ': <b>### ' . lang('templates') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		$this->sidebarMenu($group->group_id);
		$this->stdHeader();
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = sprintf(lang('templates_in_group'), $group->group_name);

		ee()->cp->render('design/index', $vars);
	}
}
// EOF