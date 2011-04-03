<?php

class Parser_test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->parser = load_class('Parser', 'libraries');
	}
	
	// --------------------------------------------------------------------
	
	public function testSetDelimiters()
	{
		// Make sure default delimiters are there
		$this->assertEquals('{', $this->parser->l_delim);
		$this->assertEquals('}', $this->parser->r_delim);
		
		// Change them to square brackets
		$this->parser->set_delimiters('[', ']');
		
		// Make sure they changed
		$this->assertEquals('[', $this->parser->l_delim);
		$this->assertEquals(']', $this->parser->r_delim);
		
		// Reset them
		$this->parser->set_delimiters();
		
		// Make sure default delimiters are there
		$this->assertEquals('{', $this->parser->l_delim);
		$this->assertEquals('}', $this->parser->r_delim);
	}
	
	// --------------------------------------------------------------------
	
	public function testParseSimpleString()
	{
		$data = array(
			'title' => 'Page Title',
			'body' => 'Lorem ipsum dolor sit amet.'
		);
		
		$template = "{title}\n{body}";
		
		$result = implode("\n", $data);
		
		$this->assertEquals($result, $this->parser->parse_string($template, $data, TRUE));
	}
	
	// --------------------------------------------------------------------

	
}