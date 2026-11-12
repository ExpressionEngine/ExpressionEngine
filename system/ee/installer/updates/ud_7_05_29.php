<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_29;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';
    public $affected_tables = ['template_routes'];

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        $steps = new \ProgressIterator([
            'recompileTemplateRoutes',
        ]);

        foreach ($steps as $step) {
            $this->$step();
        }

        return true;
    }

    /**
     * Recompile saved routes to use the current capture names.
     *
     * @return bool
     */
    public function recompileTemplateRoutes()
    {
        if (! class_exists('EE_Route', false)) {
            require_once EE_APPPATH . 'libraries/template_router/Route.php';
        }

        $routes = ee()->db->select('template_routes.route_id, route, route_required')
            ->join('templates', 'templates.template_id = template_routes.template_id')
            ->join('template_groups', 'template_groups.group_id = templates.group_id')
            ->where('route_parsed IS NOT NULL')
            ->get('template_routes');

        foreach ($routes->result() as $route) {
            $compiled = new \EE_Route($route->route, $route->route_required === 'y');
            $parsed = $compiled->compile();

            // A CLI update may still have the old route parser loaded in memory.
            foreach ($compiled->subpatterns as $capture => $variable) {
                $parsed = str_replace('(?P<' . $capture . '>', '(?P<e' . substr(sha1($variable), 0, 30) . '>', $parsed);
            }

            ee()->db->update('template_routes', ['route_parsed' => $parsed], ['route_id' => $route->route_id]);
        }

        return true;
    }
}

// EOF
