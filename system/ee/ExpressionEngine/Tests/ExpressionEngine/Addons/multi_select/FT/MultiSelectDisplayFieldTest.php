<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../MultiSelectTestBase.php';

/**
 * Test Multi Select fieldtype display_field method
 */
class MultiSelectDisplayFieldTest extends MultiSelectTestBase
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
     * Test display_field method with selected values
     */
    public function testDisplayFieldWithSelectedValues()
    {
        $data = 'option1|option2';

        // Mock for CP context
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }

        $result = $this->fieldtype->display_field($data);

        // Should return HTML with selected options
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with single selected value
     */
    public function testDisplayFieldSingleValue()
    {
        $data = 'option1';

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with no selected values
     */
    public function testDisplayFieldNoSelection()
    {
        $data = '';

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with disabled field
     */
    public function testDisplayFieldDisabled()
    {
        $data = 'option1|option2';
        $this->fieldtype->settings['field_disabled'] = true;

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

        $data = 'option1|option2';
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

        $data = '1|2';
        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with array data
     */
    public function testDisplayFieldArrayData()
    {
        $data = ['option1', 'option2'];

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with empty array
     */
    public function testDisplayFieldEmptyArray()
    {
        $data = [];

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with null data
     */
    public function testDisplayFieldNullData()
    {
        $data = null;

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

        $data = 'option1|option2';
        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with many options selected
     */
    public function testDisplayFieldManySelections()
    {
        $options = [];
        $selections = [];
        for ($i = 1; $i <= 20; $i++) {
            $options['option' . $i] = 'Option ' . $i;
            $selections[] = 'option' . $i;
        }
        $this->mockFieldOptions($options);

        $data = implode('|', $selections);
        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }
}

// EOF

