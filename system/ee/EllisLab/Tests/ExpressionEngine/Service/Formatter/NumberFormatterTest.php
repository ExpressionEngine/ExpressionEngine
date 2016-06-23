<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Formatter;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Formatter\FormatterFactory;

class NumberFormatterTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$provider = m::mock('EllisLab\ExpressionEngine\Core\Provider');
		$this->lang = m::mock('EE_Lang');
		$this->factory = new FormatterFactory($this->lang);
	}

	/**
	 * @dataProvider byteProvider
	 */
	public function testByte($content, $abbr, $include_markup, $expected)
	{
		$this->lang->shouldReceive('load')->once();
		$number = (string) $this->factory->make('Number', $content)->bytes($abbr, $include_markup);
		$this->assertEquals($expected, $number);
	}

	/**
	 * @dataProvider currencyProvider
	 */
	public function testCurrency($content, $locale, $currency_code, $expected)
	{
		// fake the server's locale setting
		$locale = ($locale) ?: 'en_US';
		ini_set('intl.default_locale', $locale);

		$this->lang->shouldReceive('load')->once();
		$currency = (string) $this->factory->make('Number', $content)->currency($currency_code);
		$this->assertEquals($expected, $currency);
	}

	/**
	 * @dataProvider badCurrencyProvider
	 * @expectedException LengthException
	 */
	public function testBadCurrency($content, $locale, $currency_code, $expected)
	{
		// fake the server's locale setting
		$locale = ($locale) ?: 'en_US';
		ini_set('intl.default_locale', $locale);

		$this->lang->shouldReceive('load')->once();
		$currency = (string) $this->factory->make('Number', $content)->currency($currency_code);
	}

	public function byteProvider()
	{
		// sets the byte() parameters and expected lang key suffix
		// array($abbr, $include_markup, 'suffix')
		$permutations = array(
			array(FALSE, FALSE, ''),
			array(TRUE, FALSE, '_abbr'),
			array(TRUE, TRUE, '_abbr_html'),
			array(FALSE, TRUE, '')
		);

		$data = array();
		foreach ($permutations as $p)
		{
			// non-abbreviated lang keys should be proceeded with a space
			$space = ($p[0]) ? '' : ' ';

			$data = array_merge($data, array(
				array(1073741824, $p[0], $p[1], "1.00{$space}formatter_gigabytes{$p[2]}"),
				array(10732049531, $p[0], $p[1], "10.00{$space}formatter_gigabytes{$p[2]}"),
				array(10732049530, $p[0], $p[1], "9.99{$space}formatter_gigabytes{$p[2]}"),

				array(1048576, $p[0], $p[1], "1.0{$space}formatter_megabytes{$p[2]}"),
				array(10433332, $p[0], $p[1], "10.0{$space}formatter_megabytes{$p[2]}"),
				array(10433331, $p[0], $p[1], "9.9{$space}formatter_megabytes{$p[2]}"),

				array(1024, $p[0], $p[1], "1{$space}formatter_kilobytes{$p[2]}"),
				array(10752, $p[0], $p[1], "11{$space}formatter_kilobytes{$p[2]}"),
				array(10751, $p[0], $p[1], "10{$space}formatter_kilobytes{$p[2]}"),

				array(999, $p[0], $p[1], "999{$space}formatter_bytes{$p[2]}"),
			));
		}

		return $data;
	}

	public function currencyProvider()
	{
		return array(
			// one float for good measure, thought the content will typically be a string
			array(1234567890.123456789, NULL, 'USD', '$1,234,567,890.12'),
			array('1234567890.123456789', NULL, 'USD', '$1,234,567,890.12'),
			array('1234567890.123456789', NULL, 'TRY', 'TRY1,234,567,890.12'),
			array('1234567890.123456789', NULL, 'EUR', '€1,234,567,890.12'),
			array('1234567890.123456789', NULL, 'JPY', '¥1,234,567,890'),
			array('1234567890.123456789', 'en_GB', 'USD', 'US$1,234,567,890.12'),
			array('1234567890.123456789', 'en_GB', 'GBP', '£1,234,567,890.12'),
			array('1234567890.123456789', 'TR', 'TRY', '1.234.567.890,12 ₺'),
			array('1234567890.123456789', 'ak_GH', 'GHS', 'GH₵1,234,567,890.12'),
			array('1234567890.123456789', NULL, 'FOO', 'FOO1,234,567,890.12'),
		);
	}

	public function badCurrencyProvider()
	{
		return array(
			array('1234567890.123456789', NULL, 'TOOMANYLETTERS', ''),
			array('1234567890.123456789', NULL, 'TF', ''),
			array('1234567890.123456789', NULL, 'FOO', ''),
		);
	}

	public function tearDown()
	{
		$this->factory = NULL;
	}
}

// EOF
