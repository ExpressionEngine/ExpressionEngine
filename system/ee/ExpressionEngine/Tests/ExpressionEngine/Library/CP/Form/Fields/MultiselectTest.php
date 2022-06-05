<?php
namespace ExpressionEngine\Tests\Library\CP\Form\Fields;

use ExpressionEngine\Library\CP\Form\Fields\Multiselect;
use PHPUnit\Framework\TestCase;

class MultiselectTest extends TestCase
{

    /**
     * @return Multiselect
     */
    public function testBaseInstance(): Multiselect
    {
        $field = new Multiselect('test-field');
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Field', $field);
        return $field;
    }

    /**
     * @depends testBaseInstance
     * @param Multiselect $field
     * @return Multiselect
     */
    public function testAddDropDownReturnInstance(Multiselect $field): Multiselect
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Multiselect', $field->addDropdown('my-dropdown', 3, 'my-dropdown', ['fds', 'ghfd', 'hh']));
        return $field;
    }

    /**
     * @depends testAddDropDownReturnInstance
     * @param Multiselect $field
     * @return Multiselect
     */
    public function testAddedDropdownIsSavedToChoices(Multiselect $field): Multiselect
    {
        $choices = $field->getChoices();
        $this->assertArrayHasKey('my-dropdown', $choices);
        return $field;
    }

    /**
     * @depends testAddedDropdownIsSavedToChoices
     * @param Multiselect $field
     * @return Multiselect
     */
    public function testRemoveDropdownReturnInstance(Multiselect $field): Multiselect
    {
        $this->assertInstanceOf('ExpressionEngine\Library\CP\Form\Fields\Multiselect', $field->removeDropdown('my-dropdown'));
        $choices = $field->getChoices();
        return $field;
    }

    /**
     * @depends testAddDropDownReturnInstance
     * @param Multiselect $field
     * @return Multiselect
     */
    public function testAddedDropdownIsSavedToChoicesAfterRemovals(Multiselect $field): Multiselect
    {
        $field->addDropdown('my-dropdown', 3, 'my-dropdown', ['fds', 'ghfd', 'hh']);
        $choices = $field->getChoices();
        $this->assertArrayHasKey('my-dropdown', $choices);
        return $field;
    }
}
