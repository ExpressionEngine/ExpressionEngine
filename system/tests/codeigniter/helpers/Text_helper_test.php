<?php

require_once(BASEPATH.'helpers/text_helper.php');

class Text_helper_test extends PHPUnit_Framework_TestCase
{
	private $_long_string;
	
	public function setUp()
	{
		$this->_long_string = 'Once upon a time, a framework had no tests.  It sad.  So some nice people began to write tests.  The more time that went on, the happier it became.  Everyone was happy.';
	}
	
	// ------------------------------------------------------------------------
	
	public function testWordLimiter()
	{
		$this->assertEquals('Once upon a time,&#8230;', word_limiter($this->_long_string, 4));
		$this->assertEquals('Once upon a time,&hellip;', word_limiter($this->_long_string, 4, '&hellip;'));
	}

	// ------------------------------------------------------------------------	
}