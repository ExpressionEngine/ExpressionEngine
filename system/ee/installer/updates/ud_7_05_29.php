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

class Updater
{
    public $version_suffix = '';
    public $affected_tables = ['template_routes'];

    /**
     * Create independent MFA approvals and recompile saved template routes.
     *
     * @return bool
     */
    public function do_update()
    {
        ee()->load->library('smartforge');

        $steps = new \ProgressIterator([
            'addMfaSessionsTable',
            'addMfaLoginId',
            'recompileTemplateRoutes',
        ]);

        foreach ($steps as $step) {
            if ($this->$step() === false) {
                return false;
            }
        }

        return true;
    }

    /** Add a stable login identifier to the existing, released sessions table. */
    private function addMfaLoginId()
    {
        unset(ee()->db->data_cache['field_names']['sessions']);
        if (ee()->db->field_exists('mfa_login_id', 'sessions')) {
            return true;
        }

        return ee()->smartforge->add_column('sessions', [
            'mfa_login_id' => ['type' => 'char', 'constraint' => 64, 'null' => false, 'default' => '']
        ]) !== false;
    }

    /**
     * Create independent MFA approvals without trusting old remembered tokens.
     *
     * @return bool
     */
    private function addMfaSessionsTable()
    {
        unset(ee()->db->data_cache['table_names']);

        if (!ee()->db->table_exists('mfa_sessions')) {
            ee()->dbforge->add_field([
                'token_hash' => ['type' => 'char', 'constraint' => 64, 'null' => false],
                'member_id' => ['type' => 'int', 'constraint' => 10, 'null' => false],
                'site_id' => ['type' => 'int', 'constraint' => 4, 'null' => false],
                'verified_at' => ['type' => 'bigint', 'constraint' => 10, 'unsigned' => true, 'null' => false],
                'expires_at' => ['type' => 'bigint', 'constraint' => 10, 'unsigned' => true, 'null' => false],
                'mfa_login_id' => ['type' => 'char', 'constraint' => 64, 'null' => false],
                'credential_hash' => ['type' => 'char', 'constraint' => 64, 'null' => false, 'default' => '']
            ]);
            ee()->dbforge->add_key('token_hash', true);
            ee()->dbforge->add_key('member_id');
            ee()->dbforge->add_key('expires_at');
            if (!ee()->smartforge->create_table('mfa_sessions')) {
                return false;
            }
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
