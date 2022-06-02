<?php
namespace ExpressionEngine\Tests\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Fields\ActionButton;
use PHPUnit\Framework\TestCase;

class _action_button extends ActionButton
{
    public function getFieldPrototype(): array
    {
        return $this->field_prototype;
    }
}

/**
 * @covers \ExpressionEngine\Library\CP\Form\Fields\ActionButton
 */
class ActionButtonTest extends TestCase
{
    /**
     * @return ActionButton
     */
    public function testFieldPrototypeAttribute(): ActionButton
    {
        $field = new _action_button('test-field');
        $this->assertObjectHasAttribute('field_prototype', $field);
        $this->assertCount(2, $field->getFieldPrototype());
        return $field;
    }

    /**
     * @depends testFieldPrototypeAttribute
     * @param ActionButton $field
     * @return void
     */
    public function testFieldPrototypeValues(ActionButton $field)
    {
        $arr = $field->getFieldPrototype();
        $this->assertArrayHasKey('link', $arr);
        $this->assertArrayHasKey('text', $arr);
    }

    /**
     * @return ActionButton
     */
    public function testSetSetLinkReturnInstance(): ActionButton
    {
        $field = new _action_button('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\ActionButton', $field->setLink('test-link'));
        return $field;
    }

    /**
     * @depends testSetSetLinkReturnInstance
     * @param ActionButton $field
     * @return void
     */
    public function testGetLinkReturnValue(ActionButton $field)
    {
        $this->assertEquals('test-link', $field->getLink());
        $this->assertEquals('test-link', $field->get('link'));
    }

    /**
     * @return ActionButton
     */
    public function testSetTextReturnInstance(): ActionButton
    {
        $field = new _action_button('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\ActionButton', $field->setText('test-text'));
        return $field;
    }

    /**
     * @depends testSetTextReturnInstance
     * @param ActionButton $field
     * @return void
     */
    public function testGetTextReturnValue(ActionButton $field)
    {
        $this->assertEquals('test-text', $field->getText());
        $this->assertEquals('test-text', $field->get('text'));
    }
}
