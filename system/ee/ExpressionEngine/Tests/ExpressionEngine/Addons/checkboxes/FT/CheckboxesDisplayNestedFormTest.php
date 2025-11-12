<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../CheckboxesTestBase.php';

/**
 * Test for Checkboxes_ft::_display_nested_form() method
 */
class CheckboxesDisplayNestedFormTest extends CheckboxesTestBase
{
    /**
     * @var Checkboxes_ft
     */
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->getMockFieldtypeWithSettings();
    }

    /**
     * Test _display_nested_form with flat options
     */
    public function testDisplayNestedFormFlatOptions()
    {
        $fields = [
            'option1' => 'Option 1',
            'option2' => 'Option 2',
            'option3' => 'Option 3'
        ];
        $values = ['option1', 'option3'];

        $result = $this->fieldtype->_display_nested_form($fields, $values);

        // Should contain labels and checkboxes for each option
        $this->assertStringContainsString('<label>', $result);
        $this->assertStringContainsString('form_checkbox', $result);
        $this->assertStringContainsString('Option 1', $result);
        $this->assertStringContainsString('Option 2', $result);
        $this->assertStringContainsString('Option 3', $result);

        // Should have checked state for selected options
        $this->assertStringContainsString('checked', $result);
    }

    /**
     * Test _display_nested_form with nested options
     */
    public function testDisplayNestedFormNestedOptions()
    {
        $fields = [
            'group1' => [
                'name' => 'Group 1',
                'children' => [
                    'option1' => 'Option 1',
                    'option2' => 'Option 2'
                ]
            ],
            'group2' => [
                'name' => 'Group 2',
                'children' => [
                    'option3' => 'Option 3'
                ]
            ]
        ];
        $values = ['option1', 'option3'];

        $result = $this->fieldtype->_display_nested_form($fields, $values);

        // Should contain group labels and child options
        $this->assertStringContainsString('Group 1', $result);
        $this->assertStringContainsString('Group 2', $result);
        $this->assertStringContainsString('Option 1', $result);
        $this->assertStringContainsString('Option 2', $result);
        $this->assertStringContainsString('Option 3', $result);
    }

    /**
     * Test _display_nested_form with empty values
     */
    public function testDisplayNestedFormEmptyValues()
    {
        $fields = [
            'option1' => 'Option 1',
            'option2' => 'Option 2'
        ];
        $values = [];

        $result = $this->fieldtype->_display_nested_form($fields, $values);

        // Should not contain checked state
        $this->assertStringNotContainsString('checked', $result);
        $this->assertStringContainsString('Option 1', $result);
        $this->assertStringContainsString('Option 2', $result);
    }

    /**
     * Test _display_nested_form with empty fields
     */
    public function testDisplayNestedFormEmptyFields()
    {
        $fields = [];
        $values = [];

        $result = $this->fieldtype->_display_nested_form($fields, $values);

        $this->assertEquals('', $result);
    }

    /**
     * Test _display_nested_form with disabled setting
     */
    public function testDisplayNestedFormDisabled()
    {
        $fields = [
            'option1' => 'Option 1',
            'option2' => 'Option 2'
        ];
        $values = ['option1'];

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_disabled' => true
        ]);

        $result = $fieldtype->_display_nested_form($fields, $values);

        $this->assertStringContainsString('disabled', $result);
        $this->assertStringContainsString('Option 1', $result);
        $this->assertStringContainsString('Option 2', $result);
    }

    /**
     * Test _display_nested_form with values containing special characters
     */
    public function testDisplayNestedFormSpecialCharacters()
    {
        $fields = [
            'option_1' => 'Option & "Quote"',
            'option_2' => 'Option <tag>'
        ];
        $values = ['option_1'];

        $result = $this->fieldtype->_display_nested_form($fields, $values);

        $this->assertStringContainsString('Option &amp; &quot;Quote&quot;', $result);
        $this->assertStringContainsString('Option &lt;tag&gt;', $result);
    }

    /**
     * Test _display_nested_form with deeply nested options
     */
    public function testDisplayNestedFormDeeplyNested()
    {
        $fields = [
            'top_group' => [
                'name' => 'Top Group',
                'children' => [
                    'sub_group' => [
                        'name' => 'Sub Group',
                        'children' => [
                            'option1' => 'Option 1',
                            'option2' => 'Option 2'
                        ]
                    ]
                ]
            ]
        ];
        $values = ['option1'];

        $result = $this->fieldtype->_display_nested_form($fields, $values);

        $this->assertStringContainsString('Top Group', $result);
        $this->assertStringContainsString('Sub Group', $result);
        $this->assertStringContainsString('Option 1', $result);
        $this->assertStringContainsString('Option 2', $result);
    }

    /**
     * Test _display_nested_form with child parameter
     */
    public function testDisplayNestedFormChildParameter()
    {
        $fields = [
            'option1' => 'Option 1',
            'option2' => 'Option 2'
        ];
        $values = ['option1'];

        $result = $this->fieldtype->_display_nested_form($fields, $values, true);

        // The child parameter doesn't affect the output structure in this implementation
        $this->assertStringContainsString('Option 1', $result);
        $this->assertStringContainsString('Option 2', $result);
    }
}

// EOF
