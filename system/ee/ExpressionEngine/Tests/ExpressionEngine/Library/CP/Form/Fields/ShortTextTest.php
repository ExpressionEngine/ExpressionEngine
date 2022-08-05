<?php
namespace ExpressionEngine\Tests\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Fields\ShortText;
use PHPUnit\Framework\TestCase;

class _short_text extends ShortText
{
    public function getFieldPrototype(): array
    {
        return $this->field_prototype;
    }
}

/**
 * @covers \ExpressionEngine\Library\CP\Form\Fields\ShortText
 */
class ShortTextTest extends TestCase
{
    public function testFieldInstanceFieldObj()
    {
        $field = new ShortText;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field);
    }

    /**
     * @return ShortText
     */
    public function testFieldPrototypeAttribute(): ShortText
    {
        $field = new _short_text('test-field');
        $this->assertObjectHasAttribute('field_prototype', $field);
        $this->assertCount(1, $field->getFieldPrototype());
        return $field;
    }

    /**
     * @depends testFieldPrototypeAttribute
     * @param ShortText $field
     * @return void
     */
    public function testFieldPrototypeValues(ShortText $field)
    {
        $arr = $field->getFieldPrototype();
        $this->assertArrayHasKey('label', $arr);
    }

    /**
     * @return ShortText
     */
    public function testSetLabelReturnInstance(): ShortText
    {
        $field = new _short_text('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\ShortText', $field->setLabel('test-label'));
        return $field;
    }

    /**
     * @depends testSetLabelReturnInstance
     * @param ShortText $field
     * @return void
     */
    public function testGetLabelReturnValue(ShortText $field)
    {
        $this->assertEquals('test-label', $field->getLabel());
        $this->assertEquals('test-label', $field->get('label'));
    }
}
