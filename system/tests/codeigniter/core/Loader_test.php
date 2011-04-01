<?php

class Loader_test extends PHPUnit_Framework_TestCase
{
	static $cls;
	protected $_loader;
	
	public static function setUpBeforeClass()
	{
		$CI = get_instance();
		self::$cls = get_class($CI->load);
	}

	// --------------------------------------------------------------------
	
	public function setUp()
	{
		$cls = self::$cls;
		$this->_loader = new $cls;
	}

	// --------------------------------------------------------------------
	
	public function testLibrary()
	{
		// Test loading as an array.
		$this->assertEquals(NULL, $this->_loader->library(array('table')));
		
		// Test no lib given
		$this->assertEquals(FALSE, $this->_loader->library());
		
		// Test a string given to params
		$this->assertEquals(NULL, $this->_loader->library('table', ' '));
	}	
	
	// --------------------------------------------------------------------
	
	public function testModels()
	{
		// Test loading as an array.
		$this->assertEquals(NULL, $this->_loader->model(array('foobar')));
		
		// Test no model given
		$this->assertEquals(FALSE, $this->_loader->model(''));
		
		// Test a string given to params
		$this->assertEquals(NULL, $this->_loader->model('foobar', ' '));		
	}

	// --------------------------------------------------------------------
	
	public function testDatabase()
	{
		$this->assertEquals(NULL, $this->_loader->database());
		$this->assertEquals(NULL, $this->_loader->dbutil());		
	}

	// --------------------------------------------------------------------
	
	public function testView()
	{
		// I'm not entirely sure this is the proper way to handle this.
		// So, let's revist it, m'kay?
		try 
		{
			 $this->_loader->view('foo');
		}
		catch (Exception $expected)
		{
			return;
		}
	}

	// --------------------------------------------------------------------

	public function testFile()
	{
		// I'm not entirely sure this is the proper way to handle this.
		// So, let's revist it, m'kay?
		try 
		{
			 $this->_loader->file('foo');
		}
		catch (Exception $expected)
		{
			return;
		}		
	}

	// --------------------------------------------------------------------
	
	public function testVars()
	{
		$vars = array(
			'foo'	=> 'bar'
		);
		
		$this->assertEquals(NULL, $this->_loader->vars($vars));
		$this->assertEquals(NULL, $this->_loader->vars('foo', 'bar'));
	}

	// --------------------------------------------------------------------
	
	public function testHelper()
	{
		$this->assertEquals(NULL, $this->_loader->helper('array'));
		$this->assertEquals(NULL, $this->_loader->helper('bad'));
	}
	
	// --------------------------------------------------------------------

	public function testHelpers()
	{
		$this->assertEquals(NULL, $this->_loader->helpers(array('file', 'array', 'string')));
	}
	
	// --------------------------------------------------------------------
	
	// public function testLanguage()
	// {
	// 	$this->assertEquals(NULL, $this->_loader->language('test'));
	// }	

	// --------------------------------------------------------------------

	public function testLoadConfig()
	{
		$this->assertEquals(NULL, $this->_loader->config('config', FALSE, TRUE));
	}
	
	
}