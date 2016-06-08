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
			'template',
			'group',
			'route' => array('encode' => FALSE),
			'segments_required' => array('encode' => FALSE)
		);

		$table->setColumns($columns);
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

			$route = ee('View')->make('_shared/form/field')
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

		$vars = array(
			'table'          => $table->viewData($this->base_url),
			'form_url'       => ee('CP/URL')->make('design/routes/update'),
			'cp_page_title'  => lang('template_manager'),
			'cp_heading'     => lang('template_routes_header'),
			'cp_sub_heading' => lang('template_routes_header_desc')
		);

		$this->stdHeader();

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
