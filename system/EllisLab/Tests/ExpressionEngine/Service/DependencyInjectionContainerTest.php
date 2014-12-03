<?php
namespace EllisLab\Tests\ExpressionEngine\Service;

use \stdClass;
use EllisLab\ExpressionEngine\Service\DependencyInjectionContainer;

class DependencyInjectionContainerTest extends \PHPUnit_Framework_TestCase {

	protected $di;

	protected function setUp()
	{
		$this->di = new DependencyInjectionContainer;
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
		$this->assertInstanceOf('EllisLab\ExpressionEngine\Service\DependencyInjectionContainer', $di);
		$this->assertSame($this->di, $di);

		$di = $this->di->registerSingleton('Bar', 'Baz');
		$this->assertInstanceOf('EllisLab\ExpressionEngine\Service\DependencyInjectionContainer', $di);
		$this->assertSame($this->di, $di);

		$di = $this->di->bind('Foo', 'Bar');
		$this->assertInstanceOf('EllisLab\ExpressionEngine\Service\DependencyInjectionBindingDecorator', $di);
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