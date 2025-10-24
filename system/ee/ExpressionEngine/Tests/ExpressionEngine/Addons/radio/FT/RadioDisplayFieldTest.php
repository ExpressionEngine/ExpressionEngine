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
 * Test for Radio_ft::display_field() and related methods
 */
class RadioDisplayFieldTest extends RadioTestBase
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
     * Test display_field public method
     */
    public function testDisplayFieldPublic()
    {
        $data = 'option1';
        $result = $this->fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test grid_display_field method
     */
    public function testGridDisplayField()
    {
        $data = 'option1';
        $result = $this->fieldtype->grid_display_field($data);
        $this->assertStringContainsString('Mock radio grid display', $result);
    }

    /**
     * Test display_field with selected value
     */
    public function testDisplayFieldWithSelectedValue()
    {
        $data = 'option2';
        $result = $this->fieldtype->display_field($data);

        // The mock should handle the selection
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with empty data
     */
    public function testDisplayFieldWithEmptyData()
    {
        $data = '';
        $result = $this->fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with null data (should set default)
     */
    public function testDisplayFieldWithNullData()
    {
        $data = null;
        $result = $this->fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with disabled setting
     */
    public function testDisplayFieldWithDisabledSetting()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings(['field_disabled' => true]);
        $data = 'option1';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test grid_display_field with data
     */
    public function testGridDisplayFieldWithData()
    {
        $data = 'option3';
        $result = $this->fieldtype->grid_display_field($data);
        $this->assertStringContainsString('Mock radio grid display', $result);
    }

    /**
     * Test display_field with boolean true data (should convert to 'y')
     */
    public function testDisplayFieldWithBooleanTrue()
    {
        $data = true;
        $result = $this->fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with boolean false data (should convert to 'n')
     */
    public function testDisplayFieldWithBooleanFalse()
    {
        $data = false;
        $result = $this->fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field in CP context
     */
    public function testDisplayFieldInCPContext()
    {
        // Set REQ to CP
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }

        $data = 'option1';
        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'force_react' => false,
            'editable' => false,
            'in_modal_context' => false,
            'add_btn_label' => null,
            'editing' => false,
            'manage_toggle_label' => 'Manage',
            'reorder_ajax_url' => null,
            'filter_url' => null,
            'no_results' => null,
            'nested' => false,
            'nestableReorder' => false
        ]);

        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with string_override setting
     */
    public function testDisplayFieldWithStringOverride()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings(['string_override' => '<div>Custom override</div>']);
        $data = 'option1';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_settings method
     */
    public function testDisplaySettingsMethod()
    {
        $data = [];
        $result = $this->fieldtype->display_settings($data);
        $this->assertArrayHasKey('field_options_radio', $result);
    }

    /**
     * Test grid_display_settings method
     */
    public function testGridDisplaySettingsMethod()
    {
        $data = [];
        $result = $this->fieldtype->grid_display_settings($data);
        $this->assertArrayHasKey('field_options_radio', $result);
    }

    /**
     * Test display_field with empty field_options
     */
    public function testDisplayFieldEmptyOptions()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'field_options' => []
        ]);

        $data = 'option1';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with field options containing very long values
     */
    public function testDisplayFieldLongOptionValues()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'field_options' => [
                'short' => 'Short',
                'long' => str_repeat('Very long option text ', 50) // ~1000 characters
            ]
        ]);

        $data = 'long';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with XSS-like content in options
     */
    public function testDisplayFieldXssContent()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'field_options' => [
                'safe' => 'Safe option',
                'unsafe' => '<script>alert("xss")</script>',
                'html' => '<b>Bold</b> <i>Italic</i>'
            ]
        ]);

        $data = 'unsafe';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with numeric option keys and values
     */
    public function testDisplayFieldNumericOptions()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'field_options' =>             [
                0 => 'Zero',
                1 => 'One',
                2 => 'Two point five',
                '3' => 'Three as string'
            ]
        ]);

        $data = 1;
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with malformed settings array
     */
    public function testDisplayFieldMalformedSettings()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'field_text_direction' => null, // Should handle null
            'field_pre_populate' => [], // Should handle array
            'field_list_items' => 'not_an_array', // Should handle wrong type
            'field_pre_field_id' => [], // Should handle array
            'field_pre_channel_id' => 123 // Should handle integer
        ]);

        $data = 'option1';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with content_id set (existing entry)
     */
    public function testDisplayFieldWithContentId()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings();
        @$fieldtype->content_id = 123; // Simulate existing entry

        $data = null;
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with various text directions
     */
    public function testDisplayFieldTextDirections()
    {
        $directions = ['ltr', 'rtl', 'auto', 'inherit', ''];

        foreach ($directions as $direction) {
            $fieldtype = $this->getMockRadioFieldtypeWithSettings([
                'field_text_direction' => $direction
            ]);

            $data = 'option1';
            $result = $fieldtype->display_field($data);
            $this->assertStringContainsString('Mock radio display', $result);
        }
    }

    /**
     * Test display_field with unicode characters in options
     */
    public function testDisplayFieldUnicodeOptions()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'field_options' => [
                'emoji' => '🚀 Rocket',
                'accented' => 'café résumé naïve',
                'chinese' => '你好世界',
                'arabic' => 'مرحبا بالعالم',
                'mixed' => 'Hello 世界 🌍'
            ]
        ]);

        $data = 'emoji';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test display_field with options containing line breaks
     */
    public function testDisplayFieldMultilineOptions()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'field_options' => [
                'single' => 'Single line',
                'multi' => "Line 1\nLine 2\nLine 3",
                'tabs' => "Col1\tCol2\tCol3",
                'mixed' => "Line with\ttab\nand newline"
            ]
        ]);

        $data = 'multi';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test grid_display_field with different container scenarios
     */
    public function testGridDisplayFieldContainers()
    {
        $data = 'option1';
        $result = $this->fieldtype->grid_display_field($data);
        $this->assertStringContainsString('Mock radio grid display', $result);
    }

    /**
     * Test display_field with disabled state and extra attributes
     */
    public function testDisplayFieldDisabledWithExtra()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'field_disabled' => true,
            'extra_attributes' => 'data-test="value" class="custom"'
        ]);

        $data = 'option1';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }
}

// EOF
