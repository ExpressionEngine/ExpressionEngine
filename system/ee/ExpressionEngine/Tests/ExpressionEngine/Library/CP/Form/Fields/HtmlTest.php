<?php
namespace ExpressionEngine\Tests\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Fields\Html;
use PHPUnit\Framework\TestCase;

class _html extends Html
{
    public function getFieldPrototype(): array
    {
        return $this->field_prototype;
    }
}

class HtmlTest extends TestCase
{
    /**
     * @return Html
     */
    public function testFieldPrototypeAttribute(): Html
    {
        $field = new _html('test-field');
        $this->assertObjectHasAttribute('field_prototype', $field);
        $this->assertCount(1, $field->getFieldPrototype());
        return $field;
    }

    /**
     * @depends testFieldPrototypeAttribute
     * @param Html $field
     * @return void
     */
    public function testFieldPrototypeValues(Html $field)
    {
        $arr = $field->getFieldPrototype();
        $this->assertArrayHasKey('content', $arr);
    }

    /**
     * @return Html
     */
    public function testSetTextReturnInstance(): Html
    {
        $field = new _html('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Html', $field->setContent('test-content'));
        return $field;
    }

    /**
     * @depends testSetTextReturnInstance
     * @param Html $field
     * @return void
     */
    public function testGetTextReturnValue(Html $field)
    {
        $this->assertEquals('test-content', $field->getContent());
        $this->assertEquals('test-content', $field->get('content'));
    }
}
