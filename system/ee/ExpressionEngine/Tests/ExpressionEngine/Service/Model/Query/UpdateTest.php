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
use ExpressionEngine\Service\Model\Facade;
use ExpressionEngine\Service\Model\Query\Builder;
use ExpressionEngine\Service\Model\Query\Update;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testRunReturnsEarlyWhenObjectIsNotDirty()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $object = m::mock();

        $builder->shouldReceive('getExisting')->once()->andReturn($object);
        $builder->shouldReceive('getSet')->once()->andReturn(array());

        $object->shouldReceive('getOriginal')->once()->andReturn(array('backup'));
        $object->shouldReceive('getDirty')->once()->andReturn(array());
        $object->shouldNotReceive('emit');
        $object->shouldNotReceive('markAsClean');

        $query = new UpdateRunProxy($store, $builder);
        $query->run();

        $this->assertFalse($query->doWorkCalled);
    }

    public function testRunCreatesObjectWhenMissingAndExecutesLifecycleWhenDirty()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $object = m::mock();
        $facade = new Facade(m::mock(DataStore::class));

        $builder->shouldReceive('getExisting')->once()->andReturn(null);
        $builder->shouldReceive('getFrom')->once()->andReturn('Entry');
        $builder->shouldReceive('getFacade')->once()->andReturn($facade);
        $builder->shouldReceive('getSet')->once()->andReturn(array());
        $store->shouldReceive('make')->once()->with('Entry', $facade)->andReturn($object);

        $object->shouldReceive('getOriginal')->twice()->andReturn(array('a' => 1), array('b' => 2));
        $object->shouldReceive('getDirty')->once()->andReturn(array('title' => 'changed'));
        $object->shouldReceive('emit')->once()->with('beforeUpdate', array('a' => 1));
        $object->shouldReceive('emit')->once()->with('beforeSave');
        $object->shouldReceive('markAsClean')->once();
        $object->shouldReceive('emit')->once()->with('afterSave');
        $object->shouldReceive('emit')->once()->with('afterUpdate', array('b' => 2));

        $query = new UpdateRunProxy($store, $builder);
        $query->run();

        $this->assertTrue($query->doWorkCalled);
        $this->assertSame($object, $query->doWorkObject);
    }

    public function testPrepObjectAppliesBuilderSetValues()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('getSet')->once()->andReturn(array(
            'title' => 'Alpha',
            'status' => 'open'
        ));

        $query = new UpdateAccessProxy($store, $builder);
        $object = new UpdateObjectStub('ee:Entry', array());

        $query->prepObjectPublic($object);

        $this->assertSame('Alpha', $object->title);
        $this->assertSame('open', $object->status);
    }

    public function testDoWorkReturnsWhenDirtyValuesAreEmpty()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $metadata = m::mock();
        $gateway = m::mock();

        $builder->shouldReceive('getSet')->once()->andReturn(array('title' => 'from-set'));
        $store->shouldReceive('getMetaDataReader')->once()->with('ee:Entry')->andReturn($metadata);
        $metadata->shouldReceive('getGateways')->once()->andReturn(array($gateway));
        $gateway->shouldNotReceive('fill');

        $query = new UpdateDoWorkProxy($store, $builder);
        $object = new UpdateObjectStub('ee:Entry', array());

        $query->doWorkPublic($object);

        $this->assertSame('from-set', $object->title);
        $this->assertSame(0, $query->actOnGatewayCalls);
    }

    public function testDoWorkFillsGatewaysAndDelegatesToActOnGateway()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $metadata = m::mock();
        $gateway1 = m::mock();
        $gateway2 = m::mock();

        $builder->shouldReceive('getSet')->once()->andReturn(array('title' => 'from-set'));
        $store->shouldReceive('getMetaDataReader')->once()->with('ee:Entry')->andReturn($metadata);
        $metadata->shouldReceive('getGateways')->once()->andReturn(array($gateway1, $gateway2));
        $gateway1->shouldReceive('fill')->once()->with(array('title' => 'dirty'));
        $gateway2->shouldReceive('fill')->once()->with(array('title' => 'dirty'));

        $query = new UpdateDoWorkProxy($store, $builder);
        $object = new UpdateObjectStub('ee:Entry', array('title' => 'dirty'));

        $query->doWorkPublic($object);

        $this->assertSame('from-set', $object->title);
        $this->assertSame(2, $query->actOnGatewayCalls);
        $this->assertSame(array($gateway1, $gateway2), $query->actOnGatewayGateways);
    }

    public function testActOnGatewayReturnsWhenNoDirtyValuesIntersectGatewayFields()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $gateway = m::mock();
        $object = m::mock();

        $object->shouldReceive('getDirty')->once()->andReturn(array('status' => 'closed'));
        $gateway->shouldReceive('getFieldList')->once()->andReturn(array('title'));
        $store->shouldNotReceive('rawQuery');

        $query = new UpdateActProxy($store, $builder);
        $query->actOnGatewayPublic($gateway, $object);
        $this->assertTrue($query->parentActCalled);
    }

    public function testActOnGatewayUpdatesWithPrimaryKeyForExistingObjects()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $gateway = m::mock();
        $object = m::mock();
        $queryBuilder = m::mock();

        $object->shouldReceive('getDirty')->once()->andReturn(array('title' => 'New', 'status' => 'open'));
        $object->shouldReceive('isNew')->once()->andReturn(false);
        $object->shouldReceive('getId')->once()->andReturn(42);

        $gateway->shouldReceive('getFieldList')->once()->andReturn(array('title', 'entry_id'));
        $gateway->shouldReceive('getPrimaryKey')->once()->andReturn('entry_id');
        $gateway->shouldReceive('getTableName')->once()->andReturn('exp_channel_titles');

        $store->shouldReceive('rawQuery')->once()->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('set')->once()->with(array('title' => 'New'))->andReturnSelf();
        $queryBuilder->shouldReceive('where')->once()->with('entry_id', 42)->andReturnSelf();
        $queryBuilder->shouldReceive('update')->once()->with('exp_channel_titles');

        $query = new UpdateActProxy($store, $builder);
        $query->actOnGatewayPublic($gateway, $object);
        $this->assertTrue($query->parentActCalled);
    }

    public function testActOnGatewaySkipsWhereForNewObjects()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $gateway = m::mock();
        $object = m::mock();
        $queryBuilder = m::mock();

        $object->shouldReceive('getDirty')->once()->andReturn(array('title' => 'Draft'));
        $object->shouldReceive('isNew')->once()->andReturn(true);
        $object->shouldNotReceive('getId');

        $gateway->shouldReceive('getFieldList')->once()->andReturn(array('title'));
        $gateway->shouldReceive('getTableName')->once()->andReturn('exp_channel_titles');

        $store->shouldReceive('rawQuery')->once()->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('set')->once()->with(array('title' => 'Draft'))->andReturnSelf();
        $queryBuilder->shouldNotReceive('where');
        $queryBuilder->shouldReceive('update')->once()->with('exp_channel_titles');

        $query = new UpdateActProxy($store, $builder);
        $query->actOnGatewayPublic($gateway, $object);
        $this->assertTrue($query->parentActCalled);
    }
}

class UpdateAccessProxy extends Update
{
    public function prepObjectPublic($object)
    {
        $this->prepObject($object);
    }

    public function doWorkPublic($object)
    {
        $this->doWork($object);
    }

    public function actOnGatewayPublic($gateway, $object)
    {
        $this->actOnGateway($gateway, $object);
    }
}

class UpdateRunProxy extends UpdateAccessProxy
{
    public $doWorkCalled = false;
    public $doWorkObject;

    protected function doWork($object)
    {
        $this->doWorkCalled = true;
        $this->doWorkObject = $object;
    }
}

class UpdateDoWorkProxy extends UpdateAccessProxy
{
    public $actOnGatewayCalls = 0;
    public $actOnGatewayGateways = array();

    protected function actOnGateway($gateway, $object)
    {
        $this->actOnGatewayCalls++;
        $this->actOnGatewayGateways[] = $gateway;
    }
}

class UpdateActProxy extends UpdateAccessProxy
{
    public $parentActCalled = false;

    protected function actOnGateway($gateway, $object)
    {
        $this->parentActCalled = true;

        return parent::actOnGateway($gateway, $object);
    }
}

class UpdateObjectStub
{
    public $title;
    public $status;

    private $name;
    private $dirty;

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
}

// EOF
