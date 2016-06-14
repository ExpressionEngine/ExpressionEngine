<?php

namespace EllisLab\ExpressionEngine\Model\Template;

use \EE_Route;
use EllisLab\ExpressionEngine\Service\Model\Model;

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
 * ExpressionEngine Template Route Model
 *
 * A model representing a template route.
 *
 * @package		ExpressionEngine
 * @subpackage	Template
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class TemplateRoute extends Model {

	protected static $_primary_key = 'route_id';
	protected static $_table_name = 'template_routes';

	protected static $_hook_id = 'template_route';

	protected static $_typed_columns = array(
		'order'          => 'int',
		'route_required' => 'boolString',
	);

	protected static $_relationships = array(
		'Template' => array(
			'type' => 'BelongsTo'
		)
	);

	protected static $_validation_rules = array(
		'template_id'    => 'required|isNatural',
		'route'          => 'required|validateRouteIsValid[route_required]|validateRouteIsUnique[route_required]',
		'route_required' => 'enum[y,n]',
	);

	protected static $_events = array(
		'beforeSave',
	);

	protected $route_id;
	protected $template_id;
	protected $order;
	protected $route;
	protected $route_parsed;
	protected $route_required;

	public static function getConfig()
	{
		$site_id = ee()->config->item('site_id');
		$routes = ee()->config->item('routes:' . $site_id);

		if (empty($routes))
		{
			$routes = ee()->config->item('routes');
		}

		if (empty($routes))
		{
			return FALSE;
		}

		return self::flatten($routes);
	}

	public static function flatten($array)
	{
   		$return = array();

		foreach ($array as $key => $value)
		{
			if (is_array($value))
			{
				$return = array_merge($return, array_flatten($value));
			}
			else
			{
				$return[$key] = $value;
			}
   		}

   		return $return;
	}

	public function onBeforeSave()
	{
		ee()->load->library('template_router');
		$ee_route = new EE_Route($this->getProperty('route'), $this->getProperty('route_required'));
		$this->setProperty('route_parsed', $ee_route->compile());
	}

	/**
	 * A getter for the route property. Will override with file based config if
	 * it exists.
	 *
	 * @return string Route
	 */
	protected function get__route()
	{
		$route = "";
		$routes = self::getConfig();

		$template = $this->Template;

		if ( ! empty($template))
		{
			$group = $template->TemplateGroup;
			$name = "{$group->group_name}/{$template->template_name}";

			if ( ! empty($routes[$name]))
			{
				$route = $routes[$name];
			}
		}

		if (empty($route))
		{
			$route = $this->route;
		}

		return $route;
	}

	/**
	 * Validates that the route is valid
	 */
	public function validateRouteIsValid($key, $value, $params, $rule)
	{
		if (empty($value))
		{
			return TRUE;
		}

		$route_required = $params[0];

		ee()->load->library('template_router');

		try
		{
			$ee_route = new EE_Route($value, $route_required);
		}
		catch (\Exception $error)
		{
			$rule->stop();
			return $error->getMessage();
		}

		return TRUE;
	}

	/**
	 * Validates that the route is unique
	 */
	public function validateRouteIsUnique($key, $value, $params, $rule)
	{
		if (empty($value))
		{
			return TRUE;
		}

		$route_required = $this->getProperty($params[0]);

		ee()->load->library('template_router');
		$ee_route = new EE_Route($value, $route_required);

		// Get a list of template IDs for the current site excluding the
		// template ID for this route.
		$template_ids = $this->getModelFacade()->get('Template')
			->fields('template_id')
			->filter('template_id', '!=', $this->template_id)
			->filter('site_id', ee()->config->item('site_id'))
			->all()
			->pluck('template_id');

		if (empty($template_ids))
		{
			return TRUE;
		}

		// Get all non-empty routes based on the template IDs we just grabbed
		$routes = $this->getModelFacade()->get('TemplateRoute')
			->fields('route')
			->filter('template_id', 'IN', $template_ids)
			->all()
			->pluck('route');

		foreach ($routes as $route)
		{
			if ($ee_route->equals(new EE_Route($route, $route_required)))
			{
				return 'duplicate_route';
			}
		}

		return TRUE;
	}

}

// EOF
