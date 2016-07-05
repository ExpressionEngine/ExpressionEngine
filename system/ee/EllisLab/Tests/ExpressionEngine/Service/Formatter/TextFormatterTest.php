<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Formatter;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Formatter\FormatterFactory;

class TextFormatterTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->lang = m::mock('EE_Lang');
		$this->factory = new FormatterFactory($this->lang);
	}

	/**
	 * @dataProvider attributeEscapeProvider
	 */
	public function testAttributeEscape($content, $expected)
	{
		$this->lang->shouldReceive('load')->once();
		$text = (string) $this->factory->make('Text', $content)->attributeEscape();
		$this->assertEquals($expected, $text);
	}

	public function attributeEscapeProvider()
	{
		return array(
			array('<script>alert("hi");</script>', '&lt;script&gt;alert(&quot;hi&quot;);&lt;/script&gt;'),
			array('&"\'<>', '&amp;&quot;&#039;&lt;&gt;'),

			// these should be left alone, would be converted only by htmlentities()
			array('©$*@¢£', '©$*@¢£')
		);
	}

	public function tearDown()
	{
		$this->factory = NULL;
	}
}

// EOF
