<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\BooleanExpression;
use EllisLab\ExpressionEngine\Library\Parser\Conditional\Token;
use PHPUnit\Framework\TestCase;

class BooleanExpressionTest extends TestCase {

	private $expr;

	public function setUp()
	{
		$this->expr = new BooleanExpression();
	}

	/**
	 * @dataProvider truthyDataProvider
	 */
	public function testTruthy($token)
	{
		$this->expr->add($token);
		$this->assertTrue($this->expr->evaluate());
	}

	/**
	 * @dataProvider truthyDataProvider
	 */
	public function testTruthyEqualsTrue($token)
	{
		$this->expr->add($token);
		$this->expr->add(new Token\Operator('=='));
		$this->expr->add(new Token\Boolean('TRUE'));

		$this->assertTrue($this->expr->evaluate());
	}

	/**
	 * @dataProvider truthyDataProvider
	 */
	public function testTruthyNotEqualsFalse($token)
	{
		$this->expr->add($token);
		$this->expr->add(new Token\Operator('!='));
		$this->expr->add(new Token\Boolean('FALSE'));

		$this->assertTrue($this->expr->evaluate());
	}

	/**
	 * @dataProvider falseyDataProvider
	 */
	public function testFalsey($token)
	{
		$this->expr->add($token);
		$this->assertFalse($this->expr->evaluate());
	}

	/**
	 * @dataProvider falseyDataProvider
	 */
	public function testFalseyEqualsFalse($token)
	{
		$this->expr->add($token);
		$this->expr->add(new Token\Operator('=='));
		$this->expr->add(new Token\Boolean('FALSE'));

		$this->assertTrue($this->expr->evaluate());
	}

	/**
	 * @dataProvider falseyDataProvider
	 */
	public function testFalseyEqualsZero($token)
	{
		$this->expr->add($token);
		$this->expr->add(new Token\Operator('=='));
		$this->expr->add(new Token\Number(0));

		$this->assertTrue($this->expr->evaluate());
	}

	/**
	* @dataProvider falseyDataProvider
	*/
	public function testFalseyEqualsEmptyString($token)
	{
		$this->expr->add($token);
		$this->expr->add(new Token\Operator('=='));
		$this->expr->add(new Token\StringLiteral(''));

		$this->assertTrue($this->expr->evaluate());
	}

	/**
	 * @dataProvider falseyDataProvider
	 */
	public function testFalseyNotEqualsTrue($token)
	{
		$this->expr->add($token);
		$this->expr->add(new Token\Operator('!='));
		$this->expr->add(new Token\Boolean('TRUE'));

		$this->assertTrue($this->expr->evaluate());
	}

	public function truthyDataProvider()
	{
		return array(
			array(new Token\StringLiteral('ee')),
			array(new Token\StringLiteral('0')),
			array(new Token\Number(1)),
			array(new Token\Number(0.001)),
			array(new Token\Boolean('TRUE')),
		);
	}

	public function falseyDataProvider()
	{
		return array(
			array(new Token\StringLiteral('')),
			array(new Token\Number(0)),
			array(new Token\Number(0.0)),
			array(new Token\Boolean('FALSE')),
		);
	}
}

// EOF
