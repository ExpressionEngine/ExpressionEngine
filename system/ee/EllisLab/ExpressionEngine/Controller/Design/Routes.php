<?php

namespace EllisLab\ExpressionEngine\Controller\Design;

use ZipArchive;
use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;
use EllisLab\ExpressionEngine\Library\Data\Collection;

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
class Routes extends AbstractDesignController {

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

	public function index($routes = NULL, $errors = NULL)
	{
		$vars = array();
		$grid = ee('CP/GridInput', array(
			'wrap' => FALSE,
			'field_name' => 'routes',
		));
		$grid->loadAssets();

		$grid->setColumns(array(
			'template',
			'group',
			'route',
			'segments_required',
		));
		$data = array();

		if (is_null($routes))
		{
			$routes = ee('Model')->get('TemplateRoute')
				->with(array('Template' => 'TemplateGroup'))
				->filter('Template.site_id', ee()->config->item('site_id'))
				->order('TemplateRoute.order', 'asc')
				->all();
		}

		foreach($routes as $route)
		{
			$data[] = $this->getRouteRow($route, $errors);
		}

		$grid->setNoResultsText('no_template_routes');
		$grid->setData($data);

		$blank_row = $this->getRouteRow(ee('Model')->make('TemplateRoute'), $errors);
		$grid->setBlankRow($blank_row['columns']);

		$grid->addActionButton('#', lang('new_route'), 'add');

		$vars = array(
			'table'          => $grid->viewData($this->base_url),
			'form_url'       => ee('CP/URL')->make('design/routes/update'),
			'cp_page_title'  => lang('template_manager'),
			'cp_heading'     => lang('template_routes_header'),
			'cp_sub_heading' => lang('template_routes_header_desc')
		);

		$this->stdHeader();

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/design/routes',
			),
		));

		ee()->cp->render('design/routes/index', $vars);
	}

	private function getRouteRow($route, $errors)
	{
		static $new_route_index = 0;
		$row = array();

		$group_field = ($route->Template) ? htmlentities($route->Template->TemplateGroup->group_name, ENT_QUOTES, 'UTF-8') : '';
		$new_route_index++;

		if ($route->isNew())
		{
			$id = 'new_row_' . $new_route_index;
			$row['attrs']['row_id'] = $id;

			$template_field = ee('View')->make('_shared/form/field')
				->render(array(
					'field_name' => "template_id",
					'field' => array(
						'type' => 'select',
						'choices' => $this->getTemplatesWithoutRoutes(),
						'value' => ($route->Template) ? $route->Template->template_id : ''
					),
					'grid' => TRUE,
				));
		}
		else
		{
			$row['attrs']['row_id'] = $route->Template->template_id;
			$id = 'row_id_' . $route->Template->template_id;

			$template_field = htmlentities($route->Template->template_name, ENT_QUOTES, 'UTF-8');
		}

		$required = ee('View')->make('_shared/form/field')
			->render(array(
				'field_name' => "required",
				'field' => array(
					'type' => 'yes_no',
					'value' => ($route->route_required === FALSE) ? 'n' : 'y'
				),
				'grid' => TRUE,
				'errors' => $errors
			));

		$route_field = ee('View')->make('_shared/form/field')
			->render(array(
				'field_name' => "route",
				'field' => array(
					'type' => 'text',
					'value' => $route->route ?: ''
				),
				'grid' => TRUE,
			));

		$row['columns'] = array(
			array(
				'html' => $template_field,
				'error' => (isset($errors) && $errors->hasErrors("routes[rows][{$id}][template_id]")) ? implode('<br>', $errors->getErrors("routes[rows][{$id}][template_id]")) : NULL
			),
			$group_field,
			array(
				'html' => $route_field,
				'error' => (isset($errors) && $errors->hasErrors("routes[rows][{$id}][route]")) ? implode('<br>', $errors->getErrors("routes[rows][{$id}][route]")) : NULL
			),
			$required,
		);
		$row['attrs']['class'] = 'setting-field';

		return $row;
	}

	public function update()
	{
		if (ee('Request')->method() != 'POST')
		{
			ee()->functions->redirect($this->base_url);
		}

		$errors = new ValidationResult;

		$routes = new Collection(array());

		$existing_routes = ee('Model')->get('TemplateRoute')
			->with(array('Template' => 'TemplateGroup'))
			->filter('Template.site_id', ee()->config->item('site_id'))
			->order('TemplateRoute.order', 'asc')
			->all()
			->indexBy('template_id');

		$submitted = ee()->input->post('routes');

		if ( ! $submitted || ! array_key_exists('rows', $submitted))
		{
			$submitted = array('rows' => array());
		}

		$order = array_keys($submitted['rows']);

		foreach ($submitted['rows'] as $template_id => $data)
		{
			$data['route'] = trim($data['route']);

			if (strpos($template_id, 'new_') === 0)
			{
				$route = ee('Model')->make('TemplateRoute');
				$route->Template = ee('Model')->get('Template', $data['template_id'])
					->with('TemplateGroup')
					->first();
			}
			else
			{
				$route = $existing_routes[str_replace('row_id_', '', $template_id)];
			}

			$route->route = $data['route'];
			$route->route_required = ($data['required'] == 'y') ? TRUE : FALSE;
			$route->order = array_search($template_id, $order);

			$field_prefix = "routes[rows][{$template_id}]";

			$validator = ee('Validation')->make(array(
				'route' => 'uniqueRoute'
			));

			$validator->defineRule('uniqueRoute', function($key, $route, $parameters) use ($routes) {
				foreach ($routes as $r)
				{
					if (($r->route == $route->route)
						&& ($r->route_required == $route->route_required))
					{
						return 'duplicate_route';
					}
				}

				return TRUE;
			});

			$errors = $this->transferErrors($field_prefix, $validator->validate(compact('route')), $errors);
			$errors = $this->transferErrors($field_prefix, $route->validate(), $errors);

			$routes[] = $route;
		}

		if ($errors->isValid())
		{
			foreach($routes as $route)
			{
				$route->save();
			}

			$to_delete = ee('Model')->get('TemplateRoute')
				->with('Template')
				->filter('Template.site_id', ee()->config->item('site_id'));

			if (count($routes) > 0)
			{
				$to_delete->filter('route_id', 'NOT IN', $routes->pluck('route_id'));
			}

			$to_delete->delete();

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

			$this->index($routes, $errors);
		}
	}

	/**
	 * Gets a list of the templates for the current site that do not already
	 * have a route, grouped by their template group name:
	 *   array(
	 *     'news' => array(
	 *       1 => 'index',
	 *       3 => 'about',
	 *     )
	 *   )
	 *
	 * @return array An associative array of templates
	 */
	private function getTemplatesWithoutRoutes()
	{
		static $existing_templates;

		if ($existing_templates)
		{
			return $existing_templates;
		}

		$existing_templates = array(
			'0' => '-- ' . strtolower(lang('none')) . ' --'
		);

		$all_templates = ee('Model')->get('Template')
			->filter('site_id', ee()->config->item('site_id'))
			->with('TemplateGroup')
			->order('TemplateGroup.group_name')
			->order('template_name');

		$template_ids = ee('Model')->get('TemplateRoute')
			->fields('template_id')
			->with('Template')
			->filter('Template.site_id', ee()->config->item('site_id'))
			->all()
			->pluck('template_id');

		if ($template_ids)
		{
			$all_templates->filter('template_id', 'NOT IN', $template_ids);
		}

		foreach ($all_templates->all() as $template)
		{
			if ( ! isset($existing_templates[$template->TemplateGroup->group_name]))
			{
				$existing_templates[$template->TemplateGroup->group_name] = array();
			}
			$existing_templates[$template->TemplateGroup->group_name][$template->template_id] = $template->template_name;
		}

		return $existing_templates;
	}

	private function transferErrors($field_prefix, ValidationResult $result, ValidationResult $errors)
	{
		if ($result->isNotValid())
		{
			foreach ($result->getFailed() as $field_name => $rules)
			{
				foreach ($rules as $rule)
				{
					$errors->addFailed($field_prefix . '[' . $field_name . ']', $rule);
				}
			}
		}

		return $errors;
	}
}

// EOF
