<?php

class Lang_test extends PHPUnit_Framework_TestCase
{
	protected $lang;
	
	public function setUp()
	{
		$this->lang = load_class('Lang', 'core');
	}
	
	// --------------------------------------------------------------------
	
	public function testLoad()
	{
		$this->assertTrue($this->lang->load('profiler'));
	}
	
	// --------------------------------------------------------------------

	public function testLine()
	{
		$this->assertEquals('URI STRING', $this->lang->line('profiler_uri_string'));
	}
	
}