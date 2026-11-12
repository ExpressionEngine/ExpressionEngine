<?php

// Recovery must remain usable when the application's files cannot be bootstrapped.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$recoveryStep = isset($argv[1]) ? $argv[1] : 'rollback';
$recoverySteps = ['rollback', 'restoreDatabase', 'selfDestruct[rollback]'];
if (! in_array($recoveryStep, $recoverySteps, true)) {
    fwrite(STDERR, "Invalid recovery step.\n");
    exit(1);
}

defined('REQ') || define('REQ', 'CLI');
defined('SELF') || define('SELF', 'eecli.php');
defined('EESELF') || define('EESELF', SELF);
defined('SYSPATH') || define('SYSPATH', dirname(__DIR__, 2) . '/');
defined('SYSDIR') || define('SYSDIR', basename(rtrim(SYSPATH, '/')));
defined('FCPATH') || define('FCPATH', SYSPATH . 'ee/');
defined('DEBUG') || define('DEBUG', 1);
defined('CLI_VERBOSE') || define('CLI_VERBOSE', false);
$_SERVER['SERVER_NAME'] = $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_HOST'] = null;
$_GET['step'] = $recoveryStep;
require_once __DIR__ . '/legacy/helpers/cli_helper.php';

try {
    require __DIR__ . '/boot.php';
    $runner = new ExpressionEngine\Updater\Service\Updater\Runner();
    $runner->runStep($recoveryStep);
    $next = $runner->getNextStep();
    if ($next !== false) {
        if (! in_array($next, $recoverySteps, true)) {
            throw new RuntimeException('Invalid recovery continuation.');
        }
        // Reload the restored application before restoring its database or configuration.
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($next), $status);
        exit($status);
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
