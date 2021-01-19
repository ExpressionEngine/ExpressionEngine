<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Model;

use Mockery as m;
use ExpressionEngine\Service\Model\Facade;
use PHPUnit\Framework\TestCase;

class FacadeTest extends TestCase
{
    public function tearDown() : void
    {
        m::close();
    }

    public function testGet()
    {
        $store = m::mock('ExpressionEngine\Service\Model\DataStore');
        $qb = m::mock('ExpressionEngine\Service\Model\Query\Builder');

        $facade = new Facade($store);

        $store->shouldReceive('get')->with('TestModel')->andReturn($qb);
        $qb->shouldReceive('setFacade')->with($facade);

        $result = $facade->get('TestModel');

        $this->assertSame($qb, $result);
    }

    public function testMakeWithString()
    {
        $store = m::mock('ExpressionEngine\Service\Model\DataStore');
        $result = m::mock('ExpressionEngine\Service\Model\Model');

        $facade = new Facade($store);

        $store->shouldReceive('make')
            ->with('TestModel', $facade, array())
            ->andReturn($result);

        $this->assertSame($result, $facade->make('TestModel'));
    }

    public function testMakeWithExisting()
    {
        $store = m::mock('ExpressionEngine\Service\Model\DataStore');
        $result = m::mock('ExpressionEngine\Service\Model\Model');

        $facade = new Facade($store);

        $store
            ->shouldReceive('make')
            ->with($result, $facade, array())
            ->andReturn($result);

        $this->assertSame($result, $facade->make($result));
    }

    public function testMakeWithData()
    {
        $store = m::mock('ExpressionEngine\Service\Model\DataStore');
        $result = m::mock('ExpressionEngine\Service\Model\Model');

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
