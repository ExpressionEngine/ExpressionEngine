<?php

// Report all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

$project_base = realpath(dirname(__FILE__) . '/../../../') . '/';

// Path constants
define('SYSPATH', $project_base);
define('BASEPATH', SYSPATH . 'ee/legacy/');
define('PATH_CACHE', SYSPATH . 'user/cache/');
define('APPPATH', BASEPATH);
define('APP_VER', '7.6.0');
define('PATH_THEMES', realpath(SYSPATH . '/../themes') . '/');
define('DOC_URL', 'http://our.doc.url/');
define('PATH_THIRD', SYSPATH . 'user/addons/');
define('PATH_ADDONS', SYSPATH . 'ee/ExpressionEngine/Addons/');

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
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists($project_base . '../vendor/autoload.php')) {
    require_once $project_base . '../vendor/autoload.php';
} else {
    require_once realpath(dirname(__FILE__) . '/../../../../') . '/vendor/autoload.php';
}
require_once SYSPATH . 'ee/vendor-build/autoload.php';

// Load Hamcrest functions
require_once __DIR__ . '/vendor/hamcrest/hamcrest-php/hamcrest/Hamcrest.php';

function lang($str)
{
    return $str;
}

require_once 'eeObjectMock.php';

// Helper functions for testing - only define if not already loaded by EE
// Note: remove_invisible_characters is defined in boot.common.php when EE is fully loaded
