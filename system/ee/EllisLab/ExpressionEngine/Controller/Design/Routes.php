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

	public function index($templates = NULL, $errors = NULL)
	{
		$vars = array();
		$table = ee('CP/Table', array('reorder' => TRUE, 'sortable' => FALSE, 'wrap' => FALSE));

		$table->setColumns(array(
			'template' => array('encode' => FALSE),
			'group',
			'route' => array('encode' => FALSE),
			'segments_required' => array('encode' => FALSE),
			'remove' => array(
				'type' => Table::COL_TOOLBAR,
				'label' => ''
			)
		));
		$data = array();

		if (is_null($templates))
		{
			$templates = ee()->api->get('TemplateRoute')
				->with(array('Template' => 'TemplateGroup'))
				->filter('Template.site_id', ee()->config->item('site_id'))
				->order('TemplateRoute.order', 'asc')
				->all();
		}

		foreach($templates as $route)
		{
			$template = $route->Template;
			$group = $template->TemplateGroup;
			$id = $template->template_id;

			$required = ee('View')->make('_shared/form/field')
				->render(array(
					'field_name' => "routes[{$id}][required]",
					'field' => array(
						'type' => 'yes_no',
						'value' => ($route->route_required === FALSE) ? 'n' : 'y'
					),
					'grid' => FALSE,
					'errors' => $errors
				));

			$route_field = ee('View')->make('_shared/form/field')
				->render(array(
					'field_name' => "routes[{$id}][route]",
					'field' => array(
						'type' => 'text',
						'value' => $route->route ?: ''
					),
					'grid' => FALSE,
				));

			$row = array();
			$row['columns'] = array(
				$template->template_name,
				htmlentities($group->group_name, ENT_QUOTES, 'UTF-8'),
				array(
					'html' => $route_field,
					'error' => (isset($errors) && $errors->hasErrors("routes[{$id}][route]")) ? implode('<br>', $errors->getErrors("routes[{$id}][route]")) : NULL
				),
				$required,
				array('toolbar_items' => array(
					'remove' => array(
						'href' => '#',
						'title' => lang('remove_route')
					)
				))
			);
			$row['attrs']['class'] = 'setting-field';

			$data[] = $row;
		}

		// Blank Row
		$required = ee('View')->make('_shared/form/field')
			->render(array(
				'field_name' => "routes[new_route_0][required]",
				'field' => array(
					'type' => 'yes_no',
					'value' => 'n'
				),
				'grid' => FALSE,
				'errors' => $errors
			));

		$route_field = ee('View')->make('_shared/form/field')
			->render(array(
				'field_name' => "routes[new_route_0][route]",
				'field' => array(
					'type' => 'text',
					'value' => ''
				),
				'grid' => FALSE,
			));

		$template_field = ee('View')->make('_shared/form/field')
			->render(array(
				'field_name' => "routes[new_route_0][template]",
				'field' => array(
					'type' => 'select',
					'choices' => $this->getTemplatesWithoutRoutes($templates->pluck('template_id')),
					'value' => ''
				),
				'grid' => FALSE,
			));

		$row = array();
		$row['columns'] = array(
			$template_field,
			'',
			array(
				'html' => $route_field,
				'error' => (isset($errors) && $errors->hasErrors("routes[new-0][route]")) ? implode('<br>', $errors->getErrors("routes[new-0][route]")) : NULL
			),
			$required,
			array('toolbar_items' => array()),
		);
		$row['attrs']['class'] = 'setting-field hidden';

		$data[] = $row;

		$table->setNoResultsText('no_template_routes');
		$table->setData($data);
		$table->addActionButton('#', lang('new_route'));

		$vars = array(
			'table'          => $table->viewData($this->base_url),
			'form_url'       => ee('CP/URL')->make('design/routes/update'),
			'cp_page_title'  => lang('template_manager'),
			'cp_heading'     => lang('template_routes_header'),
			'cp_sub_heading' => lang('template_routes_header_desc')
		);

		$this->stdHeader();

		ee()->javascript->set_global('lang.remove_confirm', lang('route') . ': <b>### ' . lang('routes') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
				'cp/design/routes'
			),
		));

		ee()->cp->render('design/routes/index', $vars);
	}

	public function update()
	{
		if (empty($_POST) || ! array_key_exists('routes', $_POST))
		{
			ee()->functions->redirect($this->base_url);
		}

		$errors = new ValidationResult;

		$routes = new Collection(array());

		$existing_routes = ee()->api->get('TemplateRoute')
			->with(array('Template' => 'TemplateGroup'))
			->filter('Template.site_id', ee()->config->item('site_id'))
			->order('TemplateRoute.order', 'asc')
			->all()
			->indexBy('template_id');

		$submitted = ee()->input->post('routes');

		$order = array_keys($submitted);

		foreach ($submitted as $template_id => $data)
		{
			$data['route'] = trim($data['route']);

			if (strpos($template_id, 'new_route_') === 0)
			{
				$route = ee('Model')->make('TemplateRoute');
				$route->template_id = $data['template'];
			}
			else
			{
				$route = $existing_routes[$template_id];

				if (empty($data['route']))
				{
					$route->delete();
					continue;
				}
			}

			$route->route = $data['route'];
			$route->route_required = ($data['required'] == 'y') ? TRUE : FALSE;
			$route->order = array_search($template_id, $order);

			$result = $route->validate();
			if ($result->isNotValid())
			{
				foreach ($result->getFailed() as $field => $rules)
				{
					foreach ($rules as $rule)
					{
						$errors->addFailed("routes[{$template_id}][route]", $rule);
					}
				}
			}
			else
			{
				$routes[] = $route;
			}
		}

		if ($errors->isValid())
		{
			foreach($routes as $route)
			{
				$route->save();
			}

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
	 * Gets a list of all the templates for the current site, grouped by
	 * their template group name, that do not already have a route:
	 *   array(
	 *     'news' => array(
	 *       1 => 'index',
	 *       3 => 'about',
	 *     )
	 *   )
	 *
	 * @return array An associative array of templates
	 */
	private function getTemplatesWithoutRoutes(array $template_ids)
	{
		$existing_templates = array(
			'0' => '-- ' . strtolower(lang('none')) . ' --'
		);

		$all_templates = ee('Model')->get('Template')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('template_id', 'NOT IN', $template_ids)
			->with('TemplateGroup')
			->order('TemplateGroup.group_name')
			->order('template_name')
			->all();

		foreach ($all_templates as $template)
		{
			if ( ! isset($existing_templates[$template->TemplateGroup->group_name]))
			{
				$existing_templates[$template->TemplateGroup->group_name] = array();
			}
			$existing_templates[$template->TemplateGroup->group_name][$template->template_id] = $template->template_name;
		}

		return $existing_templates;
	}
}

// EOF
