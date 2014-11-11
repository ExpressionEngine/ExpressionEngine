<?php
namespace EllisLab\Tests\ExpressionEngine\Service;

use EllisLab\ExpressionEngine\Service\DependencyInjectionContainer;
use EllisLab\ExpressionEngine\Service\DependencyInjectionBindingDecorator;

class DependencyInjectionBindingDecoratorTest extends \PHPUnit_Framework_TestCase {

	protected $di;

	protected function setUp()
	{
		$this->di = new DependencyInjectionContainer;
		$this->di->register('Bird', function($di) { return 'Crow'; });
		$this->di->register('Flock', function($di)
		{
			$bird = $di->make('Bird');
			return 'Flock of ' . $bird . 's.';
		});
	}

	protected function tearDown()
	{
		$this->di = NULL;
	}

	public function testBindReturnsADecoratorInstance()
	{
		$di = $this->di->bind('Bird', 'Raven');
		$this->assertInstanceOf('EllisLab\ExpressionEngine\Service\DependencyInjectionBindingDecorator', $di);
	}

	public function testSimpleBinds()
	{
		$tests = array();

		// Bind something already registered and make it
		$value = $this->di->bind('Bird', 'Raven')->make('Bird');
		$this->assertEquals('Raven', $value, 'Bindings override registered values');

		// Bind something not already registered and make it
		$value = $this->di->bind('Mammal', 'Whale')->make('Mammal');
		$this->assertEquals('Whale', $value, 'Can bind something not registered');

		// Bind something that depends on something registered
		$value = $this->di->bind('Flock', function($di)
		{
			$bird = $this->di->make('Bird');
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

		// Bind a dependency of a singleton

		// Bind and consume a singleton
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

}