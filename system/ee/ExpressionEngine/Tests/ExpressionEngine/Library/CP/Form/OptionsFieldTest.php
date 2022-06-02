<?php
namespace ExpressionEngine\Tests\Library\CP\Form;

use ExpressionEngine\Library\CP\Form\OptionsField;
use PHPUnit\Framework\TestCase;

class _options_field extends OptionsField
{
    public function getFieldPrototype(): array
    {
        return $this->field_prototype;
    }
}

/**
 * @covers \ExpressionEngine\Library\CP\Form\OptionsField
 */
class OptionsFieldTest extends TestCase
{
    /**
     * @return OptionsField
     */
    public function testFieldPrototypeAttribute(): OptionsField
    {
        $field = new _options_field('test-field');
        $this->assertObjectHasAttribute('field_prototype', $field);
        $this->assertCount(8, $field->getFieldPrototype());
        return $field;
    }

    /**
     * @return OptionsField
     */
    public function testWithNoResultsReturnInstance(): OptionsField
    {
        $field = new _options_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\OptionsField', $field->withNoResults('test-text', 'test-link-text', 'test-link-href'));
        return $field;
    }

    /**
     * @depends testWithNoResultsReturnInstance
     * @param OptionsField $field
     * @return OptionsField
     */
    public function testWithNoResultsSetValues(OptionsField $field): OptionsField
    {
        $no_results = $field->get('no_results');
        $this->assertCount(3, $no_results);
        $this->assertArrayHasKey('text', $no_results);
        $this->assertArrayHasKey('link_href', $no_results);
        $this->assertArrayHasKey('link_text', $no_results);
        $this->assertEquals('test-text', $no_results['text']);
        $this->assertEquals('test-link-text', $no_results['link_text']);
        $this->assertEquals('test-link-href', $no_results['link_href']);
        return $field;
    }

    /**
     * @depends testWithNoResultsSetValues
     * @param OptionsField $field
     * @return OptionsField
     */
    public function testWithoutNoResultsSetValues(OptionsField $field): OptionsField
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\OptionsField', $field->withOutNoResults());
        return $field;
    }

    /**
     * @depends testWithoutNoResultsSetValues
     * @param OptionsField $field
     * @return void
     */
    public function testWithNoResultsIsNull(OptionsField $field)
    {
        $this->assertNull($field->get('no_results'));
    }

    /**
     * @return OptionsField
     */
    public function testSetChoicesReturnInstance(): OptionsField
    {
        $field = new _options_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\OptionsField', $field->setChoices([2,4]));
        return $field;
    }

    /**
     * @depends testSetChoicesReturnInstance
     * @param OptionsField $field
     * @return void
     */
    public function testGetClassReturnValue(OptionsField $field)
    {
        $this->assertEquals([2,4], $field->getChoices());
        $this->assertEquals([2,4], $field->get('choices'));
    }

    /**
     * @return OptionsField
     */
    public function testSetEncodeReturnInstance(): OptionsField
    {
        $field = new _options_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\OptionsField', $field->setEncode(true));
        return $field;
    }

    /**
     * @depends testSetEncodeReturnInstance
     * @param OptionsField $field
     * @return void
     */
    public function testGetEncodeReturnValue(OptionsField $field)
    {
        $this->assertTrue($field->getEncode());
        $this->assertTrue($field->get('encode'));
    }

    /**
     * @return OptionsField
     */
    public function testSetDisabledChoicesReturnInstance(): OptionsField
    {
        $field = new _options_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\OptionsField', $field->setDisabledChoices([2,4]));
        return $field;
    }

    /**
     * @depends testSetDisabledChoicesReturnInstance
     * @param OptionsField $field
     * @return void
     */
    public function testGetDisabledChoicesReturnValue(OptionsField $field)
    {
        $this->assertEquals([2,4], $field->getDisabledChoices());
        $this->assertEquals([2,4], $field->get('disabled_choices'));
    }

    /**
     * @return OptionsField
     */
    public function testSetEmptyTextReturnInstance(): OptionsField
    {
        $field = new _options_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\OptionsField', $field->setEmptyText('test-empty-text'));
        return $field;
    }

    /**
     * @depends testSetEmptyTextReturnInstance
     * @param OptionsField $field
     * @return void
     */
    public function testGetEmptyTextReturnValue(OptionsField $field)
    {
        $this->assertEquals('test-empty-text', $field->getEmptyText());
        $this->assertEquals('test-empty-text', $field->get('empty_text'));
    }

    /**
     * @return OptionsField
     */
    public function testSetReorderableReturnInstance(): OptionsField
    {
        $field = new _options_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\OptionsField', $field->setReorderable(true));
        return $field;
    }

    /**
     * @depends testSetReorderableReturnInstance
     * @param OptionsField $field
     * @return void
     */
    public function testGetReorderableReturnValue(OptionsField $field)
    {
        $this->assertTrue($field->getReorderable());
        $this->assertTrue($field->get('reorderable'));
    }

    /**
     * @return OptionsField
     */
    public function testSetRemovableReturnInstance(): OptionsField
    {
        $field = new _options_field('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\OptionsField', $field->setRemovable(true));
        return $field;
    }

    /**
     * @depends testSetRemovableReturnInstance
     * @param OptionsField $field
     * @return void
     */
    public function testGetRemovableReturnValue(OptionsField $field)
    {
        $this->assertTrue($field->getRemovable());
        $this->assertTrue($field->get('removable'));
    }
}
