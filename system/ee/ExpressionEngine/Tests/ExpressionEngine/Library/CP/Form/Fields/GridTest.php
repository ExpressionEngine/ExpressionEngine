<?php
namespace ExpressionEngine\Tests\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Fields\Grid;
use PHPUnit\Framework\TestCase;

class _grid_field extends Grid
{
    public function getFieldPrototype(): array
    {
        return $this->field_prototype;
    }
}

class GridTest extends TestCase
{
    protected $test_row_definition = [
        ['name' => 'foo-text', 'type' => 'text', 'value' => ''],
        ['name' => 'barr-select', 'type' => 'select', 'value' => '', 'choices' => []],
        ['name' => 'foo-password', 'type' => 'password', 'value' => ''],
        ['name' => 'bar-checkbox', 'type' => 'checkbox', 'value' => 1],
        ['name' => 'foo-textarea', 'type' => 'textarea', 'value' => '', 'cols' => 2, 'rows' => 5],
        ['name' => 'bar-upload', 'type' => 'file', ]
    ];


    public function testFieldInstanceFieldObj()
    {
        $field = new Grid;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Html', $field);
    }

    /**
     * @return Grid
     */
    public function testFieldPrototypeAttribute(): Grid
    {
        $field = new _grid_field('test-field');
        $this->assertObjectHasAttribute('field_prototype', $field);
        $this->assertCount(2, $field->getFieldPrototype());
        return $field;
    }

    /**
     * @depends testFieldPrototypeAttribute
     * @param Grid $field
     * @return Grid
     */
    public function testDefineRowReturnInstance(Grid $field): Grid
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Html', $field->defineRow($this->test_row_definition));
        return $field;
    }
}
