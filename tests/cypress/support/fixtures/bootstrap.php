<?php
$project_base = realpath(dirname(__FILE__).'/../../../../system/').'/';

// fake SERVER vars for CLI context
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Path constants
define('SYSPATH', $project_base);
define('DEBUG', 1);
define('FIXTURE', TRUE);
define('FCPATH', __DIR__.'/');

define('BOOT_ONLY', TRUE);
include_once SYSPATH.'ee/EllisLab/ExpressionEngine/Boot/boot.php';

ee()->load->library('session');
ee()->load->library('functions');
