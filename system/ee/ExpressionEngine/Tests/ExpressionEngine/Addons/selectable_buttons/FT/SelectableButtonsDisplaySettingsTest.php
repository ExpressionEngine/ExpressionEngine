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
 * Test Selectable Buttons fieldtype display_settings method
 */
class SelectableButtonsDisplaySettingsTest extends SelectableButtonsTestBase
{
    /**
     * Test display_settings method
     */
    public function testDisplaySettingsMethod()
    {
        $data = [];

        $result = $this->fieldtype->display_settings($data);

        // Should return the expected settings structure
        $this->assertArrayHasKey('field_options_selectable_buttons', $result);
        $this->assertEquals('field_options', $result['field_options_selectable_buttons']['label']);
        $this->assertEquals('selectable_buttons', $result['field_options_selectable_buttons']['group']);
        $this->assertArrayHasKey('settings', $result['field_options_selectable_buttons']);
    }

    /**
     * Test display_settings method with existing data
     */
    public function testDisplaySettingsWithData()
    {
        $data = [
            'value_label_pairs' => [
                'option1' => 'Option 1',
                'option2' => 'Option 2'
            ],
            'allow_multiple' => 'y'
        ];

        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_selectable_buttons', $result);
    }

    /**
     * Test display_settings method with empty data
     */
    public function testDisplaySettingsEmptyData()
    {
        $data = [];

        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_selectable_buttons', $result);
    }

    /**
     * Test display_settings method structure
     */
    public function testDisplaySettingsStructure()
    {
        $data = [];

        $result = $this->fieldtype->display_settings($data);

        $this->assertIsArray($result);
        $this->assertIsArray($result['field_options_selectable_buttons']);
        $this->assertIsArray($result['field_options_selectable_buttons']['settings']);
    }

    /**
     * Test display_settings method with allow_multiple setting
     */
    public function testDisplaySettingsWithAllowMultiple()
    {
        $data = [
            'allow_multiple' => 'y'
        ];

        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_selectable_buttons', $result);
    }

    /**
     * Test display_settings method with nested options
     */
    public function testDisplaySettingsWithNestedOptions()
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

        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_selectable_buttons', $result);
    }

    /**
     * Test display_settings method with special characters
     */
    public function testDisplaySettingsSpecialCharacters()
    {
        $data = [
            'value_label_pairs' => [
                'option1' => 'Option & "Quote"'
            ]
        ];

        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_selectable_buttons', $result);
    }

    /**
     * Test display_settings method with boolean allow_multiple
     */
    public function testDisplaySettingsBooleanAllowMultiple()
    {
        $data = [
            'allow_multiple' => true
        ];

        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_selectable_buttons', $result);
    }

    /**
     * Test display_settings method with false allow_multiple
     */
    public function testDisplaySettingsFalseAllowMultiple()
    {
        $data = [
            'allow_multiple' => false
        ];

        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_selectable_buttons', $result);
    }

    /**
     * Test display_settings method with null allow_multiple
     */
    public function testDisplaySettingsNullAllowMultiple()
    {
        $data = [
            'allow_multiple' => null
        ];

        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_selectable_buttons', $result);
    }
}

// EOF

