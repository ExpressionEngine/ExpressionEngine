<?php

class Loader_test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->CI =& get_instance();
		$this->base_classes = array();
	}

	public function testLoadLibrary()
	{
		$this->assertNull($this->CI->load->library('table'));           
	}

	public function testLoadNonExistentLibrary()
	{
		$this->markTestSkipped('Sorry, not sure how to test for failure now');
		// $this->assertTrue($this->CI->load->library('i_dont_exist'));
	}
}