<?php

class Table_test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->EE =& get_instance();
	}
	
	
	public function testSetHeader()
	{
		$this->EE->load->library('table');
		$this->EE->table->set_heading(
			'test',
			'test2',
			'test3'
		);
		
		print_r($this->EE->table->heading);
		
		
	}
	
	
	
}

