<?php

namespace EllisLab\ExpressionEngine\Controllers\Design;

use \EE_Route;
use EllisLab\ExpressionEngine\Controllers\Design\Design;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Model\Template\Template as TemplateModel;

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
 * ExpressionEngine CP Design\Template Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Template extends Design {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->stdHeader();
	}

	public function create($group_name)
	{
		$group = ee('Model')->get('TemplateGroup')
			->filter('group_name', $group_name)
			->first();

		if ( ! $group)
		{
			show_error(sprintf(lang('error_no_template_group'), $group_name));
		}

		if ($this->hasEditTemplatePrivileges($group->group_id) === FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		$existing_templates = array(
			'0' => '-- ' . strtolower(lang('none')) . ' --'
		);

		foreach (ee('Model')->get('TemplateGroup')->all() as $template_group)
		{
			$templates = array();
			foreach ($template_group->getTemplates() as $template)
			{
				$templates[$template->template_id] = $template->template_name;
			}
			$existing_templates[$template_group->group_name] = $templates;
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => cp_url('design/template/create/' . $group_name),
			'save_btn_text' => 'btn_create_template',
			'save_btn_text_working' => 'btn_create_template_working',
			'sections' => array(
				array(
					array(
						'title' => 'name',
						'desc' => 'template_name_desc',
						'fields' => array(
							'template_name' => array(
								'type' => 'text',
								'required' => TRUE
							)
						)
					),
					array(
						'title' => 'template_type',
						'desc' => 'template_type_desc',
						'fields' => array(
							'template_type' => array(
								'type' => 'dropdown',
								'choices' => $this->getTemplateTypes()
							)
						)
					),
					array(
						'title' => 'duplicate_existing_template',
						'desc' => 'duplicate_existing_template_desc',
						'fields' => array(
							'template_id' => array(
								'type' => 'dropdown',
								'choices' => $existing_templates
							)
						)
					),
				)
			)
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'template_name',
				'label' => 'lang:template_name',
				'rules' => 'required|callback__template_name_checks[' . $group->group_id . ']'
			),
			array(
				'field' => 'template_type',
				'label' => 'lang:template_type',
				'rules' => 'required'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if (ee()->input->post('template_id'))
			{
				$template = ee('Model')->get('Template', ee()->input->post('template_id'));
				$template->template_id = NULL;
			}
			else
			{
				$template = ee('Model')->make('Template');
			}
			$template->site_id = ee()->config->item('site_id');
			$template->group_id = $group->group_id;
			$template->template_name = ee()->input->post('template_name');
			$template->template_type = ee()->input->post('template_type');
			$template->last_author_id = ee()->session->userdata('member_id');
			$template->save();

			ee('Alert')->makeInline('settings-form')
				->asSuccess()
				->withTitle(lang('create_template_success'))
				->addToBody(sprintf(lang('create_template_success_desc'), $group_name, $template->template_name))
				->defer();

			ee()->functions->redirect(cp_url('design/manager/' . $group->group_name));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('settings-form')
				->asIssue()
				->withTitle(lang('create_template_error'))
				->addToBody(lang('create_template_error_desc'));
		}

		$this->sidebarMenu($group->group_id);
		ee()->view->cp_page_title = lang('create_template');

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($template_id)
	{
		$template = ee('Model')->get('Template', $template_id)->first();

		if ( ! $template)
		{
			show_error(lang('error_no_template'));
		}

		$group = $template->getTemplateGroup();

		if ($this->hasEditTemplatePrivileges($group->group_id) === FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'template_name',
				'label' => 'lang:template_name',
				'rules' => 'required|callback__template_name_checks[' . $group->group_id . ']'
			),
			array(
				'field' => 'template_type',
				'label' => 'lang:template_type',
				'rules' => 'required'
			),
			array(
				'field' => 'cache',
				'label' => 'lang:enable_caching',
				'rules' => 'enum[y,n]'
			),
			array(
				'field' => 'allow_php',
				'label' => 'lang:enable_php',
				'rules' => 'enum[y,n]'
			),
			array(
				'field' => 'php_parse_location',
				'label' => 'lang:parse_stage',
				'rules' => 'enum[i,o]'
			),
			array(
				'field' => 'enable_http_auth',
				'label' => 'lang:enable_http_authentication',
				'rules' => 'enum[y,n]'
			),
			array(
				'field' => 'route',
				'label' => 'lang:template_route_override',
				'rules' => 'callback__template_route_checks'
			),
			array(
				'field' => 'route_required',
				'label' => 'lang:require_all_segments',
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
			$template->template_notes = ee()->input->post('template_notes');

			$template = $this->updateSettingsAndAccess($template);

			$template->save();

			$alert = ee('Alert')->makeInline('template-form')
				->asSuccess()
				->withTitle(lang('update_template_success'))
				->addToBody(sprintf(lang('eupdate_template_success_desc'), $group->group_name, $template->template_name));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('Alert')->makeInline('template-form')
				->asIssue()
				->withTitle(lang('update_template_error'))
				->addToBody(lang('update_template_error_desc'));
		}

		$vars = array(
			'form_url' => cp_url('design/template/edit/' . $template_id),
			'settings' => $this->renderSettingsPartial($template),
			'access' => $this->renderAccessPartial($template),
			'template' => $template,
			'group' => $group,
			'author' => $template->getLastAuthor(),
		);

		$this->stdHeader();
		$this->loadCodeMirrorAssets();

		ee()->view->cp_page_title = sprintf(lang('edit_template'), $group->group_name, $template->template_name);
		ee()->view->cp_breadcrumbs = array(
			cp_url('design') => lang('template_manager'),
			cp_url('design/manager/' . $group->group_name) => sprintf(lang('breadcrumb_group'), $group->group_name)
		);

		ee()->cp->render('design/template/edit', $vars);
	}

	public function remove()
	{

	}

	public function export()
	{

	}

	public function sync()
	{

	}

	public function settings($template_id)
	{
		$template = ee('Model')->get('Template', $template_id)->first();

		if ( ! $template)
		{
			show_error(lang('error_no_template'));
		}

		$vars = array(
			'form_url' => cp_url('design/template/edit/' . $template_id),
			'template' => $template
		);
		ee()->cp->render('design/template/settings', $vars);
	}

	private function updateSettingsAndAccess(TemplateModel $template)
	{
		// Settings
		$template->template_name = ee()->input->post('template_name');
		$template->template_type = ee()->input->post('template_type');
		$template->cache = ee()->input->post('cache');
		$template->refresh = ee()->input->post('refresh');
		$template->allow_php = ee()->input->post('allow_php');
		$template->php_parse_location = ee()->input->post('php_parse_location');
		$template->hits = ee()->input->post('hits');

		// Access
		$template->no_auth_bounce = ee()->input->post('no_auth_bounce');
		$template->enable_http_auth = ee()->input->post('enable_http_auth');

		// Route
		$route = $template->getTemplateRoute();

		if ( ! $route)
		{
			$route = ee('Model')->make('TemplateRoute');
			$route->template_id = $template->template_id;
		}

		$route->route = ee()->input->post('route');
		$route->route_required = ee()->input->post('route_required');

		if (empty($route->route))
		{
			if ($route->route_id)
			{
				$route->delete();
			}
		}
		else
		{
			ee()->load->library('template_router');
			$ee_route = new EE_Route($route->route, $route->route_required);
			$route->route_parsed = $ee_route->compile();

			$route->save();
		}

		return $template;
	}

	private function loadCodeMirrorAssets()
	{
		$this->cp->add_to_head($this->view->head_link('css/codemirror.css'));
		$this->cp->add_to_head($this->view->head_link('css/codemirror-additions.css'));
		ee()->cp->add_js_script(array(
				'plugin'	=> 'ee_codemirror',
				'file'		=> array(
					'codemirror/codemirror',
					'codemirror/closebrackets',
					'codemirror/overlay',
					'codemirror/xml',
					'codemirror/css',
					'codemirror/javascript',
					'codemirror/htmlmixed',
					'codemirror/ee-mode',
					'codemirror/dialog',
					'codemirror/searchcursor',
					'codemirror/search',

					'cp/template_editor',
					'cp/manager'
				)
			)
		);
	}

	/**
	 * Get template types
	 *
	 * Returns a list of the standard EE template types to be used in
	 * template type selection dropdowns, optionally merged with
	 * user-defined template types via the template_types hook.
	 *
	 * @access private
	 * @return array Array of available template types
	 */
	private function getTemplateTypes()
	{
		$template_types = array(
			'webpage'	=> lang('webpage'),
			'feed'		=> lang('rss'),
			'css'		=> lang('css_stylesheet'),
			'js'		=> lang('js'),
			'static'	=> lang('static'),
			'xml'		=> lang('xml')
		);

		// -------------------------------------------
		// 'template_types' hook.
		//  - Provide information for custom template types.
		//
		$custom_templates = ee()->extensions->call('template_types', array());
		//
		// -------------------------------------------

		if ($custom_templates != NULL)
		{
			// Instead of just merging the arrays, we need to get the
			// template_name value out of the associative array for
			// easy use of the form_dropdown helper
			foreach ($custom_templates as $key => $value)
			{
				$template_types[$key] = $value['template_name'];
			}
		}

		return $template_types;
	}

	private function renderSettingsPartial(TemplateModel $template)
	{
		$vars = array(
			'template' => $template,
			'template_types' => $this->getTemplateTypes(),
		);
		return ee('View')->make('design/template/partials/settings')->render($vars);
	}

	private function renderAccessPartial(TemplateModel $template)
	{
		$existing_templates = array();

		foreach (ee('Model')->get('TemplateGroup')->all() as $template_group)
		{
			$templates = array();
			foreach ($template_group->getTemplates() as $template)
			{
				$templates[$template->template_id] = $template->template_name;
			}
			$existing_templates[$template_group->group_name] = $templates;
		}

		$member_gropus = ee('Model')->get('MemberGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', '!=', 1)
			->all();

		$route = $template->getTemplateRoute();

		if ( ! $route)
		{
			$route = ee('Model')->make('TemplateRoute');
		}

		$vars = array(
			'template' => $template,
			'route' => $route,
			'denied_member_groups' => $template->getNoAccess()->getIds(),
			'member_groups' => $member_gropus,
			'existing_templates' => $existing_templates
		);
		return ee('View')->make('design/template/partials/access')->render($vars);
	}

	/**
	  *	 Check Template Name
	  */
	public function _template_name_checks($str, $group_id)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $str))
		{
			ee()->lang->loadfile('admin');
			ee()->form_validation->set_message('_template_name_checks', lang('illegal_characters'));
			return FALSE;
		}

		$reserved_names = array('act', 'css');

		if (in_array($str, $reserved_names))
		{
			ee()->form_validation->set_message('_template_name_checks', lang('reserved_name'));
			return FALSE;
		}

		$count = ee('Model')->get('Template')
			->filter('group_id', $group_id)
			->filter('template_name', $str)
			->count();

		if ((strtolower($this->input->post('old_name')) != strtolower($str)) AND $count > 0)
		{
			ee()->form_validation->set_message('_template_name_checks', lang('template_name_taken'));
			return FALSE;
		}
		elseif ($count > 1)
		{
			ee()->form_validation->set_message('_template_name_checks', lang('template_name_taken'));
			return FALSE;
		}

		return TRUE;
	}

	public function _template_route_checks($str)
	{
		if (empty($str))
		{
			return TRUE;
		}

		ee()->load->library('template_router');
		$ee_route = new EE_Route($str, ee()->input->post('route_required'));

		$template_ids = ee('Model')->get('Template')
			->fields('template_id')
			->filter('site_id', ee()->config->item('site_id'))
			->all()
			->getIds();

		$routes = ee('Model')->get('TemplateRoute')
			->filter('template_id', 'IN', $template_ids)
			->all();

		foreach ($routes as $route)
		{
			if ($ee_route->equals($route))
			{
				ee()->form_validation->set_message('_template_route_checks', lang('duplicate_route'));
				return FALSE;
			}
		}
	}
}
// EOF