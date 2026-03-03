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

use InvalidArgumentException;
use Mockery as m;
use ExpressionEngine\Library\Data\Collection as CoreCollection;
use ExpressionEngine\Service\Model\Association\Association;
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Service\Model\Model;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testGetIdsAndIndexByIds()
    {
        $first = $this->makeModel(10, 1, 'a');
        $second = $this->makeModel(20, 2, 'b');

        $collection = new Collection(array($first, $second));

        $this->assertSame(array(10, 20), $collection->getIds());

        $indexed = $collection->indexByIds();
        $this->assertSame($first, $indexed[10]);
        $this->assertSame($second, $indexed[20]);
    }

    public function testFilterSupportsClosuresAndAllOperators()
    {
        $first = $this->makeModel(1, 5, 'alpha');
        $second = $this->makeModel(2, 10, 'beta');
        $third = $this->makeModel(3, 15, 'gamma');

        $collection = new Collection(array($first, $second, $third));

        $this->assertSame(array(2), $collection->filter('id', 2)->getIds(), 'default equals operator');
        $this->assertSame(array(1), $collection->filter('score', '<', 10)->getIds());
        $this->assertSame(array(3), $collection->filter('score', '>', 10)->getIds());
        $this->assertSame(array(1, 2), $collection->filter('score', '<=', 10)->getIds());
        $this->assertSame(array(2, 3), $collection->filter('score', '>=', 10)->getIds());
        $this->assertSame(array(2), $collection->filter('tag', '==', 'beta')->getIds());
        $this->assertSame(array(1, 3), $collection->filter('tag', '!=', 'beta')->getIds());
        $this->assertSame(array(1, 3), $collection->filter('id', 'IN', array(1, 3))->getIds());
        $this->assertSame(array(1, 3), $collection->filter('id', 'NOT IN', array(2))->getIds());
        $this->assertSame(array(2, 3), $collection->filter(function ($model) {
            return $model->score >= 10;
        })->getIds());
    }

    public function testFilterThrowsOnInvalidOperator()
    {
        $collection = new Collection(array($this->makeModel(1, 1, 'a')));

        $this->expectException(InvalidArgumentException::class);
        $collection->filter('score', '<>', 1);
    }

    public function testIntersectReturnsSelfForSmallCollections()
    {
        $collection = new Collection(array($this->makeModel(1, 5, 'alpha')));

        $this->assertSame($collection, $collection->intersect());
    }

    public function testIntersectReturnsUniqueModelsForModelCollection()
    {
        $first = $this->makeModel(1, 5, 'alpha');
        $second = $this->makeModel(1, 9, 'duplicate');

        $collection = new Collection(array($first, $second));
        $intersection = $collection->intersect();

        $this->assertInstanceOf(Collection::class, $intersection);
        $this->assertSame(array(1), $intersection->getIds());
    }

    public function testIntersectReturnsCommonModelsForCollectionOfCollections()
    {
        $model_one = $this->makeModel(1, 5, 'one');
        $model_two = $this->makeModel(2, 6, 'two');
        $model_three = $this->makeModel(3, 7, 'three');

        $left = new Collection(array($model_one, $model_two));
        $middle = new Collection(array($model_two, $model_three));
        $right = new Collection(array($model_two));

        $collection = new Collection(array($left, $middle, $right));
        $intersection = $collection->intersect();

        $this->assertSame(array(2), $intersection->getIds());
    }

    public function testAddPropagatesOnlyWhenEnabled()
    {
        $association = m::mock(Association::class);
        $association->shouldReceive('add')->once();

        $collection = new Collection();
        $collection->setAssociation($association);

        $collection->add($this->makeModel(1, 5, 'one'));
        $collection->add($this->makeModel(2, 6, 'two'), false);

        $this->assertCount(2, $collection);
    }

    public function testRemoveReturnsSelfWhenEmpty()
    {
        $collection = new Collection();

        $this->assertSame($collection, $collection->remove($this->makeModel(1, 5, 'one')));
    }

    public function testRemoveByModelCollectionClosureAndPrimaryKey()
    {
        $first = $this->makeModel(1, 5, 'one');
        $second = $this->makeModel(2, 10, 'two');
        $third = $this->makeModel(3, 15, 'three');

        $association = m::mock(Association::class);
        $association->shouldReceive('remove')->once()->with($first);
        $association->shouldReceive('remove')->once()->with($second);
        $association->shouldReceive('remove')->once()->with($third);
        $association->shouldReceive('remove')->once()->with($second);

        $collection = new Collection(array($first, $second, $third));
        $collection->setAssociation($association);

        $this->assertSame($collection, $collection->remove($first));
        $this->assertSame($collection, $collection->remove(new CoreCollection(array($second))));
        $this->assertSame($collection, $collection->remove(function ($model) {
            return $model->id === 3;
        }));
        $this->assertSame($collection, $collection->remove(2));
    }

    public function testWithIsCurrentlyNoop()
    {
        $collection = new Collection(array($this->makeModel(1, 5, 'one')));

        $this->assertNull($collection->with('Author'));
    }

    private function makeModel($id, $score, $tag)
    {
        $model = new CollectionModelStub();
        $model->fill(array(
            'id' => $id,
            'score' => $score,
            'tag' => $tag,
        ));

        return $model;
    }
}

class CollectionModelStub extends Model
{
    protected static $_primary_key = 'id';

    protected $id;
    protected $score;
    protected $tag;
}

// EOF
