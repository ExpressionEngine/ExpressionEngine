<?php

class Config_test extends PHPUnit_Framework_TestCase
{
	static $cls;
	protected $_loader;
	
	public static function setUpBeforeClass()
	{
		$CI = get_instance();
		self::$cls = get_class($CI->config);
	}

	// --------------------------------------------------------------------
	
	public function setUp()
	{
		$cls = self::$cls;
		$this->config = new $cls;
	}
	
	// --------------------------------------------------------------------

	public function testItem()
	{
		$this->assertEquals('http://example.com/', $this->config->item('base_url'));
		
		// Bad Config value
		$this->assertEquals(FALSE, $this->config->item('no_good_item'));
		
		// Index
		$this->assertEquals(FALSE, $this->config->item('no_good_item', 'bad_index'));
		$this->assertEquals(FALSE, $this->config->item('no_good_item', 'default'));
	}

	// --------------------------------------------------------------------
	
	public function testSlashItem()
	{
		// Bad Config value
		$this->assertEquals(FALSE, $this->config->slash_item('no_good_item'));
		
		$this->assertEquals('http://example.com/', $this->config->slash_item('base_url'));

		$this->assertEquals('MY_/', $this->config->slash_item('subclass_prefix'));
	}

	// --------------------------------------------------------------------

	public function testSiteUrl()
	{
		$this->assertEquals('http://example.com/index.php', $this->config->site_url());
		
		$base_url = $this->config->item('base_url');
		
		$this->config->set_item('base_url', '');
		
		$q_string = $this->config->item('enable_query_strings');
		
		$this->config->set_item('enable_query_strings', FALSE);
		
		$this->assertEquals('index.php/test', $this->config->site_url('test'));
		$this->assertEquals('index.php/test/1', $this->config->site_url(array('test', '1')));
		
		$this->config->set_item('enable_query_strings', TRUE);

		$this->assertEquals('index.php?test', $this->config->site_url('test'));
		$this->assertEquals('index.php?0=test&1=1', $this->config->site_url(array('test', '1')));
		
		$this->config->set_item('base_url', $base_url);

		$this->assertEquals('http://example.com/index.php?test', $this->config->site_url('test'));
		
		// back to home base
		$this->config->set_item('enable_query_strings', $q_string);				
	}

	// --------------------------------------------------------------------
	
	public function testSystemUrl()
	{
		$this->assertEquals('http://example.com/system/', $this->config->system_url());
	}

}