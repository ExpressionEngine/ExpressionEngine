<?php

// Report all errors
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

$project_base = realpath(dirname(__FILE__).'/../../../').'/';

// Path constants
define('SYSPATH', $project_base);
define('BASEPATH', SYSPATH.'ee/legacy/');
define('APPPATH',  BASEPATH);

// application constants
define('AMP', '&amp;');
define('SELF', 'index.php');
define('LD', '{');
define('RD', '}');

require '../ExpressionEngine/Config/constants.php';

// Minor CI annoyance
function log_message() {}

// Add hamcrest matchers
require_once __DIR__ . '/vendor/hamcrest/hamcrest-php/hamcrest/Hamcrest.php';

// add the composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

function lang($str)
{
	return $str;
}

// Added for Typography tests (eew)

require_once APPPATH.'helpers/string_helper.php';
require_once APPPATH.'helpers/security_helper.php';
require_once APPPATH.'libraries/Functions.php';
require_once APPPATH.'libraries/Typography.php';
require_once APPPATH.'libraries/typography/Markdown/Michelf/MarkdownExtra.inc.php';

class ConfigStub {
	public function item($str = '')
	{
		return FALSE;
	}
}

class LoadStub {
	public function model($str = '')
	{
		return;
	}

	public function helper($str = '')
	{
		return;
	}
}

class AddonsModelStub {
	public function get_plugin_formatting()
	{
		return array();
	}
}

class ExtensionsStub {
	public function active_hook($str)
	{
		return FALSE;
	}
}

class TypographyStub extends EE_Typography
{
	public function __construct()
	{
		// Skipping initialize and autoloader
	}
}

function ee($str = '')
{
	if ($str)
	{
		require_once SYSPATH.'ee/EllisLab/ExpressionEngine/Library/Security/XSS.php';
		return new EllisLab\ExpressionEngine\Library\Security\XSS();
	}

	$obj = new StdClass();
	$obj->config = new ConfigStub();
	$obj->load = new LoadStub();
	$obj->addons_model = new AddonsModelStub();
	$obj->functions = new EE_Functions();
	$obj->extensions = new ExtensionsStub();

	return $obj;
}
