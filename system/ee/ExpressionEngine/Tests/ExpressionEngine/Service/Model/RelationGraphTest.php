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

use ExpressionEngine\Service\Model\DataStore;
use ExpressionEngine\Service\Model\MetaDataReader;
use ExpressionEngine\Service\Model\Relation\Relation;
use ExpressionEngine\Service\Model\RelationGraph;
use ExpressionEngine\Service\Model\Registry;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RelationGraphTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testGetAllBuildsCachesAndAddsForeignInverses()
    {
        $store = m::mock(DataStore::class);
        $registry = m::mock(Registry::class);
        $graph = m::mock(RelationGraph::class, array($store, $registry, array(
            'addon:forum' => array('ee:entry'),
            'addon:disabled' => array('ee:entry'),
        )))->makePartial();

        $entryReader = m::mock();
        $entryReader->shouldReceive('getRelationships')->once()->andReturn(array(
            'author' => array('type' => 'BelongsTo')
        ));
        $forumReader = m::mock();
        $forumReader->shouldReceive('getRelationships')->once()->andReturn(array(
            'thread' => array('type' => 'BelongsTo')
        ));

        $registry->shouldReceive('getPrefix')->with('entry')->andReturn('ee');
        $registry->shouldReceive('getPrefix')->with('addon:forum')->andReturn('addon');
        $registry->shouldReceive('isEnabled')->with('addon:forum')->twice()->andReturn(true);
        $registry->shouldReceive('isEnabled')->with('addon:disabled')->twice()->andReturn(false);
        $registry->shouldReceive('getMetaDataReader')->with('ee:entry')->once()->andReturn($entryReader);
        $registry->shouldReceive('getMetaDataReader')->with('addon:forum')->once()->andReturn($forumReader);

        $author = m::mock(Relation::class);
        $forumRelation = m::mock(Relation::class);
        $forumInverse = m::mock(Relation::class);

        $graph->shouldReceive('makeRelation')->once()->with('ee:entry', 'author')->andReturn($author);
        $graph->shouldReceive('makeRelation')->once()->with('addon:forum', 'thread')->andReturn($forumRelation);

        $forumRelation->shouldReceive('getTargetModel')->once()->andReturn('ee:entry');
        $forumRelation->shouldReceive('getInverse')->once()->andReturn($forumInverse);
        $forumInverse->shouldReceive('getName')->once()->andReturn('forum_inverse');

        $relations = $graph->getAll('entry');
        $cached = $graph->getAll('entry');

        $this->assertSame($relations, $cached);
        $this->assertSame($author, $relations['author']);
        $this->assertSame($forumInverse, $relations['forum_inverse']);
        $this->assertSame($author, $graph->get('entry', 'author'));
    }

    public function testGetInverseReturnsMatchingCandidate()
    {
        $store = m::mock(DataStore::class);
        $registry = m::mock(Registry::class);
        $graph = m::mock(RelationGraph::class, array($store, $registry, array()))->makePartial();

        $relation = m::mock(Relation::class);
        $relation->shouldReceive('getTargetModel')->once()->andReturn('target');
        $relation->shouldReceive('getSourceModel')->atLeast()->once()->andReturn('ee:source');
        $relation->shouldReceive('getKeys')->zeroOrMoreTimes()->andReturn(array('left', 'right'));
        $relation->shouldReceive('getPivot')->atLeast()->once()->andReturn(array(
            'table' => 'pivot',
            'left' => 'left_id',
            'right' => 'right_id'
        ));

        $mismatchCount = m::mock(Relation::class);
        $mismatchCount->shouldReceive('getTargetModel')->once()->andReturn('ee:source');
        $mismatchCount->shouldReceive('getKeys')->once()->andReturn(array('right', 'left'));
        $mismatchCount->shouldReceive('getPivot')->once()->andReturn(array());

        $mismatchPivot = m::mock(Relation::class);
        $mismatchPivot->shouldReceive('getTargetModel')->once()->andReturn('ee:source');
        $mismatchPivot->shouldReceive('getKeys')->once()->andReturn(array('right', 'left'));
        $mismatchPivot->shouldReceive('getPivot')->once()->andReturn(array(
            'table' => 'wrong',
            'left' => 'left_id',
            'right' => 'right_id'
        ));

        $match = m::mock(Relation::class);
        $match->shouldReceive('getTargetModel')->once()->andReturn('ee:source');
        $match->shouldReceive('getKeys')->once()->andReturn(array('right', 'left'));
        $match->shouldReceive('getPivot')->once()->andReturn(array(
            'table' => 'pivot',
            'left' => 'right_id',
            'right' => 'left_id'
        ));

        $registry->shouldReceive('getPrefix')->with('target')->once()->andReturn('ee');
        $graph->shouldReceive('getAll')->once()->with('ee:target')->andReturn(array(
            'a' => $mismatchCount,
            'b' => $mismatchPivot,
            'c' => $match
        ));

        $this->assertSame($match, $graph->getInverse($relation));
    }

    public function testGetInverseThrowsWhenNoReverseRelationExists()
    {
        $store = m::mock(DataStore::class);
        $registry = m::mock(Registry::class);
        $graph = m::mock(RelationGraph::class, array($store, $registry, array()))->makePartial();

        $relation = m::mock(Relation::class);
        $relation->shouldReceive('getTargetModel')->once()->andReturn('target');
        $relation->shouldReceive('getSourceModel')->atLeast()->once()->andReturn('ee:source');
        $relation->shouldReceive('getKeys')->zeroOrMoreTimes()->andReturn(array('left', 'right'));
        $relation->shouldReceive('getName')->once()->andReturn('authors');

        $candidate = m::mock(Relation::class);
        $candidate->shouldReceive('getTargetModel')->once()->andReturn('ee:other');

        $registry->shouldReceive('getPrefix')->with('target')->once()->andReturn('ee');
        $graph->shouldReceive('getAll')->once()->with('ee:target')->andReturn(array('x' => $candidate));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing Relationship.');
        $graph->getInverse($relation);
    }

    public function testMakeForeignInversePrefixesNameAndStoresRelation()
    {
        $store = m::mock(DataStore::class);
        $registry = m::mock(Registry::class);
        $graph = m::mock(RelationGraph::class, array($store, $registry, array()))->makePartial();

        $relation = m::mock(Relation::class);
        $relation->shouldReceive('getTargetModel')->once()->andReturn('target');
        $relation->shouldReceive('getInverseOptions')->once()->andReturn(array(
            'type' => 'BelongsTo',
            'name' => 'backref',
            'model' => 'ignored',
            'from_key' => 'fk_to',
            'to_key' => 'fk_from',
            'from_primary_key' => 'to_id',
            'to_primary_key' => 'from_id',
            'weak' => false,
        ));
        $relation->shouldReceive('getSourceModel')->twice()->andReturn('source');

        $registry->shouldReceive('getPrefix')->with('source')->once()->andReturn('ee');
        $registry->shouldReceive('getPrefix')->with('target')->once()->andReturn('ee');

        $created = m::mock(Relation::class);
        $graph->shouldReceive('makeRelation')->once()->with(
            'ee:target',
            'ee:backref',
            m::on(function ($options) {
                return $options['type'] === 'BelongsTo'
                    && $options['model'] === 'source'
                    && ! array_key_exists('name', $options);
            })
        )->andReturn($created);

        $this->assertSame($created, $graph->makeForeignInverse($relation));
    }

    public function testMakeRelationCreatesConcreteRelationAndSetsDataStore()
    {
        $store = m::mock(DataStore::class);
        $registry = m::mock(Registry::class);
        $graph = new RelationGraph($store, $registry, array());

        $fromReader = $this->readerMock('entry_id', 'exp_channel_titles', 'EntryClass', 'ee:entry');
        $toReader = $this->readerMock('member_id', 'exp_members', 'MemberClass', 'ee:member');

        $registry->shouldReceive('getMetaDataReader')->with('ee:entry')->once()->andReturn($fromReader);
        $registry->shouldReceive('getMetaDataReader')->with('ee:member')->once()->andReturn($toReader);

        $relation = $graph->makeRelation('ee:entry', 'author', array(
            'type' => 'BelongsTo',
            'model' => 'ee:member',
            'from_key' => 'author_id',
            'to_key' => 'member_id',
        ));

        $this->assertInstanceOf('ExpressionEngine\\Service\\Model\\Relation\\BelongsTo', $relation);

        $ref = new ReflectionClass('ExpressionEngine\\Service\\Model\\Relation\\Relation');
        $prop = $ref->getProperty('datastore');
        \TestReflectionHelper::makePropertyAccessible($prop);
        $this->assertSame($store, $prop->getValue($relation));
    }

    public function testMakeRelationThrowsOnUnknownType()
    {
        $store = m::mock(DataStore::class);
        $registry = m::mock(Registry::class);
        $graph = new RelationGraph($store, $registry, array());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown relationship type Nope in ee:entry');
        $graph->makeRelation('ee:entry', 'author', array(
            'type' => 'Nope',
            'model' => 'ee:member',
        ));
    }

    public function testMakeRelationWithoutOptionsUsesRelationshipMetadataAndSupportsPrefixedModelShortcut()
    {
        $store = m::mock(DataStore::class);
        $registry = m::mock(Registry::class);
        $graph = new RelationGraph($store, $registry, array());

        $entryReader = m::mock();
        $entryReader->shouldReceive('getRelationships')->once()->andReturn(array(
            'author' => array(
                'type' => 'BelongsTo',
                'model' => ':member',
                'from_key' => 'author_id',
                'to_key' => 'member_id',
            )
        ));
        $registry->shouldReceive('getMetaDataReader')->with('ee:entry')->once()->andReturn($entryReader);
        $registry->shouldReceive('getPrefix')->with('ee:entry')->once()->andReturn('ee');
        $registry->shouldReceive('modelExists')->with('ee::member')->once()->andReturn(true);

        $fromReader = $this->readerMock('entry_id', 'exp_channel_titles', 'EntryClass', 'ee:entry');
        $toReader = $this->readerMock('member_id', 'exp_members', 'MemberClass', 'ee::member');
        $registry->shouldReceive('getMetaDataReader')->with('ee:entry')->once()->andReturn($fromReader);
        $registry->shouldReceive('getMetaDataReader')->with('ee::member')->once()->andReturn($toReader);

        $relation = $graph->makeRelation('ee:entry', 'author');
        $this->assertInstanceOf('ExpressionEngine\\Service\\Model\\Relation\\BelongsTo', $relation);
    }

    public function testMakeRelationThrowsWhenRelationshipMissing()
    {
        $store = m::mock(DataStore::class);
        $registry = m::mock(Registry::class);
        $graph = new RelationGraph($store, $registry, array());

        $reader = m::mock();
        $reader->shouldReceive('getRelationships')->once()->andReturn(array('other' => array()));
        $registry->shouldReceive('getMetaDataReader')->with('ee:entry')->once()->andReturn($reader);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Relationship author not found in model ee:entry');
        $graph->makeRelation('ee:entry', 'author');
    }

    public function testMakeRelationThrowsWhenPreparedModelDoesNotExist()
    {
        $store = m::mock(DataStore::class);
        $registry = m::mock(Registry::class);
        $graph = new RelationGraph($store, $registry, array());

        $reader = m::mock();
        $reader->shouldReceive('getRelationships')->once()->andReturn(array(
            'author' => array(
                'type' => 'BelongsTo',
                'model' => 'ee:missing'
            )
        ));
        $registry->shouldReceive('getMetaDataReader')->with('ee:entry')->once()->andReturn($reader);
        $registry->shouldReceive('modelExists')->with('ee:missing')->once()->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown model "ee:missing". Used in model "ee:entry" for a relationship called "author".');
        $graph->makeRelation('ee:entry', 'author');
    }

    private function readerMock($primary, $table, $class, $name)
    {
        $reader = m::mock(MetaDataReader::class);
        $reader->shouldReceive('getPrimaryKey')->andReturn($primary);
        $reader->shouldReceive('getTableForField')->andReturn($table);
        $reader->shouldReceive('getClass')->andReturn($class);
        $reader->shouldReceive('getName')->andReturn($name);

        return $reader;
    }
}

// EOF
