<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../RadioTestBase.php';

use Mockery as m;

/**
 * Test for Radio_ft::display_settings() and grid_display_settings() methods
 */
class RadioDisplaySettingsTest extends RadioTestBase
{
    /**
     * @var Radio_ft
     */
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->getMockRadioFieldtypeWithSettings();
    }

    /**
     * Test display_settings with empty data
     */
    public function testDisplaySettingsEmptyData()
    {
        $data = [];
        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_radio', $result);
        $this->assertEquals('field_options', $result['field_options_radio']['label']);
        $this->assertEquals('radio', $result['field_options_radio']['group']);
        $this->assertArrayHasKey('settings', $result['field_options_radio']);
    }

    /**
     * Test display_settings with existing data
     */
    public function testDisplaySettingsWithData()
    {
        $data = [
            'field_list_items' => ['option1' => 'Option 1', 'option2' => 'Option 2']
        ];
        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_radio', $result);
        $this->assertEquals('field_options', $result['field_options_radio']['label']);
        $this->assertEquals('radio', $result['field_options_radio']['group']);
    }

    /**
     * Test grid_display_settings with empty data
     */
    public function testGridDisplaySettingsEmptyData()
    {
        $data = [];
        $result = $this->fieldtype->grid_display_settings($data);

        $this->assertArrayHasKey('field_options_radio', $result);
        $this->assertEquals('field_options', $result['field_options_radio']['label']);
        $this->assertEquals('radio', $result['field_options_radio']['group']);
        $this->assertArrayHasKey('settings', $result['field_options_radio']);
    }

    /**
     * Test grid_display_settings with existing data
     */
    public function testGridDisplaySettingsWithData()
    {
        $data = [
            'field_list_items' => ['option1' => 'Option 1', 'option2' => 'Option 2']
        ];
        $result = $this->fieldtype->grid_display_settings($data);

        $this->assertArrayHasKey('field_options_radio', $result);
        $this->assertEquals('field_options', $result['field_options_radio']['label']);
        $this->assertEquals('radio', $result['field_options_radio']['group']);
    }

    /**
     * Test display_settings structure
     */
    public function testDisplaySettingsStructure()
    {
        $data = [];
        $result = $this->fieldtype->display_settings($data);

        // Verify the structure matches expected format
        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $fieldOptions = $result['field_options_radio'];
        $this->assertArrayHasKey('label', $fieldOptions);
        $this->assertArrayHasKey('group', $fieldOptions);
        $this->assertArrayHasKey('settings', $fieldOptions);
    }

    /**
     * Test grid_display_settings structure
     */
    public function testGridDisplaySettingsStructure()
    {
        $data = [];
        $result = $this->fieldtype->grid_display_settings($data);

        // Verify the structure matches expected format
        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $fieldOptions = $result['field_options_radio'];
        $this->assertArrayHasKey('label', $fieldOptions);
        $this->assertArrayHasKey('group', $fieldOptions);
        $this->assertArrayHasKey('settings', $fieldOptions);
    }

    /**
     * Test display_settings with complex data structure
     */
    public function testDisplaySettingsWithComplexData()
    {
        $data = [
            'field_list_items' => [
                'group1' => [
                    'name' => 'Group 1',
                    'children' => [
                        'option1' => 'Option 1',
                        'option2' => 'Option 2'
                    ]
                ],
                'option3' => 'Standalone Option'
            ],
            'field_pre_populate' => 'y',
            'field_text_direction' => 'ltr'
        ];

        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_radio', $result);
        $this->assertEquals('field_options', $result['field_options_radio']['label']);
    }
}

// EOF
