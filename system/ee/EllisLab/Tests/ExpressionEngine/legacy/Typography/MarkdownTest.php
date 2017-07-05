<?php

require_once SYSPATH.'ee/EllisLab/ExpressionEngine/Boot/boot.common.php';
require_once APPPATH.'helpers/string_helper.php';
require_once APPPATH.'libraries/Typography.php';
require_once APPPATH.'libraries/typography/Markdown/Michelf/MarkdownExtra.inc.php';

define('PATH_ADDONS', APPPATH.'modules/');

class TypographyTest extends \PHPUnit_Framework_TestCase {

	private $typography;

	public function setUp()
	{
		$this->typography = new TypographyStub();
	}

	private function getContentForMarkup($name)
	{
		$path = realpath(__DIR__.'/../../../support/typography/' . $name);
		return file_get_contents($path);
	}

	public function testCodeFence()
	{
		$str = $this->typography->markdown($this->getContentForMarkup('code-fence.md'));

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
		$str = $this->typography->markdown($this->getContentForMarkup('code-block.md'));

		// Should contain no tabs
		$this->assertNotContains("\t", $str);

		// Must contain unaffected opening and close php tags
		$this->assertContains('&lt;?php', $str);
		$this->assertContains('?&gt;', $str);
	}

	public function testCodeBlockAndFence()
	{
		$str = $this->typography->markdown($this->getContentForMarkup('code-block-and-fence.md'));

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
		$smartypants = $this->getContentForMarkup('smartypants.md');
		$str = $this->typography->markdown($smartypants);

		// The em and en dashes should be where you'd expect them
		$this->assertContains("dashes&#8212;they", $str);
		$this->assertContains("thoughts&#8212;with", $str);
		$this->assertContains("2004&#8211;2014", $str);

		// Fancy quotes should be around "fancy quotes" and 'there'
		$this->assertContains("&#8220;fancy quotes&#8221;", $str);
		$this->assertContains("&#8216;there&#8217;", $str);

		// Test WITHOUT SmartyPants
		$str = $this->typography->markdown($smartypants, array('smartypants' => FALSE));

		// dashes and quotes should not be converted
		$this->assertContains("dashes---they", $str);
		$this->assertContains("thoughts---with", $str);
		$this->assertContains("2004--2014", $str);
		$this->assertContains("\"fancy quotes\"", $str);
		$this->assertContains("'there'", $str);
	}

	public function testNoMarkup()
	{
		$markdown = $this->getContentForMarkup('markdown.md');
		$str = $this->typography->markdown($markdown, array('no_markup' => TRUE));

		// Make sure markup is not parsed
		$this->assertNotContains('<div', $str);
		$this->assertNotContains('<span>really</span>', $str);
		$this->assertNotContains('<em>just</em>', $str);
	}

	public function testLinksWithSpaces()
	{
		$markdown = $this->getContentForMarkup('markdown.md');
		$str = $this->typography->markdown($markdown);
		$this->assertContains('<a href="https://packagecontrol.io/packages/Marked%20App%20Menu">Marked App Menu</a>', $str);
	}
}

class TypographyStub extends EE_Typography
{
	public function __construct()
	{
		// Skipping initialize and autoloader
	}
}
// EOF
