<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Service\Model\Relation;

use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Service\Model\DataStore;
use ExpressionEngine\Service\Model\MetaDataReader;
use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Relation\HasOneOrMany;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class HasOneOrManyTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testCoreLinkMethodsAndDerivedKeys()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
        ));

        $source = new HasOneOrManySourceModelStub();
        $source->fill(array('entry_id' => 5));
        $target = new HasOneOrManyTargetModelStub();
        $target->fill(array('member_id' => 8));

        $this->assertTrue($relation->canSaveAcross());
        $this->assertSame(array('entry_id', 'member_id'), $relation->getKeys());

        $relation->fillLinkIds($source, $target);
        $this->assertSame(5, $target->member_id);

        $target->member_id = 9;
        $relation->linkIds($source, $target);
        $this->assertSame(5, $target->member_id);

        $target->member_id = 10;
        $this->assertArrayHasKey('member_id', $target->getDirty());
        $relation->markLinkAsClean($source, $target);
        $this->assertArrayNotHasKey('member_id', $target->getDirty());

        $relation->insert($source, array($target));
        $this->assertTrue(true);
    }

    public function testUnlinkIdsForStrongAndWeakRelations()
    {
        $strong = $this->makeRelation(array(
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
            'weak' => false
        ));
        $weak = $this->makeRelation(array(
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
            'weak' => true
        ));

        $source = new HasOneOrManySourceModelStub();
        $source->fill(array('entry_id' => 1));

        $strongTarget = new HasOneOrManyTargetModelStub();
        $strongTarget->member_id = 4;
        $strong->unlinkIds($source, $strongTarget);
        $this->assertNull($strongTarget->member_id);

        $weakTarget = new HasOneOrManyTargetModelStub();
        $weakTarget->member_id = 4;
        $weak->unlinkIds($source, $weakTarget);
        $this->assertSame(0, $weakTarget->member_id);
    }

    public function testDropDeletesByArrayTargetIdsForStrongRelations()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
            'weak' => false
        ));

        $source = new HasOneOrManySourceModelStub();
        $source->fill(array('entry_id' => 7));
        $targets = array(
            $this->makeTargetWithId(11),
            $this->makeTargetWithId(12),
        );

        $store = m::mock(DataStore::class);
        $query = m::mock();
        $store->shouldReceive('rawQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('member_id', 7)->andReturnSelf();
        $query->shouldReceive('where_in')->once()->with('member_id', array(11, 12))->andReturnSelf();
        $query->shouldReceive('delete')->once()->with('exp_members');

        $relation->setDataStore($store);
        $relation->drop($source, $targets);
        $this->assertTrue(true);
    }

    public function testDropWeakRelationWithSingleTargetUpdatesInsteadOfDelete()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
            'weak' => true
        ));

        $source = new HasOneOrManySourceModelStub();
        $source->fill(array('entry_id' => 3));
        $target = $this->makeTargetWithId(15);

        $store = m::mock(DataStore::class);
        $query = m::mock();
        $store->shouldReceive('rawQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('member_id', 3)->andReturnSelf();
        $query->shouldReceive('where_in')->once()->with('member_id', array(15))->andReturnSelf();
        $query->shouldReceive('set')->once()->with('member_id', 0)->andReturnSelf();
        $query->shouldReceive('update')->once()->with('exp_members');

        $relation->setDataStore($store);
        $relation->drop($source, $target);
        $this->assertTrue(true);
    }

    public function testDropWithoutTargetsSkipsWhereIn()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
            'weak' => false
        ));

        $source = new HasOneOrManySourceModelStub();
        $source->fill(array('entry_id' => 20));

        $store = m::mock(DataStore::class);
        $query = m::mock();
        $store->shouldReceive('rawQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('member_id', 20)->andReturnSelf();
        $query->shouldNotReceive('where_in');
        $query->shouldReceive('delete')->once()->with('exp_members');

        $relation->setDataStore($store);
        $relation->drop($source, null);
        $this->assertTrue(true);
    }

    public function testSetDropsComplementForArrayTargetsInStrongRelation()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
            'weak' => false
        ));

        $source = new HasOneOrManySourceModelStub();
        $source->fill(array('entry_id' => 7));
        $targets = array(
            $this->makeTargetWithId(5),
            $this->makeTargetWithId(6),
        );

        $store = m::mock(DataStore::class);
        $query = m::mock();
        $store->shouldReceive('rawQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('member_id', 7)->andReturnSelf();
        $query->shouldReceive('where_not_in')->once()->with('member_id', array(5, 6))->andReturnSelf();
        $query->shouldReceive('delete')->once()->with('exp_members');

        $relation->setDataStore($store);
        $relation->set($source, $targets);
        $this->assertTrue(true);
    }

    public function testSetDropsComplementForSingleTargetInWeakRelation()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
            'weak' => true
        ));

        $source = new HasOneOrManySourceModelStub();
        $source->fill(array('entry_id' => 7));
        $target = $this->makeTargetWithId(9);

        $store = m::mock(DataStore::class);
        $query = m::mock();
        $store->shouldReceive('rawQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('member_id', 7)->andReturnSelf();
        $query->shouldReceive('where_not_in')->once()->with('member_id', array(9))->andReturnSelf();
        $query->shouldReceive('set')->once()->with('member_id', 0)->andReturnSelf();
        $query->shouldReceive('update')->once()->with('exp_members');

        $relation->setDataStore($store);
        $relation->set($source, $target);
        $this->assertTrue(true);
    }

    private function makeRelation(array $options, $fromPrimary = 'entry_id', $toPrimary = 'member_id')
    {
        $from = m::mock(MetaDataReader::class);
        $to = m::mock(MetaDataReader::class);

        $from->shouldReceive('getPrimaryKey')->andReturn($fromPrimary);
        $to->shouldReceive('getPrimaryKey')->andReturn($toPrimary);
        $from->shouldReceive('getTableForField')->andReturn('exp_channel_titles');
        $to->shouldReceive('getTableForField')->andReturn('exp_members');
        $from->shouldReceive('getClass')->andReturn('HasOneOrManyFromClass');
        $to->shouldReceive('getClass')->andReturn('HasOneOrManyToClass');
        $from->shouldReceive('getName')->andReturn('ee:entry');
        $to->shouldReceive('getName')->andReturn('ee:member');

        return new HasOneOrManyTestRelation($from, $to, 'Author', $options);
    }

    private function makeTargetWithId($id)
    {
        $target = new HasOneOrManyTargetModelStub();
        $target->fill(array('member_id' => $id));

        return $target;
    }
}

class HasOneOrManyTestRelation extends HasOneOrMany
{
    public function createAssociation()
    {
        return null;
    }
}

class HasOneOrManySourceModelStub extends Model
{
    protected static $_primary_key = 'entry_id';

    protected $entry_id;
}

class HasOneOrManyTargetModelStub extends Model
{
    protected static $_primary_key = 'member_id';

    protected $member_id;
}

// EOF
