<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Validation;

use EllisLab\ExpressionEngine\Service\Validation\Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase {

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
}