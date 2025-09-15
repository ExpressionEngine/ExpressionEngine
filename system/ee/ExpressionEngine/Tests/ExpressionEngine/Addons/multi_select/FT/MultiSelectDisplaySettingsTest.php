<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../MultiSelectTestBase.php';

/**
 * Test Multi Select fieldtype display_settings method
 */
class MultiSelectDisplaySettingsTest extends MultiSelectTestBase
{
    /**
     * Test display_settings method
     */
    public function testDisplaySettingsMethod()
    {
        $data = [];

        $result = $this->fieldtype->display_settings($data);

        // Should return the expected settings structure
        $this->assertArrayHasKey('field_options_multi_select', $result);
        $this->assertEquals('field_options', $result['field_options_multi_select']['label']);
        $this->assertEquals('multi_select', $result['field_options_multi_select']['group']);
        $this->assertArrayHasKey('settings', $result['field_options_multi_select']);
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
            ]
        ];

        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_multi_select', $result);
    }

    /**
     * Test display_settings method with empty data
     */
    public function testDisplaySettingsEmptyData()
    {
        $data = [];

        $result = $this->fieldtype->display_settings($data);

        $this->assertArrayHasKey('field_options_multi_select', $result);
    }

    /**
     * Test display_settings method structure
     */
    public function testDisplaySettingsStructure()
    {
        $data = [];

        $result = $this->fieldtype->display_settings($data);

        $this->assertIsArray($result);
        $this->assertIsArray($result['field_options_multi_select']);
        $this->assertIsArray($result['field_options_multi_select']['settings']);
    }
}

// EOF

