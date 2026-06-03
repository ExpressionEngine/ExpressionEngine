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

use ArrayObject;
use Mockery as m;
use ExpressionEngine\Service\Model\Mixin\Relationship;
use PHPUnit\Framework\TestCase;

class RelationshipMixinTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testGetNameAndAssociationActionLookup()
    {
        $scope = new RelationshipMixinScopeStub();
        $association = m::mock();
        $scope->registerAssociation('Author', $association);

        $mixin = new Relationship($scope);

        $this->assertSame('Model:Relationship', $mixin->getName());
        $this->assertSame(
            array($association, 'Author', 'get'),
            $mixin->getAssociationActionFromMethod('getAuthor')
        );
        $this->assertNull($mixin->getAssociationActionFromMethod('unknownMethod'));
        $this->assertNull($mixin->getAssociationAction('Missing', 'get'));
    }

    public function testRunAssociationActionForGetFillSetAddAndRemove()
    {
        $scope = new RelationshipMixinScopeStub();
        $scope->Author = 'current';

        $association = m::mock();
        $association->shouldReceive('fill')->once()->with('payload')->andReturn('filled');
        $association->shouldReceive('remove')->once()->with(123)->andReturn('removed');

        $mixin = new Relationship($scope);

        $this->assertSame('current', $mixin->runAssociationAction(array($association, 'Author', 'get'), array()));
        $this->assertSame('filled', $mixin->runAssociationAction(array($association, 'Author', 'fill'), array('payload')));

        $result = $mixin->runAssociationAction(array($association, 'Author', 'set'), array('updated'));
        $this->assertSame($scope, $result);
        $this->assertSame('updated', $scope->Author);

        $scope->Author = new ArrayObject(array('first'));
        $result = $mixin->runAssociationAction(array($association, 'Author', 'add'), array('second'));
        $this->assertSame($scope, $result);
        $this->assertSame(array('first', 'second'), $scope->Author->getArrayCopy());

        $this->assertSame('removed', $mixin->runAssociationAction(array($association, 'Author', 'remove'), array(123)));
    }

    public function testRunAssociationActionThrowsForDeprecatedAndIllegalActions()
    {
        $scope = new RelationshipMixinScopeStub();
        $association = m::mock();
        $mixin = new Relationship($scope);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Can no longer create relationships');
        $mixin->runAssociationAction(array($association, 'Author', 'create'), array());
    }

    public function testRunAssociationActionThrowsForDeleteAndUnknownAction()
    {
        $scope = new RelationshipMixinScopeStub();
        $association = m::mock();
        $mixin = new Relationship($scope);

        try {
            $mixin->runAssociationAction(array($association, 'Author', 'delete'), array());
            $this->fail('Expected delete action to throw.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Can no longer delete relationships', $e->getMessage());
        }

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Illegal Relationship action: noop');
        $mixin->runAssociationAction(array($association, 'Author', 'noop'), array());
    }
}

class RelationshipMixinScopeStub
{
    public $Author;
    private $associations = array();

    public function registerAssociation($name, $association)
    {
        $this->associations[$name] = $association;
    }

    public function hasAssociation($name)
    {
        return array_key_exists($name, $this->associations);
    }

    public function getAssociation($name)
    {
        return $this->associations[$name];
    }
}

// EOF
