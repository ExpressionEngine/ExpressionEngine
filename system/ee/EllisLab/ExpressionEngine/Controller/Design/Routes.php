<?php

namespace EllisLab\ExpressionEngine\Controller\Design;

use ZipArchive;
use EllisLab\ExpressionEngine\Controller\Design\Design;
use EllisLab\ExpressionEngine\Library\CP\Table;

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
 * ExpressionEngine CP Design\Routes Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Routes extends Design {

	protected $base_url;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_design', 'can_admin_design'))
		{
			show_error(lang('unauthorized_access'));
		}

		if (ee()->config->item('enable_template_routes') == 'n')
		{
			ee()->functions->redirect(ee('CP/URL', 'design'));
		}

		// Only show this page if we're not using a file based config
		if ( ! empty(ee()->config->item('routes')))
		{
			ee()->functions->redirect(ee('CP/URL', 'design'));
		}

		$this->sidebarMenu();
		$this->stdHeader();
		ee()->lang->loadfile('template_router');

		$this->base_url = ee('CP/URL', 'design/routes');
	}

	public function index()
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
		$templates = ee()->api->get('Template')
						->with('TemplateGroup')
						->with('TemplateRoute')
						->order('TemplateRoute.order', 'asc')
						->order('TemplateGroup.group_name', 'asc')
						->order('template_name', 'asc')
						->all();

		foreach($templates as $template)
		{
			$route = $template->TemplateRoute;

			// create a route of the template doesn't have one yet
			if (empty($route))
			{
				$template->TemplateRoute = ee('Model')->make('TemplateRoute');
				$template->save();
				$route = $template->TemplateRoute;
			}

			$group = $template->TemplateGroup;
			$id = $template->template_id;
			$route_id = $route->getId();

			if (empty($route) || $route->route_required === FALSE)
			{
				$no_class = "chosen";
				$no_selected = "checked='checked'";
				$yes_class = $yes_selected = '';
			}
			else
			{
				$yes_class = "chosen";
				$yes_selected = "checked='checked'";
				$no_class = $no_selected = '';
			}

			$required = <<<RADIO
<label class="choice yes mr $yes_class">
	<input type="radio" name="routes[{$id}][required]" value="y" $yes_selected>
	yes
</label>
<label class="choice no $no_class">
	<input type="radio" name="routes[{$id}][required]" value="n" $no_selected>
	no
</label>
RADIO;

			$route = $route->route;
			$route = empty($route) ? '' : $route;
			$route = form_input("routes[{$id}][route]", $route);

			$row = array();
			$row['columns'] = array(
				htmlentities($group->group_name, ENT_QUOTES),
				$template->template_name,
				$route,
				$required
			);
			$row['attrs']['class'] = 'setting-field';

			$data[] = $row;
		}

		$table->setNoResultsText('no_template_routes');
		$table->setData($data);

		$vars['table'] = $table->viewData($this->base_url);
		$vars['form_url'] = ee('CP/URL', 'design/routes/update');

		$this->stdHeader();

		ee()->cp->add_js_script('plugin', 'ee_table_reorder');
		ee()->cp->add_js_script('file', 'cp/v3/route_reorder');

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


		ee()->load->library('template_router');

		$errors = array();
		$error_ids = array();
		$updated_routes = array();
		$templates = ee()->api->get('Template')->all();
		$submitted = ee()->input->post('routes');
		$order = array_keys($submitted);

		foreach ($templates as $template)
		{
			$error = FALSE;
			$id = $template->template_id;
			$route_required = FALSE;

			if ( ! empty($submitted[$id]['required']))
			{
				$route_required = $submitted[$id]['required'];
			}

			$route = $submitted[$id]['route'];
			$ee_route = NULL;

			if ($route_required !== FALSE)
			{
				$required = $route_required;
			}
			else
			{
				$required = 'n';
			}

			if ( ! empty($route))
			{
				try
				{
					$ee_route = new \EE_Route($route, $required == 'y');
					$compiled = $ee_route->compile();
				}
				catch (Exception $error)
				{
					$error = $error->getMessage();
					$error_ids[] = $id;
					$errors[$id] = $error;
				}
			}
			else
			{
				$compiled = NULL;
				$route = NULL;
			}

			// Check if we have a duplicate route
			if ( ! empty($ee_route))
			{
				foreach ($updated_routes as $existing_route)
				{
					if ($ee_route->equals($existing_route))
					{
						$error_ids[] = $id;
						$errors[$id] = lang('duplicate_route');
						$error = TRUE;
					}
				}
				if ($error === FALSE)
				{
					$updated_routes[] = $ee_route;
				}
			}

			if ($error === FALSE)
			{
				$data = array(
					'route' => $route,
					'route_parsed' => $compiled,
					'route_required' => $required,
					'template_id' => $id,
					'order' => array_search($id, $order)
				);
				$current = ee()->api->get('TemplateRoute')->filter('template_id', $id)->first();

				if (empty($current))
				{
					$current = ee()->api->make('TemplateRoute', $data);
					$current->save();
				}
				else
				{
					ee()->api->get('TemplateRoute')->filter('template_id', $id)->first()->set($data)->save();
				}
			}
		}

		if (empty($errors))
		{
			ee()->session->set_flashdata('message_success', lang('template_routes_saved'));
			ee()->functions->redirect($this->base_url);
		}
		else
		{
			$this->index();
		}
	}
}
// EOF
