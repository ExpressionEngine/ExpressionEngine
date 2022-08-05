<?php
namespace ExpressionEngine\Tests\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Fields\Input;
use PHPUnit\Framework\TestCase;

class _input_field extends Input
{
    public function getCustomParams(): array
    {
        return $this->custom_params;
    }
}

/**
 * @covers \ExpressionEngine\Library\CP\Form\Fields\Input
 */
class InputTest extends TestCase
{
    public function testFieldInstanceFieldObj()
    {
        $field = new Input;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field);
    }

    /**
     * @return Input
     */
    public function testFieldParamsAttribute(): Input
    {
        $field = new _input_field('test-field', 'color');
        $this->assertObjectHasAttribute('custom_params', $field);
        $this->assertCount(0, $field->getCustomParams());
        return $field;
    }

    public function testParamsReturnInstance(): Input
    {
        $field = new _input_field('test-field', 'color');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Input', $field->params(['key' => 'value']));
        return $field;
    }
}
