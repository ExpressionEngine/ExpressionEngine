<?php

if (strpos(__DIR__, '/ee/eecms') === false) {
    exit('The eecms utility must be kept in your /system/ee directory.');
}

if (version_compare(phpversion(), '5.4', '<')) {
    exit('The command line version of PHP is less than the required version of 5.4.');
}

// In case a default isn't set on the server
date_default_timezone_set('UTC');

require_once __DIR__ . '/helpers.php';

$project_base = realpath(str_replace('phar://', '', dirname(__DIR__)) . '/../') . '/';

$args = parseArguments();

// Path constants
define('SELF', basename(__FILE__));
define('EESELF', basename(__FILE__));
define('SYSPATH', $project_base);
define('SYSDIR', basename($project_base));
define('FCPATH', dirname(EESELF));
define('DEBUG', 1);
define('REQ', 'CLI');
define('CLI_VERBOSE', isset($args['v']) or isset($args['verbose']));

// Load up ExpressionEngine
if (! isset($args['no-bootstrap']) && !
    (in_array('upgrade', $args) && isset($args['rollback']))) {
    $bootstrap = SYSPATH . 'ee/ExpressionEngine/Boot/boot.php';
    if (file_exists($bootstrap)) {
        define('BOOT_ONLY', true);
        require_once $bootstrap;
    }
}

$supported_commands = ['upgrade'];

// Load up the file for this command
if (isset($args[0]) && in_array($args[0], $supported_commands)) {
    $command = array_shift($args);

    try {
        require_once 'phar://eecms.phar/' . $command . '.php';
        new Command($args);
    } catch (\Exception $e) {
        echo $e->getMessage();
        exit;
    }
} else {
    exit('Available commands:

upgrade               Upgrade this installation of ExpressionEngine
upgrade --rollback    Rollback the install if an upgrade failed');
}
