<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Model;

use Mockery as m;
use ExpressionEngine\Service\Database\Database;
use ExpressionEngine\Service\Model\Configuration;
use ExpressionEngine\Service\Model\DataStore;
use ExpressionEngine\Service\Model\Facade;
use ExpressionEngine\Service\Model\Query\Builder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DataStoreTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testMakeWithAliasCreatesModelSetsNameAndInitializesAssociations()
    {
        $store = $this->newWorkflowStore();
        $model = new DataStoreMakeModelStub();
        $store->newModelFromAliasResult = $model;
        $facade = new Facade(m::mock(DataStore::class));

        $result = $store->make('member', $facade, array('title' => 'hello'));

        $this->assertSame($model, $result);
        $this->assertSame(array('member'), $store->newModelFromAliasCalls);
        $this->assertSame('ee:member', $model->getName());
        $this->assertSame($facade, $model->getModelFacade());
        $this->assertSame('hello', $model->title);
        $this->assertSame($model, $store->initializedModel);
    }

    public function testMakeWithExistingModelDoesNotResetName()
    {
        $store = $this->newWorkflowStore();
        $model = new DataStoreMakeModelStub();
        $model->setName('ee:existing');
        $facade = new Facade(m::mock(DataStore::class));

        $result = $store->make($model, $facade, array('title' => 'updated'));

        $this->assertSame($model, $result);
        $this->assertSame(array(), $store->newModelFromAliasCalls);
        $this->assertSame('ee:existing', $model->getName());
        $this->assertSame('updated', $model->title);
        $this->assertSame($model, $store->initializedModel);
    }

    public function testGetBuildsBuilderForStringAndModel()
    {
        $store = $this->newWorkflowStore();

        $first = $store->get('Entry');
        $this->assertInstanceOf(Builder::class, $first);
        $this->assertSame('Entry', $first->getFrom());
        $this->assertNull($first->getExisting());

        $model = new DataStoreMakeModelStub();
        $model->setName('ee:Entry');
        $second = $store->get($model);
        $this->assertSame('ee:Entry', $second->getFrom());
        $this->assertSame($model, $second->getExisting());
    }

    public function testQueryWrappersDispatchToRunQuery()
    {
        $store = $this->newWorkflowStore();
        $builder = new Builder('Entry');

        $this->assertSame('query:Select', $store->selectQuery($builder));
        $this->assertSame('query:Insert', $store->insertQuery($builder));
        $this->assertSame('query:Update', $store->updateQuery($builder));
        $this->assertSame('query:Delete', $store->deleteQuery($builder));
        $this->assertSame('query:Count', $store->countQuery($builder));
        $this->assertSame(array('Select', 'Insert', 'Update', 'Delete', 'Count'), $store->runQueryCalls);
    }

    public function testRawQueryGraphAndRegistryDelegation()
    {
        $db = m::mock(Database::class);
        $db->shouldReceive('newQuery')->once()->andReturn('raw-query');

        $store = $this->newAccessStore($db);
        $registry = m::mock('ExpressionEngine\Service\Model\Registry');
        $graph = m::mock('ExpressionEngine\Service\Model\RelationGraph');
        $relation = m::mock('ExpressionEngine\Service\Model\Relation\Relation');

        $registry->shouldReceive('getMetaDataReader')->once()->with('Entry')->andReturn('reader');
        $graph->shouldReceive('getAll')->once()->with('Entry')->andReturn(array('x' => 'y'));
        $graph->shouldReceive('getInverse')->once()->with($relation)->andReturn('inverse');
        $graph->shouldReceive('get')->once()->with('Entry', 'Author')->andReturn('relation');

        $this->setPrivate($store, 'registry', $registry);
        $this->setPrivate($store, 'graph', $graph);

        $this->assertSame('raw-query', $store->rawQuery());
        $this->assertSame($graph, $store->getGraph());
        $this->assertSame('reader', $store->getMetaDataReader('Entry'));
        $this->assertSame(array('x' => 'y'), $store->getAllRelations('Entry'));
        $this->assertSame('inverse', $store->getInverseRelation($relation));
        $this->assertSame('relation', $store->getRelation('Entry', 'Author'));
    }

    public function testInitializeAssociationsOn()
    {
        $store = $this->newAccessStore();
        $model = new DataStoreMakeModelStub();
        $model->setName('ee:Entry');

        $assoc = m::mock('ExpressionEngine\\Service\\Model\\Association\\Association');
        $assoc->shouldReceive('setFacade')->once()->withAnyArgs();
        $assoc->shouldReceive('getForeignKey')->once()->andReturn('id');
        $assoc->shouldReceive('isBooted')->once()->andReturn(true);
        $relation = m::mock('ExpressionEngine\Service\Model\Relation\Relation');
        $relation->shouldReceive('createAssociation')->once()->andReturn($assoc);

        $graph = m::mock('ExpressionEngine\Service\Model\RelationGraph');
        $graph->shouldReceive('getAll')->once()->with('ee:Entry')->andReturn(array('Author' => $relation));
        $this->setPrivate($store, 'graph', $graph);

        $store->initializeAssociationsOnPublic($model);

        $this->assertTrue($model->hasAssociation('Author'));
        $this->assertSame($assoc, $model->getAssociation('Author'));
    }

    public function testNewModelFromAliasClosureAndClassAndMissingClass()
    {
        $store = $this->newAccessStore();
        $registry = m::mock('ExpressionEngine\Service\Model\Registry');
        $this->setPrivate($store, 'registry', $registry);

        $closure_model = new DataStoreMakeModelStub();
        $registry->shouldReceive('expandAlias')->once()->with('closure')->andReturn(function () use ($closure_model) {
            return $closure_model;
        });
        $this->assertSame($closure_model, $store->newModelFromAliasPublic('closure'));

        $registry->shouldReceive('expandAlias')->once()->with('class')->andReturn(DataStoreAliasModelStub::class);
        $instance = $store->newModelFromAliasPublic('class');
        $this->assertInstanceOf(DataStoreAliasModelStub::class, $instance);

        $registry->shouldReceive('expandAlias')->once()->with('missing')->andReturn('ExpressionEngine\\Tests\\Service\\Model\\NopeClass');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Class "ExpressionEngine\\Tests\\Service\\Model\\NopeClass" not found');
        $store->newModelFromAliasPublic('missing');
    }

    public function testRunQueryCreatesAndRunsWorker()
    {
        $store = $this->newAccessStore();
        $builder = new Builder('Entry');

        $this->assertSame('dummy-run', $store->runQueryPublic('Dummy', $builder));
    }

    private function newWorkflowStore()
    {
        return new DataStoreWorkflowProxy($this->mockDatabase(), $this->mockConfiguration());
    }

    private function newAccessStore($db = null)
    {
        return new DataStoreAccessProxy($db ?: $this->mockDatabase(), $this->mockConfiguration());
    }

    private function mockDatabase()
    {
        return m::mock(Database::class);
    }

    private function mockConfiguration()
    {
        $config = m::mock(Configuration::class);
        $config->shouldReceive('getModelAliases')->andReturn(array());
        $config->shouldReceive('getDefaultPrefix')->andReturn('ee');
        $config->shouldReceive('getEnabledPrefixes')->andReturn(array('ee'));
        $config->shouldReceive('getModelDependencies')->andReturn(array());

        return $config;
    }

    private function setPrivate($object, $property, $value)
    {
        $ref = new ReflectionClass(DataStore::class);
        $prop = $ref->getProperty($property);
        \TestReflectionHelper::makePropertyAccessible($prop);
        $prop->setValue($object, $value);
    }
}

class DataStoreWorkflowProxy extends DataStore
{
    public $newModelFromAliasResult;
    public $newModelFromAliasCalls = array();
    public $initializedModel;
    public $runQueryCalls = array();

    protected function newModelFromAlias($name)
    {
        $this->newModelFromAliasCalls[] = $name;

        return $this->newModelFromAliasResult;
    }

    protected function initializeAssociationsOn(\ExpressionEngine\Service\Model\Model $model)
    {
        $this->initializedModel = $model;
    }

    protected function runQuery($name, Builder $qb)
    {
        $this->runQueryCalls[] = $name;

        return 'query:' . $name;
    }
}

class DataStoreAccessProxy extends DataStore
{
    public function initializeAssociationsOnPublic(\ExpressionEngine\Service\Model\Model $model)
    {
        $this->initializeAssociationsOn($model);
    }

    public function newModelFromAliasPublic($name)
    {
        return $this->newModelFromAlias($name);
    }

    public function runQueryPublic($name, Builder $qb)
    {
        return $this->runQuery($name, $qb);
    }
}

class DataStoreMakeModelStub extends \ExpressionEngine\Service\Model\Model
{
    protected static $_primary_key = 'id';

    protected $id;
    protected $title;
}

class DataStoreAliasModelStub extends \ExpressionEngine\Service\Model\Model
{
    protected static $_primary_key = 'id';

    protected $id;
}

namespace ExpressionEngine\Service\Model\Query;

class Dummy extends Query
{
    public function run()
    {
        return 'dummy-run';
    }
}

// EOF
