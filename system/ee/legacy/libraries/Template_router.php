<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use  ExpressionEngine\Model\Template\TemplateRoute;

/**
 * Template Router
 */
class EE_Template_Router extends CI_Router
{
    public $end_points = array();

    public function __construct()
    {
        require_once APPPATH . 'libraries/template_router/Match.php';
        require_once APPPATH . 'libraries/template_router/Route.php';
        $this->set_routes();
    }

    /**
     * Match a URL to its template and group
     *
     * @param EE_URI $uri
     * @access public
     * @return EE_Route_match Instantiated match object for the matched template & group
     */
    public function match($uri)
    {
        $request = $uri->uri_string;

        // First check if we have a bare match
        if (! empty($this->end_points[$request])) {
            return $this->end_points[$request];
        }

        foreach ($this->end_points as $route => $end_point) {
            if (preg_match_all("/$route/i", $request, $matches) == 1) {
                $route = $this->fetch_route($end_point['group'], $end_point['template']);

                return new EE_Route_match($end_point, $matches, $route);
            }
        }

        throw new Exception(lang('route_not_found'));
    }

    /**
     * Grab our parsed template routes from the database
     *
     * @access protected
     * @return void
     */
    public function set_routes()
    {
        $site_id = ee()->config->item('site_id');
        $config = TemplateRoute::getConfig();

        if (! empty($config)) {
            foreach ($config as $template => $route) {
                list($group_name, $template_name) = explode('/', $template);
                $route_parsed = new EE_Route($route);

                $this->end_points[$route_parsed->compile()] = array(
                    "template" => $template_name,
                    "group" => $group_name
                );
            }
        }

        ee()->db->select('route_parsed, template_name, group_name');
        ee()->db->from('templates');
        ee()->db->join('template_routes', 'templates.template_id = template_routes.template_id');
        ee()->db->join('template_groups', 'templates.group_id = template_groups.group_id');
        ee()->db->where('route_parsed is not null');
        ee()->db->where('templates.site_id', $site_id);
        ee()->db->order_by('order, group_name, template_name', 'ASC');
        $query = ee()->db->get();

        foreach ($query->result() as $template) {
            $this->end_points[$template->route_parsed] = array(
                "template" => $template->template_name,
                "group" => $template->group_name
            );
        }
    }

    /**
     * Fetch the template route for the specified template.
     *
     * @param string $group		The name of the template group
     * @param string $template	The name of the template
     * @access public
     * @return EE_Route  An instantiated route object for the matched route
     */
    public function fetch_route($group, $template)
    {
        $site_id = ee()->config->item('site_id');
        $config = TemplateRoute::getConfig();
        $route = "$group/$template";

        if (! empty($config[$route])) {
            return new EE_Route($config[$route]);
        }

        ee()->db->select('route, route_parsed, route_required, template_name, group_name');
        ee()->db->from('templates');
        ee()->db->join('template_routes', 'templates.template_id = template_routes.template_id');
        ee()->db->join('template_groups', 'templates.group_id = template_groups.group_id');
        ee()->db->where('templates.site_id', $site_id);
        ee()->db->where('template_name', $template);
        ee()->db->where('group_name', $group);
        ee()->db->where('route is not null');
        $query = ee()->db->get();

        if ($query->num_rows() > 0) {
            $required = $query->row()->route_required == 'y';

            return new EE_Route($query->row()->route, $required);
        }
    }

    /**
     * Create EE_Route object from EE formatted route string
     *
     * @param string $route   An EE formatted route string
     * @param bool $required  Set whether segments are optional or required
     * @access public
     * @return EE_Route The instantiated route object.
     */
    public function create_route($route, $required = false)
    {
        return new EE_Route($route, $required);
    }
}
// END CLASS

// EOF
