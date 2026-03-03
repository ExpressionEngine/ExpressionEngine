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

use ExpressionEngine\Service\Model\MetaDataReader;
use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Service\Model\Relation\Relation;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RelationTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testMetadataGettersPivotAndInverseOptions()
    {
        $relation = $this->makeRelation(array(
            'weak' => true,
            'from_key' => 'fk_from',
            'to_key' => 'fk_to',
            'inverse' => array(
                'type' => 'BelongsTo',
                'name' => 'AuthorInverse'
            )
        ));

        $this->assertSame('Author', $relation->getName());
        $this->assertSame('from:model', $relation->getSourceModel());
        $this->assertSame('to:model', $relation->getTargetModel());
        $this->assertTrue($relation->isWeak());
        $this->assertSame(array(), $relation->getPivot());
        $this->assertTrue($relation->hasForeignInverse());
        $this->assertSame(array('fk_from', 'fk_to'), $relation->getKeys());

        $this->assertSame(array(
            'type' => 'BelongsTo',
            'name' => 'AuthorInverse',
            'model' => 'from:model',
            'from_key' => 'fk_to',
            'from_primary_key' => 'to_id',
            'to_key' => 'fk_from',
            'to_primary_key' => 'from_id',
            'weak' => true,
        ), $relation->getInverseOptions());
    }

    public function testSetInverseAndHasInverseKeepsFirstValue()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'fk_from',
            'to_key' => 'fk_to',
        ));
        $inverseA = $this->makeRelation(array(
            'from_key' => 'fk_from',
            'to_key' => 'fk_to',
        ));
        $inverseB = $this->makeRelation(array(
            'from_key' => 'fk_from',
            'to_key' => 'fk_to',
        ));

        $this->assertFalse($relation->hasInverse());
        $relation->setInverse($inverseA);
        $relation->setInverse($inverseB);
        $this->assertTrue($relation->hasInverse());
        $this->assertSame($inverseA, $relation->getInverse());
    }

    public function testGetInverseUsesGraphAndCachesResult()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'fk_from',
            'to_key' => 'fk_to',
        ));
        $inverse = $this->makeRelation(array(
            'from_key' => 'fk_from',
            'to_key' => 'fk_to',
        ));

        $store = m::mock();
        $graph = m::mock();
        $store->shouldReceive('getGraph')->once()->andReturn($graph);
        $graph->shouldReceive('getInverse')->once()->with($relation)->andReturn($inverse);

        $relation->setDataStore($store);
        $this->assertSame($inverse, $relation->getInverse());
        $this->assertSame($inverse, $relation->getInverse());
    }

    public function testGetInverseUsesForeignInverseFactoryWhenConfigured()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'fk_from',
            'to_key' => 'fk_to',
            'inverse' => array(
                'type' => 'HasMany',
                'name' => 'Children'
            )
        ));
        $inverse = $this->makeRelation(array(
            'from_key' => 'fk_from',
            'to_key' => 'fk_to',
        ));

        $store = m::mock();
        $graph = m::mock();
        $store->shouldReceive('getGraph')->once()->andReturn($graph);
        $graph->shouldReceive('makeForeignInverse')->once()->with($relation)->andReturn($inverse);

        $relation->setDataStore($store);
        $this->assertSame($inverse, $relation->getInverse());
    }

    public function testModifyEagerAndLazyQueries()
    {
        $relation = $this->makeRelation(array(
            'from_key' => 'fk_from',
            'to_key' => 'fk_to',
        ));
        $query = m::mock();
        $source = new RelationSourceModelStub();
        $source->fill(array('fk_from' => 21));

        $query->shouldReceive('join')->once()->with(
            'exp_to AS t_exp_to',
            't_exp_to.fk_to = f_exp_from.fk_from',
            'LEFT'
        );
        $query->shouldReceive('where')->once()->with('t_exp_to.fk_to', 21);

        $relation->modifyEagerQuery($query, 'f', 't');
        $relation->modifyLazyQuery($query, $source, 't');
        $this->assertTrue(true);
    }

    public function testProcessOptionsThrowsWhenFromTableMissing()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot find table for field missing_from on FromClass');

        $this->makeRelation(
            array('from_key' => 'missing_from', 'to_key' => 'fk_to'),
            array('fk_from' => 'exp_from'),
            array('fk_to' => 'exp_to')
        );
    }

    public function testProcessOptionsThrowsWhenToTableMissing()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot find table for field missing_to on ToClass from FromClass');

        $this->makeRelation(
            array('from_key' => 'fk_from', 'to_key' => 'missing_to'),
            array('fk_from' => 'exp_from'),
            array('fk_to' => 'exp_to')
        );
    }

    private function makeRelation(array $options, array $fromTables = array(), array $toTables = array())
    {
        $fromTables = $fromTables ?: array('fk_from' => 'exp_from');
        $toTables = $toTables ?: array('fk_to' => 'exp_to');

        $from = m::mock(MetaDataReader::class);
        $to = m::mock(MetaDataReader::class);

        $from->shouldReceive('getPrimaryKey')->andReturn('from_id');
        $to->shouldReceive('getPrimaryKey')->andReturn('to_id');
        $from->shouldReceive('getTableForField')->andReturnUsing(function ($field) use ($fromTables) {
            return array_key_exists($field, $fromTables) ? $fromTables[$field] : null;
        });
        $to->shouldReceive('getTableForField')->andReturnUsing(function ($field) use ($toTables) {
            return array_key_exists($field, $toTables) ? $toTables[$field] : null;
        });
        $from->shouldReceive('getClass')->andReturn('FromClass');
        $to->shouldReceive('getClass')->andReturn('ToClass');
        $from->shouldReceive('getName')->andReturn('from:model');
        $to->shouldReceive('getName')->andReturn('to:model');

        return new RelationTestProxy($from, $to, 'Author', $options);
    }
}

class RelationTestProxy extends Relation
{
    public function createAssociation()
    {
        return null;
    }

    public function fillLinkIds(Model $source, Model $target)
    {
    }

    public function linkIds(Model $source, Model $target)
    {
    }

    public function unlinkIds(Model $source, Model $target)
    {
    }

    public function markLinkAsClean(Model $source, Model $target)
    {
    }

    public function canSaveAcross()
    {
        return false;
    }

    public function insert(Model $source, $targets)
    {
    }

    public function drop(Model $source, $targets = null)
    {
    }

    public function set(Model $source, $targets)
    {
    }

    protected function deriveKeys()
    {
        $from = $this->from_key ?: $this->from_primary_key;
        $to = $this->to_key ?: $this->to_primary_key;

        return array($from, $to);
    }
}

class RelationSourceModelStub extends Model
{
    protected static $_primary_key = 'id';

    protected $id;
    protected $fk_from;
}

// EOF
