<?php

namespace EllisLab\Tests\ExpressionEngine\Service\Model;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Model\Frontend;

class FrontendTest extends \PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testGet()
	{
		$store = m::mock('EllisLab\ExpressionEngine\Service\Model\DataStore');
		$qb = m::mock('EllisLab\ExpressionEngine\Service\Model\Query\Builder');

		$frontend = new Frontend($store);

		$store->shouldReceive('get')->with('TestModel')->andReturn($qb);
		$qb->shouldReceive('setFrontend')->with($frontend);

		$result = $frontend->get('TestModel');

		$this->assertSame($qb, $result);
	}

	public function testMakeWithString()
	{

		$store = m::mock('EllisLab\ExpressionEngine\Service\Model\DataStore');
		$result = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');

		$frontend = new Frontend($store);

		$store->shouldReceive('make')
			->with('TestModel', $frontend, array())
			->andReturn($result);

		$this->assertSame($result, $frontend->make('TestModel'));
	}

	public function testMakeWithExisting()
	{
		$store = m::mock('EllisLab\ExpressionEngine\Service\Model\DataStore');
		$result = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');

		$frontend = new Frontend($store);

		$store
			->shouldReceive('make')
			->with($result, $frontend, array())
			->andReturn($result);

		$this->assertSame($result, $frontend->make($result));
	}

	public function testMakeWithData()
	{
		$store = m::mock('EllisLab\ExpressionEngine\Service\Model\DataStore');
		$result = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');

		$frontend = new Frontend($store);
		$data = array('foo' => 'bar');

		$store
			->shouldReceive('make')
			->with('TestModel', $frontend, $data)
			->andReturn($result);

		$this->assertSame($result, $frontend->make('TestModel', $data));
	}
}