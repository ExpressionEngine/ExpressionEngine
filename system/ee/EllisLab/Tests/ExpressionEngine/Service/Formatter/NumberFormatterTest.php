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
use EllisLab\ExpressionEngine\Service\Formatter\FormatterFactory;

class NumberFormatterTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->lang = m::mock('EE_Lang');
		$options = (extension_loaded('intl')) ? 0b00000001 : 0;
		$this->factory = new FormatterFactory($this->lang, $options);
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

	/**
	 * @dataProvider currencyProvider
	 */
	public function testCurrency($content, $currency, $locale, $expected)
	{
		$this->lang->shouldReceive('load')->once();

		$opts = [
			'currency' => $currency,
			'locale' => $locale,
		];

		$number = (string) $this->factory->make('Number', $content)->currency($opts);
		$this->assertEquals($expected, $number);
	}

	public function currencyProvider()
	{
		if (extension_loaded('intl'))
		{
			return [
				[112358.13, NULL, NULL, '$112,358.13'],
				[112358.13, 'EUR', 'de_DE', '112.358,13 €'],
				[112358.13, 'GBP', 'en_UK', '£112,358.13'],
				[112358.13, 'AUD', 'en_US.UTF-8', 'A$112,358.13'],
				[112358.13, 'AUD', 'de_DE', '112.358,13 AU$'],
				[112358.13, 'RUR', 'ru', '112 358,13 р.'],
				[112358.13, 'UAH', 'uk', '112 358,13 ₴'],
				[112358.13, 'UAH', 'en', (version_compare(INTL_ICU_VERSION, '4.8', '>') ? 'UAH112,358.13' : '₴1,234,567.89')],
			];
		}

		// no intl extension means installed locales and money_format() will be used. Inaccurate for non-US locales.
		return [
			[112358.13, NULL, NULL, '$112,358.13'],
			[112358.13, 'EUR', 'de_DE', 'Eu112.358,13'],
			[112358.13, 'GBP', 'en_UK', '112358.13'],
			[112358.13, 'AUD', 'en_US.UTF-8', '$112,358.13'],
			[112358.13, 'AUD', 'de_DE', 'Eu112.358,13'],
			[112358.13, 'RUR', 'ru', '112358.13'],
			[112358.13, 'UAH', 'uk', '112358.13'],
			[112358.13, 'UAH', 'en', '112358.13'],
		];
	}

	public function tearDown()
	{
		$this->factory = NULL;
	}
}

// EOF
