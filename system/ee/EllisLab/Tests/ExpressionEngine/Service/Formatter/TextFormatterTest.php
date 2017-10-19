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

		$this->lang->shouldReceive('load');
	}

	public function testAccentsToAscii()
	{
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
		$text = (string) $this->format($content)->attributeEscape();
		$this->assertEquals($expected, $text);
	}

	public function attributeEscapeProvider()
	{
		return [
			['<script>alert("hi");</script>', '&lt;script&gt;alert(&quot;hi&quot;);&lt;/script&gt;'],
			['&"\'<>', '&amp;&quot;&#039;&lt;&gt;'],

			// these should be left alone, would be converted only by htmlentities()
			['©$*@¢£', '©$*@¢£'],
		];
	}

	/**
	 * @dataProvider attributeSafeProvider
	 */
	public function testAttributeSafe($content, $params, $expected)
	{
		$text = (string) $this->format($content)->attributeSafe($params);
		$this->assertEquals($expected, $text);
	}

	public function attributeSafeProvider()
	{
		$sample = 'Some text with "double quotes", <samp>tags</samp>, and some &#8220;typographical quotes&#8221; and &quot;quote entities&quot; that is a bit long';
		return [
			['<script>alert("hi");</script>', [], 'alert(&quot;hi&quot;);'],
			['&"\'<>', [], '&amp;&quot;&#039;'],
			[
				$sample,
				[
					'limit' => 20,
				],
				'Some text with…'
			],
			[
				$sample,
				[
					'limit' => 10,
					'end_char' => 'TEST'
				],
				'SomeTEST'
			],
			[
				$sample,
				[
					'double_encode' => TRUE,
				],
				'Some text with &quot;double quotes&quot;, tags, and some “typographical quotes” and &amp;quot;quote entities&amp;quot; that is a bit long'
			],
			[
				$sample,
				[
					'unicode_punctuation' => FALSE,
				],
				'Some text with &quot;double quotes&quot;, tags, and some &#8220;typographical quotes&#8221; and &quot;quote entities&quot; that is a bit long'
			],
			// these should be left alone, would be converted only by htmlentities()
			['©$*@¢£', [], '©$*@¢£'],
		];
	}

	public function testCensor()
	{
		$this->sess->shouldReceive('cache')->andReturn(FALSE);
		$this->sess->shouldReceive('set_cache');

		$config['censored_words'] = "bleeping\nblarping";

		$text = (string) $this->format('This is a bLeEPing test!', $config)->censor();
		$this->assertEquals('This is a ######## test!', $text);

		$config['censor_replacement'] = 'NOT-IN-MY-HOUSE';

		$text = (string) $this->format('This is a bLeEPing test!', $config)->censor();
		$this->assertEquals('This is a NOT-IN-MY-HOUSE test!', $text);
	}

	/**
	 * @dataProvider convertToEntitiesProvider
	 */
	public function testConvertToEntities($content, $expected)
	{
		$text = (string) $this->format($content)->convertToEntities();
		$this->assertEquals($expected, $text);
	}

	public function convertToEntitiesProvider()
	{
		return [
			['<script>alert("hi");</script>', '&lt;script&gt;alert(&quot;hi&quot;);&lt;/script&gt;'],
			['&"\'<>', '&amp;&quot;&#039;&lt;&gt;'],
			['©$*@¢£', '&copy;$*@&cent;&pound;'],
		];
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
