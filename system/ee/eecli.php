<?php

define('EE_START', microtime(true));

// Set the system path
$system_path = dirname(__DIR__);

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 404 Not Found.', true, '404');
    exit("Not Found\n");
}

// Error Reporting
$debug = 1;

// Get CLI
define('REQ', 'CLI');

// Define some server vars, so as not to break anything
$_SERVER['SERVER_NAME'] = null;
$_SERVER['REMOTE_ADDR'] = null;
$_SERVER['HTTP_HOST'] = null;

/*
 * ---------------------------------------------------------------
 *  Disable all routing, send everything to the frontend
 * ---------------------------------------------------------------
 */
$routing['directory'] = '';
$routing['controller'] = 'ee';
$routing['function'] = 'index';

/*
 * --------------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * --------------------------------------------------------------------
 */

if (realpath($system_path) !== false) {
    $system_path = realpath($system_path);
}

$system_path = rtrim($system_path, '/') . '/';

/*
 * --------------------------------------------------------------------
 *  Now that we know the path, set the main constants
 * --------------------------------------------------------------------
 */
// The name of this file
defined('SELF') || define('SELF', basename(__FILE__));

// Path to this file
defined('FCPATH') || define('FCPATH', __DIR__ . '/');

// Path to the "system" folder
defined('SYSPATH') || define('SYSPATH', $system_path);

// Name of the "system folder"
defined('SYSDIR') || define('SYSDIR', basename($system_path));

// The $debug value as a constant for global access
defined('DEBUG') || define('DEBUG', $debug);

// If EE is not installed, we will not boot the core, but CLI commands are more limited as well.
defined('EE_INSTALLED') || define('EE_INSTALLED', file_exists(SYSPATH . 'user/config/config.php'));
defined('INSTALL_MODE') || define('INSTALL_MODE', ! EE_INSTALLED);

/*
 * --------------------------------------------------------------------
 *  Set the error reporting level
 * --------------------------------------------------------------------
 */
if (DEBUG == 1) {
    error_reporting(E_ALL);
    @ini_set('display_errors', 1);
} else {
    error_reporting(0);
}

// Always turn off HTML Errors
ini_set('html_errors', 0);

// Since this is the CLI, we disable CSRF protection so our request can go through
$assign_to_config['disable_csrf_protection'] = 'y';

/*
 *---------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 *---------------------------------------------------------------
 *
 * And away we go...
 *
 */
if (! file_exists(SYSPATH . 'ee/ExpressionEngine/Boot/boot.php')) {
    exit("\033[31mYour system folder path does not appear to be set correctly.\n");
}

// Fail if EE isn't installed
if (! EE_INSTALLED) {
    exit("\033[31mExpressionEngine does not appear to be installed. Please install ExpressionEngine to use the CLI component.\n");
    die();
}

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.php';
