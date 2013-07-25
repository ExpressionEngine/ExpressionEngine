<?php

require_once APPPATH.'libraries/channel_entries_parser/Components.php';
require_once APPPATH.'libraries/channel_entries_parser/components/Switch.php';

class Channel_entries_switch_parser_test extends CI_TestCase 
{
	public function set_up()
	{
		$this->parser_component = new EE_Channel_switch_parser();

		$this->_func_stub = $this->getMock('EE_Functions', array('assign_parameters'));
		$this->_preparser_stub = $this->getMock('EE_Channel_preparser', array('has_tag'));
		$this->_parser_stub = $this->getMock('EE_Channel_data_parser', array('count', 'tag', 'prefix'));

		$this->ci_instance_var('functions', $this->_func_stub);
	}

	public function test_disabled()
	{
		$this->_preparser_stub
			->expects($this->any())
			->method('has_tag')
			->with($this->equalTo('switch'))
			->will($this->onConsecutiveCalls(TRUE, FALSE));

		$this->assertFalse(
			$this->parser_component->disabled(array(), $this->_preparser_stub)
		);

		$this->assertTrue(
			$this->parser_component->disabled(array(), $this->_preparser_stub)
		);
	}

	public function test_replace_skip()
	{
		$this->_parser_stub
			->expects($this->never())
			->method('count');
		$this->_parser_stub
			->expects($this->once())
			->method('tag')
			->will($this->returnValue('definitelynotswitch'));
		$this->_parser_stub
			->expects($this->once())
			->method('prefix')
			->will($this->returnValue(''));

		$this->parser_component->replace('tagdata', $this->_parser_stub, NULL);
	}

	public function test_replace()
	{
		$tag = 'switch="one|two|three"';

		$this->_func_stub
			->expects($this->any())
			->method('assign_parameters')
			->will($this->returnValue(array('switch' => 'one|two|three')));

		$this->_parser_stub
			->expects($this->any())
			->method('tag')
			->will($this->returnValue($tag));
		$this->_parser_stub
			->expects($this->any())
			->method('prefix')
			->will($this->returnValue(''));

		$this->_parser_stub
			->expects($this->any())
			->method('count')
			->will($this->onConsecutiveCalls(0, 1, 2, 3));

		$this->assertEquals(
			'one',
			$this->parser_component->replace('{'.$tag.'}', $this->_parser_stub, NULL)
		);

		$this->assertEquals(
			'two',
			$this->parser_component->replace('{'.$tag.'}', $this->_parser_stub, NULL)
		);

		$this->assertEquals(
			'three',
			$this->parser_component->replace('{'.$tag.'}', $this->_parser_stub, NULL)
		);

		// wrap around
		$this->assertEquals(
			'one',
			$this->parser_component->replace('{'.$tag.'}', $this->_parser_stub, NULL)
		);
	}

	public function test_replace_prefixed()
	{
		$tag = 'foo:bar:switch="one|two|three"';

		$this->_func_stub
			->expects($this->any())
			->method('assign_parameters')
			->will($this->returnValue(array('foo:bar:switch' => 'one|two|three')));

		$this->_parser_stub
			->expects($this->any())
			->method('tag')
			->will($this->returnValue($tag));
		$this->_parser_stub
			->expects($this->any())
			->method('prefix')
			->will($this->returnValue('foo:bar:'));

		$this->_parser_stub
			->expects($this->any())
			->method('count')
			->will($this->returnValue(3));

		$this->assertEquals(
			'one',
			$this->parser_component->replace('{'.$tag.'}', $this->_parser_stub, NULL)
		);
	}
}