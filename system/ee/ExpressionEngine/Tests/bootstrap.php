<?php

// Report all errors
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

$project_base = realpath(dirname(__FILE__) . '/../../../') . '/';

// Path constants
define('SYSPATH', $project_base);
define('BASEPATH', SYSPATH . 'ee/legacy/');
define('PATH_CACHE', SYSPATH . 'user/cache/');
define('APPPATH', BASEPATH);
define('APP_VER', '6.2.1');
define('PATH_THEMES', realpath(SYSPATH . '/../themes') . '/');
define('DOC_URL', 'http://our.doc.url/');

// application constants
define('AMP', '&amp;');
define('SELF', 'index.php');
define('EESELF', 'index.php');
define('LD', '{');
define('RD', '}');

$constants = require '../Config/constants.php';

foreach ($constants as $name => $val) {
    define($name, $val);
}

// Minor CI annoyance
function log_message()
{
}

// add the composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

function lang($str)
{
    return $str;
}

require_once 'eeObjectMock.php';
