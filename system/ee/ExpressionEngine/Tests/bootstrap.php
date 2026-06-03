<?php

// Keep strict runtime checks without letting vendor deprecations break
// isolated-process result serialization on newer PHP versions.
$showDeprecations = getenv('EE_SHOW_DEPRECATIONS') === '1';
error_reporting($showDeprecations ? E_ALL : (E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED));
ini_set('display_errors', 1);

$project_base = realpath(dirname(__FILE__) . '/../../../') . '/';

if ($showDeprecations) {
    $vendorDeprecationPath = str_replace('\\', '/', realpath(__DIR__ . '/vendor')) . '/';
    set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($vendorDeprecationPath) {
        if (($errno & (E_DEPRECATED | E_USER_DEPRECATED)) === 0) {
            return false;
        }

        $normalizedPath = str_replace('\\', '/', (string) $errfile);
        if ($vendorDeprecationPath !== '/' && strpos($normalizedPath, $vendorDeprecationPath) === 0) {
            return true;
        }

        $stream = defined('STDERR') ? STDERR : fopen('php://stderr', 'wb');
        fwrite($stream, "Deprecated: {$errstr} in {$errfile} on line {$errline}\n");
        if (!defined('STDERR') && is_resource($stream)) {
            fclose($stream);
        }

        return true;
    });
}

// Path constants
define('SYSPATH', $project_base);
define('BASEPATH', SYSPATH . 'ee/legacy/');
define('PATH_CACHE', SYSPATH . 'user/cache/');
define('APPPATH', BASEPATH);
define('APP_VER', '7.5.24');

define('PATH_THEMES', realpath(SYSPATH . '/../themes') . '/');
define('DOC_URL', 'http://our.doc.url/');
define('PATH_THIRD', SYSPATH . 'user/addons/');
define('PATH_ADDONS', SYSPATH . 'ee/ExpressionEngine/Addons/');
define('PATH_PRO_ADDONS', PATH_ADDONS);
define('PATH_MOD', PATH_ADDONS);

// application constants
define('AMP', '&amp;');
define('SELF', 'index.php');
define('EESELF', 'index.php');
define('LD', '{');
define('RD', '}');

$constants = require __DIR__ . '/../Config/constants.php';

foreach ($constants as $name => $val) {
    define($name, $val);
}

// Minor CI annoyance
function log_message()
{
}

// add the composer autoloader (prefer local, fallback to repo root)
$composerAutoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($composerAutoloadPath)) {
    $composerAutoloadPath = $project_base . '../vendor/autoload.php';
}

if (!file_exists($composerAutoloadPath)) {
    $composerAutoloadPath = realpath(dirname(__FILE__) . '/../../../../') . '/vendor/autoload.php';
}

$composerAutoloader = require_once $composerAutoloadPath;
$composerAutoloaders = [];

if ($composerAutoloader instanceof \Composer\Autoload\ClassLoader) {
    $composerAutoloaders = [$composerAutoloader];
}

if ($composerAutoloaders === [] && class_exists(\Composer\Autoload\ClassLoader::class)) {
    $composerAutoloaders = \Composer\Autoload\ClassLoader::getRegisteredLoaders();
}

foreach ($composerAutoloaders as $composerAutoloader) {
    $composerAutoloader->addPsr4(
        'ExpressionEngine\\Updater\\',
        SYSPATH . 'ee/installer/updater/ExpressionEngine/Updater/'
    );
}
require_once SYSPATH . 'ee/vendor-build/autoload.php';

// Load Hamcrest functions
require_once __DIR__ . '/vendor/hamcrest/hamcrest-php/hamcrest/Hamcrest.php';

function lang($str)
{
    return $str;
}

require_once 'eeObjectMock.php';
require_once 'TestReflectionHelper.php';

// Helper functions for testing - only define if not already loaded by EE
// Note: remove_invisible_characters is defined in boot.common.php when EE is fully loaded
