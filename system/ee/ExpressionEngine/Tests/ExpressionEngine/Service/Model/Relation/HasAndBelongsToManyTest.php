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

use ExpressionEngine\Service\Model\DataStore;
use ExpressionEngine\Service\Model\MetaDataReader;
use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Relation\HasAndBelongsToMany;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class HasAndBelongsToManyTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testCoreMetadataMethodsAssociationAndInverseOptions()
    {
        $relation = $this->makeRelation(array(
            'pivot' => array(
                'table' => 'exp_rel',
                'left' => 'entry_id',
                'right' => 'member_id',
            ),
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
            'inverse' => array(
                'type' => 'HasAndBelongsToMany',
                'name' => 'Members',
            ),
        ));

        $this->assertFalse($relation->canSaveAcross());
        $this->assertSame(
            array('left' => 'entry_id', 'right' => 'member_id', 'table' => 'exp_rel'),
            $relation->getPivot()
        );
        $this->assertSame(array('entry_id', 'member_id'), $relation->getKeys());
        $this->assertTrue($relation->isWeak());
        $this->assertInstanceOf(\ExpressionEngine\Service\Model\Association\ToMany::class, $relation->createAssociation());
        $this->assertSame(
            array(
                'type' => 'HasAndBelongsToMany',
                'name' => 'Members',
                'model' => 'from:model',
                'from_key' => 'member_id',
                'from_primary_key' => 'member_id',
                'to_key' => 'entry_id',
                'to_primary_key' => 'entry_id',
                'weak' => true,
                'pivot' => array(
                    'table' => 'exp_rel',
                    'left' => 'member_id',
                    'right' => 'entry_id',
                ),
            ),
            $relation->getInverseOptions()
        );
    }

    public function testDeriveKeysUsesPrimaryKeysWhenNoExplicitKeysProvided()
    {
        $relation = $this->makeRelation(
            array('pivot' => 'exp_rel'),
            'entry_id',
            'member_id'
        );

        $this->assertSame(array('entry_id', 'member_id'), $relation->getKeys());
        $this->assertSame(
            array('left' => 'entry_id', 'right' => 'member_id', 'table' => 'exp_rel'),
            $relation->getPivot()
        );
    }

    public function testModifyEagerAndLazyQueryJoinAndWhereClauses()
    {
        $relation = $this->makeRelation(array(
            'pivot' => array(
                'table' => 'exp_rel',
                'left' => 'entry_id',
                'right' => 'member_id',
            ),
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
        ));

        $query = m::mock();
        $query->shouldReceive('join')->once()->with(
            'exp_rel as from_to_exp_rel',
            'from_to_exp_rel.entry_id = from_exp_channel_titles.entry_id',
            'LEFT'
        );
        $query->shouldReceive('join')->once()->with(
            'exp_members as to_exp_members',
            'to_exp_members.member_id = from_to_exp_rel.member_id',
            'LEFT'
        );
        $relation->modifyEagerQuery($query, 'from', 'to');

        $lazyQuery = m::mock();
        $lazyQuery->shouldReceive('join')->once()->with(
            'exp_rel as _to_exp_rel',
            '_to_exp_rel.member_id = to_exp_members.member_id',
            'LEFT'
        );
        $lazyQuery->shouldReceive('where')->once()->with('_to_exp_rel.entry_id', 12);

        $source = new HasAndBelongsToManySourceModelStub();
        $source->fill(array('entry_id' => 12));
        $relation->modifyLazyQuery($lazyQuery, $source, 'to');
        $this->addToAssertionCount(1);
    }

    public function testInsertReturnsEarlyForEmptyTargets()
    {
        $relation = $this->makeRelation(array(
            'pivot' => array('table' => 'exp_rel', 'left' => 'entry_id', 'right' => 'member_id'),
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
        ));

        $store = m::mock(DataStore::class);
        $store->shouldNotReceive('rawQuery');
        $relation->setDataStore($store);

        $source = new HasAndBelongsToManySourceModelStub();
        $source->fill(array('entry_id' => 5));

        $relation->insert($source, array());
        $this->assertTrue(true);
    }

    public function testInsertWritesPivotRowsForEachTarget()
    {
        $relation = $this->makeRelation(array(
            'pivot' => array('table' => 'exp_rel', 'left' => 'entry_id', 'right' => 'member_id'),
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
        ));

        $store = m::mock(DataStore::class);
        $queryOne = m::mock();
        $queryTwo = m::mock();
        $store->shouldReceive('rawQuery')->twice()->andReturn($queryOne, $queryTwo);
        $queryOne->shouldReceive('set')->once()->with('entry_id', 5)->andReturnSelf();
        $queryOne->shouldReceive('set')->once()->with('member_id', 8)->andReturnSelf();
        $queryOne->shouldReceive('insert')->once()->with('exp_rel');
        $queryTwo->shouldReceive('set')->once()->with('entry_id', 5)->andReturnSelf();
        $queryTwo->shouldReceive('set')->once()->with('member_id', 9)->andReturnSelf();
        $queryTwo->shouldReceive('insert')->once()->with('exp_rel');
        $relation->setDataStore($store);

        $source = new HasAndBelongsToManySourceModelStub();
        $source->fill(array('entry_id' => 5));
        $targets = array(
            $this->makeTarget(8),
            $this->makeTarget(9),
        );

        $relation->insert($source, $targets);
        $this->addToAssertionCount(1);
    }

    public function testDropDeletesAllLinksWhenTargetsNotProvided()
    {
        $relation = $this->makeRelation(array(
            'pivot' => array('table' => 'exp_rel', 'left' => 'entry_id', 'right' => 'member_id'),
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
        ));

        $store = m::mock(DataStore::class);
        $query = m::mock();
        $store->shouldReceive('rawQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('entry_id', 3)->andReturnSelf();
        $query->shouldNotReceive('where_in');
        $query->shouldReceive('delete')->once()->with('exp_rel');
        $relation->setDataStore($store);

        $source = new HasAndBelongsToManySourceModelStub();
        $source->fill(array('entry_id' => 3));

        $relation->drop($source, null);
        $this->addToAssertionCount(1);
    }

    public function testDropDeletesOnlyProvidedTargetIds()
    {
        $relation = $this->makeRelation(array(
            'pivot' => array('table' => 'exp_rel', 'left' => 'entry_id', 'right' => 'member_id'),
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
        ));

        $store = m::mock(DataStore::class);
        $query = m::mock();
        $store->shouldReceive('rawQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('entry_id', 7)->andReturnSelf();
        $query->shouldReceive('where_in')->once()->with('member_id', array(11, 12))->andReturnSelf();
        $query->shouldReceive('delete')->once()->with('exp_rel');
        $relation->setDataStore($store);

        $source = new HasAndBelongsToManySourceModelStub();
        $source->fill(array('entry_id' => 7));
        $targets = array($this->makeTarget(11), $this->makeTarget(12));

        $relation->drop($source, $targets);
        $this->addToAssertionCount(1);
    }

    public function testDropReturnsEarlyWhenTargetIteratorYieldsNoIds()
    {
        $relation = $this->makeRelation(array(
            'pivot' => array('table' => 'exp_rel', 'left' => 'entry_id', 'right' => 'member_id'),
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
        ));

        $store = m::mock(DataStore::class);
        $query = m::mock();
        $store->shouldReceive('rawQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('entry_id', 99)->andReturnSelf();
        $query->shouldNotReceive('where_in');
        $query->shouldNotReceive('delete');
        $relation->setDataStore($store);

        $source = new HasAndBelongsToManySourceModelStub();
        $source->fill(array('entry_id' => 99));

        $relation->drop($source, new \ArrayIterator(array()));
        $this->addToAssertionCount(1);
    }

    public function testSetDelegatesToDropAndInsert()
    {
        $from = $this->makeMetaReader('entry_id', 'exp_channel_titles', 'FromClass', 'from:model');
        $to = $this->makeMetaReader('member_id', 'exp_members', 'ToClass', 'to:model');
        $relation = new HasAndBelongsToManySetHarness(
            $from,
            $to,
            'Members',
            array(
                'pivot' => array('table' => 'exp_rel', 'left' => 'entry_id', 'right' => 'member_id'),
                'from_key' => 'entry_id',
                'to_key' => 'member_id',
            )
        );

        $source = new HasAndBelongsToManySourceModelStub();
        $targets = array($this->makeTarget(1));
        $relation->set($source, $targets);

        $this->assertSame(array($source, null), $relation->dropCall);
        $this->assertSame(array($source, $targets), $relation->insertCall);
    }

    public function testNoOpLinkMethodsReturnNull()
    {
        $relation = $this->makeRelation(array(
            'pivot' => 'exp_rel',
            'from_key' => 'entry_id',
            'to_key' => 'member_id',
        ));

        $source = new HasAndBelongsToManySourceModelStub();
        $target = $this->makeTarget(10);

        $this->assertNull($relation->fillLinkIds($source, $target));
        $this->assertNull($relation->linkIds($source, $target));
        $this->assertNull($relation->unlinkIds($source, $target));
        $this->assertNull($relation->markLinkAsClean($source, $target));
    }

    private function makeRelation(array $options, $fromPk = 'entry_id', $toPk = 'member_id')
    {
        $from = $this->makeMetaReader($fromPk, 'exp_channel_titles', 'FromClass', 'from:model');
        $to = $this->makeMetaReader($toPk, 'exp_members', 'ToClass', 'to:model');

        return new HasAndBelongsToManyTestRelation($from, $to, 'Members', $options);
    }

    private function makeMetaReader($primaryKey, $table, $class, $name)
    {
        $meta = m::mock(MetaDataReader::class);
        $meta->shouldReceive('getPrimaryKey')->andReturn($primaryKey);
        $meta->shouldReceive('getTableForField')->andReturn($table);
        $meta->shouldReceive('getClass')->andReturn($class);
        $meta->shouldReceive('getName')->andReturn($name);

        return $meta;
    }

    private function makeTarget($memberId)
    {
        $target = new HasAndBelongsToManyTargetModelStub();
        $target->fill(array('member_id' => $memberId));

        return $target;
    }
}

class HasAndBelongsToManyTestRelation extends HasAndBelongsToMany
{
}

class HasAndBelongsToManySetHarness extends HasAndBelongsToMany
{
    public $dropCall;
    public $insertCall;

    public function drop(Model $source, $targets = null)
    {
        $this->dropCall = array($source, $targets);
    }

    public function insert(Model $source, $targets)
    {
        $this->insertCall = array($source, $targets);
    }
}

class HasAndBelongsToManySourceModelStub extends Model
{
    protected static $_primary_key = 'entry_id';

    protected $entry_id;
}

class HasAndBelongsToManyTargetModelStub extends Model
{
    protected static $_primary_key = 'member_id';

    protected $member_id;
}

// EOF
