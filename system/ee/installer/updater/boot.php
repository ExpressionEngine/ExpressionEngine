<?php

// In case a default isn't set on the server
date_default_timezone_set('UTC');

$needFullBootstrap = isset($_GET['step']) &&
(strpos($_GET['step'], 'backupDatabase') === 0 or
    strpos($_GET['step'], 'updateDatabase') === 0 or
    strpos($_GET['step'], 'selfDestruct') === 0 or
    in_array($_GET['step'], ['addLegacyFiles', 'checkForDbUpdates', 'restoreDatabase'])
);
if (file_exists(SYSPATH . 'ee/ExpressionEngine/Boot/boot.php') && $needFullBootstrap) {
    define('BOOT_ONLY', true);
    include_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.php';
} elseif (file_exists(SYSPATH . 'ee/EllisLab/ExpressionEngine/Boot/boot.php') && $needFullBootstrap) {
    define('BOOT_ONLY', true);
    include_once SYSPATH . 'ee/EllisLab/ExpressionEngine/Boot/boot.php';
} else {
    if (! defined('BASEPATH')) {
        defined('BASEPATH') || define('BASEPATH', SYSPATH . 'ee/legacy/');
        defined('PATH_CACHE') || define('PATH_CACHE', SYSPATH . 'user/cache/');
        defined('FILE_READ_MODE') || define('FILE_READ_MODE', 0644);
        defined('FILE_WRITE_MODE') || define('FILE_WRITE_MODE', 0666);
        defined('DIR_READ_MODE') || define('DIR_READ_MODE', 0755);
        defined('DIR_WRITE_MODE') || define('DIR_WRITE_MODE', 0777);

        require __DIR__ . '/ExpressionEngine/Updater/Boot/boot.common.php';
    }
}

// add EE constants
if (file_exists(SYSPATH . '/ee/EllisLab/ExpressionEngine/Config/constants.php')) {
    $constants = require SYSPATH . 'ee/EllisLab/ExpressionEngine/Config/constants.php';
} else {
    $constants = require SYSPATH . 'ee/ExpressionEngine/Config/constants.php';
}

foreach ($constants as $k => $v) {
    defined($k) || define($k, $v);
}

/*
 * ------------------------------------------------------
 *  Load the autoloader and register it
 * ------------------------------------------------------
 */

require SYSPATH . 'ee/updater/ExpressionEngine/Updater/Core/Autoloader.php';

ExpressionEngine\Updater\Core\Autoloader::getInstance()
    ->addPrefix('ExpressionEngine', SYSPATH . 'ee/updater/ExpressionEngine/')
    ->register();

/*
 * ------------------------------------------------------
 *  Route the request to a controller
 * ------------------------------------------------------
 */

if (REQ != 'CLI') {
    if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
        exit('The updater folder is still present. Delete the folder at system/ee/updater to access the control panel.');
    }

    $directory = (isset($_GET['D']) && $_GET['D'] !== 'cp') ? $_GET['D'] : 'updater';
    $controller = (isset($_GET['C'])) ? $_GET['C'] : 'updater';
    $method = (isset($_GET['M'])) ? $_GET['M'] : 'index';

    try {
        routeRequest($directory, $controller, $method);
    } catch (\Exception $e) {
        set_status_header(500);
        $return = [
            'messageType' => 'error',
            'message' => $e->getMessage(),
            'trace' => explode("\n", $e->getTraceAsString())
        ];
        echo json_encode($return);
        exit;
    }
}

function routeRequest($directory, $controller, $method = '')
{
    $class = 'ExpressionEngine\Updater\Controller\\' . ucfirst($directory) . '\\' . ucfirst($controller);

    if (class_exists($class)) {
        $controller_methods = array_map(
            'strtolower',
            get_class_methods($class)
        );

        if (! empty($method) && in_array($method, $controller_methods)) {
            $controller_object = new $class();

            echo $controller_object->$method();
        }
    }
}
