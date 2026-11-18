<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Service\Updater;

/**
 * Reuse the installed application's updater authorization before moving its files.
 */
class ControlPanelAuthorization
{
    public function authorize()
    {
        $namespace = $this->getApplicationNamespace();
        $requestClass = $namespace . '\Core\Request';
        $responseClass = $namespace . '\Core\Response';
        $controllerClass = $namespace . '\Controller\Updater\Updater';

        // BOOT_ONLY does not route the request. CP authentication depends on this route.
        $_GET['D'] = 'cp';
        $_GET['C'] = 'updater';
        $_GET['M'] = 'run';
        ee()->router->_set_request(['cp', 'updater', 'run']);
        ee()->uri->segments = [1 => 'cp', 2 => 'updater', 3 => 'run'];

        // BOOT_ONLY does not attach a request or response to the application either.
        ee('App')->setRequest($requestClass::fromGlobals());
        ee('App')->setResponse(new $responseClass());

        // Replace the BOOT_ONLY placeholder; the legacy registry forbids overwriting an entry.
        ee()->remove('__legacy_controller');

        // Its constructor runs CP session, CSRF, Super Admin and applicable MFA checks.
        // Do not invoke an action: this request must not run any update operations.
        new $controllerClass();
    }

    protected function getApplicationNamespace()
    {
        return is_file(SYSPATH . 'ee/ExpressionEngine/Boot/boot.php')
            ? 'ExpressionEngine'
            : 'EllisLab\ExpressionEngine';
    }
}
