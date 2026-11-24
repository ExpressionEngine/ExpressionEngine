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
// Each recovery continuation starts a new PHP process and must boot the restored site, not its installer.
defined('INSTALL_MODE') || define('INSTALL_MODE', false);
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

// Runs a later recovery step in a new PHP process and returns its exit status.
// Where PHP allows it the process is started without a shell, so nothing has
// to be quoted; where the host allows neither proc_open() nor passthru(), the
// command to run is given instead.
$continueIn = function ($step) {
    $usable = function ($function) {
        $disabled = array_map('trim', explode(',', strtolower((string) ini_get('disable_functions'))));

        return function_exists($function) && ! in_array($function, $disabled, true);
    };
    $command = [PHP_BINARY, __FILE__, $step];

    if (PHP_VERSION_ID >= 70400 && $usable('proc_open')) {
        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);

        if (is_resource($process)) {
            return proc_close($process);
        }
    }

    if ($usable('passthru') && $usable('escapeshellarg')) {
        passthru(implode(' ', array_map('escapeshellarg', $command)), $status);

        return $status;
    }

    if (DIRECTORY_SEPARATOR === '\\') {
        // Quoted only where needed, so that the usual command runs in both
        // cmd.exe and PowerShell. PowerShell runs a quoted program only after &.
        $quoted = false;
        $quote = function ($argument) use (&$quoted) {
            if (preg_match('/^[\w.:\\\\\/\[\]~-]+$/', $argument)) {
                return $argument;
            }
            $quoted = true;

            return '"' . $argument . '"';
        };
    } else {
        $quote = function ($argument) {
            return "'" . str_replace("'", "'\\''", $argument) . "'";
        };
    }
    $line = implode(' ', array_map($quote, $command));
    fwrite(STDERR, "This server does not let PHP start the next part of the rollback. To finish it, run:\n"
        . $line . "\n" . (empty($quoted) ? '' : "In PowerShell, put & and a space before that command.\n"));

    return 1;
};

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
        exit($continueIn($next));
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
