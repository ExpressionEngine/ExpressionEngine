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
		$this->assertEquals(1, count($result->getErrors('a')));

		$result = $this->validator->validate(array('a' => 'foo-ey'));
		$this->assertEquals(2, count($result->getErrors('a')));

		$result = $this->validator->validate(array('a' => 'not good++'));
		$this->assertEquals(3, count($result->getErrors('a')));
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
		$this->assertEquals(3, count($result->getErrors('a')));

		$result = $this->validator->validate(array('a' => ''));
		$this->assertEquals(1, count($result->getErrors('a')));
	}

	public function testWhenPresent()
	{
		$rules = array(
			'nickname' => 'whenPresent|min_length[5]',
			'email' => 'whenPresent[newsletter]|required|email'
		);
		$this->validator->setRules($rules);

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
}