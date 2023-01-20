<?php

 if (! defined('SYSPATH')) {
     exit('No direct script access allowed');
 }
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/*
 * ------------------------------------------------------
 *  Set and load the framework constants
 *
 *  BASEPATH - path to the legacy app folder. Most legacy
 *             files check for this (`if ! defined ...`)
 * ------------------------------------------------------
 */
    defined('BASEPATH') || define('BASEPATH', SYSPATH . 'ee/legacy/');
    defined('EESELF') || define('EESELF', SELF);

    // load user configurable constants
    $constants = require SYSPATH . 'ee/ExpressionEngine/Config/constants.php';

    if (file_exists(SYSPATH . 'user/config/constants.php')) {
        $user_constants = include SYSPATH . 'user/config/constants.php';
        if (is_array($user_constants)) {
            $constants = array_merge($constants, $user_constants);
        }
    }

    foreach ($constants as $k => $v) {
        defined($k) || define($k, $v);
    }

/*
 * ------------------------------------------------------
 *  Load the autoloader and register it
 * ------------------------------------------------------
 */
    // Include composer autoloader
    require SYSPATH . 'ee/vendor-build/autoload.php';
    require SYSPATH . 'ee/ExpressionEngine/Core/Autoloader.php';

    ExpressionEngine\Core\Autoloader::getInstance()
        ->addPrefix('EllisLab\Addons', SYSPATH . 'ee/ExpressionEngine/Addons/')
        ->addPrefix('EllisLab\ExpressionEngine', SYSPATH . 'ee/ExpressionEngine/')
        ->addPrefix('ExpressionEngine', SYSPATH . 'ee/ExpressionEngine/')
        ->addPrefix('Michelf', SYSPATH . 'ee/legacy/libraries/typography/Markdown/Michelf/')
        ->addPrefix('Mexitek', SYSPATH . 'ee/Mexitek/')
        ->register();

/*
 * ------------------------------------------------------
 *  Load the environment
 * ------------------------------------------------------
 */
    try {
        if (file_exists(SYSPATH .'../.env.php')) {
            $dotenv = ExpressionEngine\Dependency\Dotenv\Dotenv::createImmutable(SYSPATH .'../', '.env.php');
            $dotenv->load();
            // force the installer/updater?
            defined('INSTALL_MODE') || define('INSTALL_MODE', getenv('EE_INSTALL_MODE') === 'TRUE');
        }
    } catch (\Exception $e) {

    }

/*
 * ------------------------------------------------------
 *  Load the global functions
 * ------------------------------------------------------
 */
    require __DIR__ . '/boot.common.php';

/*
 * ------------------------------------------------------
 *  Define a custom error handler so we can log PHP errors
 * ------------------------------------------------------
 */
    if (defined('REQ') && REQ === 'CLI') {
        set_error_handler('cliErrorHandler');
        register_shutdown_function('cliShutdownHandler');
    } else {
        set_error_handler('_exception_handler');
    }

/*
 * ------------------------------------------------------
 *  Check for the installer if we're booting the CP
 * ------------------------------------------------------
 */
    use ExpressionEngine\Core;

    if (
        defined('REQ') && in_array(REQ, ['CP', 'CLI']) &&
        is_dir(SYSPATH . 'ee/installer/') &&
        (! defined('INSTALL_MODE') or INSTALL_MODE != false)
    ) {
        $core = new Core\Installer();
    } else {
        $core = new Core\ExpressionEngine();
    }

/*
 * ------------------------------------------------------
 *  Boot the core
 * ------------------------------------------------------
 */
    $core->boot();

/*
 * ------------------------------------------------------
 *  Set config items from the index.php file
 * ------------------------------------------------------
 */
    if (isset($assign_to_config)) {
        $core->overrideConfig($assign_to_config);
    }

/*
 * ------------------------------------------------------
 *  Set routing overrides from the index.php file
 * ------------------------------------------------------
 */
    if (isset($routing)) {
        $core->overrideRouting($routing);
    }

/*
 * ------------------------------------------------------
 *  Create global helper functions
 *
 *  Using `CI` for the global name, just in case someone
 *  is relying on that instead of get_instance()
 * ------------------------------------------------------
 */
    global $CI;
    $CI = $core->getLegacyApp()->getFacade();

    function get_instance()
    {
        global $CI;

        return $CI;
    }

    function ee($dep = null)
    {
        $facade = get_instance();

        if (isset($dep) && isset($facade->di)) {
            $args = func_get_args();

            return call_user_func_array(array($facade->di, 'make'), $args);

            return $facade->di->make($dep);
        }

        return $facade;
    }

    if(defined('BOOT_CORE_ONLY')) {
        return $core;
    }

/*
 * ------------------------------------------------------
 *  Parse the request
 * ------------------------------------------------------
 */
    $request = Core\Request::fromGlobals();

/*
 * ------------------------------------------------------
 *  Run the request and get a response
 * ------------------------------------------------------
 */
    try {
        $response = $core->run($request);
    } catch (ParseError $ex) {
        show_exception($ex);
    } catch (Error $ex) {
        show_exception($ex);
    }

/*
 * ------------------------------------------------------
 *  Send the response
 * ------------------------------------------------------
 */
    if ($response) {
        $response->send();
    }

// EOF
