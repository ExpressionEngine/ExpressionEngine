<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Service\Validation;

use EllisLab\ExpressionEngine\Service\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase {

	public function setUp()
	{
		$this->validator = new Validator();
	}

	public function tearDown()
	{
		$this->validator = NULL;
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
		$result = $this->validator->validate(array('a' => FALSE));
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
			array('5', TRUE),
			array('-6', TRUE),
			array('+6', TRUE),
			array('0', TRUE),
			array('-0', TRUE),
			array('+0', TRUE),
			array('.6', TRUE),
			array('-.6', TRUE),
			array('+.6', TRUE),
			array('8.', TRUE),
			array('-8.', TRUE),
			array('+8.', TRUE),
			array('8.23', TRUE),
			array('-8.23', TRUE),
			array('+8.23', TRUE),

			// bad!
			array('fortran', FALSE),
			array('2.8.4', FALSE),
			array('2-4', FALSE),
			array('2e4', FALSE),
			array('0x24', FALSE)
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
			array('000', TRUE),
			array('fff', TRUE),
			array('FFF', TRUE),
			array('abc', TRUE),
			array('123', TRUE),
			array('000000', TRUE),
			array('ffffff', TRUE),
			array('FFFFFF', TRUE),
			array('AABBCC', TRUE),
			array('112233', TRUE),
			array('ABCDEF', TRUE),
			array('A1B2E3', TRUE),

			// bad!
			array('#fff', FALSE),
			array('#FFFFFF', FALSE),
			array('KEVIN', FALSE),
			array('f', FALSE),
			array('ff', FALSE),
			array('ffff', FALSE),
			array('fffff', FALSE)
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
			array('test', TRUE),
			array('some text @##%#$$%&%^*', TRUE),
			array('> some text <', TRUE),
			array('tests > no tests', TRUE),
			array('test < something', TRUE),

			// bad!
			array('<br>', FALSE),
			array('test<br>', FALSE),
			array('test <br>', FALSE),
			array('test <br/>', FALSE),
			array('test < br >', FALSE),
			array('<br/>test', FALSE),
			array('</br>test', FALSE),
			array('<a href="test">test', FALSE),
			array('<a href="test">test</a>', FALSE)
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
			array('test', TRUE),
			array('<b>test<strong>', TRUE),
			array('<b>test</b>', TRUE),
			array('<i>test</i>', TRUE),
			array('<em>test</em>', TRUE),
			array('<strong>test</strong>', TRUE),
			array('<code>test</code>', TRUE),
			array('e=mc<sup>2</sup>', TRUE),
			array('<sub>sub</sub>script', TRUE),
			array('here is a <span test="test">span</span>', TRUE),
			array('xhtml linebreak <br/>', TRUE),

			// bad!
			array('check out my sweet <blink>blog post</blink>', FALSE),
			array('<script>fun javascript</script>', FALSE),
			array('other <bad> tags', FALSE),
		);
	}
}

// EOF
