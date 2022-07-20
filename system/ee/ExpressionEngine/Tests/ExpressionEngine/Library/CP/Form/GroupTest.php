<?php
namespace ExpressionEngine\Tests\Library\CP\Form;

use ExpressionEngine\Library\CP\Form\Group;
use PHPUnit\Framework\TestCase;

class _group extends Group
{
    public function getPrototype(): array
    {
        return $this->prototype;
    }

    public function getStructure(): array
    {
        return $this->structure;
    }
}

/**
 * @covers \ExpressionEngine\Library\CP\Form\Group
 */
class GroupTest extends TestCase
{
    /**
     * @return Group
     */
    public function testPrototypeAttribute(): Group
    {
        $group = new _group('test-group');
        $this->assertObjectHasAttribute('prototype', $group);
        $this->assertCount(0, $group->getPrototype());
        return $group;
    }

    /**
     * @depends testPrototypeAttribute
     * @param Group $group
     * @return Group
     */
    public function testStructureAttribute(Group $group): Group
    {
        $this->assertObjectHasAttribute('structure', $group);
        $this->assertCount(0, $group->getStructure());
        return $group;
    }

    public function testGetNameReturnValue()
    {
        $group = new Group('test-group');
        $this->assertEquals('test-group', $group->getName());
    }

    /**
     * @return Group
     */
    public function testGetEmptySetReturnsASet(): Group
    {
        $group = new Group('test-group');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Set', $group->getFieldSet('test_set'));
        return $group;
    }

    /**
     * @depends testGetEmptySetReturnsASet
     * @param Group $group
     * @return Group
     */
    public function testValidGetSetInstance(Group $group): Group
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Set', $group->getFieldSet('test_set'));
        return $group;
    }

    /**
     * @depends testValidGetSetInstance
     * @param Group $group
     * @return Group
     */
    public function testRemoveValidSetReturnsTrue(Group $group): Group
    {
        $this->assertTrue($group->removeFieldSet('test_set'));
        return $group;
    }

    /**
     * @depends testRemoveValidSetReturnsTrue
     * @param Group $group
     * @return void
     * @throws \Exception
     */
    public function testRemoveBadSetReturnsFalse(Group $group)
    {
        $set = '_'.\random_bytes(10);
        $this->assertFalse($group->removeFieldSet($set));
    }

    /**
     * @return Group
     */
    public function testDefaultReturnStructureForAsArray(): Group
    {
        $group = new Group;
        $array = $group->toArray();
        $this->assertCount(0, $array);
        return $group;
    }

    /**
     * @depends testDefaultReturnStructureForAsArray
     * @param Group $group
     * @return void
     */
    public function testSingleFieldSetAsArray(Group $group)
    {
        $group->getFieldSet('test-fieldset');
        $array = $group->toArray();
        $this->assertCount(1, $array);
    }
}
