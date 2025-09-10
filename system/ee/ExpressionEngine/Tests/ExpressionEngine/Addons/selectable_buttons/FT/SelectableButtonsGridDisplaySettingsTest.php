<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../SelectableButtonsTestBase.php';

/**
 * Test Selectable Buttons fieldtype grid_display_settings method
 */
class SelectableButtonsGridDisplaySettingsTest extends SelectableButtonsTestBase
{
    /**
     * Test grid_display_settings method
     */
    public function testGridDisplaySettingsMethod()
    {
        $data = [];

        $result = $this->fieldtype->grid_display_settings($data);

        // Should return an array
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test grid_display_settings method with existing data
     */
    public function testGridDisplaySettingsWithData()
    {
        $data = [
            'value_label_pairs' => [
                'option1' => 'Option 1',
                'option2' => 'Option 2'
            ],
            'allow_multiple' => 'y'
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        // Should return an array
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test grid_display_settings method with empty data
     */
    public function testGridDisplaySettingsEmptyData()
    {
        $data = [];

        $result = $this->fieldtype->grid_display_settings($data);

        // Should return an array
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test grid_display_settings method structure
     */
    public function testGridDisplaySettingsStructure()
    {
        $data = [];

        $result = $this->fieldtype->grid_display_settings($data);

        // Should return an array
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test grid_display_settings method with allow_multiple setting
     */
    public function testGridDisplaySettingsWithAllowMultiple()
    {
        $data = [
            'allow_multiple' => 'y'
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        // Should return an array
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test grid_display_settings method with nested options
     */
    public function testGridDisplaySettingsWithNestedOptions()
    {
        $data = [
            'value_label_pairs' => [
                'group1' => [
                    'name' => 'Group 1',
                    'children' => [
                        'option1' => 'Option 1'
                    ]
                ]
            ]
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        $this->assertArrayHasKey('field_options', $result);
    }

    /**
     * Test grid_display_settings method with special characters
     */
    public function testGridDisplaySettingsSpecialCharacters()
    {
        $data = [
            'value_label_pairs' => [
                'option1' => 'Option & "Quote"'
            ]
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        $this->assertArrayHasKey('field_options', $result);
    }

    /**
     * Test grid_display_settings method with boolean allow_multiple
     */
    public function testGridDisplaySettingsBooleanAllowMultiple()
    {
        $data = [
            'allow_multiple' => true
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        $this->assertArrayHasKey('field_options', $result);
    }

    /**
     * Test grid_display_settings method with false allow_multiple
     */
    public function testGridDisplaySettingsFalseAllowMultiple()
    {
        $data = [
            'allow_multiple' => false
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        $this->assertArrayHasKey('field_options', $result);
    }

    /**
     * Test grid_display_settings method with null allow_multiple
     */
    public function testGridDisplaySettingsNullAllowMultiple()
    {
        $data = [
            'allow_multiple' => null
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        $this->assertArrayHasKey('field_options', $result);
    }

    /**
     * Test grid_display_settings method with field_options
     */
    public function testGridDisplaySettingsWithFieldOptions()
    {
        $data = [
            'allow_multiple' => 'y',
            'field_options' => [
                'option1' => 'Option 1',
                'option2' => 'Option 2'
            ]
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        // Should return an array
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test grid_display_settings method with numeric values
     */
    public function testGridDisplaySettingsNumericValues()
    {
        $data = [
            'allow_multiple' => 'y',
            'field_options' => [
                '1' => 'First Option',
                '2' => 'Second Option'
            ]
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        $this->assertArrayHasKey('field_options', $result);
    }

    /**
     * Test grid_display_settings method returns array
     */
    public function testGridDisplaySettingsReturnsArray()
    {
        $data = [
            'allow_multiple' => 'y'
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        $this->assertIsArray($result);
    }

    /**
     * Test grid_display_settings method with empty allow_multiple
     */
    public function testGridDisplaySettingsEmptyAllowMultiple()
    {
        $data = [
            'allow_multiple' => ''
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        $this->assertArrayHasKey('field_options', $result);
    }

    /**
     * Test grid_display_settings method with very long option values
     */
    public function testGridDisplaySettingsLongValues()
    {
        $longValue = str_repeat('A very long option name ', 5);
        $data = [
            'field_options' => [
                'option1' => $longValue
            ]
        ];

        $result = $this->fieldtype->grid_display_settings($data);

        $this->assertArrayHasKey('field_options', $result);
    }
}

// EOF
