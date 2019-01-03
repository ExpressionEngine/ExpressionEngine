<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Tests\ExpressionEngine\Service\Model;

use Mockery as m;
use EllisLab\ExpressionEngine\Service\Model\Facade;
use PHPUnit\Framework\TestCase;

class FacadeTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testGet()
	{
		$store = m::mock('EllisLab\ExpressionEngine\Service\Model\DataStore');
		$qb = m::mock('EllisLab\ExpressionEngine\Service\Model\Query\Builder');

		$facade = new Facade($store);

		$store->shouldReceive('get')->with('TestModel')->andReturn($qb);
		$qb->shouldReceive('setFacade')->with($facade);

		$result = $facade->get('TestModel');

		$this->assertSame($qb, $result);
	}

	public function testMakeWithString()
	{

		$store = m::mock('EllisLab\ExpressionEngine\Service\Model\DataStore');
		$result = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');

		$facade = new Facade($store);

		$store->shouldReceive('make')
			->with('TestModel', $facade, array())
			->andReturn($result);

		$this->assertSame($result, $facade->make('TestModel'));
	}

	public function testMakeWithExisting()
	{
		$store = m::mock('EllisLab\ExpressionEngine\Service\Model\DataStore');
		$result = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');

		$facade = new Facade($store);

		$store
			->shouldReceive('make')
			->with($result, $facade, array())
			->andReturn($result);

		$this->assertSame($result, $facade->make($result));
	}

	public function testMakeWithData()
	{
		$store = m::mock('EllisLab\ExpressionEngine\Service\Model\DataStore');
		$result = m::mock('EllisLab\ExpressionEngine\Service\Model\Model');

		$facade = new Facade($store);
		$data = array('foo' => 'bar');

		$store
			->shouldReceive('make')
			->with('TestModel', $facade, $data)
			->andReturn($result);

		$this->assertSame($result, $facade->make('TestModel', $data));
	}
}

// EOF
