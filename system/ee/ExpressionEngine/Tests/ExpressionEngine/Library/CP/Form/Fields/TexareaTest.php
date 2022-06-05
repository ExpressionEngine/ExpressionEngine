<?php
namespace ExpressionEngine\Tests\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Fields\Textarea;
use PHPUnit\Framework\TestCase;

class _textarea_field extends Textarea
{
    public function getFieldPrototype(): array
    {
        return $this->field_prototype;
    }
}

/**
 * @covers \ExpressionEngine\Library\CP\Form\Fields\Textarea
 */
class TexareaTest extends TestCase
{
    public function testFieldInstanceFieldObj()
    {
        $field = new Textarea;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field);
    }

    /**
     * @return Textarea
     */
    public function testFieldPrototypeAttribute(): Textarea
    {
        $field = new _textarea_field('test-field');
        $this->assertObjectHasAttribute('field_prototype', $field);
        $this->assertCount(3, $field->getFieldPrototype());
        return $field;
    }

    /**
     * @depends testFieldPrototypeAttribute
     * @param Textarea $field
     * @return void
     */
    public function testFieldPrototypeValues(Textarea $field)
    {
        $arr = $field->getFieldPrototype();
        $this->assertArrayHasKey('kill_pipes', $arr);
        $this->assertArrayHasKey('cols', $arr);
        $this->assertArrayHasKey('rows', $arr);
    }

    /**
     * @return Textarea
     */
    public function testSetKillPipesReturnInstance(): Textarea
    {
        $field = new _textarea_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Textarea', $field->setKillPipes(true));
        return $field;
    }

    /**
     * @depends testSetKillPipesReturnInstance
     * @param Textarea $field
     * @return void
     */
    public function testGetKillPipesReturnValue(Textarea $field)
    {
        $this->assertTrue($field->getKillPipes());
        $this->assertTrue($field->get('kill_pipes'));
    }

    /**
     * @return Textarea
     */
    public function testSetColsReturnInstance(): Textarea
    {
        $field = new _textarea_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Textarea', $field->setCols(20));
        return $field;
    }

    /**
     * @depends testSetColsReturnInstance
     * @param Textarea $field
     * @return void
     */
    public function testGetColsReturnValue(Textarea $field)
    {
        $this->assertEquals(20, $field->getCols());
        $this->assertEquals(20, $field->get('cols'));
    }

    /**
     * @return Textarea
     */
    public function testSetRowsReturnInstance(): Textarea
    {
        $field = new _textarea_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Textarea', $field->setRows(40));
        return $field;
    }

    /**
     * @depends testSetRowsReturnInstance
     * @param Textarea $field
     * @return void
     */
    public function testGetRowsReturnValue(Textarea $field)
    {
        $this->assertEquals(40, $field->getRows());
        $this->assertEquals(40, $field->get('rows'));
    }
}
