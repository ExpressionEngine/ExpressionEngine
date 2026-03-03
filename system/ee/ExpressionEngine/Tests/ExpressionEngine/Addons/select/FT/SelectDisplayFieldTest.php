<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../SelectTestBase.php';

/**
 * Test Select fieldtype display_field method
 */
class SelectDisplayFieldTest extends SelectTestBase
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
     * Test display_field method with selected value
     */
    public function testDisplayFieldWithSelectedValue()
    {
        $data = 'option1';

        // Mock for CP context
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }

        $result = $this->fieldtype->display_field($data);

        // Should return HTML with selected option
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with no selection
     */
    public function testDisplayFieldNoSelection()
    {
        $data = '';

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
     * Test display_field method with disabled field
     */
    public function testDisplayFieldDisabled()
    {
        $data = 'option1';
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

        $data = 'option1';
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
        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with nested options
     */
    public function testDisplayFieldNestedOptions()
    {
        $this->mockNestedFieldOptions();
        $data = 'option1';

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with zero value
     */
    public function testDisplayFieldZeroValue()
    {
        $this->mockFieldOptions([
            '0' => 'Zero Option',
            '1' => 'One Option'
        ]);

        $data = '0';
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
        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with many options
     */
    public function testDisplayFieldManyOptions()
    {
        $options = [];
        for ($i = 1; $i <= 50; $i++) {
            $options['option' . $i] = 'Option ' . $i;
        }
        $this->mockFieldOptions($options);

        $data = 'option25';
        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with empty options list
     */
    public function testDisplayFieldEmptyOptions()
    {
        $this->mockFieldOptions([]);
        $data = '';

        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test display_field method with boolean values
     */
    public function testDisplayFieldBooleanValues()
    {
        $this->mockFieldOptions([
            '1' => 'True Option',
            '0' => 'False Option'
        ]);

        $data = '1';
        $result = $this->fieldtype->display_field($data);

        $this->assertStringContainsString('Mock display', $result);
    }
}

// EOF

