<?php
$project_base = realpath(dirname(__FILE__).'/../../../../system/').'/';

// fake SERVER vars for CLI context
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Path constants
define('SYSPATH', $project_base);
defined('SYSDIR') || define('SYSDIR', basename($project_base));
define('DEBUG', 1);
define('FIXTURE', TRUE);
define('SELF', 'index.php');
define('EESELF', 'index.php');
define('FCPATH', __DIR__.'/');
defined('REQ') ||define('REQ', 'CLI');

define('BOOT_ONLY', TRUE);
include_once SYSPATH.'ee/ExpressionEngine/Boot/boot.php';

ee()->load->library('core');
ee()->load->library('session');
ee()->load->library('functions');
