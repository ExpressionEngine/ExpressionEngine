<?php

// Report all errors
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

$project_base = realpath(dirname(__FILE__).'/../../../').'/';

// Path constants
define('SYSPATH', $project_base);
define('BASEPATH', SYSPATH.'ee/legacy/');
define('PATH_CACHE', SYSPATH.'user/cache/');
define('APPPATH',  BASEPATH);
define('APP_VER',  '4.0.0');
define('PATH_THEMES', realpath(SYSPATH.'/../themes').'/');
define('DOC_URL', 'http://our.doc.url/');

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

// Below allows both ee()-> singleton mocks to allow unit testing of methods that rely on it
// as well as ee('Foo') dependency container objects.
//
// Singleton:
// load/config/etc can be stub classes with stub methods
//
// App container:
// In your test, you must define the return value for requested object:
//
// 		ee()->setMock('Encrypt', new Encrypt\Encrypt('ADefaultKey'));
//
// Then any calls from the application to ee('Encrypt') will return the object / return value you specified.
function ee($mock = '')
{
	return new eeSingletonMock($mock);
}

class eeSingletonMock {
	public $load;
	public $config;

	protected $mock;
	protected static $mocks = [];

	public function __construct($mock = '')
	{
		$this->load = new eeSingletonLoadMock;
		$this->config = new eeSingletonConfigMock;
		$this->mock = $mock;
	}

	public function setMock($name, $return)
	{
		self::$mocks[$name] = $return;
	}

	public function __call($name, $args)
	{
		if (array_key_exists($this->mock, self::$mocks) && method_exists(self::$mocks[$this->mock], $name))
		{
			return call_user_func_array([self::$mocks[$this->mock], $name], $args);
		}
	}
}

class eeSingletonLoadMock {
	public function helper()
	{
		return;
	}
}

class eeSingletonConfigMock {
	public function item($name, $value = NULL)
	{
		return ($value) ?: $name;
	}
}
