<?php
namespace ExpressionEngine\Tests\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Fields\FilePicker;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ExpressionEngine\Library\CP\Form\Fields\FilePicker
 */
class FilepickerTest extends TestCase
{

    public function testFieldInstanceFieldObj()
    {
        $field = new FilePicker;
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field);
    }

    /**
     * @return FilePicker
     */
    public function testAsAnyReturnInstance(): FilePicker
    {
        $field = new FilePicker('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\FilePicker', $field->asAny());
        return $field;
    }

    /**
     * @depends testAsAnyReturnInstance
     * @param FilePicker $field
     * @return void
     */
    public function testAsAnyRawValue(FilePicker $field)
    {
        $this->assertFalse($field->get('_image_field'));
    }

    /**
     * @return FilePicker
     */
    public function testAsImageReturnInstance(): FilePicker
    {
        $field = new FilePicker('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\FilePicker', $field->asImage());
        return $field;
    }

    /**
     * @depends testAsImageReturnInstance
     * @param FilePicker $field
     * @return FilePicker
     */
    public function testAsImageRawValue(FilePicker $field): FilePicker
    {
        $this->assertTrue($field->get('_image_field'));
        return $field;
    }

    /**
     * @depends testAsImageRawValue
     * @param FilePicker $field
     * @return void
     */
    public function testIsImageAccuracy(FilePicker $field)
    {
        $this->assertTrue($field->isImage());
        $field->asAny();
        $this->assertFalse($field->isImage());
    }

    /**
     * @return FilePicker
     */
    public function testWithDirReturnInstance(): FilePicker
    {
        $field = new FilePicker('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\FilePicker', $field->withDir(5));
        return $field;
    }

    /**
     * @depends testWithDirReturnInstance
     * @param FilePicker $field
     * @return void
     */
    public function testWithDirRawValue(FilePicker $field)
    {
        $this->assertEquals(5, $field->get('_upload_dir'));
    }

    /**
     * @return FilePicker
     */
    public function testWithAllReturnInstance(): FilePicker
    {
        $field = new FilePicker('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\FilePicker', $field->withAll());
        return $field;
    }

    /**
     * @depends testWithAllReturnInstance
     * @param FilePicker $field
     * @return FilePicker
     */
    public function testWithAllRawValue(FilePicker $field): FilePicker
    {
        $this->assertFalse($field->get('_upload_dir'));
        return $field;
    }

    /**
     * @depends testWithAllRawValue
     * @param FilePicker $field
     * @return void
     */
    public function testIsAllAccuracy(FilePicker $field)
    {
        $this->assertTrue($field->isAll());
        $field->withDir(5);
        $this->assertFalse($field->isAll());
    }

    public function testGetValueReturnsAccurately(): FilePicker
    {
        $field = new FilePicker('test-field');
        $field->setValue('bad');
        $this->assertEquals('bad', $field->getValue());
        return $field;
    }

    /**
     * @depends testGetValueReturnsAccurately
     * @param FilePicker $field
     * @return void
     */
    public function testGetValueTakesPostIntoAccount(FilePicker $field)
    {

        $_POST[$field->getName()] = 'good';
        $this->assertEquals('good', $field->getValue());
    }
}
