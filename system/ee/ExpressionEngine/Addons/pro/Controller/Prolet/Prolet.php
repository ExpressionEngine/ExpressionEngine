<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Controller\Prolet;

use ExpressionEngine\Controller\Pro\Pro as Pro;

/**
 * Prolet Controller
 */
class Prolet extends Pro
{
    /**
     * Constructor
     */
    public function __construct()
    {
        ee()->lang->load('pro');
    }

    /**
     * Pass the call to prolet
     *
     * @param string $method Prolet ID
     * @param array $arguments Prolet action name
     * @return string generated view
     */
    public function __call($id, $arguments)
    {
        if (!is_numeric($id)) {
            throw new \InvalidArgumentException('Prolet ID is required');
        }

        ee()->lang->load('addons');

        $prolet = ee('Model')->get('pro:Prolet', $id)->first();
        if (empty($prolet)) {
            show_error(lang('prolet_does_not_exist'), 403);
        }

        $addon = ee('pro:Addon')->get($prolet->source);
        if (empty($addon) || !$addon->isInstalled() || !$addon->hasModule()) {
            show_error(lang('requested_module_not_installed') . NBS . $prolet->source, 403);
        }

        if (!ee('Permission')->isSuperAdmin()) {
            $assigned_modules = ee()->session->getMember()->getAssignedModules()->pluck('module_name');
            if (!in_array($addon->getModuleClass(), $assigned_modules)) {
                show_error(lang('unauthorized_access'), 403);
            }
        }

        if (!$addon->hasProlet() || empty($prolet->prolet) || !$prolet->prolet->checkPermissions()) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile($prolet->source);

        if (!$addon->hasProlet() || !ee('pro:License')->isRegisteredProlet($prolet->source, $prolet->class)) {
            show_error(lang('no_prolets_available'), 403);
        }

        ee()->cp->add_js_script(array(
            'pro_file' => array(
                'iframe-listener'
            )
        ));

        //if this is POST request, take care of operation
        if (ee('Request')->isPost()) {
            if (!empty($arguments[0])) {
                $action = array_shift($arguments[0]);
            } else {
                $action = $prolet->action;
            }
            if (!method_exists($prolet->prolet, $action)) {
                show_error(lang('prolet_action_does_not_exist'), 403);
            }
            return $prolet->prolet->$action();
        }

        return $prolet->generateOutput();
    }
}

// EOF
