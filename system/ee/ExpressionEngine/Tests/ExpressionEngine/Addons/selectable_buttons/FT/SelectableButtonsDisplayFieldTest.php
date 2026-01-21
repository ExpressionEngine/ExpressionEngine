<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../SelectableButtonsTestBase.php';

/**
 * Test Selectable Buttons fieldtype display_field method
 */
class SelectableButtonsDisplayFieldTest extends SelectableButtonsTestBase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->mockFieldOptions([
            'option1' => 'Option 1',
            'option2' => 'Option 2',
            'option3' => 'Option 3'
        ]);
    }

    /**
     * Test display_field method with single selection
     */
    public function testDisplayFieldSingleSelection()
    {
        $data = 'option1';
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->display_field($data);

        // Should return HTML with buttons
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with multiple selections
     */
    public function testDisplayFieldMultipleSelections()
    {
        $data = ['option1', 'option2'];
        $this->fieldtype->settings['allow_multiple'] = true;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with no selection
     */
    public function testDisplayFieldNoSelection()
    {
        $data = '';
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with disabled field
     */
    public function testDisplayFieldDisabled()
    {
        $data = 'option1';
        $this->fieldtype->settings['field_disabled'] = true;
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with special characters in options
     */
    public function testDisplayFieldSpecialCharacters()
    {
        $this->mockFieldOptions([
            'option1' => 'Option & "Quote"',
            'option2' => 'Option <tag>'
        ]);

        $data = 'option1';
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with numeric values
     */
    public function testDisplayFieldNumericValues()
    {
        $this->mockFieldOptions([
            '1' => 'First',
            '2' => 'Second'
        ]);

        $data = '1';
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with array data for single selection
     */
    public function testDisplayFieldArrayDataSingle()
    {
        $data = ['option1'];
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with array data for multiple selections
     */
    public function testDisplayFieldArrayDataMultiple()
    {
        $data = ['option1', 'option2'];
        $this->fieldtype->settings['allow_multiple'] = true;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with empty array
     */
    public function testDisplayFieldEmptyArray()
    {
        $data = [];
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with null data
     */
    public function testDisplayFieldNullData()
    {
        $data = null;
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with allow_multiple setting as string
     */
    public function testDisplayFieldAllowMultipleAsString()
    {
        $data = ['option1', 'option2'];
        $this->fieldtype->settings['allow_multiple'] = 'y';

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with allow_multiple as false (should behave like single select)
     */
    public function testDisplayFieldAllowMultipleFalse()
    {
        $data = ['option1', 'option2']; // Multiple values but allow_multiple is false
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with very long option values
     */
    public function testDisplayFieldLongValues()
    {
        $longValue = str_repeat('A very long option name ', 10);
        $this->mockFieldOptions([
            'option1' => $longValue,
            'option2' => 'Short'
        ]);

        $data = 'option1';
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with many options
     */
    public function testDisplayFieldManyOptions()
    {
        $options = [];
        for ($i = 1; $i <= 20; $i++) {
            $options['option' . $i] = 'Option ' . $i;
        }
        $this->mockFieldOptions($options);

        $data = 'option10';
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }
}

// EOF

