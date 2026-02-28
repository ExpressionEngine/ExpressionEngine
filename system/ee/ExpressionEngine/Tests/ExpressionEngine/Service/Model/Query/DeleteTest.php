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
use ExpressionEngine\Service\Model\Query\Delete;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testRunReturnsEarlyWhenParentIdsAreEmpty()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $meta = m::mock();

        $builder->shouldReceive('getFrom')->once()->andReturn('RootModel');
        $store->shouldReceive('getMetaDataReader')->once()->with('RootModel')->andReturn($meta);
        $meta->shouldReceive('getPrimaryKey')->once()->andReturn('root_id');

        $query = new DeleteRunHarness($store, $builder);
        $query->parentIds = array();

        $query->run();

        $this->assertCount(0, $query->deleteCollectionCalls);
    }

    public function testRunProcessesClosureAndBothFieldSelectionBranches()
    {
        DeleteStaticEventsModel::$events = array();

        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $facade = new DeleteRunFacadeStub();

        $rootMeta = m::mock();
        $closureMeta = m::mock();
        $eventMeta = m::mock();
        $plainMeta = m::mock();

        $builder->shouldReceive('getFrom')->once()->andReturn('RootModel');
        $builder->shouldReceive('getFacade')->times(3)->andReturn($facade);

        $store->shouldReceive('getMetaDataReader')->with('RootModel')->andReturn($rootMeta);
        $store->shouldReceive('getMetaDataReader')->with('ChildClosure')->andReturn($closureMeta);
        $store->shouldReceive('getMetaDataReader')->with('ChildEvent')->andReturn($eventMeta);
        $store->shouldReceive('getMetaDataReader')->with('ChildPlain')->andReturn($plainMeta);

        $rootMeta->shouldReceive('getPrimaryKey')->once()->andReturn('root_id');
        $rootMeta->shouldReceive('getClass')->once()->andReturn(DeleteStaticEventsModel::class);

        $closureMeta->shouldReceive('getPrimaryKey')->once()->andReturn('closure_id');
        $closureMeta->shouldReceive('getEvents')->once()->andReturn(array());
        $closureMeta->shouldReceive('publishesHooks')->once()->andReturn(false);

        $eventMeta->shouldReceive('getPrimaryKey')->once()->andReturn('event_id');
        $eventMeta->shouldReceive('getEvents')->once()->andReturn(array('beforeDelete'));
        $eventMeta->shouldReceive('publishesHooks')->once()->andReturn(false);

        $plainMeta->shouldReceive('getPrimaryKey')->once()->andReturn('plain_id');
        $plainMeta->shouldReceive('getEvents')->once()->andReturn(array());
        $plainMeta->shouldReceive('publishesHooks')->once()->andReturn(false);

        $closureQuery = new DeleteRunQueryStub('closure');
        $eventQuery = new DeleteRunQueryStub('event');
        $plainQuery = new DeleteRunQueryStub('plain');

        $facade->queries['ChildClosure AS CC'] = $closureQuery;
        $facade->queries['ChildEvent AS CE'] = $eventQuery;
        $facade->queries['ChildPlain AS CP'] = $plainQuery;

        $closureCalled = false;
        $deleteList = array(
            array(
                'ChildClosure AS CC',
                function ($query) use (&$closureCalled) {
                    $closureCalled = true;
                    $query->all();

                    return array((object) array('id' => 1));
                },
            ),
            array('ChildEvent AS CE', array('ParentPath')),
            array('ChildPlain AS CP', array('ParentPath')),
        );

        $query = new DeleteRunHarness($store, $builder);
        $query->parentIds = array(1, 2);
        $query->deleteList = $deleteList;
        $query->deleteCollectionReturn = array(1);

        $query->run();

        $this->assertTrue($closureCalled);
        $this->assertCount(3, $query->deleteCollectionCalls);

        $this->assertContains(
            array('beforeAssociationsBulkDelete', array(1, 2)),
            DeleteStaticEventsModel::$events
        );
        $this->assertContains(
            array('afterAssociationsBulkDelete', array(1, 2)),
            DeleteStaticEventsModel::$events
        );

        $this->assertContains(
            array('fields', 'CE.*'),
            $eventQuery->operations->getArrayCopy()
        );
        $this->assertContains(
            array('fields', 'CP.plain_id'),
            $plainQuery->operations->getArrayCopy()
        );
    }

    public function testDeleteCollectionReturnsEmptyArrayWhenCollectionIsEmpty()
    {
        $query = new DeleteLeafHarness(m::mock(DataStore::class), m::mock(Builder::class));
        $meta = m::mock();
        $collection = new DeleteCollectionStub(array(), array());

        $this->assertSame(array(), $query->deleteCollectionPublic($collection, $meta));
        $this->assertNull($query->leafCall);
    }

    public function testDeleteCollectionEmitsEventsAndDeletesLeafWithSiteConstraint()
    {
        DeleteCollectionStaticEventsModel::$events = array();

        $store = m::mock(DataStore::class);
        $query = new DeleteLeafHarness($store, m::mock(Builder::class));
        $meta = m::mock();
        $collection = new DeleteCollectionStub(array(10, 11), array(1, 1, 2));

        $meta->shouldReceive('getTables')->once()->andReturn(array('member_groups' => true));
        $meta->shouldReceive('getClass')->once()->andReturn(DeleteCollectionStaticEventsModel::class);

        $ids = $query->deleteCollectionPublic($collection, $meta);

        $this->assertSame(array(10, 11), $ids);
        $this->assertSame(array('beforeDelete', 'afterDelete'), $collection->events);
        $this->assertSame(array(10, 11), $query->leafCall['ids']);
        $this->assertSame(array('site_id' => array(0 => 1, 2 => 2)), $query->leafCall['extra_where']);
        $this->assertContains(
            array('beforeBulkDelete', array(10, 11)),
            DeleteCollectionStaticEventsModel::$events
        );
        $this->assertContains(
            array('afterBulkDelete', array(10, 11)),
            DeleteCollectionStaticEventsModel::$events
        );
    }

    public function testDeleteAsLeafAppliesExtraWhereAndDeletesAllMappedTables()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $reader = m::mock();
        $rawQuery = m::mock();
        $delete = new DeleteAccessProxy($store, $builder);

        $reader->shouldReceive('getTables')->once()->with(false)->andReturn(array(
            'exp_items' => true,
            'exp_items_data' => true,
        ));
        $reader->shouldReceive('getPrimaryKey')->once()->andReturn('item_id');

        $store->shouldReceive('rawQuery')->once()->andReturn($rawQuery);
        $rawQuery->shouldReceive('where_in')->once()->with('site_id', array(5, 6))->andReturnSelf();
        $rawQuery->shouldReceive('where_in')->once()->with('item_id', array(1, 2))->andReturnSelf();
        $rawQuery->shouldReceive('delete')->once()->with(array('exp_items', 'exp_items_data'));

        $delete->deleteAsLeafPublic($reader, array(1, 2), array('site_id' => array(5, 6)));
        $this->addToAssertionCount(1);
    }

    public function testGetParentIdsClonesBuilderAndReturnsPluckedIds()
    {
        $store = m::mock(DataStore::class);
        $builder = new DeleteParentIdBuilderStub('RootModel', array(4, 5));
        $delete = new DeleteAccessProxy($store, $builder);

        $ids = $delete->getParentIdsPublic('RootModel', 'root_id');

        $this->assertSame(array(4, 5), $ids);
        $this->assertSame(array('RootModel.root_id'), $builder->fieldsHistory->getArrayCopy());
        $this->assertSame(array('root_id'), $builder->pluckHistory->getArrayCopy());
    }

    public function testGetDeleteListBuildsNestedWithPathsAndAliases()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $delete = new DeleteAccessProxy($store, $builder);

        $belongsTo = m::mock(\ExpressionEngine\Service\Model\Relation\BelongsTo::class);

        $weakInverse = m::mock();
        $weakInverse->shouldReceive('getName')->andReturn('weak_back');
        $weakRelation = m::mock();
        $weakRelation->shouldReceive('isWeak')->andReturn(true);
        $weakRelation->shouldReceive('getTargetModel')->andReturn('WeakModel');
        $weakRelation->shouldReceive('getInverse')->andReturn($weakInverse);
        $weakRelation->shouldReceive('getPivot')->andReturn(array());
        $weakRelation->shouldReceive('getName')->andReturn('WeakRelation');
        $weakRelation->shouldReceive('drop')->andReturnNull();

        $forwardInverse = m::mock(\ExpressionEngine\Service\Model\Relation\BelongsTo::class);
        $forwardInverse->shouldReceive('getName')->andReturn('parent');
        $forwardRelation = m::mock();
        $forwardRelation->shouldReceive('isWeak')->andReturn(false);
        $forwardRelation->shouldReceive('getTargetModel')->andReturn('ChildModel');
        $forwardRelation->shouldReceive('getInverse')->andReturn($forwardInverse);

        $childInverse = m::mock(\ExpressionEngine\Service\Model\Relation\BelongsTo::class);
        $childInverse->shouldReceive('getName')->andReturn('parent');
        $childRecursive = m::mock();
        $childRecursive->shouldReceive('isWeak')->andReturn(false);
        $childRecursive->shouldReceive('getTargetModel')->andReturn('ChildModel');
        $childRecursive->shouldReceive('getInverse')->andReturn($childInverse);
        $childRecursive->shouldReceive('getName')->andReturn('Children');

        $store->shouldReceive('getAllRelations')->with('RootModel')->andReturn(array(
            'belongsTo' => $belongsTo,
            'weak' => $weakRelation,
            'forward' => $forwardRelation,
        ));
        $store->shouldReceive('getAllRelations')->with('ChildModel')->andReturn(array(
            'selfRecursive' => $childRecursive,
        ));

        $list = $delete->getDeleteListPublic('RootModel', 'CurrentlyDeleting');

        $this->assertNotEmpty($list);

        $hasRootAlias = false;
        $hasClosure = false;
        $hasNestedWith = false;
        foreach ($list as $entry) {
            if ($entry[0] === 'RootModel AS CurrentlyDeleting') {
                $hasRootAlias = true;
            }
            if ($entry[1] instanceof \Closure) {
                $hasClosure = true;
            }
            if (is_array($entry[1]) && ! empty($entry[1])) {
                $hasNestedWith = true;
            }
        }

        $this->assertTrue($hasRootAlias);
        $this->assertTrue($hasClosure);
        $this->assertTrue($hasNestedWith);
    }

    public function testNestReturnsEmptyArrayWhenInputIsEmpty()
    {
        $delete = new DeleteAccessProxy(m::mock(DataStore::class), m::mock(Builder::class));

        $this->assertSame(array(), $delete->nestPublic(array()));
        $this->assertSame(
            array('A' => array('B' => array('C' => array()))),
            $delete->nestPublic(array('A', 'B', 'C'))
        );
    }

    public function testRecursiveClosureFetchesWithsAndDeletesAssociations()
    {
        $store = m::mock(DataStore::class);
        $delete = new DeleteAccessProxy($store, m::mock(Builder::class));
        $relation = m::mock();
        $query = m::mock();
        $model = m::mock();
        $association = m::mock();
        $related = m::mock();

        $relation->shouldReceive('getName')->once()->andReturn('Children');
        $query->shouldReceive('with')->once()->with(array('Parent AS CurrentlyDeleting' => array()))->andReturnSelf();
        $query->shouldReceive('all')->once()->andReturn(array($model));
        $model->shouldReceive('getAssociation')->once()->with('Children')->andReturn($association);
        $association->shouldReceive('get')->once()->andReturn($related);
        $related->shouldReceive('delete')->once();

        $closure = $this->invokePrivate($delete, 'recursive', array($relation, array('Parent')));
        $result = $closure($query);

        $this->assertSame(array($model), $result);
    }

    public function testWeakClosureReturnsEmptyArrayForRolePivotDeletes()
    {
        $store = m::mock(DataStore::class);
        $delete = new DeleteAccessProxy($store, m::mock(Builder::class));
        $relation = m::mock();
        $query = m::mock();

        $relation->shouldReceive('getTargetModel')->once()->andReturn('Role');
        $relation->shouldReceive('getPivot')->once()->andReturn(array('pivot_table'));

        $closure = $this->invokePrivate($delete, 'weak', array($relation, array('Parent')));

        $this->assertSame(array(), $closure($query));
    }

    public function testWeakClosureLoadsTargetRowsAndDropsAssociations()
    {
        $store = m::mock(DataStore::class);
        $delete = new DeleteAccessProxy($store, m::mock(Builder::class));
        $relation = m::mock();
        $meta = m::mock();
        $query = m::mock();
        $model = m::mock();
        $association = m::mock();
        $target = m::mock();

        $relation->shouldReceive('getTargetModel')->times(3)->andReturn('Entry');
        $relation->shouldNotReceive('getPivot');
        $relation->shouldReceive('getName')->once()->andReturn('Entries');
        $relation->shouldReceive('drop')->once()->with($model, $target);

        $store->shouldReceive('getMetaDataReader')->once()->with('Entry')->andReturn($meta);
        $meta->shouldReceive('getPrimaryKey')->once()->andReturn('entry_id');

        $query->shouldReceive('fields')->once()->with('CurrentlyDeleting.entry_id')->andReturnSelf();
        $query->shouldReceive('with')->once()->with(array('Parent AS CurrentlyDeleting' => array()))->andReturnSelf();
        $query->shouldReceive('all')->once()->andReturn(array($model));

        $model->shouldReceive('getAssociation')->once()->with('Entries')->andReturn($association);
        $association->shouldReceive('get')->once()->andReturn($target);

        $closure = $this->invokePrivate($delete, 'weak', array($relation, array('Parent')));

        $this->assertSame(array(), $closure($query));
    }

    public function testSplitAliasReturnsModelAndAlias()
    {
        $delete = new DeleteAccessProxy(m::mock(DataStore::class), m::mock(Builder::class));

        $this->assertSame(
            array('Model', 'Alias'),
            $delete->splitAliasPublic('  Model   AS   Alias  ')
        );
        $this->assertSame(
            array('ModelOnly', 'ModelOnly'),
            $delete->splitAliasPublic('ModelOnly')
        );
    }

    private function invokePrivate($object, $method, array $args)
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }
}

class DeleteAccessProxy extends Delete
{
    public function deleteCollectionPublic($collection, $toMeta)
    {
        return $this->deleteCollection($collection, $toMeta);
    }

    public function deleteAsLeafPublic($reader, $deleteIds, $extraWhere = array())
    {
        $this->deleteAsLeaf($reader, $deleteIds, $extraWhere);
    }

    public function getParentIdsPublic($from, $fromPk)
    {
        return $this->getParentIds($from, $fromPk);
    }

    public function getDeleteListPublic($model, $deleteAlias)
    {
        return $this->getDeleteList($model, $deleteAlias);
    }

    public function nestPublic($array)
    {
        return $this->nest($array);
    }

    public function splitAliasPublic($string)
    {
        return $this->splitAlias($string);
    }
}

class DeleteRunHarness extends Delete
{
    public $parentIds = array();
    public $deleteList = array();
    public $deleteCollectionReturn = array(1);
    public $deleteCollectionCalls = array();

    protected function getParentIds($from, $fromPk)
    {
        return $this->parentIds;
    }

    protected function getDeleteList($model, $deleteAlias)
    {
        return $this->deleteList;
    }

    protected function deleteCollection($collection, $toMeta)
    {
        $this->deleteCollectionCalls[] = array($collection, $toMeta);

        return $this->deleteCollectionReturn;
    }
}

class DeleteLeafHarness extends DeleteAccessProxy
{
    public $leafCall;

    protected function deleteAsLeaf($reader, $deleteIds, $extraWhere = array())
    {
        $this->leafCall = array(
            'reader' => $reader,
            'ids' => $deleteIds,
            'extra_where' => $extraWhere,
        );
    }
}

class DeleteRunFacadeStub
{
    public $queries = array();

    public function get($name)
    {
        return $this->queries[$name];
    }
}

class DeleteRunQueryStub
{
    public $operations;
    private $label;

    public function __construct($label)
    {
        $this->label = $label;
        $this->operations = new \ArrayObject();
    }

    public function filter($property, $operator, $value)
    {
        $this->operations[] = array('filter', $property, $operator, $value, $this->label);

        return $this;
    }

    public function limit($limit)
    {
        $this->operations[] = array('limit', $limit);

        return $this;
    }

    public function with($withs)
    {
        $this->operations[] = array('with', $withs);

        return $this;
    }

    public function fields($field)
    {
        $this->operations[] = array('fields', $field);

        return $this;
    }

    public function all()
    {
        $this->operations[] = array('all');

        return array((object) array('id' => 1));
    }
}

class DeleteCollectionStub implements \Countable, \IteratorAggregate
{
    private $ids;
    private $siteIds;
    public $events = array();

    public function __construct(array $ids, array $siteIds)
    {
        $this->ids = $ids;
        $this->siteIds = $siteIds;
    }

    public function count(): int
    {
        return count($this->ids);
    }

    public function getIds()
    {
        return $this->ids;
    }

    public function pluck($field)
    {
        if ($field === 'site_id') {
            return $this->siteIds;
        }

        return array();
    }

    public function emit($event)
    {
        $this->events[] = $event;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(array());
    }
}

class DeleteParentIdBuilderStub extends Builder
{
    private $ids;
    public $fieldsHistory;
    public $pluckHistory;

    public function __construct($from, array $ids)
    {
        parent::__construct($from);
        $this->ids = $ids;
        $this->fieldsHistory = new \ArrayObject();
        $this->pluckHistory = new \ArrayObject();
    }

    public function fields()
    {
        $this->fieldsHistory[] = func_get_arg(0);

        return $this;
    }

    public function all($cache = false)
    {
        return new DeleteParentIdCollectionStub($this->ids, $this);
    }
}

class DeleteParentIdCollectionStub
{
    private $ids;
    private $builder;

    public function __construct(array $ids, DeleteParentIdBuilderStub $builder)
    {
        $this->ids = $ids;
        $this->builder = $builder;
    }

    public function pluck($key)
    {
        $this->builder->pluckHistory[] = $key;

        return $this->ids;
    }
}

class DeleteStaticEventsModel
{
    public static $events = array();

    public static function emitStatic($event, $ids)
    {
        self::$events[] = array($event, $ids);
    }
}

class DeleteCollectionStaticEventsModel
{
    public static $events = array();

    public static function emitStatic($event, $ids)
    {
        self::$events[] = array($event, $ids);
    }
}

// EOF
