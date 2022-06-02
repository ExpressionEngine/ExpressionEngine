<?php
namespace ExpressionEngine\Tests\Library\CP\Form;

use ExpressionEngine\Library\CP\Form\Field;
use PHPUnit\Framework\TestCase;

class _field_empty extends Field
{
    function __construct(string $name = '', string $type = '')
    {

    }

    public function getPrototype(): array
    {
        return $this->prototype;
    }

    public function getFieldPrototype(): array
    {
        return $this->field_prototype;
    }

    public function getDefaultPrototype(): array
    {
        return $this->default_prototype;
    }
}

class _field extends Field
{

}

class FieldTest extends TestCase
{
    /**
     * Returns the testing field
     * @return Field
     */
    protected function _getField(): Field
    {
        return new _field('test-field', 'fake-field');
    }
    /**
     * @return Field
     */
    public function testPrototypeAttribute(): Field
    {
        $field = new _field_empty('test-field');
        $this->assertObjectHasAttribute('prototype', $field);
        $this->assertCount(0, $field->getPrototype());
        return $field;
    }

    /**
     * @depends testPrototypeAttribute
     * @param Field $field
     * @return Field
     */
    public function testFieldPrototypeAttribute(Field $field): Field
    {
        $this->assertObjectHasAttribute('field_prototype', $field);
        $this->assertCount(0, $field->getFieldPrototype());
        return $field;
    }

    /**
     * @depends testPrototypeAttribute
     * @param Field $field
     * @return Field
     */
    public function testDefaultPrototypeAttribute(Field $field): Field
    {
        $this->assertObjectHasAttribute('default_prototype', $field);
        $this->assertCount(12, $field->getDefaultPrototype());
        return $field;
    }

    /**
     * @return void
     */
    public function testGetNameReturnValue()
    {
        $field = $this->_getField();
        $this->assertEquals('test-field', $field->getName());
    }

    /**
     * @return Field
     */
    public function testSetClassReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setClass('test-css-class'));
        return $field;
    }

    /**
     * @depends testSetClassReturnInstance
     * @param Field $field
     * @return void
     */
    public function testGetClassReturnValue(Field $field)
    {
        $this->assertEquals('test-css-class', $field->getClass());
        $this->assertEquals('test-css-class', $field->get('class'));
    }

    /**
     * @return Field
     */
    public function testWithMarginTopReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->withMarginTop());
        return $field;
    }

    /**
     * @depends testWithMarginTopReturnInstance
     * @param Field $field
     * @return Field
     */
    public function testWithMarginTopValueGetsSet(Field $field): Field
    {
        $array = $field->toArray();
        $this->assertArrayHasKey('margin_top', $array);
        $this->assertTrue($array['margin_top']);
        return $field;
    }

    /**
     * @depends testWithMarginTopValueGetsSet
     * @param Field $field
     * @return void
     */
    public function testWithoutMarginTopReturnInstance(Field $field): Field
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->withoutMarginTop());
        return $field;
    }

    /**
     * @depends testWithoutMarginTopReturnInstance
     * @param Field $field
     * @return Field
     */
    public function testWithoutMarginTopValueGetsSet(Field $field): Field
    {
        $array = $field->toArray();
        $this->assertArrayHasKey('margin_top', $array);
        $this->assertFalse($array['margin_top']);
        return $field;
    }

    /**
     * @return Field
     */
    public function testWithMarginLeftReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->withMarginLeft());
        return $field;
    }

    /**
     * @depends testWithMarginLeftReturnInstance
     * @param Field $field
     * @return Field
     */
    public function testWithMarginLeftValueGetsSet(Field $field): Field
    {
        $array = $field->toArray();
        $this->assertArrayHasKey('margin_left', $array);
        $this->assertTrue($array['margin_left']);
        return $field;
    }

    /**
     * @depends testWithMarginLeftValueGetsSet
     * @param Field $field
     * @return void
     */
    public function testWithoutMarginLeftReturnInstance(Field $field): Field
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->withoutMarginLeft());
        return $field;
    }

    /**
     * @depends testWithoutMarginLeftReturnInstance
     * @param Field $field
     */
    public function testWithoutMarginLeftValueGetsSet(Field $field)
    {
        $array = $field->toArray();
        $this->assertArrayHasKey('margin_left', $array);
        $this->assertFalse($array['margin_left']);
    }

    /**
     * @return Field
     */
    public function testSetNoteReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setNote('my-note'));
        return $field;
    }

    /**
     * @depends testSetNoteReturnInstance
     * @param Field $field
     * @return void
     */
    public function testGetNoteReturnValue(Field $field)
    {
        $this->assertEquals('my-note', $field->getNote());
        $this->assertEquals('my-note', $field->get('note'));
    }

    /**
     * @return Field
     */
    public function testSetAttrsReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setAttrs(' attr="my-attr" '));
        return $field;
    }

    /**
     * @depends testSetAttrsReturnInstance
     * @param Field $field
     * @return void
     */
    public function testGetAttrsReturnValue(Field $field)
    {
        $this->assertEquals(' attr="my-attr" ', $field->getAttrs());
        $this->assertEquals(' attr="my-attr" ', $field->get('attrs'));
    }

    /**
     * @return Field
     */
    public function testSetDisabledReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setDisabled(true));
        return $field;
    }

    /**
     * @depends testSetDisabledReturnInstance
     * @param Field $field
     * @return void
     */
    public function testGetDisabledReturnValue(Field $field)
    {
        $this->assertTrue($field->getDisabled());
        $this->assertTrue($field->get('disabled'));
    }

    /**
     * @return Field
     */
    public function testSetValueReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setValue('test-value'));
        return $field;
    }

    /**
     * @depends testSetValueReturnInstance
     * @param Field $field
     * @return void
     */
    public function testGetValueReturnValue(Field $field)
    {
        $this->assertEquals('test-value', $field->getValue());
        $this->assertEquals('test-value', $field->get('value'));
    }

    /**
     * @return Field
     */
    public function testSetGroupReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setGroup('test-group'));
        return $field;
    }

    /**
     * @depends testSetGroupReturnInstance
     * @param Field $field
     * @return void
     */
    public function testGetGroupReturnValue(Field $field)
    {
        $this->assertEquals('test-group', $field->getGroup());
        $this->assertEquals('test-group', $field->get('group'));
    }

    /**
     * @return Field
     */
    public function testSetGroupToggleReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setGroupToggle('test-group-toggle'));
        return $field;
    }

    /**
     * @depends testSetGroupToggleReturnInstance
     * @param Field $field
     * @return void
     */
    public function testGetGroupToggleReturnValue(Field $field)
    {
        $this->assertEquals('test-group-toggle', $field->getGroupToggle());
        $this->assertEquals('test-group-toggle', $field->get('group_toggle'));
    }

    /**
     * @return Field
     */
    public function testSetRequiredReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setRequired(true));
        return $field;
    }

    /**
     * @depends testSetRequiredReturnInstance
     * @param Field $field
     * @return void
     */
    public function testGetRequiredReturnValue(Field $field)
    {
        $this->assertTrue($field->getRequired());
        $this->assertTrue($field->get('required'));
    }

    /**
     * @return Field
     */
    public function testSetPlaceholderReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setPlaceholder('test-placeholder'));
        return $field;
    }

    /**
     * @depends testSetPlaceholderReturnInstance
     * @param Field $field
     * @return void
     */
    public function testGetPlaceholderReturnValue(Field $field)
    {
        $this->assertEquals('test-placeholder', $field->getPlaceholder());
        $this->assertEquals('test-placeholder', $field->get('placeholder'));
    }

    /**
     * @return Field
     */
    public function testSetMaxlengthReturnInstance(): Field
    {
        $field = $this->_getField();
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field->setMaxlength(20));
        return $field;
    }

    /**
     * @depends testSetMaxlengthReturnInstance
     * @param Field $field
     * @return void
     */
    public function testGetMaxlengthReturnValue(Field $field)
    {
        $this->assertEquals(20, $field->getMaxlength());
        $this->assertEquals(20, $field->get('maxlength'));
    }
}
