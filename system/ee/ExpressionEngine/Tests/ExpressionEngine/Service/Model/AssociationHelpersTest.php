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
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Association\Diff;
use ExpressionEngine\Service\Model\Association\ToMany;
use ExpressionEngine\Service\Model\Association\ToOne;
use PHPUnit\Framework\TestCase;

class AssociationHelpersTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testDiffCommitUsesSetWhenAssociationWasSet()
    {
        $parent = new \stdClass();
        $relation = m::mock();
        $model = $this->makeModel(1, 10);

        $relation->shouldReceive('set')->once()->with($parent, m::on(function ($added) use ($model) {
            return count($added) === 1 && current($added) === $model;
        }));
        $relation->shouldNotReceive('drop');
        $relation->shouldNotReceive('insert');

        $diff = new Diff($parent, $relation);
        $diff->add($model);
        $diff->wasSet();
        $diff->commit();
        $this->addToAssertionCount(1);
    }

    public function testDiffCommitDropsRemovedAndInsertsAddedWhenNotSet()
    {
        $parent = new \stdClass();
        $relation = m::mock();
        $added = $this->makeModel(1, 10);
        $removed = $this->makeModel(2, 11);

        $relation->shouldReceive('drop')->once()->with($parent, m::on(function ($items) use ($removed) {
            return count($items) === 1 && current($items) === $removed;
        }));
        $relation->shouldReceive('insert')->once()->with($parent, m::on(function ($items) use ($added) {
            return count($items) === 1 && current($items) === $added;
        }));
        $relation->shouldNotReceive('set');

        $diff = new Diff($parent, $relation);
        $diff->add($added);
        $diff->remove($removed);
        $diff->commit();
        $this->addToAssertionCount(1);
    }

    public function testDiffFastUndoRemovePath()
    {
        $parent = new \stdClass();
        $relation = m::mock();
        $model = $this->makeModel(3, 33);

        $relation->shouldReceive('insert')->once()->with($parent, array());
        $relation->shouldNotReceive('drop');
        $relation->shouldNotReceive('set');

        $diff = new Diff($parent, $relation);
        $diff->remove($model);
        $diff->add($model); // should undo remove and add model to added list
        $diff->commit();
        $this->addToAssertionCount(1);
    }

    public function testToManyFillAndHasBranches()
    {
        $relation = $this->makeRelation();
        $model = $this->makeModel(1, 10);
        $another = $this->makeModel(2, 20);

        $assoc = new ToManyTestProxy($relation);
        $assoc->fill($model, true);
        $this->assertInstanceOf(Collection::class, $assoc->getRelatedPublic());

        $assoc->fill(array($model, $another), true);
        $this->assertCount(2, $assoc->getRelatedPublic());

        $collection = new Collection(array($model));
        $assoc->fill($collection, true);
        $this->assertSame($assoc, $collection->getAssociation());

        $this->assertFalse((new ToManyTestProxy($relation))->hasPublic($model), 'null related collection should not contain item');
        $assoc->setRelatedPublic(new Collection(array($model)));
        $this->assertTrue($assoc->hasPublic($model), 'same object should be detected');
        $this->assertTrue($assoc->hasPublic($this->makeModel(1, 999)), 'same class/id should be detected');
        $this->assertFalse($assoc->hasPublic($this->makeOtherModel(1)), 'different class should not be detected');

        $assoc->foreignKeyChanged('ignored');
        $this->assertTrue(true);
    }

    public function testToOneFillHandlesCollectionAndArray()
    {
        $relation = $this->makeRelation();
        $first = $this->makeModel(1, 10);
        $second = $this->makeModel(2, 20);

        $assoc = new ToOneTestProxy($relation);
        $assoc->fill(new Collection(array($first)), true);
        $this->assertSame($first, $assoc->getRelatedPublic());

        $assoc->fill(array($second), true);
        $this->assertSame($second, $assoc->getRelatedPublic());
    }

    private function makeRelation()
    {
        $relation = m::mock('ExpressionEngine\Service\Model\Relation\Relation');
        $relation->shouldReceive('getKeys')->andReturn(array('fk', 'id'));

        return $relation;
    }

    private function makeModel($id, $fk)
    {
        $model = new AssociationModelStub();
        $model->fill(array('id' => $id, 'fk' => $fk));

        return $model;
    }

    private function makeOtherModel($id)
    {
        $model = new AssociationOtherModelStub();
        $model->fill(array('id' => $id));

        return $model;
    }
}

class ToManyTestProxy extends ToMany
{
    public function hasPublic($model)
    {
        return $this->has($model);
    }

    public function setRelatedPublic($related)
    {
        $this->related = $related;
    }

    public function getRelatedPublic()
    {
        return $this->related;
    }
}

class ToOneTestProxy extends ToOne
{
    public function getRelatedPublic()
    {
        return $this->related;
    }
}

class AssociationModelStub extends Model
{
    protected static $_primary_key = 'id';

    protected $id;
    protected $fk;
}

class AssociationOtherModelStub extends Model
{
    protected static $_primary_key = 'id';

    protected $id;
}

// EOF
