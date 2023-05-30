<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Formatter;

use Mockery as m;
use ExpressionEngine\Service\Formatter\Formats\Number;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../../ExpressionEngine/Boot/boot.common.php';

class NumberFormatterTest extends TestCase
{
    public $lang;
    public $sess;
    public $factory;

    public function setUp(): void
    {
        $this->lang = m::mock('EE_Lang');
        $this->sess = m::mock('EE_Session');

        $this->lang->shouldReceive('load');
    }

    public function tearDown(): void
    {
        $this->factory = null;

        m::close();
    }

    /**
     * @dataProvider byteProvider
     */
    public function testByte($content, $abbr, $include_markup, $expected)
    {
        $number = (string) $this->format($content)->bytes($abbr, $include_markup);
        $this->assertEquals($expected, $number);
    }

    public function byteProvider()
    {
        // sets the byte() parameters and expected lang key suffix
        // array($abbr, $include_markup, 'suffix')
        $permutations = array(
            array(false, false, ''),
            array(true, false, '_abbr'),
            array(true, true, '_abbr_html'),
            array(false, true, '')
        );

        $data = array();
        foreach ($permutations as $p) {
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
    public function testCurrency($content, $currency, $locale, $expected, $opts, $decimals = null)
    {
        $params = [
            'currency' => $currency,
            'locale' => $locale,
            'decimals' => $decimals,
        ];

        $number = (string) $this->format($content, $opts)->currency($params);
        $this->assertEquals($expected, $number);
    }

    public function currencyProvider()
    {
        $currency = [
            // with intl extension
            [112358.13, null, null, '$112,358.13', 0b00000001],
            [112358.13, null, null, '$112,358', 0b00000001, 0],
            [112358.13, 'EUR', 'de_DE', '112.358,13 €', 0b00000001],
            [112358.13, 'GBP', 'en_UK', '£112,358.13', 0b00000001],
            [112358.13, 'AUD', 'en_US.UTF-8', 'A$112,358.13', 0b00000001],
            [112358.13, 'AUD', 'de_DE', '112.358,13 AU$', 0b00000001],
            [112358.13, 'RUR', 'ru', '112 358,13 р.', 0b00000001],
            [112358.13, 'UAH', 'uk', '112 358,13 ₴', 0b00000001],
            [112358.13, 'UAH', 'en', (defined('INTL_ICU_VERSION') && version_compare(INTL_ICU_VERSION, '4.8', '>') ? 'UAH 112,358.13' : '₴1,234,567.89'), 0b00000001],
            ['fake', null, null, '$0.00', 0b00000001]
        ];
        $no_intl = [
            // no intl extension
            [112358.13, null, null, '$112,358.13', 0],
            [112358.13, null, null, '$112,358', 0, 0],
            [112358.13, 'EUR', 'de_DE', '112.358,13 EUR', 0],
            [112358.13, 'GBP', 'en_UK', '112358.13', 0],
            [112358.13, 'AUD', 'en_US.UTF-8', '$112,358.13', 0],
            [112358.13, 'AUD', 'de_DE', '112.358,13 EUR', 0],
            [112358.13, 'RUR', 'ru', '112358.13', 0],
            [112358.13, 'UAH', 'uk', '112358.13', 0],
            [112358.13, 'UAH', 'en', '112358.13', 0],
            ['fake', null, null, '$0.00', 0],
        ];
        // Currently currency formatter only works on PHP8 with the intl extension.
        // This may be a bug we need to fix
        if (PHP_OS != "WINNT" && (int) phpversion() < 8) {
            $currency = array_merge($currency, $no_intl);
        }

        return $currency;
    }

    /**
     * @dataProvider durationProvider
     */
    public function testDuration($content, $expected, $opts)
    {
        $val = (string) $this->format($content, $opts)->duration();
        $this->assertEquals($expected, $val);
    }

    public function durationProvider()
    {
        return [
            // with intl extension
            [112358, '31:12:38', 0b00000001],
            [-112358, '-32:-13:-38', 0b00000001],
            [1123, '18:43', 0b00000001],
            [11, '11 sec.', 0b00000001],
            ['fake', '0 sec.', 0b00000001],

            // no intl extension
            // don't have a good way to test the output of a sprintf()'d language variable
            [112358, '31:12:38', 0],
            [-112358, 'formatter_duration_seconds_only', 0],
            [1123, '18:43', 0],
            [11, 'formatter_duration_seconds_only', 0],
            ['fake', 'formatter_duration_seconds_only', 0],
        ];
    }

    /**
     * @dataProvider numberFormatProvider
     */
    public function testNumberFormat($content, $decimals, $decimal_point, $thousands_separator, $expected)
    {
        $params = [
            'decimals' => $decimals,
            'decimal_point' => $decimal_point,
            'thousands_separator' => $thousands_separator,
        ];

        $number = (string) $this->format($content)->number_format($params);
        $this->assertEquals($expected, $number);
    }

    public function numberFormatProvider()
    {
        $float = 12345.67890;

        return [
            [$float, 2, '.', ',', '12,345.68'],
            [$float, 2, ',', '.', '12.345,68'],
            [$float, 0, '.', ',', '12,346'],
            [$float, 3, ',', '.', '12.345,679'],
        ];
    }

    /**
     * @dataProvider ordinalProvider
     */
    public function testOrdinal($content, $locale, $expected, $opts)
    {
        $number = (string) $this->format($content, $opts)->ordinal(['locale' => $locale]);
        $this->assertEquals($expected, $number);
    }

    public function ordinalProvider()
    {
        return [
            // with intl extension
            [11235813, null, '11,235,813th', 0b00000001],
            [11235813, 'de', '11.235.813.', 0b00000001],
            [11235813, 'fr', "11" . "\u{202F}" . "235" . "\u{202F}" . "813e", 0b00000001],
            ['fake', null, '0th', 0b00000001],

            // no intl extension
            [11235813, null, '11,235,813th', 0],
            [11235813, 'de', '11,235,813th', 0],
            [11235813, 'fr', '11,235,813th', 0],
            ['fake', null, '0th', 0],
        ];
    }

    /**
     * @dataProvider spelloutProvider
     */
    public function testSpellout($locale, $capitalize, $expected)
    {
        $params = [
            'capitalize' => $capitalize,
            'locale' => $locale,
        ];

        $number = (string) $this->format(11234813)->spellout($params);
        $this->assertEquals($expected, $number);
    }

    public function testSpelloutNoIntl()
    {
        $this->expectException(\Exception::class);
        $number = (string) $this->format(11234813, 0)->spellout();
    }

    public function spelloutProvider()
    {
        return [
            [null, null, 'eleven million two hundred thirty-four thousand eight hundred thirteen'],
            [null, 'ucfirst', 'Eleven million two hundred thirty-four thousand eight hundred thirteen'],
            [null, 'ucwords', 'Eleven Million Two Hundred Thirty-four Thousand Eight Hundred Thirteen'],
            ['de', null, 'elf Millionen zwei­hundert­vier­und­dreißig­tausend­acht­hundert­dreizehn'],
            ['fr', null, 'onze millions deux cent trente-quatre mille huit cent treize'],
        ];
    }

    public function format($content, $options = 0b00000001)
    {
        return new Number($content, $this->lang, $this->sess, [], $options);
    }
}

// EOF
