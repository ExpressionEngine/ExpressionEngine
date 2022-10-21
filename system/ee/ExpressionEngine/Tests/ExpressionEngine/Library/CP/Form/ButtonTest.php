<?php
namespace ExpressionEngine\Tests\Library\CP\Form;

use ExpressionEngine\Library\CP\Form\Button;
use PHPUnit\Framework\TestCase;

class _button extends Button
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
 * @covers \ExpressionEngine\Library\CP\Form\Button
 */
class ButtonTest extends TestCase
{
    public function testPrototypeAttribute(): Button
    {
        $button = new _button('save');
        $this->assertObjectHasAttribute('prototype', $button);
        $this->assertCount(9, $button->getPrototype());
        return $button;
    }

    /**
     * @depends testPrototypeAttribute
     * @param Button $button
     * @return Button
     */
    public function testStructureAttribute(Button $button): Button
    {
        $this->assertObjectHasAttribute('structure', $button);
        $this->assertCount(0, $button->getStructure());
        return $button;
    }

    /**
     * @depends testStructureAttribute
     * @param Button $button
     * @return Button
     */
    public function testNameAttribute(Button $button): Button
    {
        $this->assertObjectHasAttribute('name', $button);
        return $button;
    }

    /**
     * @depends testNameAttribute
     * @param Button $button
     * @return Button
     */
    public function testShortcutPrototypeKey(Button $button): Button
    {
        $prototype = $button->getPrototype();
        $this->assertTrue(isset($prototype['shortcut']));
        $this->assertEquals('', $prototype['shortcut']);
        return $button;
    }

    /**
     * @depends testShortcutPrototypeKey
     * @param Button $button
     * @return Button
     */
    public function testValuePrototypeKey(Button $button): Button
    {
        $prototype = $button->getPrototype();
        $this->assertTrue(isset($prototype['value']));
        $this->assertEquals('', $prototype['value']);
        return $button;
    }

    /**
     * @depends testValuePrototypeKey
     * @param Button $button
     * @return Button
     */
    public function testNamePrototypeKey(Button $button): Button
    {
        $prototype = $button->getPrototype();
        $this->assertTrue(isset($prototype['name']));
        $this->assertEquals('save', $prototype['name']);
        return $button;
    }

    /**
     * @depends testNamePrototypeKey
     * @param Button $button
     * @return Button
     */
    public function testTypePrototypeKey(Button $button): Button
    {
        $prototype = $button->getPrototype();
        $this->assertTrue(isset($prototype['type']));
        $this->assertEquals('button', $prototype['type']);
        return $button;
    }

    /**
     * @depends testTypePrototypeKey
     * @param Button $button
     * @return Button
     */
    public function testTextPrototypeKey(Button $button): Button
    {
        $prototype = $button->getPrototype();
        $this->assertTrue(isset($prototype['text']));
        $this->assertEquals('save', $prototype['text']);
        return $button;
    }

    /**
     * @depends testTextPrototypeKey
     * @param Button $button
     * @return Button
     */
    public function testWorkingPrototypeKey(Button $button): Button
    {
        $prototype = $button->getPrototype();
        $this->assertTrue(isset($prototype['working']));
        $this->assertEquals('saving', $prototype['working']);
        return $button;
    }

    /**
     * @depends testWorkingPrototypeKey
     * @param Button $button
     * @return void
     */
    public function testGetNameReturnValue(Button $button)
    {
        $this->assertEquals('save', $button->getName());
    }

    /**
     * @return Button
     */
    public function testSetShortcutReturnInstance(): Button
    {
        $button = new Button('test-button');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Button', $button->setShortcut('my-shortcut'));
        return $button;
    }

    /**
     * @depends testSetShortcutReturnInstance
     * @param Button $button
     * @return void
     */
    public function testGetShortcutReturnValue(Button $button)
    {
        $this->assertEquals('my-shortcut', $button->getShortcut());
        $this->assertEquals('my-shortcut', $button->get('shortcut'));
    }

    /**
     * @return Button
     */
    public function testSetAttrsReturnInstance(): Button
    {
        $button = new Button('test-button');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Button', $button->setAttrs(' id="test" '));
        return $button;
    }

    /**
     * @depends testSetAttrsReturnInstance
     * @param Button $button
     * @return void
     */
    public function testGetAttrsReturnValue(Button $button)
    {
        $this->assertEquals(' id="test" ', $button->getAttrs());
        $this->assertEquals(' id="test" ', $button->get('attrs'));
    }

    /**
     * @return Button
     */
    public function testSetValueReturnInstance(): Button
    {
        $button = new Button('test-button');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Button', $button->setValue('my-value'));
        return $button;
    }

    /**
     * @depends testSetValueReturnInstance
     * @param Button $button
     * @return void
     */
    public function testGetValueReturnValue(Button $button)
    {
        $this->assertEquals('my-value', $button->getValue());
        $this->assertEquals('my-value', $button->get('value'));
    }

    /**
     * @return Button
     */
    public function testSetTypeReturnInstance(): Button
    {
        $button = new Button('test-button');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Button', $button->setType('submit'));
        return $button;
    }

    /**
     * @depends testSetTypeReturnInstance
     * @param Button $button
     * @return void
     */
    public function testSetTypeReturnValue(Button $button): Button
    {
        $this->assertEquals('submit', $button->getType());
        $this->assertEquals('submit', $button->get('type'));
        return $button;
    }

    /**
     * @depends testSetTypeReturnValue
     * @param Button $button
     * @return void
     */
    public function testSetBadTypeDefaultValue(Button $button)
    {
        $button->setType('foo');
        $this->assertEquals('button', $button->getType());
    }

    /**
     * @return Button
     */
    public function testSetClassReturnInstance(): Button
    {
        $button = new Button('test-button');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Button', $button->setClass('my-class'));
        return $button;
    }

    /**
     * @depends testSetClassReturnInstance
     * @param Button $button
     * @return void
     */
    public function testGetClassReturnValue(Button $button)
    {
        $this->assertEquals('my-class', $button->getClass());
        $this->assertEquals('my-class', $button->get('class'));
    }

    /**
     * @return Button
     */
    public function testSetHtmlReturnInstance(): Button
    {
        $button = new Button('test-button');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Button', $button->setHtml('my-html'));
        return $button;
    }

    /**
     * @depends testSetHtmlReturnInstance
     * @param Button $button
     * @return void
     */
    public function testGetHtmlReturnValue(Button $button)
    {
        $this->assertEquals('my-html', $button->getHtml());
        $this->assertEquals('my-html', $button->get('html'));
    }

    /**
     * @return Button
     */
    public function testSetTextReturnInstance(): Button
    {
        $button = new Button('test-button');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Button', $button->setText('my-text'));
        return $button;
    }

    /**
     * @depends testSetTextReturnInstance
     * @param Button $button
     * @return void
     */
    public function testGetTextReturnValue(Button $button)
    {
        $this->assertEquals('my-text', $button->getText());
        $this->assertEquals('my-text', $button->get('text'));
    }

    /**
     * @return Button
     */
    public function testSetWorkingReturnInstance(): Button
    {
        $button = new Button('test-button');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Button', $button->setWorking('my-working-text'));
        return $button;
    }

    /**
     * @depends testSetWorkingReturnInstance
     * @param Button $button
     * @return void
     */
    public function testGetWorkingReturnValue(Button $button)
    {
        $this->assertEquals('my-working-text', $button->getWorking());
        $this->assertEquals('my-working-text', $button->get('working'));
    }

    /**
     * @return array
     */
    public function testToArrayEmptyStructure(): array
    {
        $button = new Button('test-button');
        $arr = $button->toArray();

        $this->assertCount(6, $arr);
        $this->assertArrayHasKey('value', $arr);
        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayHasKey('type', $arr);
        $this->assertArrayHasKey('text', $arr);
        $this->assertArrayHasKey('working', $arr);
        $this->assertArrayHasKey('shortcut', $arr);
        $this->assertEquals('save', $arr['text']);
        $this->assertEquals('', $arr['value']);
        $this->assertEquals('test-button', $arr['name']);
        $this->assertEquals('button', $arr['type']);
        $this->assertEquals('', $arr['shortcut']);
        $this->assertEquals('saving', $arr['working']);
        return $arr;
    }
}
