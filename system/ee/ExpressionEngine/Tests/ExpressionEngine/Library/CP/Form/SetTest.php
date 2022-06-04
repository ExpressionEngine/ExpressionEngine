<?php
namespace ExpressionEngine\Tests\Library\CP\Form;

use ExpressionEngine\Library\CP\Form\Set;
use PHPUnit\Framework\TestCase;

class _set extends Set
{
    public function getPrototype(): array
    {
        return $this->prototype;
    }

    public function getStructure(): array
    {
        return $this->structure;
    }

    public function getName()
    {
        return $this->name;
    }
}

/**
 * @covers \ExpressionEngine\Library\CP\Form\Set
 */
class SetTest extends TestCase
{
    /**
     * @return Set
     */
    public function testPrototypeAttribute(): Set
    {
        $field_set = new _set('test-set');
        $this->assertObjectHasAttribute('prototype', $field_set);
        $this->assertCount(7, $field_set->getPrototype());
        return $field_set;
    }

    /**
     * @depends testPrototypeAttribute
     * @param Set $field_set
     * @return void
     */
    public function testStructureAttribute(Set $field_set)
    {
        $this->assertObjectHasAttribute('structure', $field_set);
        $this->assertCount(0, $field_set->getStructure());
    }

    /**
     * @return void
     */
    public function testInstantiationSetsTitleToPrototype()
    {
        $field_set = new Set("test-field-set");
        $this->assertEquals('test-field-set', $field_set->getTitle());
    }

    /**
     * To be honest, am not sure there's any need to test for this but whatever
     * @return void
     */
    public function testInstantiationSetsNameProperty()
    {
        $field_set = new _set("test-field-set");
        $this->assertEquals('test-field-set', $field_set->getName());
    }

    /**
     * @return void
     */
    public function testGetFieldInvalidFieldTypeButFieldDoesNotExist(): Set
    {
        $field_set = new Set("test-field-set");
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field_set->getField('test-field', 'gfdsgfds'));
        return $field_set;
    }

    /**
     * @depends testGetFieldInvalidFieldTypeButFieldDoesNotExist
     * @return void
     */
    public function testGetFieldInvalidFieldTypeButFieldDoesExist(Set $field_set)
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field_set->getField('test-field'));
    }

    /**
     * @return Set
     */
    public function testGetFieldValidFieldTypeAndFieldDoesExist(): Set
    {
        $field_set = new Set("test-field-set");
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Text', $field_set->getField('test-field'));
        return $field_set;
    }

    /**
     * @depends testGetFieldValidFieldTypeAndFieldDoesExist
     * @param Set $field_set
     * @return Set
     */
    public function testRemoveValidFieldReturnsTrue(Set $field_set): Set
    {
        $this->assertTrue($field_set->removeField('test-field'));
        return $field_set;
    }

    /**
     * @depends testRemoveValidFieldReturnsTrue
     * @param Set $field_set
     * @return void
     */
    public function testRemoveInvalidFieldReturnsFalse(Set $field_set)
    {
        $this->assertFalse($field_set->removeField('test-field'));
    }

    /**
     * @return Set
     */
    public function testWithButtonReturnInstance(): Set
    {
        $field_set = new Set("test-field-set");
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Set', $field_set->withButton('test-button', 'test-rel', 'test-for'));
        return $field_set;
    }

    /**
     * @depends testWithButtonReturnInstance
     * @param Set $field_set
     * @return Set
     */
    public function testWithButtonDataArray(Set $field_set): Set
    {
        $button = $field_set->get('button');
        $this->assertArrayHasKey('text', $button);
        $this->assertArrayHasKey('rel', $button);
        $this->assertArrayHasKey('for', $button);
        $this->assertEquals('test-button', $button['text']);
        $this->assertEquals('test-rel', $button['rel']);
        $this->assertEquals('test-for', $button['for']);
        return $field_set;
    }

    /**
     * @depends testWithButtonDataArray
     * @param Set $field_set
     * @return Set
     */
    public function testWithoutButtonReturnInstance(Set $field_set): Set
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Set', $field_set->withoutButton());
        $this->assertNull($field_set->get('button'));
        return $field_set;
    }

    /**
     * @return Set
     */
    public function testSetTitleReturnInstance(): Set
    {
        $field_set = new Set("test-field-set");
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Set', $field_set->setTitle('test-title'));
        return $field_set;
    }

    /**
     * @depends testSetTitleReturnInstance
     * @param Set $field_set
     * @return void
     */
    public function testGetTitleReturnValue(Set $field_set)
    {
        $this->assertEquals('test-title', $field_set->getTitle());
        $this->assertEquals('test-title', $field_set->get('title'));
    }

    /**
     * @return Set
     */
    public function testSetDescReturnInstance(): Set
    {
        $field_set = new Set("test-field-set");
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Set', $field_set->setDesc('test-desc'));
        return $field_set;
    }

    /**
     * @depends testSetDescReturnInstance
     * @param Set $field_set
     * @return void
     */
    public function testGetDescReturnValue(Set $field_set)
    {
        $this->assertEquals('test-desc', $field_set->getDesc());
        $this->assertEquals('test-desc', $field_set->get('desc'));
    }

    /**
     * @return Set
     */
    public function testSetDescContReturnInstance(): Set
    {
        $field_set = new Set("test-field-set");
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Set', $field_set->setDescCont('test-desc-cont'));
        return $field_set;
    }

    /**
     * @depends testSetDescContReturnInstance
     * @param Set $field_set
     * @return void
     */
    public function testGetDescContReturnValue(Set $field_set)
    {
        $this->assertEquals('test-desc-cont', $field_set->getDescCont());
        $this->assertEquals('test-desc-cont', $field_set->get('desc_cont'));
    }

    /**
     * @return Set
     */
    public function testSetExampleReturnInstance(): Set
    {
        $field_set = new Set("test-field-set");
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Set', $field_set->setExample('test-example'));
        return $field_set;
    }

    /**
     * @depends testSetExampleReturnInstance
     * @param Set $field_set
     * @return void
     */
    public function testGetExampleReturnValue(Set $field_set)
    {
        $this->assertEquals('test-example', $field_set->getExample());
        $this->assertEquals('test-example', $field_set->get('example'));
    }

    /**
     * @return Set
     */
    public function testWithGridReturnInstance(): Set
    {
        $field_set = new Set("test-field-set");
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Set', $field_set->withGrid());
        return $field_set;
    }

    /**
     * @depends testWithGridReturnInstance
     * @param Set $field_set
     * @return void
     */
    public function testWithGridSetProperValues(Set $field_set)
    {
        $this->assertTrue($field_set->get('grid'));
        $this->assertTrue($field_set->get('wide'));
    }

    /**
     * @return Set
     */
    public function testWithoutGridReturnInstance(): Set
    {
        $field_set = new Set("test-field-set");
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Set', $field_set->withoutGrid());
        return $field_set;
    }

    /**
     * @depends testWithoutGridReturnInstance
     * @param Set $field_set
     */
    public function testWithoutGridSetProperValues(Set $field_set)
    {
        $this->assertFalse($field_set->get('grid'));
        $this->assertFalse($field_set->get('wide'));
    }

    /**
     * @return Set
     */
    public function testToArrayDefault(): Set
    {
        $field_set = new Set("test-field-set");
        $array = $field_set->toArray();
        $this->assertCount(2, $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('fields', $array);
        $this->assertCount(0, $array['fields']);
        return $field_set;
    }

    /**
     * @depends testToArrayDefault
     * @param Set $field_set
     * @return void
     */
    public function testToArrayWithOneField(Set $field_set)
    {
        $field_set->getField('test-field');
        $array = $field_set->toArray();
        $this->assertCount(2, $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('fields', $array);
        $this->assertCount(1, $array['fields']);
    }
}
