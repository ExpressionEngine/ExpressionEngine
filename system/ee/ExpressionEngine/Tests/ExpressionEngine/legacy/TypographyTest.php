<?php

require_once SYSPATH.'ee/EllisLab/ExpressionEngine/Boot/boot.common.php';
require_once APPPATH.'helpers/string_helper.php';
require_once APPPATH.'libraries/Typography.php';
require_once APPPATH.'libraries/typography/Markdown/Michelf/MarkdownExtra.inc.php';

define('PATH_ADDONS', APPPATH.'modules/');

use PHPUnit\Framework\TestCase;

class TypographyTest extends TestCase {

	private $typography;

	public function setUp()
	{
		$this->typography = new TypographyStub();
	}

	public function testCodeFence()
	{
		$str = $this->typography->markdown(CODE_FENCE);

		// Make sure we've removed all code fences
		$this->assertNotContains('~~~', $str);
		$this->assertNotContains('```', $str);

		// The ~~``~~ turns to code (`` => <code>)
		$this->assertContains('~~<code>~~', $str);
		$this->assertContains('~~</code>~~', $str);

		// Must contain unaffected opening and close php tags
		$this->assertContains('&lt;?php', $str);
		$this->assertContains('?&gt;', $str);
	}

	public function testCodeBlock()
	{
		$str = $this->typography->markdown(CODE_FENCE);

		// Should contain no tabs
		$this->assertNotContains("\t", $str);

		// Must contain unaffected opening and close php tags
		$this->assertContains('&lt;?php', $str);
		$this->assertContains('?&gt;', $str);
	}

	public function testCodeBlockAndFence()
	{
		$str = $this->typography->markdown(CODE_BLOCK_AND_FENCE);

		// Should contain no tabs
		$this->assertNotContains("\t", $str);

		// Make sure we've removed all code fences
		$this->assertNotContains('~~~', $str);
		$this->assertNotContains('```', $str);

		// The ~~``~~ turns to code (`` => <code>)
		$this->assertContains('~~<code>~~', $str);
		$this->assertContains('~~</code>~~', $str);

		// Must contain unaffected opening and close php tags
		$this->assertContains('&lt;?php', $str);
		$this->assertContains('?&gt;', $str);
	}

	public function testSmartyPants()
	{
		$str = $this->typography->markdown(SMARTYPANTS);

		// The em and en dashes should be where you'd expect them
		$this->assertContains("dashes&#8212;they", $str);
		$this->assertContains("thoughts&#8212;with", $str);
		$this->assertContains("2004&#8211;2014", $str);

		// Fancy quotes should be around "fancy quotes" and 'there'
		$this->assertContains("&#8220;fancy quotes&#8221;", $str);
		$this->assertContains("&#8216;there&#8217;", $str);

		// Test WITHOUT SmartyPants
		$str = $this->typography->markdown(SMARTYPANTS, array('smartypants' => FALSE));

		// dashes and quotes should not be converted
		$this->assertContains("dashes---they", $str);
		$this->assertContains("thoughts---with", $str);
		$this->assertContains("2004--2014", $str);
		$this->assertContains("\"fancy quotes\"", $str);
		$this->assertContains("'there'", $str);
	}

	public function testNoMarkup()
	{
		$str = $this->typography->markdown(MARKDOWN, array('no_markup' => TRUE));

		// Make sure markup is not parsed
		$this->assertNotContains('<div', $str);
		$this->assertNotContains('<span>really</span>', $str);
		$this->assertNotContains('<em>just</em>', $str);
	}

	public function testLinksWithSpaces()
	{
		$str = $this->typography->markdown(MARKDOWN);
		$this->assertContains('<a href="https://packagecontrol.io/packages/Marked%20App%20Menu">Marked App Menu</a>', $str);
	}

	public function testEmoticonConversionOn()
	{
		ee()->session->setUserdata('parse_smileys', 'y');
		$sample = 'Smileys like ;) should be converted but not character entities like &rdquo;, even if they end in parenthesis &rdquo;)';
		$expected = 'Smileys like :wink: should be converted but not character entities like &rdquo;, even if they end in parenthesis &rdquo;)';

		$str = $this->typography->emoticon_replace($sample);
		$this->assertEquals($expected, $str);
	}

	public function testEmoticonConversionOff()
	{
		ee()->session->setUserdata('parse_smileys', 'n');
		$sample = 'Smileys like ;) should be converted but not character entities like &rdquo;, even if they end in parenthesis &rdquo;)';
		$expected = 'Smileys like ;) should be converted but not character entities like &rdquo;, even if they end in parenthesis &rdquo;)';

		$str = $this->typography->emoticon_replace($sample);
		$this->assertEquals($expected, $str);
	}
}

class TypographyStub extends EE_Typography
{
	public function __construct()
	{
		// Skipping initialize and autoloader
	}
}

// Define the codeblocks to test with
$code_fence = <<<'MD'
```
<?php
$no_parse = TRUE;
$value = ($no_parse) ? 5 : 6;
?>
```

In between

~~~
<?php $test = 3; ?>
~~~

In between

~~``~~
not code
~~``~~

~~~~~~
$last_block = TRUE;
~~~~~~
MD;
define('CODE_FENCE', $code_fence);

$code_block = <<<'MD'
	<?php
	$no_parse = TRUE;
	$value = ($no_parse) ? 5 : 6;
	?>

In between

	<?php $test = 3; ?>

In between

  not code

    $last_block = TRUE;
MD;
define('CODE_BLOCK', $code_block);

$code_block_and_fence = <<<'MD'
	<?php
	$no_parse = TRUE;
	$value = ($no_parse) ? 5 : 6;
	?>

```
<?php
$no_parse = TRUE;
$value = ($no_parse) ? 5 : 6;
?>
```

In between

~~~
<?php $test = 3; ?>
~~~

	<?php $test = 3; ?>

In between

  not code

    $last_block = TRUE;

In between

~~``~~
not code
~~``~~

In between

~~~~~~
$last_block = TRUE;
~~~~~~
MD;
define('CODE_BLOCK_AND_FENCE', $code_block_and_fence);

$smartypants = <<<'MD'
Testing out em and en dashes---they're the fancy long dashes that separate
thoughts---with this string. Our copyright is 2004--2014.

Let's try out some "fancy quotes" here and 'there'.
MD;
define('SMARTYPANTS', $smartypants);

$markdown = <<<'MD'
Markdown
-------------

...is <span>really</span> <em>just</em> ordinary text, *plain and simple*. How is it good for you?

- You just **type naturally**, and the result looks good.
- You **don't have to worry** about clicking formatting buttons.
  - Or fiddling with indentation. (Two spaces is all you need.)

<div class="something">This shouldn't be parsed in Markdown with the no_markup
	option set.</div>

We also have a URL here that has spaces in it: [Marked App Menu](https://packagecontrol.io/packages/Marked%20App%20Menu).

To see what else you can do with Markdown (including **tables**, **images**, **numbered lists**, and more) take a look at the [Cheatsheet][1]. And then try it out by typing in this box!

[1]: https://github.com/adam-p/markdown-here/wiki/Markdown-Here-Cheatsheet
MD;
define('MARKDOWN', $markdown);

// EOF
