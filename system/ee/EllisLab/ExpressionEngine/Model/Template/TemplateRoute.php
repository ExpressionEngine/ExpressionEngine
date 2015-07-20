<?php

namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */
class TemplateRoute extends Model {

	protected static $_primary_key = 'route_id';
	protected static $_table_name = 'template_routes';

	protected static $_relationships = array(
		'Template' => array(
			'type' => 'BelongsTo'
		)
	);

	protected static $_validation_rules = array(
		'template_id'    => 'required|isNatural',
		'route_required' => 'enum[y,n]',
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
	 * A setter for the route_required property
	 *
	 * @param str|bool $new_value Accept TRUE or 'y' for 'yes' or FALSE or 'n'
	 *   for 'no'
	 * @throws InvalidArgumentException if the provided argument is not a
	 *   boolean or is not 'y' or 'n'.
	 * @return void
	 */
	protected function set__route_required($new_value)
	{
		if ($new_value === TRUE || $new_value == 'y')
		{
			$this->route_required = 'y';
		}

		elseif ($new_value === FALSE || $new_value == 'n')
		{
			$this->route_required = 'n';
		}

		else
		{
			throw new InvalidArgumentException('route_required must be TRUE or "y", or FALSE or "n"');
		}
	}

	/**
	 * A getter for the route_required property
	 *
	 * @return bool TRUE if this is the default; FALSE if not
	 */
	protected function get__route_required()
	{
		return ($this->route_required == 'y');
	}

}
