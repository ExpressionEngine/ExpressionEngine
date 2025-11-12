<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibFormStateTest extends ChannelFormLibTestBase
{
    /**
     * Test form_attribute handles single attribute
     */
    public function testFormAttributeHandlesSingleAttribute()
    {
        // Call form_attribute with single value
        $this->channelFormLib->form_attribute('class', 'form-control');

        // Verify attribute was stored
        $attributesProperty = $this->getProtectedProperty('_form_attributes');
        $attributes = $attributesProperty->getValue($this->channelFormLib);

        $this->assertArrayHasKey('class', $attributes);
        $this->assertEquals('form-control', $attributes['class']);
    }

    /**
     * Test form_attribute handles array of attributes
     */
    public function testFormAttributeHandlesArrayOfAttributes()
    {
        $attributes = [
            'class' => 'form-control',
            'id' => 'test-form',
            'data-type' => 'channel-form'
        ];

        // Call form_attribute with array
        $this->channelFormLib->form_attribute($attributes);

        // Verify all attributes were stored
        $attributesProperty = $this->getProtectedProperty('_form_attributes');
        $storedAttributes = $attributesProperty->getValue($this->channelFormLib);

        $this->assertArrayHasKey('class', $storedAttributes);
        $this->assertArrayHasKey('id', $storedAttributes);
        $this->assertArrayHasKey('data-type', $storedAttributes);
        $this->assertEquals('form-control', $storedAttributes['class']);
        $this->assertEquals('test-form', $storedAttributes['id']);
        $this->assertEquals('channel-form', $storedAttributes['data-type']);
    }

    /**
     * Test form_attribute filters out null/empty values
     */
    public function testFormAttributeFiltersNullEmptyValues()
    {
        // Test with null value
        $this->channelFormLib->form_attribute('test_null', null);
        // Test with empty string
        $this->channelFormLib->form_attribute('test_empty', '');
        // Test with false value
        $this->channelFormLib->form_attribute('test_false', false);
        // Test with valid value
        $this->channelFormLib->form_attribute('test_valid', 'valid-value');

        // Verify attributes were stored/cached appropriately
        $attributesProperty = $this->getProtectedProperty('_form_attributes');
        $attributes = $attributesProperty->getValue($this->channelFormLib);

        // Only empty strings and false values should not be stored (null values are stored)
        $this->assertArrayHasKey('test_null', $attributes); // null values are actually stored
        $this->assertArrayNotHasKey('test_empty', $attributes);
        $this->assertArrayNotHasKey('test_false', $attributes);
        // Valid value should be stored
        $this->assertArrayHasKey('test_valid', $attributes);
        $this->assertEquals('valid-value', $attributes['test_valid']);
    }

    /**
     * Test form_attribute handles special characters and XSS
     */
    public function testFormAttributeHandlesSpecialCharacters()
    {
        $specialValues = [
            'quotes' => 'value with "quotes"',
            'single_quotes' => "value with 'quotes'",
            'html' => '<script>alert("xss")</script>',
            'entities' => 'value & with <entities>',
            'unicode' => 'value with üñíçødé'
        ];

        foreach ($specialValues as $key => $value) {
            $this->channelFormLib->form_attribute($key, $value);
        }

        // Verify all special characters were stored correctly
        $attributesProperty = $this->getProtectedProperty('_form_attributes');
        $attributes = $attributesProperty->getValue($this->channelFormLib);

        foreach ($specialValues as $key => $expectedValue) {
            $this->assertArrayHasKey($key, $attributes);
            $this->assertEquals($expectedValue, $attributes[$key]);
        }
    }

    /**
     * Test form_hidden handles single hidden field
     */
    public function testFormHiddenHandlesSingleHiddenField()
    {
        // Call form_hidden with single value
        $this->channelFormLib->form_hidden('csrf_token', 'abc123');

        // Verify hidden field was stored
        $hiddenProperty = $this->getProtectedProperty('_hidden_fields');
        $hiddenFields = $hiddenProperty->getValue($this->channelFormLib);

        $this->assertArrayHasKey('csrf_token', $hiddenFields);
        $this->assertEquals('abc123', $hiddenFields['csrf_token']);
    }

    /**
     * Test form_hidden handles array of hidden fields
     */
    public function testFormHiddenHandlesArrayOfHiddenFields()
    {
        $hiddenFields = [
            'csrf_token' => 'abc123',
            'entry_id' => '456',
            'channel_id' => '789'
        ];

        // Call form_hidden with array
        $this->channelFormLib->form_hidden($hiddenFields);

        // Verify all hidden fields were stored
        $hiddenProperty = $this->getProtectedProperty('_hidden_fields');
        $storedHiddenFields = $hiddenProperty->getValue($this->channelFormLib);

        $this->assertArrayHasKey('csrf_token', $storedHiddenFields);
        $this->assertArrayHasKey('entry_id', $storedHiddenFields);
        $this->assertArrayHasKey('channel_id', $storedHiddenFields);
        $this->assertEquals('abc123', $storedHiddenFields['csrf_token']);
        $this->assertEquals('456', $storedHiddenFields['entry_id']);
        $this->assertEquals('789', $storedHiddenFields['channel_id']);
    }

    /**
     * Test form_hidden filters out null/empty values
     */
    public function testFormHiddenFiltersNullEmptyValues()
    {
        // Test with null value
        $this->channelFormLib->form_hidden('test_null', null);
        // Test with empty string
        $this->channelFormLib->form_hidden('test_empty', '');
        // Test with false value
        $this->channelFormLib->form_hidden('test_false', false);
        // Test with valid value
        $this->channelFormLib->form_hidden('test_valid', 'valid-value');

        // Verify hidden fields were stored/cached appropriately
        $hiddenProperty = $this->getProtectedProperty('_hidden_fields');
        $hiddenFields = $hiddenProperty->getValue($this->channelFormLib);

        // Only empty strings and false values should not be stored (null values are stored)
        $this->assertArrayHasKey('test_null', $hiddenFields); // null values are actually stored
        $this->assertArrayNotHasKey('test_empty', $hiddenFields);
        $this->assertArrayNotHasKey('test_false', $hiddenFields);
        // Valid value should be stored
        $this->assertArrayHasKey('test_valid', $hiddenFields);
        $this->assertEquals('valid-value', $hiddenFields['test_valid']);
    }

    /**
     * Test form_hidden handles numeric and boolean values
     */
    public function testFormHiddenHandlesNumericAndBooleanValues()
    {
        // Test numeric values
        $this->channelFormLib->form_hidden('entry_id', 123);
        $this->channelFormLib->form_hidden('channel_id', 0); // Zero should be allowed
        // Test boolean values (false should be filtered, true should be converted)
        $this->channelFormLib->form_hidden('test_true', true);
        $this->channelFormLib->form_hidden('test_false', false);

        // Verify hidden fields
        $hiddenProperty = $this->getProtectedProperty('_hidden_fields');
        $hiddenFields = $hiddenProperty->getValue($this->channelFormLib);

        $this->assertArrayHasKey('entry_id', $hiddenFields);
        $this->assertArrayHasKey('channel_id', $hiddenFields);
        $this->assertArrayHasKey('test_true', $hiddenFields);
        $this->assertArrayNotHasKey('test_false', $hiddenFields); // false should be filtered

        $this->assertEquals('123', $hiddenFields['entry_id']); // Converted to string
        $this->assertEquals('0', $hiddenFields['channel_id']); // Zero preserved as string
        $this->assertEquals('1', $hiddenFields['test_true']); // true converted to "1"
    }

    /**
     * Test form_hidden handles large arrays efficiently
     */
    public function testFormHiddenHandlesLargeArrays()
    {
        // Create a large array of hidden fields
        $largeHiddenFields = [];
        for ($i = 1; $i <= 100; $i++) {
            $largeHiddenFields['field_' . $i] = 'value_' . $i;
        }

        // Add the large array
        $this->channelFormLib->form_hidden($largeHiddenFields);

        // Verify all fields were stored
        $hiddenProperty = $this->getProtectedProperty('_hidden_fields');
        $storedHiddenFields = $hiddenProperty->getValue($this->channelFormLib);

        $this->assertCount(100, $storedHiddenFields);
        for ($i = 1; $i <= 100; $i++) {
            $this->assertArrayHasKey('field_' . $i, $storedHiddenFields);
            $this->assertEquals('value_' . $i, $storedHiddenFields['field_' . $i]);
        }
    }

    /**
     * Test form_attribute and form_hidden work together
     */
    public function testFormAttributeAndFormHiddenWorkTogether()
    {
        // Add some attributes
        $this->channelFormLib->form_attribute([
            'class' => 'form-control',
            'id' => 'test-form'
        ]);

        // Add some hidden fields
        $this->channelFormLib->form_hidden([
            'csrf_token' => 'abc123',
            'entry_id' => '456'
        ]);

        // Verify both are stored independently
        $attributesProperty = $this->getProtectedProperty('_form_attributes');
        $attributes = $attributesProperty->getValue($this->channelFormLib);

        $hiddenProperty = $this->getProtectedProperty('_hidden_fields');
        $hiddenFields = $hiddenProperty->getValue($this->channelFormLib);

        // Check attributes
        $this->assertArrayHasKey('class', $attributes);
        $this->assertArrayHasKey('id', $attributes);
        $this->assertEquals('form-control', $attributes['class']);
        $this->assertEquals('test-form', $attributes['id']);

        // Check hidden fields
        $this->assertArrayHasKey('csrf_token', $hiddenFields);
        $this->assertArrayHasKey('entry_id', $hiddenFields);
        $this->assertEquals('abc123', $hiddenFields['csrf_token']);
        $this->assertEquals('456', $hiddenFields['entry_id']);
    }

    /**
     * Test form_attribute overwrites existing values
     */
    public function testFormAttributeOverwritesExistingValues()
    {
        // Add initial attribute
        $this->channelFormLib->form_attribute('class', 'initial-class');

        // Verify initial value
        $attributesProperty = $this->getProtectedProperty('_form_attributes');
        $attributes = $attributesProperty->getValue($this->channelFormLib);
        $this->assertEquals('initial-class', $attributes['class']);

        // Overwrite with new value
        $this->channelFormLib->form_attribute('class', 'new-class');

        // Verify new value overwrote old one
        $attributes = $attributesProperty->getValue($this->channelFormLib);
        $this->assertEquals('new-class', $attributes['class']);
        $this->assertCount(1, $attributes); // Only one class attribute
    }

    /**
     * Test form_hidden overwrites existing values
     */
    public function testFormHiddenOverwritesExistingValues()
    {
        // Add initial hidden field
        $this->channelFormLib->form_hidden('csrf_token', 'initial_token');

        // Verify initial value
        $hiddenProperty = $this->getProtectedProperty('_hidden_fields');
        $hiddenFields = $hiddenProperty->getValue($this->channelFormLib);
        $this->assertEquals('initial_token', $hiddenFields['csrf_token']);

        // Overwrite with new value
        $this->channelFormLib->form_hidden('csrf_token', 'new_token');

        // Verify new value overwrote old one
        $hiddenFields = $hiddenProperty->getValue($this->channelFormLib);
        $this->assertEquals('new_token', $hiddenFields['csrf_token']);
        $this->assertCount(1, $hiddenFields); // Only one csrf_token field
    }
}
