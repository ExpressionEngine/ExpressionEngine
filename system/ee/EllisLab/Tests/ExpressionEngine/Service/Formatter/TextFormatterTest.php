<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Service\Formatter;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Formatter\Formats\Text;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../../../EllisLab/ExpressionEngine/Boot/boot.common.php';

class TextFormatterTest extends TestCase {

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

	public function testDecrypt()
	{
		$this->markTestSkipped('This is a gateway to the Encrypt service, cannot unit test in this context');
	}

	/**
	 * @dataProvider emojiShorthandProvider
	 */
	public function testEmojiShorthand($content, $expected)
	{
		$config['emoji_map'] = include SYSPATH.'ee/EllisLab/ExpressionEngine/Config/emoji.php';
		$text = (string) $this->format($content, $config)->emojiShorthand();
		$this->assertEquals($expected, $text);
	}

	public function emojiShorthandProvider()
	{
		return [
			['Flying a :rocket: to Mars with my :moneybag: and Elon Musk', 'Flying a &#x1F680; to Mars with my &#x1F4B0; and Elon Musk'],
			['Emoji aliases like :moon: and :waxing_gibbous_moon: work', 'Emoji aliases like &#x1F314; and &#x1F314; work'],
			['Handle multi-character emoji like :man-woman-girl-boy:', 'Handle multi-character emoji like &#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F466;'],
			['Emoji 5 shortcodes like :hedgehog: and :merman: are supported', 'Emoji 5 shortcodes like &#x1F994; and &#x1F9DC;&#x200D;&#x2642;&#xFE0F; are supported'],
			['Emoji 11 (there is no 6) shortcodes like :supervillain: and :lobster: are not yet supported', 'Emoji 11 (there is no 6) shortcodes like :supervillain: and :lobster: are not yet supported'],
			[
// larger sample with multi-line code samples
'Unlike :lock:, emoji in [code]code samples :lock:[/code] should be left alone.

:rabbit: starts as sentence. :+1:

[code=markdown]
Another code block with a :rabbit::hole:.

We don\'t want this parsed.
[/code]

And if you made it to this :hole: you did pretty good.',
// expected rendering, [code] blocks are ignored
'Unlike &#x1F512;, emoji in [code]code samples :lock:[/code] should be left alone.

&#x1F430; starts as sentence. &#x1F44D;

[code=markdown]
Another code block with a :rabbit::hole:.

We don\'t want this parsed.
[/code]

And if you made it to this &#x1F573;&#xFE0F; you did pretty good.']
		];
	}

	public function testEncodeEETags()
	{
		$sample = "
{some_variable}
{exp:query sql='SELECT * FROM exp_members'}{email}{/exp:query}
{embed='foo/bar'}
{path:foo}
{redirect='404'}
{if some_conditional}content{/if}
{layout:variable}
{layout:set name='foo'}bar{/layout:set}";

		$text = (string) $this->format($sample)->encodeEETags();
		$this->assertEquals("
&#123;some_variable&#125;
&#123;exp:query sql='SELECT * FROM exp_members'&#125;&#123;email&#125;&#123;/exp:query&#125;
&#123;embed='foo/bar'&#125;
&#123;path:foo&#125;
&#123;redirect='404'&#125;
&#123;if some_conditional&#125;content&#123;/if&#125;
&#123;layout:variable&#125;
&#123;layout:set name='foo'&#125;bar&#123;/layout:set&#125;", $text);

		$text = (string) $this->format($sample)->encodeEETags(['encode_vars' => FALSE]);

		$this->assertEquals("
{some_variable}
&#123;exp:query sql='SELECT * FROM exp_members'&#125;{email}&#123;/exp:query&#125;
&#123;embed='foo/bar'&#125;
&#123;path:foo&#125;
&#123;redirect='404'&#125;
&#123;if some_conditional}content&#123;/if}
&#123;layout:variable&#125;
&#123;layout:set name='foo'&#125;bar&#123;/layout:set&#125;", $text);
	}

	public function testEncrypt()
	{
		$this->markTestSkipped('This is a gateway to the Encrypt service, cannot unit test in this context');
	}

	/**
	 * @dataProvider formPrepProvider
	 */
	public function testFormPrep($content, $expected)
	{
		if ( ! defined('REQ'))
		{
			define('REQ', 'PAGE');
		}

		require_once __DIR__.'/../../../../../legacy/helpers/form_helper.php';

		$text = (string) $this->format($content)->formPrep();
		$this->assertEquals($expected, $text);
	}

	public function formPrepProvider()
	{
		return [
			['<script>alert("hi");</script>', '&lt;script&gt;alert(&quot;hi&quot;);&lt;/script&gt;'],
			['&"\'<>', '&amp;&quot;&#039;&lt;&gt;'],
			// form_prep tracks prepped strings so this should *not* double-encode things
			['&amp;&quot;&#039;&lt;&gt;', '&amp;&quot;&#039;&lt;&gt;'],
			// and this should be left alone
			['©$*@¢£', '©$*@¢£'],
		];
	}

	public function testJson()
	{
		$sample = '"Hello"	<b>World</b>		&quot;period&quot;.
';
		$text = (string) $this->format($sample)->json();
		$this->assertEquals('"&quot;Hello&quot;\t&lt;b&gt;World&lt;\/b&gt;\t\t&amp;quot;period&amp;quot;.\n"', $text);

		$text = (string) $this->format($sample)->json(['double_encode' => FALSE]);
		$this->assertEquals('"&quot;Hello&quot;\t&lt;b&gt;World&lt;\/b&gt;\t\t&quot;period&quot;.\n"', $text);

		$text = (string) $this->format($sample)->json(['enclose_with_quotes' => FALSE]);
		$this->assertEquals('&quot;Hello&quot;\t&lt;b&gt;World&lt;\/b&gt;\t\t&amp;quot;period&amp;quot;.\n', $text);

		$text = (string) $this->format($sample)->json(['options' => 'JSON_HEX_AMP|JSON_HEX_TAG']);
		$this->assertEquals('"\u0026quot;Hello\u0026quot;\t\u0026lt;b\u0026gt;World\u0026lt;\/b\u0026gt;\t\t\u0026amp;quot;period\u0026amp;quot;.\n"', $text);
	}

	public function testLength()
	{
		$sample = 'ßaeiouãêëæ漢字';
		$text = (string) $this->format($sample)->length();

		if (extension_loaded('mbstring'))
		{
			$this->assertEquals('12', $text);
		}
		else
		{
			$this->assertEquals('21', $text);
		}
	}

	public function testLimitChars()
	{
		$sample = 'ßaeiouãêëæ漢字';

		if (extension_loaded('mbstring'))
		{
			$text = (string) $this->format($sample)->limitChars(['characters' => 12]);
			$this->assertEquals($sample, $text);

			$text = (string) $this->format($sample)->limitChars(['characters' => 10]);
			$this->assertEquals('ßaeiouãêëæ&#8230;', $text);
		}
		else
		{

			$text = (string) $this->format($sample)->limitChars(['characters' => 21]);
			$this->assertEquals('ßaeiouãêëæ漢字', $text);

			$text = (string) $this->format($sample)->limitChars(['characters' => 12]);
			$this->assertEquals('ßaeiouãê'.chr(195).'&#8230;', $text);
		}

		$text = (string) $this->format('Sample Text')->limitChars(['characters' => 4, 'end_char' => 'TEST']);
		$this->assertEquals('SampTEST', $text);

		$text = (string) $this->format('Sample Text')->limitChars(['characters' => 9, 'end_char' => 'TEST', 'preserve_words' => TRUE]);
		$this->assertEquals('SampleTEST', $text);
	}

	/**
	 * @dataProvider replaceProvider
	 */
	public function testReplace($content, $params, $expected)
	{
		if ( ! defined('DEBUG'))
		{
			define('DEBUG', 0);
		}

		$text = (string) $this->format($content)->replace($params);
		$this->assertEquals($expected, $text);
	}

	public function replaceProvider()
	{
		// <li><b>Replace:</b> {a_number:replace find="/(foo)/i" replace="bar$1bat" regex="yes"}
		$sample = 'Foo food battletanks.';

		return [
			[
				$sample,
				[
					'find' => 'foo',
					'replace' => 'bar',
				],
				'Foo bard battletanks.'
			],
			[
				$sample,
				[
					'find' => 'foo',
					'replace' => 'bar',
					'case_sensitive' => 'no',
				],
				'bar bard battletanks.'
			],
			[
				$sample,
				[
					'find' => '/(foo)/i',
					'replace' => 'bar$1bat',
					'regex' => 'yes'
				],
				'barFoobat barfoobatd battletanks.'
			],
			[
				$sample,
				[
					// intentionally invalid regex
					'find' => '/(foo)i',
					'replace' => 'bar$1bat',
					'regex' => 'yes'
				],
				'Foo food battletanks.'
			],
			[
				$sample,
				[
					// Ignore eval modifier
					'find' => '/(foo)/ei',
					'replace' => 'phpinfo()',
					'regex' => 'yes'
				],
				'phpinfo() phpinfo()d battletanks.'
			],
		];
	}

	/**
	 * @dataProvider urlEncodeDecodeProvider
	 */
	public function testUrlEncode($sample, $plus_encoded, $raw_encoded)
	{
		$text = (string) $this->format($sample)->urlEncode();
		$this->assertEquals($raw_encoded, $text);

		$text = (string) $this->format($sample)->urlEncode(['plus_encoded_spaces' => 'yes']);
		$this->assertEquals($plus_encoded, $text);
	}

	/**
	 * @dataProvider urlEncodeDecodeProvider
	 */
	public function testUrlDecode($sample, $plus_encoded, $raw_encoded)
	{
		$text = (string) $this->format($raw_encoded)->urlDecode();
		$this->assertEquals($sample, $text);

		$text = (string) $this->format($plus_encoded)->urlDecode(['plus_encoded_spaces' => 'yes']);
		$this->assertEquals($sample, $text);
	}

	public function urlEncodeDecodeProvider()
	{
		$sample = ' !"#$%&\'()*+,-./0123456789:;<=>?'
			. '@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_'
			. '`abcdefghijklmnopqrstuvwxyz{|}~'
			. "\0";

		$plus_encoded = '+%21%22%23%24%25%26%27%28%29%2A%2B%2C-.%2F0123456789%3A%3B%3C%3D%3E%3F'
			. '%40ABCDEFGHIJKLMNOPQRSTUVWXYZ%5B%5C%5D%5E_'
			. '%60abcdefghijklmnopqrstuvwxyz%7B%7C%7D%7E'
			. '%00';

		$raw_encoded = '%20%21%22%23%24%25%26%27%28%29%2A%2B%2C-.%2F0123456789%3A%3B%3C%3D%3E%3F'
			. '%40ABCDEFGHIJKLMNOPQRSTUVWXYZ%5B%5C%5D%5E_'
			. '%60abcdefghijklmnopqrstuvwxyz%7B%7C%7D~'
			. '%00';

		return [[$sample, $plus_encoded, $raw_encoded]];
	}

	/**
	 * @dataProvider urlSlugProvider
	 */
	public function testUrlSlug($content, $params, $expected)
	{
		// minimal map
		$config['foreign_chars'] = [
			'223'	=>	"ss", // ß
			'230'	=>	"ae", // æ
		];

		$config['stopwords'] = ['a', 'and', 'into', 'to'];
		$config['emoji_regex'] = EMOJI_REGEX;

		$text = (string) $this->format($content, $config)->urlSlug($params);
		$this->assertEquals($expected, $text);
	}

	public function urlSlugProvider()
	{
		$sample = 'Sample Title | to Turn Into a Slug, including 💩, <samp>tags</samp>, &quot;quotes and high ascii: ßæ and----seps____in....content....';

		return [
			[$sample, [], 'sample-title-to-turn-into-a-slug-including-💩-tags-quotes-and-high-ascii-ssae-and-seps____in....content'],
			[
				$sample,
				[
					'separator' => '_',
				],
				'sample_title_to_turn_into_a_slug_including_💩_tags_quotes_and_high_ascii_ssae_and----seps_in....content'
			],
			[
				$sample,
				[
					'remove_stopwords' => 'yes',
				],
				'sample-title-turn-slug-including-💩-tags-quotes-high-ascii-ssae-seps____in....content'
			],
			[
				$sample,
				[
					'lowercase' => 'no',
				],
				'Sample-Title-to-Turn-Into-a-Slug-including-💩-tags-quotes-and-high-ascii-ssae-and-seps____in....content'
			],
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
