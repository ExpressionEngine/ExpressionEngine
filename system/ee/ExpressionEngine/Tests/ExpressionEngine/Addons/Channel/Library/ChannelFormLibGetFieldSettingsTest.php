<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibGetFieldSettingsTest extends ChannelFormLibTestBase
{
    public function testGetFieldSettingsReturnsCorrectSettingsForValidField()
    {
        $fieldSettings = [
            'max_length' => 255,
            'field_instructions' => 'Enter your text here',
            'field_required' => 'y'
        ];

        $mockField = new class($fieldSettings) {
            private $settings;

            public function __construct($settings) {
                $this->settings = $settings;
            }

            public function getProperty($key, $default = null) {
                return $key === 'field_settings' ? $this->settings : $default;
            }

            public function getValues() {
                return [
                    'field_id' => 1,
                    'field_type' => 'text',
                    'field_settings' => $this->settings
                ];
            }
        };

        $this->channelFormLib->custom_fields['text_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_settings('text_field');
        $this->assertEquals($fieldSettings, $result);
        $this->assertArrayHasKey('max_length', $result);
        $this->assertArrayHasKey('field_instructions', $result);
        $this->assertArrayHasKey('field_required', $result);
    }

    public function testGetFieldSettingsReturnsEmptyArrayForTitleFields()
    {
        $this->channelFormLib->custom_fields['title'] = new stdClass();
        $this->channelFormLib->title_fields = ['title', 'url_title'];

        $result = $this->channelFormLib->get_field_settings('title');
        $this->assertEquals([], $result);
    }

    public function testGetFieldSettingsReturnsEmptyArrayForNonExistentField()
    {
        $this->channelFormLib->custom_fields = [];
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_settings('nonexistent_field');
        $this->assertEquals([], $result);
    }

    public function testGetFieldSettingsWithEmptyFieldSettings()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_settings' ? [] : $default;
            }

            public function getValues() {
                return [
                    'field_id' => 2,
                    'field_type' => 'text',
                    'field_settings' => []
                ];
            }
        };

        $this->channelFormLib->custom_fields['empty_settings_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_settings('empty_settings_field');
        $this->assertEquals([], $result);
        $this->assertIsArray($result);
    }

    public function testGetFieldSettingsWithComplexFieldSettings()
    {
        $complexSettings = [
            'field_instructions' => 'Select your preferred option',
            'field_pre_populate' => 'n',
            'field_pre_field_id' => '',
            'field_pre_channel_id' => '',
            'value_label_pairs' => [
                'option1' => 'First Option',
                'option2' => 'Second Option',
                'option3' => 'Third Option'
            ],
            'field_required' => 'y',
            'field_search' => 'y',
            'field_is_hidden' => 'n'
        ];

        $mockField = new class($complexSettings) {
            private $settings;

            public function __construct($settings) {
                $this->settings = $settings;
            }

            public function getProperty($key, $default = null) {
                return $key === 'field_settings' ? $this->settings : $default;
            }

            public function getValues() {
                return [
                    'field_id' => 3,
                    'field_type' => 'select',
                    'field_settings' => $this->settings
                ];
            }
        };

        $this->channelFormLib->custom_fields['complex_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_settings('complex_field');
        $this->assertEquals($complexSettings, $result);
        $this->assertArrayHasKey('value_label_pairs', $result);
        $this->assertCount(3, $result['value_label_pairs']);
        $this->assertEquals('y', $result['field_required']);
    }

    public function testGetFieldSettingsWithRelationshipFieldSettings()
    {
        $relationshipSettings = [
            'allow_multiple' => '0',
            'order_field' => 'title',
            'order_dir' => 'asc',
            'channels' => [1, 2],
            'categories' => [],
            'statuses' => ['open'],
            'authors' => [],
            'expired' => '0',
            'future' => '0',
            'limit' => 100
        ];

        $mockField = new class($relationshipSettings) {
            private $settings;

            public function __construct($settings) {
                $this->settings = $settings;
            }

            public function getProperty($key, $default = null) {
                return $key === 'field_settings' ? $this->settings : $default;
            }

            public function getValues() {
                return [
                    'field_id' => 4,
                    'field_type' => 'relationship',
                    'field_settings' => $this->settings
                ];
            }
        };

        $this->channelFormLib->custom_fields['relationship_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_settings('relationship_field');
        $this->assertEquals($relationshipSettings, $result);
        $this->assertEquals('0', $result['allow_multiple']);
        $this->assertEquals('title', $result['order_field']);
        $this->assertEquals([1, 2], $result['channels']);
    }

    public function testGetFieldSettingsWithFileFieldSettings()
    {
        $fileSettings = [
            'allowed_directories' => 'all',
            'field_content_type' => 'image',
            'file_extensions' => 'jpg,png,gif',
            'max_file_size' => '1024',
            'manipulations' => []
        ];

        $mockField = new class($fileSettings) {
            private $settings;

            public function __construct($settings) {
                $this->settings = $settings;
            }

            public function getProperty($key, $default = null) {
                return $key === 'field_settings' ? $this->settings : $default;
            }

            public function getValues() {
                return [
                    'field_id' => 5,
                    'field_type' => 'file',
                    'field_settings' => $this->settings
                ];
            }
        };

        $this->channelFormLib->custom_fields['file_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_settings('file_field');
        $this->assertEquals($fileSettings, $result);
        $this->assertEquals('image', $result['field_content_type']);
        $this->assertEquals('jpg,png,gif', $result['file_extensions']);
    }

    public function testGetFieldSettingsWithNumericFieldNames()
    {
        $settings = ['max_length' => 100];

        $mockField = new class($settings) {
            private $settings;

            public function __construct($settings) {
                $this->settings = $settings;
            }

            public function getProperty($key, $default = null) {
                return $key === 'field_settings' ? $this->settings : $default;
            }

            public function getValues() {
                return [
                    'field_id' => 6,
                    'field_type' => 'text',
                    'field_settings' => $this->settings
                ];
            }
        };

        $this->channelFormLib->custom_fields['42'] = $mockField;
        $this->channelFormLib->title_fields = [];

        // Test with string numeric field name
        $result = $this->channelFormLib->get_field_settings('42');
        $this->assertEquals($settings, $result);

        // Test with integer field name
        $result = $this->channelFormLib->get_field_settings(42);
        $this->assertEquals($settings, $result);
    }

    public function testGetFieldSettingsWithEmptyCustomFields()
    {
        $this->channelFormLib->custom_fields = [];
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_settings('any_field');
        $this->assertEquals([], $result);
    }

    public function testGetFieldSettingsWithNullCustomFields()
    {
        $this->channelFormLib->custom_fields = null;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_settings('any_field');
        $this->assertEquals([], $result);
    }
}
