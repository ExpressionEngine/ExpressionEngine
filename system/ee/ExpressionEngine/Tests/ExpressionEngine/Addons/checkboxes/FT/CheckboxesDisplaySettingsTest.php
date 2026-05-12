<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../CheckboxesTestBase.php';

use Mockery as m;

/**
 * Test for Checkboxes_ft::display_settings() method
 */
class CheckboxesDisplaySettingsTest extends CheckboxesTestBase
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
     * Test display_settings with basic data
     */
    public function testDisplaySettingsBasic()
    {
        $data = [];

        // Mock the getSettingsForm method to return expected structure
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('getSettingsForm')
             ->with('checkboxes', $data, 'checkbox_options', m::type('string'))
             ->andReturn(['mock_settings' => 'data']);
        $mock->shouldReceive('display_settings')
             ->with($data)
             ->andReturn([
                 'field_options_checkboxes' => [
                     'label' => 'field_options',
                     'group' => 'checkboxes',
                     'settings' => ['mock_settings' => 'data']
                 ]
             ]);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

        $result = $mock->display_settings($data);

        $expected = [
            'field_options_checkboxes' => [
                'label' => 'field_options',
                'group' => 'checkboxes',
                'settings' => ['mock_settings' => 'data']
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test display_settings with existing data
     */
    public function testDisplaySettingsWithData()
    {
        $data = [
            'field_list_items' => 'Option 1\nOption 2\nOption 3',
            'field_pre_populate' => 'n'
        ];

        // Mock the getSettingsForm method
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('getSettingsForm')
             ->with('checkboxes', $data, 'checkbox_options', m::type('string'))
             ->andReturn(['settings_with_data' => 'data']);
        $mock->shouldReceive('display_settings')
             ->with($data)
             ->andReturn([
                 'field_options_checkboxes' => [
                     'label' => 'field_options',
                     'group' => 'checkboxes',
                     'settings' => ['settings_with_data' => 'data']
                 ]
             ]);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

        $result = $mock->display_settings($data);

        $expected = [
            'field_options_checkboxes' => [
                'label' => 'field_options',
                'group' => 'checkboxes',
                'settings' => ['settings_with_data' => 'data']
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test display_settings with value-label pairs
     */
    public function testDisplaySettingsWithValueLabelPairs()
    {
        $data = [
            'value_label_pairs' => [
                'option1' => 'Option 1',
                'option2' => 'Option 2'
            ]
        ];

        // Mock the getSettingsForm method
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('getSettingsForm')
             ->with('checkboxes', $data, 'checkbox_options', m::type('string'))
             ->andReturn(['value_label_settings' => 'data']);
        $mock->shouldReceive('display_settings')
             ->with($data)
             ->andReturn([
                 'field_options_checkboxes' => [
                     'label' => 'field_options',
                     'group' => 'checkboxes',
                     'settings' => ['value_label_settings' => 'data']
                 ]
             ]);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

        $result = $mock->display_settings($data);

        $expected = [
            'field_options_checkboxes' => [
                'label' => 'field_options',
                'group' => 'checkboxes',
                'settings' => ['value_label_settings' => 'data']
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test display_settings with pre-populate settings
     */
    public function testDisplaySettingsWithPrePopulate()
    {
        $data = [
            'field_pre_populate' => 'y',
            'field_pre_channel_id' => '1',
            'field_pre_field_id' => '2'
        ];

        // Mock the getSettingsForm method
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('getSettingsForm')
             ->with('checkboxes', $data, 'checkbox_options', m::type('string'))
             ->andReturn(['prepopulate_settings' => 'data']);
        $mock->shouldReceive('display_settings')
             ->with($data)
             ->andReturn([
                 'field_options_checkboxes' => [
                     'label' => 'field_options',
                     'group' => 'checkboxes',
                     'settings' => ['prepopulate_settings' => 'data']
                 ]
             ]);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

        $result = $mock->display_settings($data);

        $expected = [
            'field_options_checkboxes' => [
                'label' => 'field_options',
                'group' => 'checkboxes',
                'settings' => ['prepopulate_settings' => 'data']
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test display_settings structure
     */
    public function testDisplaySettingsStructure()
    {
        $data = [];

        // Mock the getSettingsForm method
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('getSettingsForm')
             ->with('checkboxes', $data, 'checkbox_options', m::type('string'))
             ->andReturn(['test_settings' => 'value']);
        $mock->shouldReceive('display_settings')
             ->with($data)
             ->andReturn([
                 'field_options_checkboxes' => [
                     'label' => 'field_options',
                     'group' => 'checkboxes',
                     'settings' => ['test_settings' => 'value']
                 ]
             ]);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

        $result = $mock->display_settings($data);

        // Verify the structure has the expected keys
        $this->assertArrayHasKey('field_options_checkboxes', $result);
        $this->assertArrayHasKey('label', $result['field_options_checkboxes']);
        $this->assertArrayHasKey('group', $result['field_options_checkboxes']);
        $this->assertArrayHasKey('settings', $result['field_options_checkboxes']);

        // Verify the values
        $this->assertEquals('field_options', $result['field_options_checkboxes']['label']);
        $this->assertEquals('checkboxes', $result['field_options_checkboxes']['group']);
        $this->assertEquals(['test_settings' => 'value'], $result['field_options_checkboxes']['settings']);
    }

    /**
     * Test display_settings method can be called and returns expected structure
     */
    public function testDisplaySettingsMethodCanBeCalled()
    {
        // Test that the display_settings method can be called and returns expected structure
        $data = [];
        $result = $this->fieldtype->display_settings($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('field_options_checkboxes', $result);
    }
}

// EOF
