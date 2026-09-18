<?php

use ExpressionEngine\Updater\Controller\Updater\Updater;
use ExpressionEngine\Updater\Service\Updater\RequestAuthorization;
use ExpressionEngine\Updater\Service\Updater\UpdaterException;

date_default_timezone_set('UTC');

require_once __DIR__ . '/ExpressionEngine/Updater/Service/Updater/UpdaterException.php';

try {
    $step = isset($_GET['step']) && is_string($_GET['step']) ? $_GET['step'] : null;
    $needFullBootstrap = $step !== null && (
        strpos($step, 'backupDatabase') === 0
        || strpos($step, 'updateDatabase') === 0
        || strpos($step, 'selfDestruct') === 0
        || in_array($step, ['addLegacyFiles', 'checkForDbUpdates', 'restoreDatabase'], true)
    );

    if (REQ != 'CLI') {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            exit('The updater folder is still present. '
                . 'Delete the folder at system/ee/updater to access the control panel.');
        }

        require_once __DIR__ . '/ExpressionEngine/Updater/Service/Updater/RequestAuthorization.php';
        require_once __DIR__ . '/ExpressionEngine/Updater/Service/Updater/ControlPanelAuthorization.php';
        require_once __DIR__ . '/ExpressionEngine/Updater/Controller/Updater/Updater.php';

        $authorization = new RequestAuthorization();
        $authorization->acquireLock();
        $controller = new Updater($authorization);
        $needFullBootstrap = $controller->requiresFullBootstrap();
    }

    // Only an authorized update step or an initial CP handoff may load the installation.
    if (file_exists(SYSPATH . 'ee/ExpressionEngine/Boot/boot.php') && $needFullBootstrap) {
        define('BOOT_ONLY', true);
        include_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.php';
    } elseif (file_exists(SYSPATH . 'ee/EllisLab/ExpressionEngine/Boot/boot.php') && $needFullBootstrap) {
        define('BOOT_ONLY', true);
        include_once SYSPATH . 'ee/EllisLab/ExpressionEngine/Boot/boot.php';
    } elseif (! defined('BASEPATH')) {
        define('BASEPATH', SYSPATH . 'ee/legacy/');
        defined('PATH_CACHE') || define('PATH_CACHE', SYSPATH . 'user/cache/');
        defined('FILE_READ_MODE') || define('FILE_READ_MODE', 0644);
        defined('FILE_WRITE_MODE') || define('FILE_WRITE_MODE', 0666);
        defined('DIR_READ_MODE') || define('DIR_READ_MODE', 0755);
        defined('DIR_WRITE_MODE') || define('DIR_WRITE_MODE', 0777);

        require __DIR__ . '/ExpressionEngine/Updater/Boot/boot.common.php';
    }

    if (file_exists(SYSPATH . 'ee/EllisLab/ExpressionEngine/Config/constants.php')) {
        $constants = require SYSPATH . 'ee/EllisLab/ExpressionEngine/Config/constants.php';
    } else {
        $constants = require SYSPATH . 'ee/ExpressionEngine/Config/constants.php';
    }
    foreach ($constants as $k => $v) {
        defined($k) || define($k, $v);
    }

    require SYSPATH . 'ee/updater/ExpressionEngine/Updater/Core/Autoloader.php';
    ExpressionEngine\Updater\Core\Autoloader::getInstance()
        ->addPrefix('ExpressionEngine', SYSPATH . 'ee/updater/ExpressionEngine/')
        ->register();

    if (REQ != 'CLI') {
        echo $controller->run();
    }
} catch (\Throwable $e) {
    if (REQ == 'CLI') {
        throw $e;
    }

    $status = in_array($e->getCode(), [400, 403, 409], true) ? $e->getCode() : 500;
    http_response_code($status);
    $message = 'Unable to complete the updater request. Check the server error log.';
    if ($status === 403) {
        $message = 'Unauthorized updater request.';
    } elseif ($e instanceof UpdaterException) {
        $message = $e->getMessage();
    }
    if ($status === 500) {
        error_log((string) $e);
    }

    echo json_encode(['messageType' => 'error', 'message' => $message, 'trace' => []]);
    exit;
}
