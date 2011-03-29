<?php

class Table_test extends PHPUnit_Framework_TestCase
{
	static $cls;
	protected $table;
	
	public static function setUpBeforeClass()
	{
		$CI = get_instance();
		$CI->load->library('table');
		self::$cls = get_class($CI->table);
	}
	
	public function setUp()
	{
		$cls = self::$cls;
		$this->table = new $cls;
	}
	
	public function testSetHeader()
	{
		$this->table->set_heading(
			'test',
			'test2',
			'test3'
		);
		
		$this->assertEquals(
			array(
				array('data' => 'test'),
				array('data' => 'test2'),
				array('data' => 'test3')
			),
			$this->table->heading
		);
	}
}