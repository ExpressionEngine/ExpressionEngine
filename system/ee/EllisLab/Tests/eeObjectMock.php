<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

// This allows both ee()-> singleton mocks to allow unit testing of methods that rely on it
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
// In the test's tearDown() method, it should then reset the mocks so the next test does not inherit your mocks
//
// 		ee()->resetMocks();
//
function ee($mock = '')
{
	return new eeSingletonMock($mock);
}

class eeSingletonMock {
	public $load;
	public $config;
	public $session;

	protected $mock;
	protected static $mocks = [];

	public function __construct($mock = '')
	{
		$this->load = new eeSingletonLoadMock;
		$this->config = new eeSingletonConfigMock;
		$this->session = new eeSingletoneSessionMock;
		$this->mock = $mock;
	}

	public function setMock($name, $return)
	{
		self::$mocks[$name] = $return;
	}

	public function resetMocks()
	{
		self::$mocks = [];
		$this->mock = '';
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
	protected static $config = [];

	public function item($item, $index = '', $raw_value = FALSE)
	{
		return (isset(self::$config[$item])) ? self::$config[$item] : FALSE;
	}

	public function setItem($item, $value)
	{
		self::$config[$item] = $value;
	}

	public function resetConfig()
	{
		self::$config = [];
	}
}

class eeSingletoneSessionMock {
	public static $userdata = [];

	public function userdata($item, $default = FALSE)
	{
		return ( ! isset(self::$userdata[$item])) ? $default : self::$userdata[$item];
	}

	public function setUserdata($item, $value)
	{
		self::$userdata[$item] = $value;
	}

	public function resetUserdata()
	{
		self::$userdata = [];
	}
}
