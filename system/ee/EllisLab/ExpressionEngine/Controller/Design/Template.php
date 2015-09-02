<?php

namespace EllisLab\ExpressionEngine\Controller\Design;

use \EE_Route;
use ZipArchive;
use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use EllisLab\ExpressionEngine\Library\CP\Table;

use EllisLab\ExpressionEngine\Model\Template\Template as TemplateModel;

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
 * ExpressionEngine CP Design\Template Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Template extends AbstractDesignController {

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
			'base_url' => ee('CP/URL', 'design/template/create/' . $group_name),
			'sections' => array(
				array(
					array(
						'title' => 'name',
						'desc' => 'alphadash_desc',
						'fields' => array(
							'template_name' => array(
								'type' => 'text',
								'required' => TRUE
							)
						)
					),
					array(
						'title' => 'template_type',
						'fields' => array(
							'template_type' => array(
								'type' => 'select',
								'choices' => $this->getTemplateTypes()
							)
						)
					),
					array(
						'title' => 'duplicate_existing_template',
						'desc' => 'duplicate_existing_template_desc',
						'fields' => array(
							'template_id' => array(
								'type' => 'select',
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
			$template->edit_date = ee()->localize->now;
			$template->last_author_id = ee()->session->userdata('member_id');
			$template->save();

			ee()->session->set_flashdata('template_id', $template->template_id);

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('create_template_success'))
				->addToBody(sprintf(lang('create_template_success_desc'), $group_name, $template->template_name))
				->defer();

			if (ee()->input->post('submit') == 'edit')
			{
				ee()->functions->redirect(ee('CP/URL', 'design/template/edit/' . $template->template_id));
			}
			else
			{
				ee()->functions->redirect(ee('CP/URL', 'design/manager/' . $group->group_name));
			}
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('create_template_error'))
				->addToBody(lang('create_template_error_desc'))
				->now();
		}

		$this->generateSidebar($group->group_id);
		ee()->view->cp_page_title = lang('create_template');

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($template_id)
	{
		$template = ee('Model')->get('Template', $template_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

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
			$template->edit_date = ee()->localize->now;
			$template->last_author_id = ee()->session->userdata('member_id');

			$template = $this->updateSettingsAndAccess($template);

			$template->save();

			$alert = ee('CP/Alert')->makeInline('template-form')
				->asSuccess()
				->withTitle(lang('update_template_success'))
				->addToBody(sprintf(lang('update_template_success_desc'), $group->group_name . '/' . $template->template_name))
				->defer();

			if (ee()->input->post('submit') == 'finish')
			{
				ee()->session->set_flashdata('template_id', $template->template_id);
				ee()->functions->redirect(ee('CP/URL', 'design/manager/' . $group->group_name));
			}

			ee()->functions->redirect(ee('CP/URL', 'design/template/edit/' . $template->template_id));
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

		$edit_fields = array(
			array(
				'title' => '',
				'desc' => sprintf(lang('last_edit'), ee()->localize->human_time($template->edit_date), (empty($author)) ? '-' : $author->screen_name),
				'wide' => TRUE,
				'fields' => array(
					'template_data' => array(
						'type' => 'textarea',
						'attrs' => 'class="template-edit"',
						'value' => $template->template_data,
					)
				)
			)
		);

		$edit = ee('View')->make('ee:_shared/form/section')
				->render(array('name' => NULL, 'settings' => $edit_fields));

		$notes_fields = array(
			array(
				'title' => 'template_notes',
				'desc' => 'template_notes_desc',
				'wide' => TRUE,
				'fields' => array(
					'template_notes' => array(
						'type' => 'textarea',
						'value' => $template->template_notes,
					)
				)
			)
		);

		$notes = ee('View')->make('ee:_shared/form/section')
				->render(array('name' => NULL, 'settings' => $notes_fields));

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL', 'design/template/edit/' . $template_id),
			'tabs' => array(
				'edit' => $edit,
				'notes' => $notes,
				'settings' => $this->renderSettingsPartial($template),
				'access' => $this->renderAccessPartial($template),
			),
			'buttons' => array(
				array(
					'name' => 'submit',
					'type' => 'submit',
					'value' => 'update',
					'text' => sprintf(lang('btn_save'), lang('template')),
					'working' => 'btn_create_template_working'
				),
				array(
					'name' => 'submit',
					'type' => 'submit',
					'value' => 'finish',
					'text' => 'btn_update_and_finish_editing',
					'working' => 'btn_create_template_working'
				),
			),
			'sections' => array(),
		);

		$view_url = ee()->functions->fetch_site_index();
		$view_url = rtrim($view_url, '/').'/';

		if ($template->template_type == 'css')
		{
			$view_url .= QUERY_MARKER.'css='.$group->group_name.'/'.$template->template_name;
		}
		else
		{
			$view_url .= $group->group_name.(($template->template_name == 'index') ? '' : '/'.$template->template_name);
		}

		$vars['view_path'] = ee()->cp->masked_url($view_url);

		$this->stdHeader();
		$this->loadCodeMirrorAssets();

		ee()->view->cp_page_title = sprintf(lang('edit_template'), $group->group_name . '/' . $template->template_name);
		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL', 'design')->compile() => lang('template_manager'),
			ee('CP/URL', 'design/manager/' . $group->group_name)->compile() => sprintf(lang('breadcrumb_group'), $group->group_name)
		);

		// Supress browser XSS check that could cause obscure bug after saving
		ee()->output->set_header("X-XSS-Protection: 0");

		ee()->cp->render('settings/form', $vars);
	}

	public function settings($template_id)
	{
		$template = ee('Model')->get('Template', $template_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

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
				'field' => 'refresh',
				'label' => 'lang:refresh_interval',
				'rules' => 'integer'
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

		if (AJAX_REQUEST && ! empty($_POST))
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$template = $this->updateSettingsAndAccess($template);

			$template->save();

			$alert = ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('update_template_success'))
				->addToBody(sprintf(lang('update_template_success_desc'), $group->group_name.'/'.$template->template_name))
				->defer();

			ee()->session->set_flashdata('template_id', $template->template_id);
			ee()->functions->redirect(ee('CP/URL', 'design/manager/' . $group->group_name));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('template-form')
				->asIssue()
				->withTitle(lang('update_template_error'))
				->addToBody(lang('update_template_error_desc'))
				->defer();
			ee()->functions->redirect(ee('CP/URL', 'design/template/edit/' . $template->template_id));
		}

		$vars = array(
			'form_url' => ee('CP/URL', 'design/template/settings/' . $template_id),
			'settings' => $this->renderSettingsPartial($template),
			'access' => $this->renderAccessPartial($template),
		);
		ee()->cp->render('design/template/settings', $vars);
	}

	public function search()
	{
		if (ee()->input->post('bulk_action') == 'export')
		{
			$this->exportTemplates(ee()->input->post('selection'));
		}

		$search_terms = ee()->input->get_post('search');

		$return = ee()->input->get_post('return');

		if ( ! $search_terms)
		{
			$return = base64_decode(ee()->input->get_post('return'));
			$uri_elements = json_decode($return, TRUE);
			$return = ee('CP/URL', $uri_elements['path'], $uri_elements['arguments']);
			ee()->functions->redirect($return);
		}
		else
		{
			$this->stdHeader($return);
		}

		$templates = ee('Model')->get('Template')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('template_data', 'LIKE', '%' . $search_terms . '%')
			->all();

		$base_url = ee('CP/URL', 'design/template/search');

		$table = $this->buildTableFromTemplateCollection($templates, TRUE);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];
		$vars['show_new_template_button'] = FALSE;

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
				->perPage($vars['table']['limit'])
				->currentPage($vars['table']['page'])
				->render($base_url);
		}

		ee()->view->cp_heading = sprintf(
			lang('search_results_heading'),
			$templates->count(),
			$search_terms
		);

		ee()->javascript->set_global('template_settings_url', ee('CP/URL', 'design/template/settings/###')->compile());
		ee()->javascript->set_global('lang.remove_confirm', lang('template') . ': <b>### ' . lang('templates') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
				'cp/manager'
			),
		));

		$this->generateSidebar();
		$this->stdHeader();
		ee()->view->cp_page_title = lang('template_manager');

		ee()->cp->render('design/index', $vars);
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

		$member_groups = ee('Model')->get('MemberGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', '!=', 1)
			->all();

		$allowed_member_groups = ee()->input->post('allowed_member_groups') ?: array();


		$no_access = $member_groups->filter(function($group) use ($allowed_member_groups)
		{
			return ! in_array($group->group_id, $allowed_member_groups);
		});

		$template->NoAccess = $no_access;

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
		$sections = array(
			array(
				array(
					'title' => 'template_name',
					'desc' => 'alphadash_desc',
					'fields' => array(
						'old_name' => array(
							'type' => 'hidden',
							'value' => $template->template_name
						),
						'template_name' => array(
							'type' => 'text',
							'value' => $template->template_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'template_type',
					'fields' => array(
						'template_type' => array(
							'type' => 'select',
							'choices' => $this->getTemplateTypes(),
							'value' => $template->template_type
						)
					)
				),
				array(
					'title' => 'enable_caching',
					'desc' => 'enable_caching_desc',
					'fields' => array(
						'cache' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $template->cache
						)
					)
				),
				array(
					'title' => 'refresh_interval',
					'desc' => 'refresh_interval_desc',
					'fields' => array(
						'refresh' => array(
							'type' => 'text',
							'value' => $template->refresh
						)
					)
				),
				array(
					'title' => 'enable_php',
					'desc' => 'enable_php_desc',
					'caution' => TRUE,
					'fields' => array(
						'allow_php' => array(
							'type' => 'yes_no',
							'value' => $template->allow_php
						)
					)
				),
				array(
					'title' => 'parse_stage',
					'desc' => 'parse_stage_desc',
					'fields' => array(
						'php_parse_location' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'i' => 'input',
								'o' => 'output'
							),
							'value' => $template->php_parse_location
						)
					)
				),
				array(
					'title' => 'hit_counter',
					'desc' => 'hit_counter_desc',
					'fields' => array(
						'hits' => array(
							'type' => 'text',
							'disabled' => TRUE,
							'value' => $template->hits
						)
					)
				)
			)
		);

		$html = ee('CP/Alert')->makeInline('permissions-warn')
			->asWarning()
			->addToBody(lang('php_in_templates_warning'))
			->addToBody(
				sprintf(lang('php_in_templates_warning2'), '<span title="excercise caution"></span>'),
				'caution'
			)
			->cannotClose()
			->render();

		foreach ($sections as $name => $settings)
		{
			$html .= ee('View')->make('ee:_shared/form/section')
				->render(array('name' => $name, 'settings' => $settings));
				// , 'errors' => $errors
		}

		return $html;
	}

	private function renderAccessPartial(TemplateModel $template)
	{
		// @TODO: use ee('View')->make('ee:_shared/form/section') instead (see mcp.forum.php)

		$existing_templates = array();

		foreach (ee('Model')->get('TemplateGroup')->all() as $template_group)
		{
			$templates = array();
			foreach ($template_group->getTemplates() as $t)
			{
				$templates[$template->template_id] = $t->template_name;
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
			'denied_member_groups' => $template->getNoAccess()->pluck('group_id'),
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
		if ( ! preg_match("#^[a-zA-Z0-9_\.\-/]+$#i", $str))
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
			->pluck('template_id');

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
