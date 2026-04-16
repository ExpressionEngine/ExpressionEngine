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
use ExpressionEngine\Service\Model\Query\Select;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SelectTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
        ee()->resetMocks();
    }

    public function testSplitAliasStoreAliasExpandAliasAndStoreRelation()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $select = new SelectAccessProxy($store, $builder);

        $this->assertSame(array('ee:Entry', 'entry'), $select->splitAliasPublic('ee:Entry AS entry'));
        $this->assertSame(array('ee:Entry', 'ee_m_Entry'), $select->splitAliasPublic('ee:Entry'));

        $select->storeAliasPublic('root', 'ee:Entry');
        $this->assertSame('ee:Entry', $select->expandAliasPublic('root'));

        $relation = (object) array('name' => 'relation');
        $select->storeRelationPublic('root', 'child', $relation);
        $this->assertSame(array('root' => $relation), $select->getRelationsPublic()['child']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Not unique alias 'root'.");
        $select->storeAliasPublic('root', 'ee:Other');
    }

    public function testGetFieldsTranslatePropertyAndIsBinaryComparison()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $meta = m::mock();

        $builder->shouldReceive('getFields')->once()->andReturn(array('title', 'child:one.status'));
        $builder->shouldReceive('getFrom')->once()->andReturn('ee:Entry AS root');
        $store->shouldReceive('getMetaDataReader')->twice()->with('ee:Entry')->andReturn($meta);
        $meta->shouldReceive('getPrimaryKey')->once()->andReturn('entry_id');
        $meta->shouldReceive('getBinaryComparisons')->once()->andReturn(array('title'));

        $binaryMeta = m::mock();
        $store->shouldReceive('getMetaDataReader')->once()->with('ee:Child')->andReturn($binaryMeta);
        $binaryMeta->shouldReceive('getBinaryComparisons')->once()->andReturn(array('status'));

        $select = new SelectAccessProxy($store, $builder);
        $select->setRootAliasPublic('root');
        $select->setAliasesPublic(array(
            'root' => 'ee:Entry',
            'child' => 'ee:Child',
        ));
        $select->setModelFieldsPublic(array(
            'root' => array(
                'root__entry_id' => 'root_table.entry_id',
                'root__title' => 'root_table.title',
            ),
            'child' => array(
                'child__status' => 'child_table.status',
            ),
        ));

        $this->assertSame(
            array('root.title', 'child_m_one.status'),
            $select->getFieldsPublic()
        );
        $this->assertSame('FIELD(root.title, 1, 2)', $select->translatePropertyPublic('FIELD(root.title, 1, 2)'));
        $this->assertSame('root_table.title', $select->translatePropertyPublic('title'));
        $this->assertSame('root_table.entry_id', $select->translatePropertyPublic('root'));
        $this->assertSame('child_table.status', $select->translatePropertyPublic('child.status'));
        $this->assertTrue($select->isBinaryComparisonPublic('title'));
        $this->assertTrue($select->isBinaryComparisonPublic('child.status'));
    }

    public function testTranslatePropertyThrowsOnUnknownField()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $select = new SelectAccessProxy($store, $builder);
        $select->setRootAliasPublic('root');
        $select->setModelFieldsPublic(array('root' => array()));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown field root.missing');
        $select->translatePropertyPublic('missing');
    }

    public function testApplyOrdersAndFilters()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $meta = m::mock();
        $store->shouldReceive('getMetaDataReader')->andReturn($meta);
        $meta->shouldReceive('getBinaryComparisons')->andReturn(array());
        $builder->shouldReceive('getFrom')->andReturn('ee:Entry AS root');

        $select = new SelectAccessProxy($store, $builder);
        $select->setRootAliasPublic('root');
        $select->setAliasesPublic(array('root' => 'ee:Entry'));
        $select->setModelFieldsPublic(array(
            'root' => array(
                'root__title' => 'root_table.title',
                'root__status' => 'root_table.status',
            ),
        ));

        $query = new SelectQueryStub();
        $select->applyOrdersPublic($query, array(
            array('title', 'DESC', false),
            array('status', 'ASC', true),
        ));
        $select->applyFilterPublic($query, array('title', 'IN', array(1, 2), 'and'));
        $select->applyFilterPublic($query, array('title', 'IN', null, 'and'));
        $select->applyFilterPublic($query, array('title', 'NOT IN', array(1, 2), 'and'));
        $select->applyFilterPublic($query, array('status', '!=', 'NULL', 'or'));

        $this->assertCount(2, $query->orderCalls);
        $this->assertSame('order_by', $query->calls[0][0]);
        $this->assertContains('where_in', array_column($query->calls, 0));
        $this->assertContains('where', array_column($query->calls, 0));
        $this->assertContains('or_where', array_column($query->calls, 0));
    }

    public function testApplyFiltersHandlesNestedAndInvalidConnective()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $meta = m::mock();
        $store->shouldReceive('getMetaDataReader')->andReturn($meta);
        $meta->shouldReceive('getBinaryComparisons')->andReturn(array());
        $builder->shouldReceive('getFrom')->andReturn('ee:Entry AS root');

        $select = new SelectAccessProxy($store, $builder);
        $select->setRootAliasPublic('root');
        $select->setAliasesPublic(array('root' => 'ee:Entry'));
        $select->setModelFieldsPublic(array(
            'root' => array('root__title' => 'root_table.title'),
        ));

        $query = new SelectQueryStub();
        $filters = array(
            array('and', array(
                array('title', '==', 'alpha', 'and'),
                array('title', '==', 'beta', 'or'),
            )),
            array('or', array(
                array('title', '==', 'gamma', 'and'),
            )),
        );
        $select->applyFiltersPublic($query, $filters);
        $this->assertContains('start_group', array_column($query->calls, 0));
        $this->assertContains('or_start_group', array_column($query->calls, 0));
        $this->assertContains('end_group', array_column($query->calls, 0));

        $this->expectException(\LogicException::class);
        $select->applyFiltersPublic($query, array(array('xor', array())));
    }

    public function testApplySearchWithSearchedFieldsAndAdditionalSearch()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $select = new SelectAccessProxy($store, $builder);
        $select->setRootAliasPublic('root');
        $select->setModelFieldsPublic(array(
            'root' => array(
                'root__title' => 'root_table.title',
                'root__body' => 'root_table.body',
            ),
        ));
        $select->setSearchedFieldsPublic(array('body'));
        $select->setAdditionalSearchPublic(array('root_table.entry_id', array(1, 2)));

        $query = new SelectQueryStub();
        $query->ar_where[] = 'stub-where';

        $select->applySearchPublic($query, array(
            'title' => array('alpha' => true, 'beta' => false),
            'body' => array('skip' => true),
        ));

        $calls = array_column($query->calls, 0);
        $this->assertContains('start_like_group', $calls);
        $this->assertContains('or_start_like_group', $calls);
        $this->assertContains('like', $calls);
        $this->assertContains('not_like', $calls);
        $this->assertContains('or_where_in', $calls);
        $this->assertContains('end_like_group', $calls);
    }

    public function testGetFieldIdsFromFiltersRecursesNestedGroups()
    {
        $select = new SelectAccessProxy(m::mock(DataStore::class), m::mock(Builder::class));
        $ids = $select->getFieldIdsFromFiltersPublic(
            array(
                array('field_id_3', '==', 'x', 'and'),
                array('and', array(
                    array('field_id_7', '==', 'y', 'and'),
                    array('title', '==', 'z', 'or'),
                )),
            ),
            ''
        );

        $this->assertSame(array('3', '7'), $ids);
    }

    public function testSelectModelCoversMainAndJoinAndQueuedJoinPaths()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $meta = m::mock();
        $relation = m::mock();

        $store->shouldReceive('getMetaDataReader')->twice()->with('ee:Entry')->andReturn($meta);
        $store->shouldReceive('getAllRelations')->twice()->with('ee:Entry')->andReturn(array($relation));
        $relation->shouldReceive('getKeys')->twice()->andReturn(array('entry_id', 'id'));

        $meta->shouldReceive('getTables')->twice()->andReturn(array(
            'exp_entries' => array('entry_id', 'title'),
            'exp_entries_data' => array('entry_id', 'title', 'body'),
        ));
        $meta->shouldReceive('getPrimaryKey')->twice()->andReturn('entry_id');

        $select = new SelectAccessProxy($store, $builder);
        $select->setFieldsOverridePublic(array('entry.title'));

        $query = new SelectQueryStub();
        $queued = $select->selectModelPublic($query, 'ee:Entry', 'entry', false);

        $this->assertSame(array(), $queued);
        $this->assertContains('from', array_column($query->calls, 0));
        $this->assertContains('join', array_column($query->calls, 0));
        $this->assertContains('select', array_column($query->calls, 0));
        $this->assertArrayHasKey('entry__body', $select->getModelFieldsPublic()['entry']);

        $query2 = new SelectQueryStub();
        $queued2 = $select->selectModelPublic($query2, 'ee:Entry', 'entry2', true);
        $this->assertCount(1, $queued2);
        $this->assertSame('exp_entries_data as entry2_exp_entries_data', $queued2[0][0]);
    }

    public function testProcessWithsAndRecurseWiths()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $builder->shouldReceive('getWiths')->once()->andReturn(array(
            'ee:Child AS child' => array(
                'ee:Grand AS grand' => array(),
            ),
        ));

        $relation1 = m::mock();
        $relation1->shouldReceive('getTargetModel')->once()->andReturn('ee:Child');
        $relation1->shouldReceive('modifyEagerQuery')->once();
        $relation2 = m::mock();
        $relation2->shouldReceive('getTargetModel')->once()->andReturn('ee:Grand');
        $relation2->shouldReceive('modifyEagerQuery')->once();

        $store->shouldReceive('getRelation')->once()->with('ee:Entry', 'ee:Child')->andReturn($relation1);
        $store->shouldReceive('getRelation')->once()->with('ee:Child', 'ee:Grand')->andReturn($relation2);

        $select = new SelectWithProxy($store, $builder);
        $query = new SelectQueryStub();
        $select->processWithsPublic($query, 'ee:Entry', 'entry');

        $relations = $select->getRelationsPublic();
        $this->assertArrayHasKey('child', $relations);
        $this->assertArrayHasKey('grand', $relations);
        $this->assertContains('join', array_column($query->calls, 0));
    }

    public function testBuildQueryWithAllPipelineParts()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $meta = m::mock();
        $constraintRelation = m::mock();
        $parent = (object) array('id' => 5);

        $store->shouldReceive('rawQuery')->once()->andReturn(new SelectQueryStub());
        $store->shouldReceive('getMetaDataReader')->once()->with('ee:Entry')->andReturn($meta);
        $meta->shouldReceive('getClass')->once()->andReturn(SelectFieldDataEnabledModelStub::class);

        $builder->shouldReceive('getFrom')->once()->andReturn('ee:Entry AS entry');
        $builder->shouldReceive('getLazyConstraints')->once()->andReturn(array(array($constraintRelation, $parent)));
        $builder->shouldReceive('getFilters')->once()->andReturn(array(array('title', '==', 'x', 'and')));
        $builder->shouldReceive('getSearch')->once()->andReturn(array('title' => array('x' => true)));
        $builder->shouldReceive('getOrders')->once()->andReturn(array(array('title', 'ASC', true)));
        $builder->shouldReceive('getLimit')->once()->andReturn(10);
        $builder->shouldReceive('getOffset')->once()->andReturn(2);

        $constraintRelation->shouldReceive('modifyLazyQuery')->once();

        $select = new SelectBuildQueryProxy($store, $builder);
        $query = $select->buildQueryPublic();

        $this->assertTrue($select->augmentCalled);
        $this->assertTrue($select->processWithsCalled);
        $this->assertCount(1, $select->filtersApplied);
        $this->assertCount(1, $select->searchApplied);
        $this->assertCount(1, $select->ordersApplied);
        $this->assertSame(array(array(10, 2)), $query->limitCalls);
    }

    public function testRunCoversEmptyAndNonEmptyBranches()
    {
        $emptyStore = m::mock(DataStore::class);
        $emptyBuilder = m::mock(Builder::class);

        $empty = new SelectRunProxy($emptyStore, $emptyBuilder, array(), array('root' => 'ee:Entry'));
        $emptyBuilder->shouldReceive('getWiths')->never();
        $result = $empty->run();
        $this->assertNull($result->first());

        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $rows = array(array('root__entry_id' => 1));
        $nonEmpty = new SelectRunProxy($store, $builder, $rows, array('root' => 'ee:Entry', 'child' => 'ee:Child'));
        $builder->shouldReceive('getWiths')->once()->andReturn(array('child AS child' => array()));
        $result2 = $nonEmpty->run();
        $this->assertSame(2, $nonEmpty->getExtraDataCalls);
        $this->assertNotNull($result2);
    }

    public function testGetCustomFieldsUsesSessionCache()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $select = new SelectAccessProxy($store, $builder);

        ee()->setMock('session', new SelectSessionCacheStub());
        $modelService = new SelectModelServiceStub();
        $collection = new SelectFieldCollection(array(
            new SelectCustomFieldStub(1, array('field_id_1')),
            new SelectCustomFieldStub(2, array('field_id_2')),
        ));
        $modelService->queryMap['FieldModel'] = new SelectModelQueryStub($collection);
        ee()->setMock('Model', $modelService);

        $first = $select->getCustomFieldsPublic('FieldModel', '', array(1, 2));
        $second = $select->getCustomFieldsPublic('FieldModel', '', array(1, 2));

        $this->assertSame($first, $second);
        $this->assertSame(1, $modelService->getCalls['FieldModel'] ?? 0);
    }

    public function testGetExtraDataEarlyReturnAndMergePaths()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $meta = m::mock();
        $store->shouldReceive('getMetaDataReader')->twice()->andReturn($meta);
        $meta->shouldReceive('getClass')->twice()->andReturn(SelectFieldDataEnabledModelStub::class);
        $meta->shouldReceive('getPrimaryKey')->twice()->andReturn('entry_id');

        $select = new SelectAccessProxy($store, $builder);
        $select->setAliasesPublic(array('entry' => 'ee:Entry'));
        $select->setFieldsOverridePublic(array('entry.title'));
        $result = $select->getExtraDataPublic('entry', array(array('entry__entry_id' => 1)));
        $this->assertSame(array(array('entry__entry_id' => 1)), $result);

        $select->setFieldsOverridePublic(array('entry.field_id_1'));

        $modelService = new SelectModelServiceStub();
        $modelService->makeMap['FieldModel'] = new SelectFieldModelStub('exp_field_data_', '');
        $structureModels = array(
            new SelectStructureModelStub(array(
                new SelectCustomFieldStub(1, array('field_id_1', 'field_ft_1')),
            )),
        );
        $modelService->queryMap['StructureModel:with-ids'] = new SelectModelQueryStub($structureModels);
        ee()->setMock('Model', $modelService);

        $dsService = new SelectDatastoreServiceStub();
        $q = new SelectQueryStub();
        $q->resultRows = array(array(
            'entry__entry_id' => 10,
            'entry__field_id_1' => 'value-1',
            'entry__field_ft_1' => 'none',
        ));
        $dsService->queue[] = $q;
        ee()->setMock('Model/Datastore', $dsService);

        $rows = array(array(
            'entry__entry_id' => 10,
            'entry__channel_id' => 5,
        ));
        $merged = $select->getExtraDataPublic('entry', $rows);
        $this->assertSame('value-1', $merged[0]['entry__field_id_1']);
    }

    public function testGetExtraDataWithoutGroupColumnUsesFieldModelQuery()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $meta = m::mock();
        $store->shouldReceive('getMetaDataReader')->once()->andReturn($meta);
        $meta->shouldReceive('getClass')->once()->andReturn(SelectFieldDataNoGroupModelStub::class);
        $meta->shouldReceive('getPrimaryKey')->twice()->andReturn('entry_id');

        $select = new SelectAccessProxy($store, $builder);
        $select->setAliasesPublic(array('entry' => 'ee:Entry'));
        $select->setFieldsOverridePublic(array());

        $modelService = new SelectModelServiceStub();
        $modelService->makeMap['FieldModel'] = new SelectFieldModelStub('exp_field_data_', '');
        $modelService->queryMap['FieldModel'] = new SelectModelQueryStub(new SelectFieldCollection(array(
            new SelectCustomFieldStub(7, array('field_id_7')),
        )));
        ee()->setMock('Model', $modelService);

        $dsService = new SelectDatastoreServiceStub();
        $q = new SelectQueryStub();
        $q->resultRows = array(array(
            'entry__entry_id' => 77,
            'entry__field_id_7' => 'delta',
        ));
        $dsService->queue[] = $q;
        ee()->setMock('Model/Datastore', $dsService);

        $rows = array(array('entry__entry_id' => 77));
        $merged = $select->getExtraDataPublic('entry', $rows);

        $this->assertSame('delta', $merged[0]['entry__field_id_7']);
    }

    public function testAugmentQueryCacheMissAndCacheHitPaths()
    {
        $store = m::mock(DataStore::class);
        $builder = m::mock(Builder::class);
        $meta = m::mock();

        $store->shouldReceive('getMetaDataReader')->twice()->andReturn($meta);
        $meta->shouldReceive('getClass')->twice()->andReturn(SelectFieldDataEnabledModelStub::class);
        $meta->shouldReceive('getName')->twice()->andReturn('entry');
        $meta->shouldReceive('getPrimaryKey')->twice()->andReturn('entry_id');
        $meta->shouldReceive('getTables')->once()->andReturn(array('exp_entries' => array('entry_id')));

        $builder->shouldReceive('getSearch')->times(3)->andReturn(array(
            'field_id_1' => array('alpha' => true),
            'field_id_2' => array('beta' => false),
        ));
        $builder->shouldReceive('getFilters')->twice()->andReturn(array(
            array('field_id_3', '==', 'x', 'and'),
        ));
        $builder->shouldReceive('getOrders')->twice()->andReturn(array(
            array('field_id_4', 'ASC', true),
        ));

        $select = new SelectAccessProxy($store, $builder);
        $select->setRootAliasPublic('entry');
        $select->setAliasesPublic(array('entry' => 'ee:Entry'));
        $select->setModelFieldsPublic(array(
            'entry' => array(
                'entry__entry_id' => 'entry_exp_entries.entry_id',
            ),
        ));
        $select->setTableJoinLimitPublic(1);

        $modelService = new SelectModelServiceStub();
        $modelService->makeMap['FieldModel'] = new SelectFieldModelStub('exp_field_data_', '');
        $modelService->queryMap['FieldModel'] = new SelectModelQueryStub(function ($query) {
            $ids = $query->inFilterIds;
            sort($ids);

            if ($ids === array('1', '2') || $ids === array(1, 2)) {
                return new SelectFieldCollection(array(
                    new SelectCustomFieldStub(1, array('field_id_1')),
                    new SelectCustomFieldStub(2, array('field_id_2')),
                ));
            }

            return new SelectFieldCollection(array(
                new SelectCustomFieldStub(3, array('field_id_3')),
                new SelectCustomFieldStub(4, array('field_id_4')),
            ));
        });
        ee()->setMock('Model', $modelService);
        ee()->setMock('session', new SelectSessionCacheStub());

        $dsService = new SelectDatastoreServiceStub();
        $sq1 = new SelectQueryStub();
        $sq1->resultRows = array(array('entry_id' => 11));
        $sq2 = new SelectQueryStub();
        $sq2->resultRows = array(array('entry_id' => 12));
        $dsService->queue = array($sq1, $sq2);
        ee()->setMock('Model/Datastore', $dsService);

        $query = new SelectQueryStub();
        $select->augmentQueryPublic($query);
        $select->augmentQueryPublic($query);

        $this->assertNotEmpty($select->getAdditionalSearchPublic());
        $this->assertCount(2, $select->getSearchedFieldsPublic());
        $this->assertContains('select', array_column($query->calls, 0));
        $this->assertContains('join', array_column($query->calls, 0));
    }
}

class SelectAccessProxy extends Select
{
    protected $fieldsOverride;

    public function setRootAliasPublic($alias)
    {
        $this->root_alias = $alias;
    }

    public function setAliasesPublic(array $aliases)
    {
        $this->aliases = $aliases;
    }

    public function setRelationsPublic(array $relations)
    {
        $this->relations = $relations;
    }

    public function setModelFieldsPublic(array $modelFields)
    {
        $this->model_fields = $modelFields;
    }

    public function setSearchedFieldsPublic(array $searchedFields)
    {
        $this->searched_fields = $searchedFields;
    }

    public function setAdditionalSearchPublic(array $additionalSearch)
    {
        $this->additional_search = $additionalSearch;
    }

    public function setFieldsOverridePublic(array $fields)
    {
        $this->fieldsOverride = $fields;
    }

    public function setTableJoinLimitPublic($limit)
    {
        $ref = new \ReflectionProperty(Select::class, 'table_join_limit');
        \TestReflectionHelper::makePropertyAccessible($ref);
        $ref->setValue($this, $limit);
    }

    public function getModelFieldsPublic()
    {
        return $this->model_fields;
    }

    public function getRelationsPublic()
    {
        return $this->relations;
    }

    public function getAdditionalSearchPublic()
    {
        return $this->additional_search;
    }

    public function getSearchedFieldsPublic()
    {
        return $this->searched_fields;
    }

    public function getClassPublic($alias = '')
    {
        return $this->getClass($alias);
    }

    public function buildQueryPublic()
    {
        return $this->buildQuery();
    }

    public function selectModelPublic($query, $model, $alias, $willJoin = false)
    {
        return $this->selectModel($query, $model, $alias, $willJoin);
    }

    public function getExtraDataPublic($alias, $resultArray)
    {
        return $this->getExtraData($alias, $resultArray);
    }

    public function getCustomFieldsPublic($modelName, $columnPrefix, $fieldIds)
    {
        $ref = new \ReflectionMethod(Select::class, 'getCustomFields');
        \TestReflectionHelper::makeAccessible($ref);

        return $ref->invoke($this, $modelName, $columnPrefix, $fieldIds);
    }

    public function augmentQueryPublic($query)
    {
        $this->augmentQuery($query);
    }

    public function getFieldIdsFromFiltersPublic($filters, $columnPrefix)
    {
        return $this->getFieldIdsFromFilters($filters, $columnPrefix);
    }

    public function getFieldsPublic()
    {
        return $this->getFields();
    }

    public function applyOrdersPublic($query, $orders)
    {
        $this->applyOrders($query, $orders);
    }

    public function applyFiltersPublic($query, $filters)
    {
        $this->applyFilters($query, $filters);
    }

    public function applyFilterPublic($query, $filter)
    {
        $this->applyFilter($query, $filter);
    }

    public function isBinaryComparisonPublic($property)
    {
        return $this->isBinaryComparison($property);
    }

    public function applySearchPublic($query, $search)
    {
        $this->applySearch($query, $search);
    }

    public function translatePropertyPublic($property)
    {
        return $this->translateProperty($property);
    }

    public function processWithsPublic($query, $from, $fromAlias)
    {
        $this->processWiths($query, $from, $fromAlias);
    }

    public function recurseWithsPublic($query, $parent, $parentAlias, $withs)
    {
        $this->recurseWiths($query, $parent, $parentAlias, $withs);
    }

    public function storeRelationPublic($fromAlias, $toAlias, $relation)
    {
        $this->storeRelation($fromAlias, $toAlias, $relation);
    }

    public function splitAliasPublic($string)
    {
        return $this->splitAlias($string);
    }

    public function storeAliasPublic($alias, $model)
    {
        $this->storeAlias($alias, $model);
    }

    public function expandAliasPublic($alias)
    {
        return $this->expandAlias($alias);
    }

    protected function getFields()
    {
        if ($this->fieldsOverride !== null) {
            return $this->fieldsOverride;
        }

        return parent::getFields();
    }
}

class SelectWithProxy extends SelectAccessProxy
{
    protected function selectModel($query, $model, $alias, $willJoin = false)
    {
        $this->storeAlias($alias, $model);

        return array(array(
            "{$alias}_join_table",
            "{$alias}_join_condition",
            'LEFT',
        ));
    }
}

class SelectBuildQueryProxy extends SelectAccessProxy
{
    public $augmentCalled = false;
    public $processWithsCalled = false;
    public $filtersApplied = array();
    public $searchApplied = array();
    public $ordersApplied = array();

    protected function selectModel($query, $model, $alias, $willJoin = false)
    {
        $this->storeAlias($alias, $model);

        return array();
    }

    protected function augmentQuery($query)
    {
        $this->augmentCalled = true;
    }

    protected function processWiths($query, $from, $fromAlias)
    {
        $this->processWithsCalled = true;
    }

    protected function applyFilters($query, $filters)
    {
        $this->filtersApplied[] = $filters;
    }

    protected function applySearch($query, $search)
    {
        $this->searchApplied[] = $search;
    }

    protected function applyOrders($query, $orders)
    {
        $this->ordersApplied[] = $orders;
    }
}

class SelectRunProxy extends Select
{
    private $rows;
    private $aliasesForRun;
    public $getExtraDataCalls = 0;

    public function __construct(DataStore $store, Builder $builder, array $rows, array $aliases)
    {
        parent::__construct($store, $builder);
        $this->rows = $rows;
        $this->aliasesForRun = $aliases;
    }

    protected function buildQuery()
    {
        $this->root_alias = 'root';
        $this->aliases = $this->aliasesForRun;
        $query = new SelectQueryStub();
        $query->resultRows = $this->rows;

        return $query;
    }

    protected function getClass($alias = '')
    {
        return SelectFieldDataEnabledModelStub::class;
    }

    protected function getExtraData($alias, $resultArray)
    {
        $this->getExtraDataCalls++;

        return $resultArray;
    }
}

class SelectAugmentProxy extends SelectAccessProxy
{
}

class SelectFieldDataEnabledModelStub
{
    public static function getMetaData($key)
    {
        if ($key === 'field_data') {
            return array(
                'field_model' => 'FieldModel',
                'structure_model' => 'StructureModel',
                'group_column' => 'channel_id',
            );
        }

        if ($key === 'table_name') {
            return 'exp_entries';
        }

        return null;
    }
}

class SelectFieldDataNoGroupModelStub
{
    public static function getMetaData($key)
    {
        if ($key === 'field_data') {
            return array(
                'field_model' => 'FieldModel',
            );
        }

        if ($key === 'table_name') {
            return 'exp_entries';
        }

        return null;
    }
}

class SelectFieldModelStub
{
    private $tableName;
    private $columnPrefix;

    public function __construct($tableName, $columnPrefix)
    {
        $this->tableName = $tableName;
        $this->columnPrefix = $columnPrefix;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getColumnPrefix()
    {
        return $this->columnPrefix;
    }
}

class SelectStructureModelStub
{
    private $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function getAllCustomFields()
    {
        return $this->fields;
    }
}

class SelectCustomFieldStub
{
    public $legacy_field_data = false;
    public $field_id;
    private $columns;

    public function __construct($fieldId, array $columns)
    {
        $this->field_id = $fieldId;
        $this->columns = $columns;
    }

    public function getId()
    {
        return $this->field_id;
    }

    public function getColumnNames()
    {
        return $this->columns;
    }
}

class SelectFieldCollection implements \Countable, \IteratorAggregate
{
    private $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function all($cache = false)
    {
        return $this;
    }

    public function asArray()
    {
        return $this->items;
    }

    public function pluck($field)
    {
        $out = array();
        foreach ($this->items as $item) {
            if (is_object($item) && isset($item->$field)) {
                $out[] = $item->$field;
            }
        }

        return $out;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }
}

class SelectModelQueryStub
{
    private $allResult;
    public $filters = array();
    public $inFilterIds = array();

    public function __construct($allResult)
    {
        $this->allResult = $allResult;
    }

    public function fields($field)
    {
        return $this;
    }

    public function filter($field, $operator = null, $value = null)
    {
        $this->filters[] = array($field, $operator, $value);
        if (strtolower((string) $operator) === 'in' && is_array($value)) {
            $this->inFilterIds = $value;
        }

        return $this;
    }

    public function all($cache = false)
    {
        if (is_callable($this->allResult)) {
            return call_user_func($this->allResult, $this, $cache);
        }

        return $this->allResult;
    }
}

class SelectModelServiceStub
{
    public $makeMap = array();
    public $queryMap = array();
    public $getCalls = array();

    public function make($name)
    {
        return $this->makeMap[$name];
    }

    public function get($name)
    {
        $key = $name;
        $args = func_get_args();
        if (count($args) > 1) {
            $key = $name . ':with-ids';
        }

        if (! isset($this->getCalls[$name])) {
            $this->getCalls[$name] = 0;
        }
        $this->getCalls[$name]++;

        return $this->queryMap[$key];
    }
}

class SelectDatastoreServiceStub
{
    public $queue = array();

    public function rawQuery()
    {
        return array_shift($this->queue);
    }
}

class SelectSessionCacheStub
{
    private $cache = array();

    public function cache($class, $key, $default = false)
    {
        if (isset($this->cache[$class]) && array_key_exists($key, $this->cache[$class])) {
            return $this->cache[$class][$key];
        }

        return $default;
    }

    public function set_cache($class, $key, $value)
    {
        if (! isset($this->cache[$class])) {
            $this->cache[$class] = array();
        }
        $this->cache[$class][$key] = $value;
    }
}

class SelectDbResultStub
{
    private $rows;
    public $freed = false;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function result_array()
    {
        return $this->rows;
    }

    public function free_result()
    {
        $this->freed = true;
    }
}

class SelectQueryStub
{
    public $calls = array();
    public $resultRows = array();
    public $ar_where = array();
    public $ar_like = array();
    public $orderCalls = array();
    public $limitCalls = array();

    public function from($table)
    {
        $this->calls[] = array('from', $table);

        return $this;
    }

    public function join($table, $condition, $type = '')
    {
        $this->calls[] = array('join', $table, $condition, $type);

        return $this;
    }

    public function select($select, $escape = true)
    {
        $this->calls[] = array('select', $select, $escape);

        return $this;
    }

    public function start_group()
    {
        $this->calls[] = array('start_group');

        return $this;
    }

    public function or_start_group()
    {
        $this->calls[] = array('or_start_group');

        return $this;
    }

    public function end_group()
    {
        $this->calls[] = array('end_group');

        return $this;
    }

    public function where($comparison, $value = null, $escape = true, $binary = false)
    {
        $this->calls[] = array('where', $comparison, $value, $escape, $binary);
        $this->ar_where[] = $comparison;

        return $this;
    }

    public function or_where($comparison, $value = null, $escape = true, $binary = false)
    {
        $this->calls[] = array('or_where', $comparison, $value, $escape, $binary);
        $this->ar_where[] = $comparison;

        return $this;
    }

    public function where_in($comparison, $value, $binary = false)
    {
        $this->calls[] = array('where_in', $comparison, $value, $binary);
        $this->ar_where[] = $comparison;

        return $this;
    }

    public function or_where_in($comparison, $value, $binary = false)
    {
        $this->calls[] = array('or_where_in', $comparison, $value, $binary);
        $this->ar_where[] = $comparison;

        return $this;
    }

    public function where_not_in($comparison, $value, $binary = false)
    {
        $this->calls[] = array('where_not_in', $comparison, $value, $binary);
        $this->ar_where[] = $comparison;

        return $this;
    }

    public function start_like_group()
    {
        $this->calls[] = array('start_like_group');

        return $this;
    }

    public function or_start_like_group()
    {
        $this->calls[] = array('or_start_like_group');

        return $this;
    }

    public function end_like_group()
    {
        $this->calls[] = array('end_like_group');

        return $this;
    }

    public function like($field, $value)
    {
        $this->calls[] = array('like', $field, $value);
        $this->ar_like[] = $field;

        return $this;
    }

    public function not_like($field, $value)
    {
        $this->calls[] = array('not_like', $field, $value);
        $this->ar_like[] = $field;

        return $this;
    }

    public function order_by($property, $direction = '', $escape = true)
    {
        $this->calls[] = array('order_by', $property, $direction, $escape);
        $this->orderCalls[] = array($property, $direction, $escape);

        return $this;
    }

    public function limit($limit, $offset = 0)
    {
        $this->calls[] = array('limit', $limit, $offset);
        $this->limitCalls[] = array($limit, $offset);

        return $this;
    }

    public function get()
    {
        $this->calls[] = array('get');

        return new SelectDbResultStub($this->resultRows);
    }
}

// EOF
