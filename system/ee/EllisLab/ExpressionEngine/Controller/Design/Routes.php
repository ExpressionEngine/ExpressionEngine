<?php

namespace EllisLab\ExpressionEngine\Controller\Design;

use ZipArchive;
use EllisLab\ExpressionEngine\Controller\Design\Design;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;

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
 * ExpressionEngine CP Design\Routes Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Routes extends Design {

	protected $base_url;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if (IS_CORE)
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! ee()->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		if (ee()->config->item('enable_template_routes') == 'n')
		{
			ee()->functions->redirect(ee('CP/URL')->make('design'));
		}

		// Only show this page if we're not using a file based config
		$routes_config = ee()->config->item('routes');
		if ( ! empty($routes_config))
		{
			ee()->functions->redirect(ee('CP/URL')->make('design'));
		}

		$this->generateSidebar('routes');
		$this->stdHeader();
		ee()->lang->loadfile('template_router');

		$this->base_url = ee('CP/URL')->make('design/routes');
	}

	public function index($templates = NULL, $errors = NULL)
	{
		$vars = array();
		$table = ee('CP/Table', array('reorder' => TRUE, 'sortable' => FALSE));
		$columns = array(
			'group_name',
			'template_name',
			'route' => array('encode' => FALSE),
			'segments_required' => array('encode' => FALSE)
		);

		$table->setColumns($columns);
		$data = array();

		if (is_null($templates))
		{
			$templates = ee()->api->get('Template')
				->with('TemplateGroup')
				->with('TemplateRoute')
				->filter('site_id', ee()->config->item('site_id'))
				->order('TemplateGroup.group_name', 'asc')
				->order('template_name', 'asc')
				->all()
				->sortBy(function($template) {
					return ($template->TemplateRoute) ? $template->TemplateRoute->order : INF;
				});
		}

		foreach($templates as $template)
		{
			$route = $template->TemplateRoute;

			$group = $template->TemplateGroup;
			$id = $template->template_id;

			$required = ee('View')->make('_shared/form/field')
				->render(array(
					'field_name' => "routes[{$id}][required]",
					'field' => array(
						'type' => 'yes_no',
						'value' => (empty($route) || $route->route_required === FALSE) ? 'n' : 'y'
					),
					'grid' => FALSE,
					'errors' => $errors
				));

			$route = ee('View')->make('_shared/form/field')
				->render(array(
					'field_name' => "routes[{$id}][route]",
					'field' => array(
						'type' => 'text',
						'value' => ($route && $route->route) ? $route->route : ''
					),
					'grid' => FALSE,
				));

			$row = array();
			$row['columns'] = array(
				htmlentities($group->group_name, ENT_QUOTES, 'UTF-8'),
				$template->template_name,
				array(
					'html' => $route,
					'error' => (isset($errors) && $errors->hasErrors("routes[{$id}][route]")) ? implode('<br>', $errors->getErrors("routes[{$id}][route]")) : NULL
				),
				$required
			);
			$row['attrs']['class'] = 'setting-field';

			$data[] = $row;
		}

		$table->setNoResultsText('no_template_routes');
		$table->setData($data);

		$vars['table'] = $table->viewData($this->base_url);
		$vars['form_url'] = ee('CP/URL')->make('design/routes/update');

		$this->stdHeader();

		ee()->cp->add_js_script('plugin', 'ee_table_reorder');
		ee()->cp->add_js_script('file', 'cp/design/route_reorder');

		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = lang('template_routes_header');
		ee()->cp->render('design/routes/index', $vars);
	}

	public function update()
	{
		if (empty($_POST))
		{
			ee()->functions->redirect($this->base_url);
		}

		$errors = new ValidationResult;
		$templates = ee()->api->get('Template')
			->with('TemplateGroup')
			->with('TemplateRoute')
			->filter('site_id', ee()->config->item('site_id'))
			->order('TemplateRoute.order', 'asc')
			->order('TemplateGroup.group_name', 'asc')
			->order('template_name', 'asc')
			->all();

		$submitted = ee()->input->post('routes');

		$order = array_keys($submitted);

		foreach ($templates as $template)
		{
			$id = $template->template_id;
			$submitted[$id]['route'] = trim($submitted[$id]['route']);

			if (empty($submitted[$id]['route']))
			{
				if ($template->TemplateRoute)
				{
					$template->TemplateRoute = NULL;
					$template->save();
				}
				continue;
			}

			if ( ! $template->TemplateRoute)
			{
				$template->TemplateRoute = ee('Model')->make('TemplateRoute');
			}

			$route = $template->TemplateRoute;

			// We default to not requiring all segments.
			$route->route_required = FALSE;

			if (isset($submitted[$id]['required']) && $submitted[$id]['required'] == 'y')
			{
				$route->route_required = TRUE;
			}

			$route->route = $submitted[$id]['route'];
			$route->order = array_search($id, $order);

			$result = $route->validate();
			if ($result->isNotValid())
			{
				foreach ($result->getFailed() as $field => $rules)
				{
					foreach ($rules as $rule)
					{
						$errors->addFailed("routes[{$id}][route]", $rule);
					}
				}
			}
		}

		if ($errors->isValid())
		{
			$templates->save();

			ee('CP/Alert')->makeInline()
				->asSuccess()
				->withTitle(lang('template_routes_saved'))
				->addToBody(lang('template_routes_saved_desc'))
				->defer();

			ee()->functions->redirect($this->base_url);
		}
		else
		{
			ee()->load->helper('html_helper');
			ee('CP/Alert')->makeInline()
				->asIssue()
				->withTitle(lang('template_routes_not_saved'))
				->addToBody(lang('template_routes_not_saved_desc'))
				->now();

			$this->index($templates, $errors);
		}
	}
}

// EOF
