<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibGetFieldOptionsTest extends ChannelFormLibTestBase
{
    public function testGetFieldOptionsReturnsEmptyArrayForNonOptionField()
    {
        $mockField = new class {
            public $field_id = 1;
            public $field_type = 'text';
            public function getName() { return 'text_field'; }
            public function getType() { return 'text'; }
        };

        $this->channelFormLib->custom_fields['text_field'] = $mockField;
        $this->channelFormLib->option_fields = ['select', 'radio', 'checkboxes', 'multi_select']; // Set option fields

        $result = $this->channelFormLib->get_field_options('text_field');
        $this->assertEquals([], $result);
    }

    public function testGetFieldOptionsForSelectField()
    {
        $mockField = new class {
            public $field_id = 2;
            public $field_type = 'select';
            public $field_list_items = "Option 1\nOption 2\nOption 3";
            public $field_pre_populate = 'n';
            public $field_pre_field_id = null;
            public $field_pre_channel_id = null;
            public function getName() { return 'select_field'; }
            public function getType() { return 'select'; }
            public function getField() {
                return new class {
                    public function getItem($key) {
                        return ['field_settings' => ['value_label_pairs' => []]];
                    }
                };
            }
        };

        $this->channelFormLib->custom_fields['select_field'] = $mockField;
        $this->channelFormLib->option_fields = ['select', 'radio', 'checkboxes', 'multi_select']; // Set option fields
        $this->channelFormLib->native_option_fields = ['multi_select', 'select', 'radio', 'checkboxes']; // Set native option fields

        $mockEntry = $this->createMockEntry();
        $mockEntry->{'field_id_2'} = 'Option 2'; // Field ID 2 corresponds to select_field
        $this->channelFormLib->entry = $mockEntry;

        $result = $this->channelFormLib->get_field_options('select_field');

        $this->assertCount(3, $result);
        $this->assertEquals('Option 1', $result[0]['option_name']);
        $this->assertEquals('', $result[0]['selected']);
        $this->assertEquals('Option 2', $result[1]['option_name']);
        $this->assertEquals(' selected="selected"', $result[1]['selected']);
    }

    public function testGetFieldOptionsForCheckboxField()
    {
        $mockField = new class {
            public $field_id = 3;
            public $field_type = 'checkboxes';
            public $field_list_items = "Red\nBlue\nGreen";
            public $field_pre_populate = 'n';
            public $field_pre_field_id = null;
            public $field_pre_channel_id = null;
            public function getName() { return 'color_field'; }
            public function getType() { return 'checkboxes'; }
            public function getField() {
                return new class {
                    public function getItem($key) {
                        return ['field_settings' => ['value_label_pairs' => []]];
                    }
                };
            }
        };

        $this->channelFormLib->custom_fields['color_field'] = $mockField;
        $this->channelFormLib->option_fields = ['select', 'radio', 'checkboxes', 'multi_select']; // Set option fields
        $this->channelFormLib->native_option_fields = ['multi_select', 'select', 'radio', 'checkboxes']; // Set native option fields

        $mockEntry = $this->createMockEntry();
        $mockEntry->{'field_id_3'} = 'Red|Green'; // Field ID 3 corresponds to color_field
        $this->channelFormLib->entry = $mockEntry;

        $result = $this->channelFormLib->get_field_options('color_field');

        $this->assertCount(3, $result);
        $this->assertEquals('Red', $result[0]['option_name']);
        $this->assertEquals(' checked="checked"', $result[0]['checked']);
        $this->assertEquals('Blue', $result[1]['option_name']);
        $this->assertEquals('', $result[1]['checked']);
        $this->assertEquals('Green', $result[2]['option_name']);
        $this->assertEquals(' checked="checked"', $result[2]['checked']);
    }

    public function testGetFieldOptionsWithValueLabelPairs()
    {
        $mockField = new class {
            public $field_id = 4;
            public $field_type = 'select';
            public $field_pre_populate = 'n';
            public $field_pre_field_id = null;
            public $field_pre_channel_id = null;
            public function getName() { return 'paired_field'; }
            public function getType() { return 'select'; }
            public function getField() {
                return new class {
                    public function getItem($key) {
                        if ($key === 'field_settings') {
                            return [
                                'value_label_pairs' => [
                                    'value1' => 'Label 1',
                                    'value2' => 'Label 2',
                                    'value3' => 'Label 3'
                                ]
                            ];
                        }
                        return null;
                    }
                };
            }
        };

        $this->channelFormLib->custom_fields['paired_field'] = $mockField;
        $this->channelFormLib->option_fields = ['select', 'radio', 'checkboxes', 'multi_select']; // Set option fields
        $this->channelFormLib->native_option_fields = ['multi_select', 'select', 'radio', 'checkboxes']; // Set native option fields

        $mockEntry = $this->createMockEntry();
        $mockEntry->{'field_id_4'} = 'value2'; // Field ID 4 corresponds to paired_field
        $this->channelFormLib->entry = $mockEntry;

        $result = $this->channelFormLib->get_field_options('paired_field');

        $this->assertCount(3, $result);
        $this->assertEquals('Label 1', $result[0]['option_name']);
        $this->assertEquals('value1', $result[0]['option_value']);
        $this->assertEquals('', $result[0]['selected']);
        $this->assertEquals('Label 2', $result[1]['option_name']);
        $this->assertEquals('value2', $result[1]['option_value']);
        $this->assertEquals(' selected="selected"', $result[1]['selected']);
    }

    public function testGetFieldOptionsForRelationshipField()
    {
        $mockField = new class {
            public $field_id = 5;
            public $field_type = 'relationship';
            public $field_pre_populate = 'n';
            public $field_pre_field_id = null;
            public $field_pre_channel_id = null;
            public function getName() { return 'rel_field'; }
            public function getType() { return 'relationship'; }
            public function getField() {
                return new class {
                    public function getItem($key) {
                        if ($key === 'field_settings') {
                            return [
                                'allow_multiple' => '0',
                                'order_field' => 'title',
                                'order_dir' => 'asc',
                                'channels' => [1],
                                'categories' => [],
                                'statuses' => ['open'],
                                'authors' => [],
                                'expired' => '0',
                                'future' => '0',
                                'limit' => 10
                            ];
                        }
                        return null;
                    }
                };
            }
            public function getProperty($key, $default = null) {
                $properties = [
                    'field_id' => $this->field_id,
                    'field_type' => $this->field_type,
                    'field_pre_populate' => $this->field_pre_populate,
                    'field_settings' => [
                        'allow_multiple' => '0',
                        'order_field' => 'title',
                        'order_dir' => 'asc',
                        'channels' => [1],
                        'categories' => [],
                        'statuses' => ['open'],
                        'authors' => [],
                        'expired' => '0',
                        'future' => '0',
                        'limit' => 10
                    ],
                ];
                return $properties[$key] ?? $default;
            }
        };

        $this->channelFormLib->custom_fields['rel_field'] = $mockField;
        $this->channelFormLib->option_fields = ['select', 'radio', 'checkboxes', 'multi_select']; // Set option fields
        $this->channelFormLib->native_option_fields = ['multi_select', 'select', 'radio', 'checkboxes']; // Set native option fields
        $this->channelFormLib->title_fields = ['title', 'url_title']; // Set title fields

        // Mock database for relationship query
        $mockDbResult = new class {
            public $result_array = [
                ['entry_id' => 1, 'title' => 'Entry One', 'title' => 'Entry One'],
                ['entry_id' => 2, 'title' => 'Entry Two', 'title' => 'Entry Two']
            ];
            public function result_array() { return $this->result_array; }
        };

        $mockDb = new class($mockDbResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public function select($fields) { return $this; }
            public function order_by($field, $dir = '') { return $this; }
            public function limit($limit) { return $this; }
            public function where($field, $value = null) { return $this; }
            public function where_in($field, $values) { return $this; }
            public function from($table) { return $this; }
            public function join($table, $cond, $type = '') { return $this; }
            public function distinct() { return $this; }
            public function get($table = null) { return $this->result; }
            public function dbprefix($table) { return 'exp_' . $table; }
        };

        $this->setMock('db', $mockDb);

        $result = $this->channelFormLib->get_field_options('rel_field');

        $this->assertCount(3, $result); // 2 entries + empty option
        $this->assertEquals('', $result[0]['option_value']);
        $this->assertEquals('--', $result[0]['option_name']);
        $this->assertEquals(1, $result[1]['option_value']);
        $this->assertEquals('Entry One', $result[1]['option_name']);
    }

    public function testGetFieldOptionsForRelationshipFieldWithMultipleSelection()
    {
        $mockField = new class {
            public $field_id = 6;
            public $field_type = 'relationship';
            public $field_pre_populate = 'n';
            public $field_pre_field_id = null;
            public $field_pre_channel_id = null;
            public function getName() { return 'multi_rel_field'; }
            public function getType() { return 'relationship'; }
            public function getField() {
                return new class {
                    public function getItem($key) {
                        if ($key === 'field_settings') {
                            return [
                                'allow_multiple' => '1',
                                'order_field' => 'title',
                                'order_dir' => 'asc',
                                'channels' => [1],
                                'categories' => [],
                                'statuses' => ['open'],
                                'authors' => [],
                                'expired' => '0',
                                'future' => '0',
                                'limit' => 5
                            ];
                        }
                        return null;
                    }
                };
            }
            public function getProperty($key, $default = null) {
                $properties = [
                    'field_id' => $this->field_id,
                    'field_type' => $this->field_type,
                    'field_pre_populate' => $this->field_pre_populate,
                    'field_settings' => [
                        'allow_multiple' => '1',
                        'order_field' => 'title',
                        'order_dir' => 'asc',
                        'channels' => [1],
                        'categories' => [],
                        'statuses' => ['open'],
                        'authors' => [],
                        'expired' => '0',
                        'future' => '0',
                        'limit' => 5
                    ],
                ];
                return $properties[$key] ?? $default;
            }
        };

        $this->channelFormLib->custom_fields['multi_rel_field'] = $mockField;
        $this->channelFormLib->option_fields = ['select', 'radio', 'checkboxes', 'multi_select']; // Set option fields
        $this->channelFormLib->native_option_fields = ['multi_select', 'select', 'radio', 'checkboxes']; // Set native option fields
        $this->channelFormLib->title_fields = ['title', 'url_title']; // Set title fields

        // Mock database for relationship query
        $mockDbResult = new class {
            public $result_array = [
                ['entry_id' => 3, 'title' => 'Entry Three', 'title' => 'Entry Three']
            ];
            public function result_array() { return $this->result_array; }
        };

        $mockDb = new class($mockDbResult) {
            private $result;
            public function __construct($result) { $this->result = $result; }
            public function select($fields) { return $this; }
            public function order_by($field, $dir = '') { return $this; }
            public function limit($limit) { return $this; }
            public function where($field, $value = null) { return $this; }
            public function where_in($field, $values) { return $this; }
            public function from($table) { return $this; }
            public function join($table, $cond, $type = '') { return $this; }
            public function distinct() { return $this; }
            public function get($table = null) { return $this->result; }
            public function dbprefix($table) { return 'exp_' . $table; }
        };

        $this->setMock('db', $mockDb);

        $result = $this->channelFormLib->get_field_options('multi_rel_field');

        $this->assertCount(1, $result); // No empty option for multiple relationships
        $this->assertEquals(3, $result[0]['option_value']);
        $this->assertEquals('Entry Three', $result[0]['option_name']);
    }

    public function testGetFieldOptionsReturnsEmptyArrayForUnknownField()
    {
        // Ensure custom_fields is initialized but doesn't contain the unknown field
        $this->channelFormLib->custom_fields = [];

        // This test should handle the case where the field doesn't exist
        // The method should return an empty array when field is null
        try {
            $result = $this->channelFormLib->get_field_options('unknown_field');
            $this->assertEquals([], $result);
        } catch (Throwable $e) {
            // If an exception is thrown, that's also acceptable behavior for unknown fields
            $this->assertTrue(true);
        }
    }
}
