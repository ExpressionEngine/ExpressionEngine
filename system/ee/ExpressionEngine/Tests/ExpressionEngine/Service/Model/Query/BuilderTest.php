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
use ExpressionEngine\Service\Model\DataStore;
use ExpressionEngine\Service\Model\Query\Builder;
use ExpressionEngine\Service\Model\Query\Result;
use PHPUnit\Framework\TestCase;

class BuilderTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
        ee()->resetMocks();
    }

    public function testFields()
    {
        $builder = new Builder('Test');

        $this->assertEquals(array(), $builder->getFields());

        $builder->fields('foo', 'bar');
        $builder->fields('baz', 'bat');

        $this->assertEquals(
            array('foo', 'bar', 'baz', 'bat'),
            $builder->getFields()
        );
    }

    public function testLimitAndOffset()
    {
        $builder = new Builder('Test');

        $this->assertEquals('18446744073709551615', $builder->getLimit());
        $this->assertEquals(0, $builder->getOffset());

        $builder->limit(5);
        $builder->offset(10);

        $this->assertEquals(5, $builder->getLimit());
        $this->assertEquals(10, $builder->getOffset());
    }

    public function testSet()
    {
        $builder = new Builder('Test');

        $this->assertEquals(array(), $builder->getSet());

        $builder->set('name', 'Bob');
        $builder->set(array('age' => 5, 'location' => 'Boston'));

        $this->assertEquals(
            array('name' => 'Bob', 'age' => 5, 'location' => 'Boston'),
            $builder->getSet()
        );
    }

    public function testFilters()
    {
        $builder = new Builder('Test');

        $this->assertEquals(array(), $builder->getFilters());

        $builder->filter('name', 'Bob');
        $builder->orFilter('age', '>', 5);

        $this->assertEquals(
            array(
                array('name', '==', 'Bob', 'and'),
                array('age', '>', 5, 'or')
            ),
            $builder->getFilters()
        );
    }

    public function testFilterGroups()
    {
        $builder = new Builder('Test');

        $builder
            ->filterGroup()
            ->filter('name', 'Bob')
            ->orFilter('name', 'Wendy')
            ->endFilterGroup()
            ->orFilterGroup()
            ->filter('name', 'Farmer Pickles')
            ->filter('companion', 'Spud')
            ->endFilterGroup();

        $this->assertEquals(
            array(
                array('and', array(
                    array('name', '==', 'Bob', 'and'),
                    array('name', '==', 'Wendy', 'or')
                )),
                array('or', array(
                    array('name', '==', 'Farmer Pickles', 'and'),
                    array('companion', '==', 'Spud', 'and')
                ))
            ),
            $builder->getFilters()
        );
    }

    public function testWithsOneLeveL()
    {
        $builder = new Builder('Test');

        $this->assertEquals(array(), $builder->getWiths());

        $builder->with('one', 'two');
        $builder->with(array('four', 'five'));

        $this->assertEquals(
            array(
                'one' => array(),
                'two' => array(),
                'four' => array(),
                'five' => array()
            ),
            $builder->getWiths()
        );
    }

    public function testWithsGrandkids()
    {
        $builder = new Builder('Test');

        $builder->with(array('one' => 'cow', 'two', 'three' => array('dog', 'cat')));

        $this->assertEquals(
            array(
                'one' => array(
                    'cow' => array()
                ),
                'two' => array(),
                'three' => array(
                    'dog' => array(),
                    'cat' => array()
                )
            ),
            $builder->getWiths()
        );
    }

    public function testWithsMergeDescendants()
    {
        $builder = new Builder('Test');

        $builder->with('one');
        $builder->with(array('one' => 'cow'));
        $builder->with(array('two' => 'dog'));
        $builder->with('one', 'two');
        $builder->with(array('one' => array('cat' => 'meow')));

        $this->assertEquals(
            array(
                'one' => array('cow' => array(), 'cat' => array('meow' => array())),
                'two' => array('dog' => array()),
            ),
            $builder->getWiths()
        );
    }

    public function testSearch()
    {
        $builder = new Builder('Test');

        $builder->search('words', 'hello world');
        $builder->search('wordnotword', 'hello -world');
        $builder->search('phrase', '"hello world"');
        $builder->search('phraseword', '"hello world" people');
        $builder->search('notphraseword', '-"hello world" people');
        $builder->search('apostrophe', "hello world's people");

        $this->assertEquals(
            array(
                'words' => array('hello' => true, 'world' => true),
                'wordnotword' => array('hello' => true, 'world' => false),
                'phrase' => array('hello world' => true),
                'phraseword' => array('hello world' => true, 'people' => true),
                'notphraseword' => array('hello world' => false, 'people' => true),
                'apostrophe' => array('hello' => true, "world's" => true, 'people' => true),
            ),
            $builder->getSearch()
        );
    }

    public function testSearchIgnoresTooShortTerms()
    {
        $builder = new Builder('Test');

        $builder->search('terms', 'aa bb');

        $this->assertSame(array(), $builder->getSearch());
    }

    public function testFirstSetsLimitAndReturnsFirstItem()
    {
        $result = m::mock();
        $result->shouldReceive('first')->once()->andReturn('first-row');

        $builder = new BuilderFetchProxy('Test');
        $builder->setFetchResult($result);

        $this->assertSame('first-row', $builder->first());
        $this->assertSame(1, $builder->getLimit());
        $this->assertSame(array(false), $builder->fetchCacheArgs);
    }

    public function testAllReturnsAllItemsFromFetch()
    {
        $result = m::mock();
        $result->shouldReceive('all')->once()->andReturn(array('row-1', 'row-2'));

        $builder = new BuilderFetchProxy('Test');
        $builder->setFetchResult($result);

        $this->assertSame(array('row-1', 'row-2'), $builder->all());
        $this->assertSame(array(false), $builder->fetchCacheArgs);
    }

    public function testUpdateInsertAndDeleteDelegateToDataStore()
    {
        $datastore = m::mock(DataStore::class);
        $builder = new Builder('Test');
        $builder->setDataStore($datastore);

        $datastore->shouldReceive('updateQuery')->once()->with($builder)->andReturn('updated');
        $datastore->shouldReceive('insertQuery')->once()->with($builder)->andReturn('inserted');
        $datastore->shouldReceive('deleteQuery')->once()->with($builder)->andReturn('deleted');

        $this->assertSame('updated', $builder->update());
        $this->assertSame('inserted', $builder->insert());
        $this->assertSame('deleted', $builder->delete());
    }

    public function testCountReturnsZeroWhenMarkedFutile()
    {
        $builder = new BuilderAccessProxy('Test');
        $builder->markAsFutile();

        $this->assertSame(0, $builder->count());
        $this->assertTrue($builder->callIsFutile());
    }

    public function testCountWithoutCacheDelegatesToDataStore()
    {
        $datastore = m::mock(DataStore::class);
        $builder = new Builder('Test');
        $builder->setDataStore($datastore);

        $datastore->shouldReceive('countQuery')->once()->with($builder)->andReturn(13);

        $this->assertSame(13, $builder->count());
    }

    public function testCountWithCacheReturnsCachedValueWithoutQuery()
    {
        $builder = new BuilderCacheProxy('Test');
        $builder->nextCacheValue = 88;

        $this->assertSame(88, $builder->count(true));
        $this->assertCount(1, $builder->getCacheCalls);
        $this->assertSame(array(), $builder->savedCache);
    }

    public function testCountWithCacheMissRunsQueryAndCachesResult()
    {
        $datastore = m::mock(DataStore::class);
        $builder = new BuilderCacheProxy('Test');
        $builder->setDataStore($datastore);
        $builder->nextCacheValue = false;

        $datastore->shouldReceive('countQuery')->once()->with($builder)->andReturn(7);

        $this->assertSame(7, $builder->count(true));
        $this->assertCount(1, $builder->getCacheCalls);
        $this->assertCount(1, $builder->savedCache);
        $this->assertSame(7, array_values($builder->savedCache)[0]);
    }

    public function testBatchAcceptsCallbackAsFirstArgument()
    {
        $builder = new BuilderBatchProxy('Test', array('a', 'b', 'c'));
        $builder->limit(3);
        $builder->offset(0);

        $items = array();
        $count = $builder->batch(function ($item) use (&$items) {
            $items[] = $item;
        });

        $this->assertSame(3, $count);
        $this->assertSame(array('a', 'b', 'c'), $items);
    }

    public function testBatchWithExplicitBatchSize()
    {
        $builder = new BuilderBatchProxy('Test', array('a', 'b', 'c', 'd'));
        $builder->limit(4);
        $builder->offset(0);

        $items = array();
        $count = $builder->batch(2, function ($item) use (&$items) {
            $items[] = $item;
        });

        $this->assertSame(4, $count);
        $this->assertSame(array('a', 'b', 'c', 'd'), $items);
    }

    public function testFetchThrowsWhenFilterGroupIsUnclosed()
    {
        $builder = new BuilderAccessProxy('Test');
        $builder->filterGroup()->filter('title', 'x');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unclosed filter group.');

        $builder->callFetch();
    }

    public function testFetchReturnsEmptyResultWhenFutile()
    {
        $builder = new BuilderAccessProxy('Test');
        $builder->markAsFutile();

        $result = $builder->callFetch();
        $this->assertInstanceOf(Result::class, $result);
        $this->assertNull($result->first());
        $this->assertCount(0, $result->all());
    }

    public function testFetchWithCacheReturnsCachedObject()
    {
        $cached = (object) array('cached' => true);
        $builder = new BuilderCacheProxy('Test');
        $builder->nextCacheValue = $cached;

        $this->assertSame($cached, $builder->callFetch(true));
        $this->assertCount(1, $builder->getCacheCalls);
        $this->assertSame(array(), $builder->savedCache);
    }

    public function testFetchWithCacheMissSelectsAndStoresResult()
    {
        $datastore = m::mock(DataStore::class);
        $selected = m::mock();
        $facade = (object) array('name' => 'facade');

        $builder = new BuilderCacheProxy('Test');
        $builder->setDataStore($datastore);
        $builder->setFacade($facade);
        $builder->nextCacheValue = false;

        $datastore->shouldReceive('selectQuery')->once()->with($builder)->andReturn($selected);
        $selected->shouldReceive('setFacade')->once()->with($facade)->andReturn('selected-result');

        $this->assertSame('selected-result', $builder->callFetch(true));
        $this->assertCount(1, $builder->savedCache);
        $this->assertSame('selected-result', array_values($builder->savedCache)[0]);
    }

    public function testFilterStackIsEmptyTracksOpenAndClosedGroups()
    {
        $builder = new BuilderAccessProxy('Test');
        $this->assertTrue($builder->callFilterStackIsEmpty());

        $builder->filterGroup();
        $this->assertFalse($builder->callFilterStackIsEmpty());

        $builder->endFilterGroup();
        $this->assertTrue($builder->callFilterStackIsEmpty());
    }

    public function testOrderAndGetOrders()
    {
        $builder = new Builder('Test');
        $builder->order('title', 'DESC', false)->order('entry_date');

        $this->assertSame(
            array(
                array('title', 'DESC', false),
                array('entry_date', '', true),
            ),
            $builder->getOrders()
        );
    }

    public function testLazyConstraintsAndGetter()
    {
        $builder = new Builder('Test');
        $model = (object) array('id' => 5);

        $builder->setLazyConstraint('Author', $model);

        $this->assertSame(array(array('Author', $model)), $builder->getLazyConstraints());
    }

    public function testSetAndGetFacade()
    {
        $builder = new Builder('Test');
        $facade = (object) array('id' => 99);

        $builder->setFacade($facade);

        $this->assertSame($facade, $builder->getFacade());
    }

    public function testGetFromAndExistingModelAccessors()
    {
        $builder = new Builder('Entry');
        $model = (object) array('entry_id' => 10);

        $this->assertSame('Entry', $builder->getFrom());
        $this->assertNull($builder->getExisting());

        $builder->setExisting($model);

        $this->assertSame($model, $builder->getExisting());
    }

    public function testWithTreatsClosureChildrenAsEmptyRelatedSet()
    {
        $builder = new Builder('Test');
        $builder->with(array('author' => function () {
        }));

        $this->assertSame(array('author' => array()), $builder->getWiths());
    }

    public function testSaveToCacheDoesNothingWhenCoreIsMissing()
    {
        $builder = new BuilderAccessProxy('Test');
        $builder->callSaveToCache('abc', 1);

        $this->assertFalse($builder->callGetFromCache('abc'));
    }

    public function testSaveToCacheAndGetFromCacheWithCore()
    {
        $core = m::mock();
        ee()->setMock('core', $core);

        $builder = new BuilderAccessProxy('Test');

        $core->shouldReceive('set_cache')->once()->with(
            Builder::class,
            'cache-key',
            'cache-value'
        );
        $core->shouldReceive('cache')->once()->with(
            Builder::class,
            'cache-key',
            false
        )->andReturn('cache-value');

        $builder->callSaveToCache('cache-key', 'cache-value');
        $this->assertSame('cache-value', $builder->callGetFromCache('cache-key'));
    }
}

class BuilderFetchProxy extends Builder
{
    public $fetchCacheArgs = array();
    private $fetchResult;

    public function setFetchResult($result)
    {
        $this->fetchResult = $result;
    }

    protected function fetch($cache = false)
    {
        $this->fetchCacheArgs[] = $cache;

        return $this->fetchResult;
    }
}

class BuilderAccessProxy extends Builder
{
    public function callFetch($cache = false)
    {
        return $this->fetch($cache);
    }

    public function callIsFutile()
    {
        return $this->isFutile();
    }

    public function callFilterStackIsEmpty()
    {
        return $this->filterStackIsEmpty();
    }

    public function callSaveToCache($key, $data)
    {
        $this->saveToCache($key, $data);
    }

    public function callGetFromCache($key)
    {
        return $this->getFromCache($key);
    }
}

class BuilderCacheProxy extends BuilderAccessProxy
{
    public $nextCacheValue = false;
    public $getCacheCalls = array();
    public $savedCache = array();

    protected function getFromCache($key)
    {
        $this->getCacheCalls[] = $key;

        return $this->nextCacheValue;
    }

    protected function saveToCache($key, $data)
    {
        $this->savedCache[$key] = $data;
    }
}

class BuilderBatchProxy extends Builder
{
    private $rows = array();

    public function __construct($from, array $rows)
    {
        parent::__construct($from);
        $this->rows = $rows;
    }

    public function all($cache = false)
    {
        $offset = (int) $this->getOffset();
        $limit = (int) $this->getLimit();
        $slice = array_slice($this->rows, $offset, $limit);

        return new Collection($slice);
    }
}
