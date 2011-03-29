<?php

error_reporting(E_ALL);

define('PROJECT_BASE', realpath(dirname(__FILE__) . '/../../') . '/');
define('BASEPATH', PROJECT_BASE.'codeigniter/system/');
define('APPPATH', PROJECT_BASE.'codeigniter/application/');
define('EXT', '.php');

require(APPPATH.'config/constants.php');
require(PROJECT_BASE.'tests/codeigniter/Common.php');


$CFG =& load_class('Config', 'core');

require BASEPATH.'core/Controller.php';

function &get_instance()
{
	return CI_Controller::get_instance();
}

class Test_controller extends CI_Controller
{
	public function index()
	{
		return 'hi';
	}
}

$CI = new Test_controller();


// require_once BASEPATH.'core/CodeIgniter'.EXT;