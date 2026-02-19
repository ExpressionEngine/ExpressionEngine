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

use ExpressionEngine\Service\Model\Association\Association;
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Service\Model\MetaDataReader;
use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Relation\HasOneOrMany;
use ExpressionEngine\Service\Model\Relation\Relation;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AssociationCoreTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testFillUsesInverseForHasOneOrManyAndMarksLoaded()
    {
        $relation = $this->makeHasOneOrManyRelation();
        $association = new AssociationFillProxy($relation);
        $inverse = new AssociationInverseRecorder();
        $association->inverse = $inverse;

        $source = new AssociationCoreSourceModelStub();
        $source->fill(array('id' => 1, 'fk' => 77));
        $target = new AssociationCoreTargetModelStub();
        $target->fill(array('id' => 2, 'fk' => 0));

        $association->boot($source);
        $association->fill(array($target), false);

        $this->assertTrue($association->isLoaded());
        $this->assertSame(77, $target->fk);
        $this->assertSame(0, $inverse->calls);
    }

    public function testGetReloadsWhenNotLoadedThenReturnsCachedRelated()
    {
        $relation = m::mock(Relation::class);
        $relation->shouldReceive('getKeys')->once()->andReturn(array('fk', 'id'));
        $relation->shouldReceive('getTargetModel')->once()->andReturn('ee:target');

        $association = new Association($relation);
        $source = new AssociationCoreSourceModelStub();
        $source->fill(array('id' => 10, 'fk' => 42));
        $association->boot($source);

        $diff = m::mock();
        $diff->shouldReceive('reset')->once();
        $this->setProtected($association, 'diff', $diff);

        $query = m::mock();
        $query->shouldReceive('setLazyConstraint')->once()->with($relation, $source);

        $related = new Collection(array(new AssociationCoreTargetModelStub()));
        $relation->shouldReceive('fillLinkIds')->once()->with($source, m::type(Model::class));
        $query->shouldReceive('all')->once()->andReturn($related);

        $facade = m::mock();
        $facade->shouldReceive('get')->once()->with('ee:target')->andReturn($query);
        $association->setFacade($facade);

        $first = $association->get();
        $second = $association->get();

        $this->assertSame($related, $first);
        $this->assertSame($first, $second);
        $this->assertSame($association, $related->getAssociation());
    }

    public function testSaveCommitsDiffAndHandlesSavingGuards()
    {
        $relation = m::mock(Relation::class);
        $relation->shouldReceive('getKeys')->once()->andReturn(array('fk', 'id'));
        $relation->shouldReceive('canSaveAcross')->once()->andReturn(true);

        $association = new Association($relation);
        $diff = m::mock();
        $diff->shouldReceive('commit')->once();
        $this->setProtected($association, 'diff', $diff);

        $related = m::mock();
        $related->shouldReceive('save')->once();
        $this->setProtected($association, 'related', $related);

        $association->save();

        $guardedRelation = m::mock(Relation::class);
        $guardedRelation->shouldReceive('getKeys')->once()->andReturn(array('fk', 'id'));
        $guardedRelation->shouldNotReceive('canSaveAcross');

        $guarded = new Association($guardedRelation);
        $guardedDiff = m::mock();
        $guardedDiff->shouldReceive('commit')->once();
        $this->setProtected($guarded, 'diff', $guardedDiff);
        $this->setProtected($guarded, 'saving', true);

        $guardedRelated = m::mock();
        $guardedRelated->shouldNotReceive('save');
        $this->setProtected($guarded, 'related', $guardedRelated);

        $guarded->save();
        $this->assertTrue(true);
    }

    public function testToModelArrayConvertsInputShapesAndRejectsInvalidType()
    {
        $relation = m::mock(Relation::class);
        $relation->shouldReceive('getKeys')->once()->andReturn(array('fk', 'id'));
        $association = new AssociationAccessProxy($relation);

        $model = new AssociationCoreTargetModelStub();
        $collection = new Collection(array($model));

        $this->assertSame(array(), $association->toModelArrayPublic(null));
        $this->assertSame(array($model), $association->toModelArrayPublic(array($model)));
        $this->assertSame(array($model), $association->toModelArrayPublic($model));
        $this->assertSame(array($model), $association->toModelArrayPublic($collection));

        $this->expectException(\InvalidArgumentException::class);
        $association->toModelArrayPublic(new \stdClass());
    }

    private function makeHasOneOrManyRelation()
    {
        $from = m::mock(MetaDataReader::class);
        $to = m::mock(MetaDataReader::class);
        $from->shouldReceive('getPrimaryKey')->andReturn('id');
        $to->shouldReceive('getPrimaryKey')->andReturn('id');
        $from->shouldReceive('getTableForField')->andReturn('exp_from');
        $to->shouldReceive('getTableForField')->andReturn('exp_to');
        $from->shouldReceive('getClass')->andReturn('AssociationFromClass');
        $to->shouldReceive('getClass')->andReturn('AssociationToClass');
        $from->shouldReceive('getName')->andReturn('ee:source');
        $to->shouldReceive('getName')->andReturn('ee:target');

        return new AssociationHasOneOrManyRelationStub($from, $to, 'Target', array(
            'from_key' => 'fk',
            'to_key' => 'fk',
        ));
    }

    private function setProtected($object, $property, $value)
    {
        $ref = new ReflectionClass(Association::class);
        $prop = $ref->getProperty($property);
        \TestReflectionHelper::makePropertyAccessible($prop);
        $prop->setValue($object, $value);
    }
}

class AssociationFillProxy extends Association
{
    public $inverse;

    public function getInverse(Model $model)
    {
        return $this->inverse;
    }
}

class AssociationAccessProxy extends Association
{
    public function toModelArrayPublic($item)
    {
        return $this->toModelArray($item);
    }
}

class AssociationHasOneOrManyRelationStub extends HasOneOrMany
{
    public function createAssociation()
    {
        return null;
    }
}

class AssociationCoreSourceModelStub extends Model
{
    protected static $_primary_key = 'id';

    protected $id;
    protected $fk;
}

class AssociationCoreTargetModelStub extends Model
{
    protected static $_primary_key = 'id';

    protected $id;
    protected $fk;
}

class AssociationInverseRecorder
{
    public $calls = 0;

    public function fill($related, $_skip_inverse = false)
    {
        $this->calls++;
    }
}

// EOF
