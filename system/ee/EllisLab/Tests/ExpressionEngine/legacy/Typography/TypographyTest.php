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

	/**
	 * @dataProvider encodeEmailData
	 */
	public function testEncodeEmail($description, $in, $out)
	{
		$str = $this->typography->encode_email($in[0], $in[1], $in[2]);
		$this->assertEquals($str, $out, $description);
	}

	public function encodeEmailData()
	{
		return array(
			array('Encode email with defaults', array('example@example.com', '', TRUE), "<span data-eeEncEmail_test='1'>.encoded_email</span><script type=\"text/javascript\">/*<![CDATA[*/var out = '',el = document.getElementsByTagName('span'),l = ['>','a','/','<',' 109',' 111',' 99',' 46',' 101',' 108',' 112',' 109',' 97',' 120',' 101',' 64',' 101',' 108',' 112',' 109',' 97',' 120',' 101','>','\\\"',' 109',' 111',' 99',' 46',' 101',' 108',' 112',' 109',' 97',' 120',' 101',' 64',' 101',' 108',' 112',' 109',' 97',' 120',' 101',':','o','t','l','i','a','m','\\\"','=','f','e','r','h','a ','<'],i = l.length,j = el.length;while (--i >= 0)out += unescape(l[i].replace(/^\s\s*/, '&#'));while (--j >= 0)if (el[j].getAttribute('data-eeEncEmail_test'))el[j].innerHTML = out;/*]]>*/</script>"),
			array('Encode email with title', array('example@example.com', 'my email', TRUE), "<span data-eeEncEmail_test='1'>.encoded_email</span><script type=\"text/javascript\">/*<![CDATA[*/var out = '',el = document.getElementsByTagName('span'),l = ['>','a','/','<',' 108',' 105',' 97',' 109',' 101',' 32',' 121',' 109','>','\\\"',' 109',' 111',' 99',' 46',' 101',' 108',' 112',' 109',' 97',' 120',' 101',' 64',' 101',' 108',' 112',' 109',' 97',' 120',' 101',':','o','t','l','i','a','m','\\\"','=','f','e','r','h','a ','<'],i = l.length,j = el.length;while (--i >= 0)out += unescape(l[i].replace(/^\s\s*/, '&#'));while (--j >= 0)if (el[j].getAttribute('data-eeEncEmail_test'))el[j].innerHTML = out;/*]]>*/</script>"),
			array('Encode email without anchor', array('example@example.com', '', FALSE), "<span data-eeEncEmail_test='1'>.encoded_email</span><script type=\"text/javascript\">/*<![CDATA[*/var out = '',el = document.getElementsByTagName('span'),l = [' 109',' 111',' 99',' 46',' 101',' 108',' 112',' 109',' 97',' 120',' 101',' 64',' 101',' 108',' 112',' 109',' 97',' 120',' 101'],i = l.length,j = el.length;while (--i >= 0)out += unescape(l[i].replace(/^\s\s*/, '&#'));while (--j >= 0)if (el[j].getAttribute('data-eeEncEmail_test'))el[j].innerHTML = out;/*]]>*/</script>"),
			array('Encode email with title and without anchor', array('example@example.com', 'my email', FALSE), "<span data-eeEncEmail_test='1'>.encoded_email</span><script type=\"text/javascript\">/*<![CDATA[*/var out = '',el = document.getElementsByTagName('span'),l = [' 109',' 111',' 99',' 46',' 101',' 108',' 112',' 109',' 97',' 120',' 101',' 64',' 101',' 108',' 112',' 109',' 97',' 120',' 101'],i = l.length,j = el.length;while (--i >= 0)out += unescape(l[i].replace(/^\s\s*/, '&#'));while (--j >= 0)if (el[j].getAttribute('data-eeEncEmail_test'))el[j].innerHTML = out;/*]]>*/</script>"),
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

class FunctionsStub {
	public function random($str, $int)
	{
		return 'test';
	}
}

function ee()
{
	$obj = new StdClass();
	$obj->encode_email = TRUE;
	$obj->functions = new FunctionsStub();
	return $obj;
}
// EOF
