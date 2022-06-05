<?php
namespace ExpressionEngine\Tests\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Fields\Slider;
use PHPUnit\Framework\TestCase;

class _slider_field extends Slider
{
    public function getFieldPrototype(): array
    {
        return $this->field_prototype;
    }
}

/**
 * @covers \ExpressionEngine\Library\CP\Form\Fields\Slider
 */
class SliderTest extends TestCase
{
    public function testFieldInstanceFieldObj()
    {
        $field = new Slider;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field);
    }

    /**
     * @return Slider
     */
    public function testFieldPrototypeAttribute(): Slider
    {
        $field = new _slider_field('test-field');
        $this->assertObjectHasAttribute('field_prototype', $field);
        $this->assertCount(4, $field->getFieldPrototype());
        return $field;
    }

    /**
     * @depends testFieldPrototypeAttribute
     * @param Slider $field
     * @return void
     */
    public function testFieldPrototypeValues(Slider $field)
    {
        $arr = $field->getFieldPrototype();
        $this->assertArrayHasKey('min', $arr);
        $this->assertArrayHasKey('max', $arr);
        $this->assertArrayHasKey('step', $arr);
        $this->assertArrayHasKey('unit', $arr);
    }

    /**
     * @return Slider
     */
    public function testSetMinReturnInstance(): Slider
    {
        $field = new _slider_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Slider', $field->setMin(20));
        return $field;
    }

    /**
     * @depends testSetMinReturnInstance
     * @param Slider $field
     * @return void
     */
    public function testGetMinReturnValue(Slider $field)
    {
        $this->assertEquals(20, $field->getMin());
        $this->assertEquals(20, $field->get('min'));
    }

    /**
     * @return Slider
     */
    public function testSetMaxReturnInstance(): Slider
    {
        $field = new _slider_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Slider', $field->setMax(20));
        return $field;
    }

    /**
     * @depends testSetMaxReturnInstance
     * @param Slider $field
     * @return void
     */
    public function testGetMaxReturnValue(Slider $field)
    {
        $this->assertEquals(20, $field->getMax());
        $this->assertEquals(20, $field->get('max'));
    }

    /**
     * @return Slider
     */
    public function testSetStepReturnInstance(): Slider
    {
        $field = new _slider_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Slider', $field->setStep(10));
        return $field;
    }

    /**
     * @depends testSetStepReturnInstance
     * @param Slider $field
     * @return void
     */
    public function testGetStepReturnValue(Slider $field)
    {
        $this->assertEquals(10, $field->getStep());
        $this->assertEquals(10, $field->get('step'));
    }

    /**
     * @return Slider
     */
    public function testSetUnitReturnInstance(): Slider
    {
        $field = new _slider_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Slider', $field->setUnit('unit-value'));
        return $field;
    }

    /**
     * @depends testSetUnitReturnInstance
     * @param Slider $field
     * @return void
     */
    public function testGetUnitReturnValue(Slider $field)
    {
        $this->assertEquals('unit-value', $field->getUnit());
        $this->assertEquals('unit-value', $field->get('unit'));
    }
}
