<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Service\Dependency;

use EllisLab\ExpressionEngine\Service\Dependency\InjectionContainer;
use EllisLab\ExpressionEngine\Service\Dependency\InjectionBindingDecorator;
use PHPUnit\Framework\TestCase;

class InjectionBindingDecoratorTest extends TestCase {

	protected $di;

	protected function setUp()
	{
		$this->di = new InjectionContainer;
		$this->di->register('Bird', function($di) { return 'Crow'; });
		$this->di->register('Flock', function($di)
		{
			$bird = $di->make('Bird');
			return 'Flock of ' . $bird . 's.';
		});
		$this->di->registerSingleton('One', function($di) { return 'Ein'; });
		$this->di->registerSingleton('Dinner', function($di) {
			$bird = $di->make('Bird');
			return 'Time to eat ' . $bird;
		});
	}

	protected function tearDown()
	{
		$this->di = NULL;
	}

	public function testBindReturnsADecoratorInstance()
	{
		$di = $this->di->bind('Bird', 'Raven');
		$this->assertInstanceOf('EllisLab\ExpressionEngine\Service\Dependency\InjectionBindingDecorator', $di);
	}

	public function testBindingAClosure()
	{
		$value = $this->di->bind('Bird', function($di) { return 'Raven'; })->make('Bird');
		$this->assertEquals('Raven', $value, 'Can bind a Closure');
	}

	public function testBindingAScalar()
	{
		$value = $this->di->bind('Bird', 'Raven')->make('Bird');
		$this->assertEquals('Raven', $value, 'Can bind a scalar');
	}

	public function testSimpleBinds()
	{
		// Bind something already registered and make it
		$value = $this->di->bind('Bird', 'Raven')->make('Bird');
		$this->assertEquals('Raven', $value, 'Bindings override registered values');

		// Bind something not already registered and make it
		$value = $this->di->bind('Mammal', 'Whale')->make('Mammal');
		$this->assertEquals('Whale', $value, 'Can bind something not registered');

		// Bind something that depends on something registered
		$value = $this->di->bind('Flock', function($di)
		{
			$bird = $di->make('Bird');
			return 'Murder of ' . $bird . 's.';
		})->make('Flock');
		$this->assertEquals('Murder of Crows.', $value, 'A binding can make something registered');

		// Bind a dependency of something registered
		$value = $this->di->bind('Bird', 'Raven')->make('Flock');
		$this->assertEquals('Flock of Ravens.', $value, 'Can bind a dependency of a registered value');
	}

	public function testSingletons()
	{
		// Bind over a singleton
		$value = $this->di->bind('One', 'Uno')->make('One');
		$this->assertEquals('Uno', $value, 'Bindings override registered singleton values');

		// Bind a dependency of a singleton before singleton was cached
		$value = $this->di->bind('Bird', 'Blackbird')->make('Dinner');
		$this->assertEquals('Time to eat Crow', $value, 'Singletons ignore binds on first execution');

		// Bind a dependency of a singleton after singleton was cached
		$value = $this->di->bind('Bird', 'Blackbird')->make('Dinner');
		$this->assertEquals('Time to eat Crow', $value, 'Singletons ignore binds on subsequent execution');

		// Bind and consume a singleton
		$value = $this->di->bind('Punny', function($di)
		{
			$one = $di->make('One');
			return 'Albert ' . $one . 'stein!';
		})->make('Punny');
		$this->assertEquals('Albert Einstein!', $value, 'A binding can consume registered singletons');
	}

	public function testChainedBinds()
	{
		// Bind -> Bind -> Make returns second Bind
		$value = $this->di
			->bind('Bird', 'Raven')
			->bind('Bird', 'Seagull')
			->make('Bird');
		$this->assertEquals('Seagull', $value, 'Can bind something already bound');

		// Bind without make will not persist
		$this->di->bind('Bird', 'Raven');
		$this->assertEquals('Crow', $this->di->make('Bird'), 'Binds do not persist');

		// Bind -> Register -> Make will return Bind
		$value = $this->di
			->bind('Mammal', 'Whale')
			->register('Mammal', 'Bat')
			->make('Mammal');
		$this->assertEquals($value, 'Whale', 'Bind + Register returns bind');

		// Bind -> Register, the Register will persist
		$this->assertEquals( 'Bat',$this->di->make('Mammal'), 'Chaining a register after a bind persists the register');
	}

	/**
	 * @expectedException Exception
	 */
	public function testReregisteringAfterBind()
	{
		$this->di->bind('Bird', 'Raven')->register('Bird', 'Seagull');
	}

	/**
	 * @expectedException Exception
	 */
	public function testReregisteringASingletonAfterBind()
	{
		$this->di->bind('One', 'Uno')->registerSingleton('One', 'Ichi');
	}

}
