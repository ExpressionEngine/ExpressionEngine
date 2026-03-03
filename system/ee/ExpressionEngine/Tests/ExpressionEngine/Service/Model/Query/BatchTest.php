<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Model\Query;

use Mockery as m;
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Service\Model\Query\Batch;
use ExpressionEngine\Service\Model\Query\Builder;
use PHPUnit\Framework\TestCase;

class BatchTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testProcessBatchesAndClampsToConfiguredLimit()
    {
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('getOffset')->once()->andReturn(0);
        $builder->shouldReceive('getLimit')->once()->andReturn(5);

        $builder->shouldReceive('offset')->with(0)->once()->andReturnSelf();
        $builder->shouldReceive('limit')->with(2)->once()->andReturnSelf();
        $builder->shouldReceive('all')->once()->andReturn(new Collection(array('a', 'b')));

        $builder->shouldReceive('offset')->with(2)->once()->andReturnSelf();
        $builder->shouldReceive('limit')->with(2)->once()->andReturnSelf();
        $builder->shouldReceive('all')->once()->andReturn(new Collection(array('c', 'd')));

        $builder->shouldReceive('offset')->with(4)->once()->andReturnSelf();
        $builder->shouldReceive('limit')->with(1)->once()->andReturnSelf();
        $builder->shouldReceive('all')->once()->andReturn(new Collection(array('e')));

        $batch = new Batch($builder);
        $batch->setBatchSize(2);

        $items = array();
        $count = $batch->process(function ($item) use (&$items) {
            $items[] = $item;
        });

        $this->assertSame(5, $count);
        $this->assertSame(array('a', 'b', 'c', 'd', 'e'), $items);
    }

    public function testProcessStopsWhenQueryReturnsNull()
    {
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('getOffset')->once()->andReturn(0);
        $builder->shouldReceive('getLimit')->once()->andReturn(10);
        $builder->shouldReceive('offset')->with(0)->once()->andReturnSelf();
        $builder->shouldReceive('limit')->with(3)->once()->andReturnSelf();
        $builder->shouldReceive('all')->once()->andReturn(null);

        $batch = new Batch($builder);
        $batch->setBatchSize(3);

        $count = $batch->process(function () {
        });

        $this->assertSame(0, $count);
    }
}

// EOF
