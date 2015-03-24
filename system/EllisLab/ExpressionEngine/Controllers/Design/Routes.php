<?php

namespace EllisLab\ExpressionEngine\Controllers\Design;

use ZipArchive;
use EllisLab\ExpressionEngine\Controllers\Design\Design;
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
			ee()->functions->redirect(cp_url('design'));
		}

		$this->sidebarMenu();
		$this->stdHeader();

		$this->base_url = new URL('design/routes', ee()->session->session_id());
	}

	public function index()
	{
		$vars = array();
		$table = Table::create(array('reorder' => TRUE, 'sortable' => FALSE));
		$columns = array(
			'group_name',
			'template_name',
			'route',
			'segments_required'
		);

		$table->setColumns($columns);
		$data = array();
		$routes = ee()->api->get('TemplateRoute')->order('order', 'asc')->all();

		if (count($routes) == 0)
		{
			$routes = ee()->api->get('Template')->with('TemplateGroup')->order('TemplateGroup.group_name', 'asc')->order('template_name', 'asc')->all();
		}

		foreach($routes as $route)
		{
			if (property_exists($route, 'template_name'))
			{
				$template = $route;
				$route = $route->getTemplateRoute();
			}
			else
			{
				$template = $route->getTemplate();
			}

			$id = $template->template_id;
			$group = $template->getTemplateGroup();

			if (empty($route) || $route->route_required == 'n')
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
	<input type="radio" name="{$id}[required]" value="y" $yes_selected>
	yes
</label>
<label class="choice no $no_class">
	<input type="radio" name="{$id}[required]" value="n" $no_selected>
	no
</label>
RADIO;

			$route = empty($route->route) ? '' : $route->route;
			$route = form_input("{$id}[route]", $route);

			$row = array();
			$row['columns'] = array(
				$group->group_name,
				$template->template_name,
				$route,
				$required
			);
			$row['attrs']['class'] = 'setting-field';

			$data[] = $row;
		}

		$table->setNoResultsText('no_template_variables');
		$table->setData($data);

		$vars['table'] = $table->viewData($this->base_url);
		$vars['form_url'] = $vars['table']['base_url'];


		$this->stdHeader();

		ee()->cp->add_js_script('plugin', 'ee_table_reorder');
		ee()->cp->add_js_script('file', 'cp/v3/route_reorder');

		$reorder_ajax_fail = ee('Alert')->makeBanner('reorder-ajax-fail')
			->asIssue()
			->canClose()
			->withTitle(lang('route_ajax_reorder_fail'))
			->addToBody(lang('route_ajax_reorder_fail_desc'));

		ee()->javascript->set_global('routes.reorder_url', cp_url('design/routes/reorder'));
		ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = lang('template_routes_header');
		ee()->cp->render('design/routes/index', $vars);
	}

	public function reorder()
	{
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
		$query = $this->template_model->get_templates();

		foreach ($query->result() as $template)
		{
			$error = FALSE;
			$id = $template->template_id;
			$route_required = $this->input->post('required_' . $id);
			$route = $this->input->post('route_' . $id);
			$ee_route = NULL;
			if ($route_required !== FALSE)
			{
				$required = $route_required; }
			else
			{
				$required = 'n';
			}
			if ( ! empty($route))
			{
				try
				{
					$ee_route = new EE_Route($route, $required == 'y');
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
				$required = 'n';
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
					'template_id' => $id
				);
				$current = ee()->api->get('TemplateRoute')->filter('template_id', $id)->first();

				if (empty($current))
				{
					$current = ee()->api->make('TemplateRoute', $data);
					$current->save();
				}
				else
				{
					ee()->api->get('TemplateRoute')->filter('template_id', $id)->set($data)->update();
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
