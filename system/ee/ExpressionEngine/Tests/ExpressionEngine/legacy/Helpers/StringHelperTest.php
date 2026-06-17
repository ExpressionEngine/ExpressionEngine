<?php

require_once APPPATH . 'helpers/string_helper.php';

use PHPUnit\Framework\TestCase;

class StringHelperTest extends TestCase
{
    public function testBasicRandomStringReturnsAnInteger()
    {
        $value = random_string('basic');

        $this->assertIsInt($value);
        $this->assertGreaterThanOrEqual(0, $value);
        $this->assertLessThanOrEqual(mt_getrandmax(), $value);
    }

    /**
     * @dataProvider randomStringFormatProvider
     */
    public function testRandomStringReturnsExpectedFormats($type, $length, $pattern, $expectedLength)
    {
        $value = random_string($type, $length);

        $this->assertMatchesRegularExpression($pattern, $value);
        $this->assertSame($expectedLength, strlen($value));
    }

    public function randomStringFormatProvider()
    {
        return array(
            'alpha' => array('alpha', 12, '/^[a-zA-Z]{12}$/', 12),
            'alnum' => array('alnum', 12, '/^[a-zA-Z0-9]{12}$/', 12),
            'numeric' => array('numeric', 12, '/^[0-9]{12}$/', 12),
            'nozero' => array('nozero', 12, '/^[1-9]{12}$/', 12),
            'unique' => array('unique', 8, '/^[a-f0-9]{32}$/', 32),
            'md5' => array('md5', 8, '/^[a-f0-9]{32}$/', 32),
            'encrypt' => array('encrypt', 8, '/^[a-f0-9]{40}$/', 40),
            'sha1' => array('sha1', 8, '/^[a-f0-9]{40}$/', 40),
        );
    }

    public function testAntipoolRemovesCharactersFromRandomPool()
    {
        $this->assertSame('BBBBBBBBBB', random_string('alpha', 10, 'abcdefghijklmnopqrstuvwxyzACDEFGHIJKLMNOPQRSTUVWXYZ'));
    }

    public function testTokenFormattedRandomStringsDoNotUseUniqid()
    {
        $function = new ReflectionFunction('random_string');
        $source = implode('', array_slice(
            file($function->getFileName()),
            $function->getStartLine() - 1,
            $function->getEndLine() - $function->getStartLine() + 1
        ));

        $this->assertStringNotContainsString('uniqid(', $source);
    }
}
