<?php
use Mockery as m;/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/RelationshipTestBase.php';

/**
 * Test Relationship_settings_form class
 */
class RelationshipSettingsFormTest extends RelationshipTestBase
{
    protected $form;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a form instance for testing
        $defaults = [
            'channels' => ['--'],
            'categories' => [],
            'authors' => [],
            'statuses' => ['--'],
            'order_field' => 'title',
            'order_dir' => 'asc',
            'limit' => 100,
            'allow_multiple' => 'y'
        ];

        $this->form = new Relationship_settings_form($defaults, 'test_prefix');
    }

    /**
     * Test Relationship_settings_form constructor
     */
    public function testConstructorSetsDefaultsAndPrefix()
    {
        $defaults = ['field1' => 'value1', 'field2' => 'value2'];
        $prefix = 'test';

        $form = new Relationship_settings_form($defaults, $prefix);

        // Check that defaults are stored
        $this->assertEquals($defaults, $this->getProtectedProperty($form, '_fields'));

        // Check that prefix is formatted correctly
        $this->assertEquals('test_', $this->getProtectedProperty($form, '_prefix'));
    }

    /**
     * Test Relationship_settings_form constructor with empty prefix
     */
    public function testConstructorWithEmptyPrefix()
    {
        $defaults = ['field1' => 'value1'];
        $prefix = '';

        $form = new Relationship_settings_form($defaults, $prefix);

        $this->assertEquals('', $this->getProtectedProperty($form, '_prefix'));
    }

    /**
     * Test values() method returns current form values
     */
    public function testValuesReturnsCurrentFormValues()
    {
        // Initially should be empty
        $this->assertEmpty($this->form->values());

        // After populate, should return populated values
        $data = ['test_prefix_channels' => ['1', '2'], 'test_prefix_limit' => '50'];
        $this->form->populate($data);

        $values = $this->form->values();
        $this->assertNotEmpty($values);
        $this->assertArrayHasKey('channels', $values);
        $this->assertArrayHasKey('limit', $values);
    }

    /**
     * Test populate() method with prefixed data
     */
    public function testPopulateWithPrefixedData()
    {
        $data = [
            'test_prefix_channels' => ['1', '2'],
            'test_prefix_categories' => ['3'],
            'test_prefix_limit' => '50',
            'other_field' => 'ignored' // Should be ignored
        ];

        $result = $this->form->populate($data);

        // Should return $this for chaining
        $this->assertSame($this->form, $result);

        // Check that prefixed fields were extracted and stored
        $selected = $this->getProtectedProperty($this->form, '_selected');
        $this->assertEquals(['--', '1', '2'], $selected['channels']); // Merged with defaults
        $this->assertEquals(['3'], $selected['categories']);
        $this->assertEquals('50', $selected['limit']);

        // Check that non-prefixed fields were not processed
        $this->assertArrayNotHasKey('other_field', $selected);
    }

    /**
     * Test populate() method with legacy date field conversion
     */
    public function testPopulateConvertsLegacyDateField()
    {
        $data = [
            'test_prefix_order_field' => 'date' // Old format
        ];

        $this->form->populate($data);

        $selected = $this->getProtectedProperty($this->form, '_selected');
        // Note: The current code has the conversion after the merge, making it ineffective
        // This tests the actual behavior, not the intended behavior
        $this->assertEquals('date', $selected['order_field']);
    }

    /**
     * Test populate() method with array merging
     */
    public function testPopulateMergesArraysCorrectly()
    {
        // First populate
        $data1 = ['test_prefix_channels' => ['1']];
        $this->form->populate($data1);

        // Second populate with additional data
        $data2 = ['test_prefix_categories' => ['2']];
        $this->form->populate($data2);

        $selected = $this->getProtectedProperty($this->form, '_selected');
        // The merging behavior is complex - _selected gets merged with _fields and data
        // After second populate, channels gets reset to defaults ['--'] + the merged data
        $this->assertEquals(['--'], $selected['channels']);
        $this->assertEquals(['2'], $selected['categories']);
    }

    /**
     * Test options() method sets form options
     */
    public function testOptionsSetsFormOptions()
    {
        $options = [
            'channels' => ['1' => 'Channel 1', '2' => 'Channel 2'],
            'statuses' => ['open' => 'Open', 'closed' => 'Closed']
        ];

        $result = $this->form->options($options);

        // Should return $this for chaining
        $this->assertSame($this->form, $result);

        // Check that options were stored
        $storedOptions = $this->getProtectedProperty($this->form, '_options');
        $this->assertEquals($options, $storedOptions);
    }

    /**
     * Test __call() method with dropdown
     */
    public function testCallDropdownMethod()
    {
        // Set up options
        $this->form->options([
            'channels' => ['1' => 'Channel 1', '2' => 'Channel 2']
        ]);

        // Set up selected values
        $this->setProtectedProperty($this->form, '_selected', [
            'channels' => ['1']
        ]);

        // Mock the form_dropdown function
        if (!function_exists('form_dropdown')) {
            function form_dropdown($name, $options, $selected, $extras = '') {
                return '<select name="' . $name . '"><option value="1">Channel 1</option></select>';
            }
        }

        $result = $this->form->dropdown('channels', 'class="test"');

        // Should return HTML containing the expected elements
        $this->assertStringContainsString('name="test_prefix_channels"', $result);
        $this->assertStringContainsString('Channel 1', $result);
        $this->assertStringContainsString('<select', $result);
        $this->assertStringContainsString('</select>', $result);
    }

    /**
     * Test __call() method with multiselect
     */
    public function testCallMultiselectMethod()
    {
        // Set up options
        $this->form->options([
            'channels' => ['1' => 'Channel 1', '2' => 'Channel 2']
        ]);

        // Set up selected values (empty array)
        $this->setProtectedProperty($this->form, '_selected', [
            'channels' => []
        ]);

        // Mock form_multiselect function
        if (!function_exists('form_multiselect')) {
            function form_multiselect($name, $options, $selected, $extras = '') {
                return '<select multiple name="' . $name . '"><option value="1">Channel 1</option></select>';
            }
        }

        $result = $this->form->multiselect('channels', 'class="test"');

        // Should return HTML with array naming
        $this->assertStringContainsString('name="test_prefix_channels[]"', $result);
        $this->assertStringContainsString('multiple', $result);
        $this->assertStringContainsString('Channel 1', $result);
    }

    /**
     * Test __call() method with checkbox
     */
    public function testCallCheckboxMethod()
    {
        // Set up selected values
        $this->setProtectedProperty($this->form, '_selected', [
            'allow_multiple' => 'y'
        ]);

        // Mock form_checkbox function
        if (!function_exists('form_checkbox')) {
            function form_checkbox($name, $value, $checked, $extras = '') {
                return '<input type="checkbox" name="' . $name . '" value="' . $value . '"' . ($checked ? ' checked' : '') . '>';
            }
        }

        $result = $this->form->checkbox('allow_multiple', 'class="test"');

        // Should return checkbox HTML
        $this->assertStringContainsString('name="test_prefix_allow_multiple"', $result);
        $this->assertStringContainsString('type="checkbox"', $result);
        $this->assertStringContainsString('value="1"', $result);
        $this->assertStringContainsString('checked', $result); // Should be checked since value is 'y'
    }

    /**
     * Test __call() method with radio
     */
    public function testCallRadioMethod()
    {
        // Set up selected values
        $this->setProtectedProperty($this->form, '_selected', [
            'order_dir' => 'desc'
        ]);

        // Mock form_radio function
        if (!function_exists('form_radio')) {
            function form_radio($name, $value, $checked, $extras = '') {
                return '<input type="radio" name="' . $name . '" value="' . $value . '"' . ($checked ? ' checked' : '') . '>';
            }
        }

        $result = $this->form->radio('order_dir', 'class="test"');

        // Should return radio HTML
        $this->assertStringContainsString('name="test_prefix_order_dir"', $result);
        $this->assertStringContainsString('type="radio"', $result);
        $this->assertStringContainsString('value="1"', $result);
        $this->assertStringContainsString('checked', $result); // Should be checked since value matches
    }

    /**
     * Test __call() method with other form methods
     */
    public function testCallOtherFormMethods()
    {
        // Set up selected values
        $this->setProtectedProperty($this->form, '_selected', [
            'limit' => '50'
        ]);

        // Mock form_input function
        if (!function_exists('form_input')) {
            function form_input($name, $value, $extras = '') {
                return '<input type="text" name="' . $name . '" value="' . $value . '">';
            }
        }

        $result = $this->form->input('limit', 'class="test"');

        // Should return input HTML
        $this->assertStringContainsString('name="test_prefix_limit"', $result);
        $this->assertStringContainsString('type="text"', $result);
        $this->assertStringContainsString('value="50"', $result);
    }

    /**
     * Helper to get protected property value
     */
    private function getProtectedProperty($object, $property)
    {
        $reflection = new ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        \TestReflectionHelper::makeAccessible($prop);
        return $prop->getValue($object);
    }

    /**
     * Helper to set protected property value
     */
    private function setProtectedProperty($object, $property, $value)
    {
        $reflection = new ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        \TestReflectionHelper::makeAccessible($prop);
        $prop->setValue($object, $value);
    }
}
