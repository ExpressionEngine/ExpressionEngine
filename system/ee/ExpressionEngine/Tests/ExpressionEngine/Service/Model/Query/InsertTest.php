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

use ExpressionEngine\Service\Model\DataStore;
use ExpressionEngine\Service\Model\Query\Builder;
use ExpressionEngine\Service\Model\Query\Insert;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class InsertTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testRunEmitsInsertLifecycleEventsAndMarksObjectClean()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $object = m::mock();

        $builder->shouldReceive('getExisting')->once()->andReturn($object);
        $object->shouldReceive('emit')->once()->with('beforeSave')->ordered();
        $object->shouldReceive('emit')->once()->with('beforeInsert')->ordered();
        $object->shouldReceive('markAsClean')->once()->ordered();
        $object->shouldReceive('emit')->once()->with('afterInsert')->ordered();
        $object->shouldReceive('emit')->once()->with('afterSave')->ordered();

        $query = new InsertRunProxy($store, $builder, 123);
        $query->run();

        $this->assertSame(1, $query->doWorkCalls);
    }

    public function testDoWorkRunsParentUpdateAndSetsReturnedInsertId()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $metadata = m::mock();
        $gateway = m::mock();
        $rawInsert = m::mock();
        $rawId = m::mock();

        $builder->shouldReceive('getSet')->once()->andReturn(array('title' => 'from-builder'));

        $store->shouldReceive('getMetaDataReader')->once()->with('ee:Entry')->andReturn($metadata);
        $metadata->shouldReceive('getGateways')->once()->andReturn(array($gateway));

        $gateway->shouldReceive('fill')->once()->with(array('title' => 'dirty'));
        $gateway->shouldReceive('getValues')->once()->andReturn(array('entry_id' => 10, 'title' => 'dirty'));
        $gateway->shouldReceive('getPrimaryKey')->once()->andReturn('entry_id');
        $gateway->shouldReceive('getTableName')->once()->andReturn('exp_channel_titles');

        $rawInsert->shouldReceive('set')->once()->with(array('title' => 'dirty'))->andReturnSelf();
        $rawInsert->shouldReceive('insert')->once()->with('exp_channel_titles');
        $rawId->shouldReceive('insert_id')->once()->andReturn(99);
        $store->shouldReceive('rawQuery')->twice()->andReturn($rawInsert, $rawId);

        $object = new InsertObjectStub('ee:Entry', array('title' => 'dirty'));
        $query = new InsertAccessProxy($store, $builder);

        $insert_id = $query->doWorkPublic($object);

        $this->assertSame(99, $insert_id);
        $this->assertSame(99, $object->getId());
        $this->assertSame('from-builder', $object->title);
        $this->assertSame(99, $query->getInsertIdPublic());
    }

    public function testSetInsertIdOnlyTakesFirstValue()
    {
        $query = new InsertAccessProxy(m::mock(DataStore::class), m::mock(Builder::class));

        $query->setInsertIdPublic(7);
        $query->setInsertIdPublic(8);

        $this->assertSame(7, $query->getInsertIdPublic());
    }

    public function testActOnGatewayKeepsPrimaryKeyForMemberObjects()
    {
        $store = m::mock(DataStore::class);
        $gateway = m::mock();
        $rawInsert = m::mock();
        $rawId = m::mock();

        $gateway->shouldReceive('getValues')->once()->andReturn(array('member_id' => 5, 'screen_name' => 'bob'));
        $gateway->shouldReceive('getPrimaryKey')->once()->andReturn('member_id');
        $gateway->shouldReceive('getTableName')->once()->andReturn('exp_members');

        $rawInsert->shouldReceive('set')->once()->with(array('member_id' => 5, 'screen_name' => 'bob'))->andReturnSelf();
        $rawInsert->shouldReceive('insert')->once()->with('exp_members');
        $rawId->shouldReceive('insert_id')->once()->andReturn(44);
        $store->shouldReceive('rawQuery')->twice()->andReturn($rawInsert, $rawId);

        $query = new InsertAccessProxy($store, m::mock(Builder::class));
        $query->actOnGatewayPublic($gateway, new InsertObjectStub('ee:Member', array()));

        $this->assertSame(44, $query->getInsertIdPublic());
    }

    public function testActOnGatewayUsesExistingInsertIdOnSubsequentGatewayWork()
    {
        $store = m::mock(DataStore::class);
        $gateway = m::mock();
        $rawInsert = m::mock();
        $rawId = m::mock();

        $gateway->shouldReceive('getValues')->once()->andReturn(array('entry_id' => 1, 'title' => 'post'));
        $gateway->shouldReceive('getPrimaryKey')->once()->andReturn('entry_id');
        $gateway->shouldReceive('getTableName')->once()->andReturn('exp_channel_titles');

        $rawInsert->shouldReceive('set')->once()->with(array('entry_id' => 55, 'title' => 'post'))->andReturnSelf();
        $rawInsert->shouldReceive('insert')->once()->with('exp_channel_titles');
        $rawId->shouldReceive('insert_id')->once()->andReturn(999);
        $store->shouldReceive('rawQuery')->twice()->andReturn($rawInsert, $rawId);

        $query = new InsertAccessProxy($store, m::mock(Builder::class));
        $query->setInsertIdPublic(55);
        $query->actOnGatewayPublic($gateway, new InsertObjectStub('ee:Entry', array()));

        $this->assertSame(55, $query->getInsertIdPublic());
    }
}

class InsertAccessProxy extends Insert
{
    public function doWorkPublic($object)
    {
        return $this->doWork($object);
    }

    public function setInsertIdPublic($id)
    {
        $this->setInsertId($id);
    }

    public function actOnGatewayPublic($gateway, $object)
    {
        $this->actOnGateway($gateway, $object);
    }

    public function getInsertIdPublic()
    {
        return $this->insert_id;
    }
}

class InsertRunProxy extends InsertAccessProxy
{
    public $doWorkCalls = 0;
    private $doWorkResult;

    public function __construct(DataStore $store, Builder $builder, $do_work_result)
    {
        parent::__construct($store, $builder);
        $this->doWorkResult = $do_work_result;
    }

    public function doWork($object)
    {
        $this->doWorkCalls++;

        return $this->doWorkResult;
    }
}

class InsertObjectStub
{
    public $title;

    private $name;
    private $dirty;
    private $id;

    public function __construct($name, array $dirty)
    {
        $this->name = $name;
        $this->dirty = $dirty;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDirty()
    {
        return $this->dirty;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}

// EOF
