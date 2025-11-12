<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibGetFieldDataTest extends ChannelFormLibTestBase
{
    public function testGetFieldDataReturnsAllDataWhenNoKeySpecified()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                $properties = [
                    'field_id' => 1,
                    'field_type' => 'text',
                    'field_settings' => ['max_length' => 100],
                    'field_name' => 'test_field'
                ];
                return $properties[$key] ?? $default;
            }

            public function getValues() {
                return [
                    'field_id' => 1,
                    'field_type' => 'text',
                    'field_settings' => ['max_length' => 100],
                    'field_name' => 'test_field'
                ];
            }
        };

        $this->channelFormLib->custom_fields['test_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_data('test_field');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('field_id', $result);
        $this->assertArrayHasKey('field_type', $result);
        $this->assertArrayHasKey('field_settings', $result);
        $this->assertEquals(1, $result['field_id']);
        $this->assertEquals('text', $result['field_type']);
    }

    public function testGetFieldDataReturnsSpecificKeyWhenKeySpecified()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                $properties = [
                    'field_id' => 2,
                    'field_type' => 'select',
                    'field_settings' => ['options' => ['opt1', 'opt2']]
                ];
                return $properties[$key] ?? $default;
            }

            public function getValues() {
                return [
                    'field_id' => 2,
                    'field_type' => 'select',
                    'field_settings' => ['options' => ['opt1', 'opt2']]
                ];
            }
        };

        $this->channelFormLib->custom_fields['select_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_data('select_field', 'field_type');
        $this->assertEquals('select', $result);

        $result = $this->channelFormLib->get_field_data('select_field', 'field_id');
        $this->assertEquals(2, $result);
    }

    public function testGetFieldDataReturnsEmptyArrayForTitleFields()
    {
        $this->channelFormLib->custom_fields['title'] = new stdClass();
        $this->channelFormLib->title_fields = ['title', 'url_title'];

        $result = $this->channelFormLib->get_field_data('title');
        $this->assertEquals([], $result);

        $result = $this->channelFormLib->get_field_data('title', 'field_id');
        $this->assertEquals([], $result);
    }

    public function testGetFieldDataReturnsEmptyArrayForNonExistentField()
    {
        $this->channelFormLib->custom_fields = [];
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_data('nonexistent_field');
        $this->assertEquals([], $result);

        $result = $this->channelFormLib->get_field_data('nonexistent_field', 'field_id');
        $this->assertEquals([], $result);
    }

    public function testGetFieldDataHandlesMissingKeyWithDefault()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                $properties = [
                    'field_id' => 3,
                    'field_type' => 'textarea'
                ];
                return $properties[$key] ?? $default;
            }

            public function getValues() {
                return [
                    'field_id' => 3,
                    'field_type' => 'textarea'
                ];
            }
        };

        $this->channelFormLib->custom_fields['textarea_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        // Test requesting a key that doesn't exist
        $result = $this->channelFormLib->get_field_data('textarea_field', 'nonexistent_key');
        $this->assertEquals([], $result); // Should return the default empty array
    }

    public function testGetFieldDataWithComplexFieldSettings()
    {
        $complexSettings = [
            'field_instructions' => 'Enter your content here',
            'field_pre_populate' => 'n',
            'field_pre_field_id' => '',
            'field_pre_channel_id' => '',
            'value_label_pairs' => [
                'option1' => 'Option One',
                'option2' => 'Option Two'
            ]
        ];

        $mockField = new class($complexSettings) {
            private $settings;

            public function __construct($settings) {
                $this->settings = $settings;
            }

            public function getProperty($key, $default = null) {
                if ($key === 'field_settings') {
                    return $this->settings;
                }
                return $key === 'field_id' ? 4 : $default;
            }

            public function getValues() {
                return [
                    'field_id' => 4,
                    'field_type' => 'select',
                    'field_settings' => $this->settings
                ];
            }
        };

        $this->channelFormLib->custom_fields['complex_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_data('complex_field', 'field_settings');
        $this->assertEquals($complexSettings, $result);
        $this->assertArrayHasKey('value_label_pairs', $result);
        $this->assertCount(2, $result['value_label_pairs']);
    }

    public function testGetFieldDataWithNumericFieldNames()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_id' ? 5 : $default;
            }

            public function getValues() {
                return ['field_id' => 5, 'field_type' => 'text'];
            }
        };

        $this->channelFormLib->custom_fields['5'] = $mockField;
        $this->channelFormLib->title_fields = [];

        // Test with string numeric field name
        $result = $this->channelFormLib->get_field_data('5');
        $this->assertEquals(['field_id' => 5, 'field_type' => 'text'], $result);

        // Test with integer numeric field name
        $result = $this->channelFormLib->get_field_data(5);
        $this->assertEquals(['field_id' => 5, 'field_type' => 'text'], $result);
    }

    public function testGetFieldDataWithEmptyCustomFields()
    {
        $this->channelFormLib->custom_fields = [];
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_data('any_field');
        $this->assertEquals([], $result);
    }

    public function testGetFieldDataWithNullCustomFields()
    {
        $this->channelFormLib->custom_fields = null;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_data('any_field');
        $this->assertEquals([], $result);
    }

    public function testGetFieldDataWithSpecialCharactersInFieldNames()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_id' ? 6 : $default;
            }

            public function getValues() {
                return ['field_id' => 6, 'field_type' => 'text'];
            }
        };

        $specialFieldNames = [
            'field-with-dashes',
            'field_with_underscores',
            'field.with.dots',
            'field[with]brackets'
        ];

        foreach ($specialFieldNames as $fieldName) {
            $this->channelFormLib->custom_fields[$fieldName] = $mockField;
        }
        $this->channelFormLib->title_fields = [];

        foreach ($specialFieldNames as $fieldName) {
            $result = $this->channelFormLib->get_field_data($fieldName, 'field_id');
            $this->assertEquals(6, $result, "Failed for field name: $fieldName");
        }
    }
}
