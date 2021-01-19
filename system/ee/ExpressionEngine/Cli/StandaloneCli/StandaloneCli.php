<?php

// Autoload the CLI
require SYSPATH . 'ee/ExpressionEngine/Core/Autoloader.php';

ExpressionEngine\Core\Autoloader::getInstance()
    ->addPrefix('ExpressionEngine', SYSPATH . 'ee/ExpressionEngine')
    ->addPrefix('ExpressionEngine\Addons', SYSPATH . 'ee/ExpressionEngine/Addons')
    ->register();

// Define constants we need
defined('FILE_READ_MODE') || define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') || define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE') || define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE') || define('DIR_WRITE_MODE', 0777);
defined('PATH_CACHE') || define('PATH_CACHE', SYSPATH . 'user/cache/');

$cli = new ExpressionEngine\Cli\Cli();

$cli->process();

// This will be all we do, so we'll die here.
// However, the CLI service should handle the completion, this is just a fallback
die();
