<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Tests\ExpressionEngine\Service\Formatter;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Formatter\Formats\Text;

class TextFormatterTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->lang = m::mock('EE_Lang');
		$this->sess = m::mock('EE_Session');
	}

	public function testAccentsToAscii()
	{
		$this->lang->shouldReceive('load')->once();

		// minimal map
		$config['foreign_chars'] = [
			'223'	=>	"ss", // ß
			'224'	=>  "a",
			'225'	=>  "a",
			'226'	=>	"a",
			'229'	=>	"a",
			'227'	=>	"ae", // ã
			'228'	=>	"ae", // ä
			'230'	=>	"ae", // æ
			'231'	=>	"c",
			'232'	=>	"e",  // è
			'233'	=>	"e",  // é
			'234'	=>	"e",  // ê
			'235'	=>	"e",  // ë
		];

		$text = (string) $this->format('ßaeiouãêëæ ærstlnãêëß', $config)->accentsToAscii();
		$this->assertEquals('ssaeiouaeeeae aerstlnaeeess', $text);
	}

	/**
	 * @dataProvider attributeEscapeProvider
	 */
	public function testAttributeEscape($content, $expected)
	{
		$this->lang->shouldReceive('load')->once();
		$text = (string) $this->format($content)->attributeEscape();
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

	public function format($content, $config = [])
	{
		return new Text($content, $this->lang, $this->sess, $config, 0b00000001);
	}
}

// EOF
