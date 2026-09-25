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

use ExpressionEngine\Service\Model\Query\Result;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testAllReturnsEmptyCollectionWhenNoRows()
    {
        $result = new Result(array(), array('root' => 'RootModel'), array());

        $this->assertCount(0, $result->all());
    }

    public function testFirstReturnsNullWhenNoRows()
    {
        $result = new Result(array(), array('root' => 'RootModel'), array());

        $this->assertNull($result->first());
    }

    public function testAllParsesRowsBuildsRelationsAndEmitsLifecycleEvents()
    {
        $relation = m::mock();
        $relation->shouldReceive('getName')->once()->andReturn('Children');

        $rows = array(
            array(
                'root__id' => 1,
                'root__title' => 'Root',
                'child__id' => 2,
                'child__title' => 'Child',
            ),
            array(
                'root__id' => 1,
                'root__title' => 'Root',
                'child__id' => 2,
                'child__title' => 'Child',
            ),
        );

        $result = new Result(
            $rows,
            array('root' => 'RootModel', 'child' => 'ChildModel'),
            array('child' => array('root' => $relation))
        );

        $facade = new ResultFacadeStub();
        $result->setFacade($facade);
        $collection = $result->all();

        $this->assertCount(1, $collection);
        $root = $collection->first();
        $this->assertInstanceOf(ResultModelStub::class, $root);
        $this->assertSame(array('beforeLoad', 'afterLoad'), $root->events);
        $this->assertSame(array('beforeLoad', 'afterLoad'), $facade->created['ChildModel'][0]->events);

        $association = $root->getAssociation('Children');
        $this->assertCount(1, $association->fills);
        $this->assertCount(1, $association->fills[0]);
        $this->assertSame(2, $association->fills[0][0]->getId());
    }

    public function testAllDoesNotAttachNullPrimaryKeyRelatedRows()
    {
        $relation = m::mock();
        $relation->shouldReceive('getName')->once()->andReturn('Children');

        $rows = array(
            array(
                'root__id' => 1,
                'root__title' => 'Root',
                'child__id' => null,
                'child__title' => null,
            ),
        );

        $result = new Result(
            $rows,
            array('root' => 'RootModel', 'child' => 'ChildModel'),
            array('child' => array('root' => $relation))
        );

        $result->setFacade(new ResultFacadeStub());
        $collection = $result->all();

        $this->assertCount(1, $collection);
        $root = $collection->first();
        $association = $root->getAssociation('Children');

        $this->assertCount(1, $association->fills);
        $this->assertCount(0, $association->fills[0]);
    }

    public function testFirstReturnsFirstModelWhenRowsExist()
    {
        $rows = array(
            array('root__id' => 10, 'root__title' => 'Alpha')
        );

        $result = new Result($rows, array('root' => 'RootModel'), array());
        $result->setFacade(new ResultFacadeStub());

        $first = $result->first();

        $this->assertInstanceOf(ResultModelStub::class, $first);
        $this->assertSame(10, $first->getId());
    }

    public function testParseRowThrowsExceptionForUnknownPropertyInColumns()
    {
        $result = new ResultAccessProxy(array(), array('root' => 'RootModel'), array());
        $result->setFacade(new ResultFacadeStub());
        $result->setColumnsPublic(array('root' => array('id', 'missing')));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown model property in query result: `root.missing`');

        $result->parseRowPublic(array('root__id' => 1));
    }

    public function testParseRowSkipsAliasesWithNoColumns()
    {
        $result = new ResultAccessProxy(array(), array('root' => 'RootModel'), array());
        $result->setFacade(new ResultFacadeStub());
        $result->setColumnsPublic(array('root' => array()));

        $result->parseRowPublic(array());

        $this->assertSame(array(), $result->getObjectsPublic());
    }

    public function testCollectColumnsByAliasPrefixGroupsByAlias()
    {
        $result = new ResultAccessProxy(array(), array('root' => 'RootModel'), array());
        $result->collectColumnsByAliasPrefixPublic(array(
            'root__id' => 1,
            'root__title' => 'A',
            'child__id' => 2,
        ));

        $this->assertSame(
            array(
                'root' => array('id', 'title'),
                'child' => array('id'),
            ),
            $result->getColumnsPublic()
        );
    }

    public function testInitializeResultArrayCreatesBucketsPerAlias()
    {
        $result = new ResultAccessProxy(array(), array('root' => 'RootModel', 'child' => 'ChildModel'), array());
        $result->initializeResultArrayPublic();

        $this->assertSame(
            array('root' => array(), 'child' => array()),
            $result->getObjectsPublic()
        );
    }

    public function testSetFacadeReturnsSameInstance()
    {
        $result = new Result(array(), array(), array());
        $facade = new ResultFacadeStub();

        $this->assertSame($result, $result->setFacade($facade));
    }
}

class ResultAccessProxy extends Result
{
    public function setColumnsPublic($columns)
    {
        $this->columns = $columns;
    }

    public function getColumnsPublic()
    {
        return $this->columns;
    }

    public function getObjectsPublic()
    {
        return $this->objects;
    }

    public function parseRowPublic($row)
    {
        $this->parseRow($row);
    }

    public function collectColumnsByAliasPrefixPublic($row)
    {
        $this->collectColumnsByAliasPrefix($row);
    }

    public function initializeResultArrayPublic()
    {
        $this->initializeResultArray();
    }
}

class ResultFacadeStub
{
    public $created = array();

    public function make($name)
    {
        $model = new ResultModelStub();
        if (! isset($this->created[$name])) {
            $this->created[$name] = array();
        }
        $this->created[$name][] = $model;

        return $model;
    }
}

class ResultModelStub
{
    public $events = array();
    private $id;
    private $associations = array();

    public function emit($event)
    {
        $this->events[] = $event;
    }

    public function fill(array $data)
    {
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPrimaryKey()
    {
        return 'id';
    }

    public function getAssociation($name)
    {
        if (! isset($this->associations[$name])) {
            $this->associations[$name] = new ResultAssociationStub();
        }

        return $this->associations[$name];
    }
}

class ResultAssociationStub
{
    public $fills = array();

    public function fill($collection)
    {
        $this->fills[] = $collection;
    }
}

// EOF
