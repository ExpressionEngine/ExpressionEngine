<?php

// Report all errors
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

$project_base = realpath(dirname(__FILE__).'/../../').'/';

// Path constants
define('BASEPATH', $project_base.'codeigniter/system/');
define('APPPATH',  $project_base.'expressionengine/');

define('LD', '{');
define('RD', '}');

// Minor CI annoyance
function log_message() {}
function get_instance() { return new stdClass(); }
function ee() { return new stdClass(); }