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
use ExpressionEngine\Service\Model\Query\Builder;
use ExpressionEngine\Service\Model\Query\Count;
use ExpressionEngine\Service\Model\DataStore;
use PHPUnit\Framework\TestCase;

class CountTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testRunReturnsCountFromBuiltQuery()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $query = m::mock();
        $query->shouldReceive('count_all_results')->once()->andReturn(7);

        $count = new CountTestStub($store, $builder, $query);

        $this->assertSame(7, $count->run());
    }
}

class CountTestStub extends Count
{
    private $query;

    public function __construct(DataStore $store, Builder $builder, $query)
    {
        parent::__construct($store, $builder);
        $this->query = $query;
    }

    protected function buildQuery()
    {
        return $this->query;
    }
}

// EOF
