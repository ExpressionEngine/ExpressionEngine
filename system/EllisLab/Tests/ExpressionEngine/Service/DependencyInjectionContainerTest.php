<?php
namespace EllisLab\Tests\ExpressionEngine\Service;

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
			$dummy = new DummyObject;
			return $dummy->getCount();
		});

		$this->assertEquals('1', $this->di->make('Dummy'), 'Singleton was made');

		$dummy = new DummyObject;
		$this->assertEquals('2', $dummy->getCount(), 'Static property changed');
		$this->assertEquals('1', $this->di->make('Dummy'), 'Singleton was not re-made');
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

class DummyObject {
	protected static $count = 0;

	public function __construct ()
	{
		self::$count++;
	}

	public function getCount()
	{
		return self::$count;
	}
}