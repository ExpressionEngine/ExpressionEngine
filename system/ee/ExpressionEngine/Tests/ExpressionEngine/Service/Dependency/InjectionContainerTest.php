<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Dependency;

use stdClass;
use ExpressionEngine\Service\Dependency\InjectionContainer;
use PHPUnit\Framework\TestCase;

class InjectionContainerTest extends TestCase {

	protected $di;

	protected function setUp()
	{
		$this->di = new InjectionContainer;
	}

	protected function tearDown()
	{
		$this->di = NULL;
	}

	public function testRegisterAClosure()
	{
		$this->di->register('Bird', function($di) { return 'Crow'; });
		$this->assertEquals('Crow', $this->di->make('Bird'), 'Can bind a Closure');
	}

	public function testRegisterAScalar()
	{
		$this->di->register('Bird', 'Crow');
		$this->assertEquals('Crow', $this->di->make('Bird'), 'Can bind a scalar');
	}

	public function testRegisterAClosureAsASingleton()
	{
		$this->di->registerSingleton('Bird', function($di) { return 'Crow'; });
		$this->assertEquals('Crow', $this->di->make('Bird'), 'Can bind a Closure');
	}

	public function testRegisterAScalarAsASingleton()
	{
		$this->di->registerSingleton('Bird', 'Crow');
		$this->assertEquals('Crow', $this->di->make('Bird'), 'Can bind a scalar');
	}

	public function testSingletons()
	{
		$this->di->registerSingleton('Dummy', function($di)
		{
		    return new stdClass();
		});

		$object1 = $this->di->make('Dummy');
		$object2 = $this->di->make('Dummy');

		$this->assertSame($object1, $object2);
	}

	public function testChaining()
	{
		$di = $this->di->register('Foo', 'Bar');
		$this->assertInstanceOf('ExpressionEngine\Service\Dependency\InjectionContainer', $di);
		$this->assertSame($this->di, $di);

		$di = $this->di->registerSingleton('Bar', 'Baz');
		$this->assertInstanceOf('ExpressionEngine\Service\Dependency\InjectionContainer', $di);
		$this->assertSame($this->di, $di);

		$di = $this->di->bind('Foo', 'Bar');
		$this->assertInstanceOf('ExpressionEngine\Service\Dependency\InjectionBindingDecorator', $di);
		$this->assertFalse($this->di === $di);
	}

	/**
	 * @expectedException Exception
	 */
	public function testMakingAnUnregisteredObject()
	{
		$this->di->make('AllTheThings');
	}

	/**
	 * @expectedException Exception
	 */
	public function testReregistering()
	{
		$this->di->register('Bird', 'Raven')->register('Bird', 'Seagull');
	}

	/**
	 * @expectedException Exception
	 */
	public function testReregisteringAsSingleton()
	{
		$this->di->register('One', 'Uno')->registerSingleton('One', 'Ichi');
	}

	/**
	 * @expectedException Exception
	 */
	public function testReregisteringOverASingleton()
	{
		$this->di->registerSingleton('One', 'Uno')->register('One', 'Ichi');
	}

}
