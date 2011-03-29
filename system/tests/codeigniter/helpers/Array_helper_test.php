<?php

require_once(BASEPATH.'helpers/array_helper.php');

class Array_helper_test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->my_array = array(
			'foo'		=> 'bar',
			'sally'		=> 'jim',
			'maggie'	=> 'bessie',
			'herb'		=> 'cook'
		);
	}
	
	// ------------------------------------------------------------------------
	
	public function testElementWithExistingItem()
	{	
		$this->assertEquals(FALSE, element('testing', $this->my_array));
		
		$this->assertEquals('not set', element('testing', $this->my_array, 'not set'));
		
		$this->assertEquals('bar', element('foo', $this->my_array));
	}
	
	// ------------------------------------------------------------------------	
}