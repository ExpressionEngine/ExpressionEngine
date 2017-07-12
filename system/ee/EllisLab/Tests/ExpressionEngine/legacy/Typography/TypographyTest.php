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

	/**
	 * @dataProvider fortmatCharactersData
	 */
	public function testFormatCharacters($description, $in, $out)
	{
		$str = $this->typography->format_characters($in);
		$this->assertEquals($str, $out, $description);
	}

	public function fortmatCharactersData()
	{
		return array(
			array('Unterminated ampersands converted', 'M&Ms', 'M&amp;Ms'),
			array('&amp; left alone', 'Foo &amp; Bar', 'Foo &amp; Bar'),
			array('Ampersand', '&', '&amp;'),
			array('Em dash', '--', '&#8212;'),
			array('Ellipses', 'you know...', 'you know&#8230;'),
			array('Apostrophy', "It's its", "It&#8217;s its"),
			array('US quote no whitespace', '"Hello"', '&#8220;Hello&#8221;'),
			array('UK quote no whitespace', "'Hello'", '&#8216;Hello&#8217;'),
			array('Simple nested US quote', '"I said, \'Hello\'"', '&#8220;I said, &#8216;Hello&#8217;&#8221;'),
			array('Simple nested UK quote', "'I said, \"Hello\"'", '&#8216;I said, &#8220;Hello&#8221;&#8217;'),
			array('US quote surrounded by whitespace', 'Foo "Bar" Baz', 'Foo &#8220;Bar&#8221; Baz'),
			array('UK quote surrounded by whitespace', "Foo 'Bar' Baz", 'Foo &#8216;Bar&#8217; Baz'),
		);
	}

	/**
	 * @dataProvider nlToBrData
	 */
	public function testNlToBrExceptPre($description, $in, $out)
	{
		$str = $this->typography->nl2br_except_pre($in);
		$this->assertEquals($str, $out, $description);
	}

	public function nlToBrData()
	{
		return array(
			array('\n gets a <br>', "Here is added\na newline", "Here is added<br />\na newline"),
			array('\r gets a <br>', "Here is added\ra newline", "Here is added<br />\ra newline"),
			array('\n\r gets a <br>', "Here is added\n\ra newline", "Here is added<br />\n\ra newline"),
			array('\r\n gets a <br>', "Here is added\r\na newline", "Here is added<br />\r\na newline"),

			array('NL2BR inside <p> tags', "<p>This has\na newline</p>\n", "<p>This has<br />\na newline</p><br />\n"),
			array('NL2BR except <pre> tags', "Here we have a newline\n<pre>But not\nin here!<pre>", "Here we have a newline<br />\n<pre>But not\nin here!<pre>"),
		);
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
