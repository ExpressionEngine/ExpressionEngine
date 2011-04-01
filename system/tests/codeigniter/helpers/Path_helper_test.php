<?php

require_once(BASEPATH.'helpers/path_helper.php');

class Path_helper_test extends PHPUnit_Framework_TestCase
{
	public function testSubmitUrl()
	{
		set_realpath('http://example.com');
		
		$this->assertEquals(realpath(BASEPATH . '../../../') . '/', set_realpath('./'));
		
	$this->assertEquals('/this/does/not/exist/', set_realpath('/this/does/not/exist/', TRUE));
		
		// try 
		// {
		// 	
		// }
		// catch (Exception $expected)
		// {
		// 	
		// }
	}


}