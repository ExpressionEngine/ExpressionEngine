<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Validation;

use ExpressionEngine\Service\Validation\Validator;
use PHPUnit\Framework\TestCase;

require_once APPPATH.'helpers/multibyte_helper.php';

class ValidatorTest extends TestCase
{
    public function setUp() : void
    {
        $this->validator = new Validator();
    }

    public function tearDown() : void
    {
        $this->validator = null;
    }

    public function testRequired()
    {
        $rules = array('a' => 'required');
        $this->validator->setRules($rules);

        // true
        $result = $this->validator->validate(array('a' => 'exists'));
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(array('a' => 0));
        $this->assertTrue($result->isValid());

        // false
        $result = $this->validator->validate(array('a' => false));
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(array('a' => '  '));
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(array('b' => 'wrong key'));
        $this->assertFalse($result->isValid());
    }

    public function testChaining()
    {
        $rules = array(
            'a' => 'enum[yes, exists]|alpha|min_length[2]|max_length[6]'
        );
        $this->validator->setRules($rules);

        // true
        $result = $this->validator->validate(array('a' => 'exists'));
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(array('a' => 'yes'));
        $this->assertTrue($result->isValid());

        // false
        $result = $this->validator->validate(array('a' => 'no'));
        $this->assertEquals(1, count($result->getFailed('a')));

        $result = $this->validator->validate(array('a' => 'foo-ey'));
        $this->assertEquals(2, count($result->getFailed('a')));

        $result = $this->validator->validate(array('a' => 'not good++'));
        $this->assertEquals(3, count($result->getFailed('a')));
    }

    public function testStopAfterRequired()
    {
        $rules = array(
            'a' => 'required|enum[yes, exists]|alpha|min_length[2]|max_length[6]'
        );
        $this->validator->setRules($rules);

        // true
        $result = $this->validator->validate(array('a' => 'exists'));
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(array('a' => 'yes'));
        $this->assertTrue($result->isValid());

        // false
        $result = $this->validator->validate(array('a' => '+'));
        $this->assertEquals(3, count($result->getFailed('a')));

        $result = $this->validator->validate(array('a' => ''));
        $this->assertEquals(1, count($result->getFailed('a')));
    }

    public function testSkipIfBlankAndNotRequired()
    {
        $rules = array(
            'a' => 'enum[yes, exists]|alpha|min_length[2]|max_length[6]'
        );
        $this->validator->setRules($rules);

        $result = $this->validator->validate(array('a' => 'not blank'));
        $this->assertFalse($result->isValid());


        $result = $this->validator->validate(array('a' => ''));
        $this->assertTrue($result->isValid());
    }

    public function testWhenPresent()
    {
        $rules = array(
            'nickname' => 'whenPresent|required|min_length[5]',
            'email' => 'whenPresent[newsletter]|required|email'
        );
        $this->validator->setRules($rules);

        $result = $this->validator->validate(array('not' => 'set'));
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(array('nickname' => 'jimmy'));
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(array('nickname' => 'jim'));
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(array(
            'email' => 'not an email'
        ));
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(array(
            'newsletter' => '1',
            'email' => 'not an email'
        ));
        $this->assertFalse($result->isValid());
    }

    public function testPartial()
    {
        $rules = array('a' => 'required|min_length[8]');
        $this->validator->setRules($rules);

        // true
        $result = $this->validator->validatePartial(array('a' => 'more than eight'));
        $this->assertTrue($result->isValid());

        $result = $this->validator->validatePartial(array('b' => 'wrong key'));
        $this->assertTrue($result->isValid());

        // false
        $result = $this->validator->validatePartial(array('a' => 'short'));
        $this->assertFalse($result->isValid());
    }

    public function testGreaterThan()
    {
        $rules = array('a' => 'greater_than[8]');
        $this->validator->setRules($rules);

        // true
        $result = $this->validator->validate(array('a' => 10));
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(array('a' => '13'));
        $this->assertTrue($result->isValid());

        // false
        $result = $this->validator->validate(array('a' => 5));
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(array('a' => -5));
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(array('a' => '-5'));
        $this->assertFalse($result->isValid());
    }

    public function testLessThan()
    {
        $rules = array('a' => 'less_than[8]');
        $this->validator->setRules($rules);

        // false
        $result = $this->validator->validate(array('a' => 10));
        $this->assertFalse($result->isValid());

        $result = $this->validator->validate(array('a' => '13'));
        $this->assertFalse($result->isValid());

        // true
        $result = $this->validator->validate(array('a' => 5));
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(array('a' => -5));
        $this->assertTrue($result->isValid());

        $result = $this->validator->validate(array('a' => '-5'));
        $this->assertTrue($result->isValid());
    }

    /**
     * @dataProvider numericDataProvider
     */
    public function testNumeric($value, $expected)
    {
        $this->validator->setRules(array(
            'number' => 'numeric'
        ));

        $result = $this->validator->validate(array('number' => $value));
        $this->assertEquals($expected, $result->isValid());
    }

    public function numericDataProvider()
    {
        return array(
            // good!
            array('5', true),
            array('-6', true),
            array('+6', true),
            array('0', true),
            array('-0', true),
            array('+0', true),
            array('.6', true),
            array('-.6', true),
            array('+.6', true),
            array('8.', true),
            array('-8.', true),
            array('+8.', true),
            array('8.23', true),
            array('-8.23', true),
            array('+8.23', true),

            // bad!
            array('fortran', false),
            array('2.8.4', false),
            array('2-4', false),
            array('2e4', false),
            array('0x24', false)
        );
    }

    /**
     * @dataProvider hexColorDataProvider
     */
    public function testHexColor($value, $expected)
    {
        $this->validator->setRules(array(
            'color' => 'hexColor'
        ));

        $result = $this->validator->validate(array('color' => $value));
        $this->assertEquals($expected, $result->isValid());
    }

    public function hexColorDataProvider()
    {
        return array(
            // good!
            array('000', true),
            array('fff', true),
            array('FFF', true),
            array('abc', true),
            array('123', true),
            array('000000', true),
            array('ffffff', true),
            array('FFFFFF', true),
            array('AABBCC', true),
            array('112233', true),
            array('ABCDEF', true),
            array('A1B2E3', true),

            // bad!
            array('#fff', false),
            array('#FFFFFF', false),
            array('KEVIN', false),
            array('f', false),
            array('ff', false),
            array('ffff', false),
            array('fffff', false)
        );
    }

    /**
     * @dataProvider noHtmlDataProvider
     */
    public function testNoHtml($value, $expected)
    {
        $this->validator->setRules(array(
            'somefield' => 'noHtml'
        ));

        $result = $this->validator->validate(array('somefield' => $value));
        $this->assertEquals($expected, $result->isValid());
    }

    public function noHtmlDataProvider()
    {
        return array(
            // good!
            array('test', true),
            array('some text @##%#$$%&%^*', true),
            array('> some text <', true),
            array('tests > no tests', true),
            array('test < something', true),

            // bad!
            array('<br>', false),
            array('test<br>', false),
            array('test <br>', false),
            array('test <br/>', false),
            array('test < br >', false),
            array('<br/>test', false),
            array('</br>test', false),
            array('<a href="test">test', false),
            array('<a href="test">test</a>', false)
        );
    }

    /**
     * @dataProvider limitHtmlDataProvider
     */
    public function testLimitHtml($value, $expected)
    {
        $this->validator->setRules(array(
            'somefield' => 'limitHtml[i,b,em,strong,code,sup,sub,span,br]'
        ));

        $result = $this->validator->validate(array('somefield' => $value));
        $this->assertEquals($expected, $result->isValid());
    }

    public function limitHtmlDataProvider()
    {
        return array(
            // good!
            array('test', true),
            array('<b>test<strong>', true),
            array('<b>test</b>', true),
            array('<i>test</i>', true),
            array('<em>test</em>', true),
            array('<strong>test</strong>', true),
            array('<code>test</code>', true),
            array('e=mc<sup>2</sup>', true),
            array('<sub>sub</sub>script', true),
            array('here is a <span test="test">span</span>', true),
            array('xhtml linebreak <br/>', true),

            // bad!
            array('check out my sweet <blink>blog post</blink>', false),
            array('<script>fun javascript</script>', false),
            array('other <bad> tags', false),
        );
    }
}

// EOF
