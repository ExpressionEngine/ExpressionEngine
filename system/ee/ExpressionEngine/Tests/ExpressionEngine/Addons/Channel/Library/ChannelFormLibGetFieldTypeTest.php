<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibGetFieldTypeTest extends ChannelFormLibTestBase
{
    public function testGetFieldTypeReturnsCorrectTypeForValidField()
    {
        $fieldTypes = [
            'text' => 'text',
            'textarea' => 'textarea',
            'select' => 'select',
            'radio' => 'radio',
            'checkboxes' => 'checkboxes',
            'date' => 'date',
            'relationship' => 'relationship',
            'file' => 'file',
            'grid' => 'grid'
        ];

        foreach ($fieldTypes as $fieldName => $expectedType) {
            $mockField = new class($expectedType) {
                private $type;

                public function __construct($type) {
                    $this->type = $type;
                }

                public function getProperty($key, $default = null) {
                    return $key === 'field_type' ? $this->type : $default;
                }

                public function getValues() {
                    return [
                        'field_id' => 1,
                        'field_type' => $this->type,
                        'field_settings' => []
                    ];
                }
            };

            $this->channelFormLib->custom_fields[$fieldName . '_field'] = $mockField;
        }

        $this->channelFormLib->title_fields = [];

        foreach ($fieldTypes as $fieldName => $expectedType) {
            $result = $this->channelFormLib->get_field_type($fieldName . '_field');
            $this->assertEquals($expectedType, $result, "Failed for field type: $expectedType");
        }
    }

    public function testGetFieldTypeReturnsEmptyArrayForTitleFields()
    {
        $this->channelFormLib->custom_fields['title'] = new stdClass();
        $this->channelFormLib->title_fields = ['title', 'url_title'];

        $result = $this->channelFormLib->get_field_type('title');
        $this->assertEquals([], $result);
    }

    public function testGetFieldTypeReturnsEmptyArrayForNonExistentField()
    {
        $this->channelFormLib->custom_fields = [];
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_type('nonexistent_field');
        $this->assertEquals([], $result);
    }

    public function testGetFieldTypeWithCustomFieldTypes()
    {
        $customTypes = [
            'custom_addon_field',
            'third_party_field',
            'legacy_field_type'
        ];

        foreach ($customTypes as $index => $type) {
            $mockField = new class($type) {
                private $type;

                public function __construct($type) {
                    $this->type = $type;
                }

                public function getProperty($key, $default = null) {
                    return $key === 'field_type' ? $this->type : $default;
                }

                public function getValues() {
                    return [
                        'field_id' => $index + 10,
                        'field_type' => $this->type,
                        'field_settings' => []
                    ];
                }
            };

            $this->channelFormLib->custom_fields['custom_field_' . $index] = $mockField;
        }

        $this->channelFormLib->title_fields = [];

        foreach ($customTypes as $index => $expectedType) {
            $result = $this->channelFormLib->get_field_type('custom_field_' . $index);
            $this->assertEquals($expectedType, $result, "Failed for custom field type: $expectedType");
        }
    }

    public function testGetFieldTypeWithNumericFieldNames()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_type' ? 'number' : $default;
            }

            public function getValues() {
                return [
                    'field_id' => 99,
                    'field_type' => 'number',
                    'field_settings' => []
                ];
            }
        };

        $this->channelFormLib->custom_fields['42'] = $mockField;
        $this->channelFormLib->title_fields = [];

        // Test with string numeric field name
        $result = $this->channelFormLib->get_field_type('42');
        $this->assertEquals('number', $result);

        // Test with integer field name
        $result = $this->channelFormLib->get_field_type(42);
        $this->assertEquals('number', $result);
    }

    public function testGetFieldTypeWithSpecialCharactersInFieldNames()
    {
        $fieldNames = [
            'field-with-dashes',
            'field_with_underscores',
            'field.with.dots',
            'field[with]brackets'
        ];

        $fieldTypes = [
            'text',
            'select',
            'textarea',
            'date'
        ];

        foreach ($fieldNames as $index => $fieldName) {
            $type = $fieldTypes[$index % count($fieldTypes)];

            $mockField = new class($type) {
                private $type;

                public function __construct($type) {
                    $this->type = $type;
                }

                public function getProperty($key, $default = null) {
                    return $key === 'field_type' ? $this->type : $default;
                }

                public function getValues() {
                    return [
                        'field_id' => $index + 20,
                        'field_type' => $this->type,
                        'field_settings' => []
                    ];
                }
            };

            $this->channelFormLib->custom_fields[$fieldName] = $mockField;
        }

        $this->channelFormLib->title_fields = [];

        $expectedTypes = ['text', 'select', 'textarea', 'date'];
        foreach ($fieldNames as $index => $fieldName) {
            $result = $this->channelFormLib->get_field_type($fieldName);
            $this->assertEquals($expectedTypes[$index], $result, "Failed for field: $fieldName");
        }
    }

    public function testGetFieldTypeWithEmptyFieldType()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_type' ? '' : $default;
            }

            public function getValues() {
                return [
                    'field_id' => 30,
                    'field_type' => '',
                    'field_settings' => []
                ];
            }
        };

        $this->channelFormLib->custom_fields['empty_type_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_type('empty_type_field');
        $this->assertEquals('', $result);
    }

    public function testGetFieldTypeWithNullFieldType()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_type' ? null : $default;
            }

            public function getValues() {
                return [
                    'field_id' => 31,
                    'field_type' => null,
                    'field_settings' => []
                ];
            }
        };

        $this->channelFormLib->custom_fields['null_type_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_type('null_type_field');
        $this->assertNull($result);
    }

    public function testGetFieldTypeWithEmptyCustomFields()
    {
        $this->channelFormLib->custom_fields = [];
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_type('any_field');
        $this->assertEquals([], $result);
    }

    public function testGetFieldTypeWithNullCustomFields()
    {
        $this->channelFormLib->custom_fields = null;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_type('any_field');
        $this->assertEquals([], $result);
    }

    public function testGetFieldTypeWithBooleanFieldTypes()
    {
        $booleanTypes = [true, false];

        foreach ($booleanTypes as $index => $type) {
            $mockField = new class($type) {
                private $type;

                public function __construct($type) {
                    $this->type = $type;
                }

                public function getProperty($key, $default = null) {
                    return $key === 'field_type' ? $this->type : $default;
                }

                public function getValues() {
                    return [
                        'field_id' => $index + 40,
                        'field_type' => $this->type,
                        'field_settings' => []
                    ];
                }
            };

            $this->channelFormLib->custom_fields['bool_type_field_' . $index] = $mockField;
        }

        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_type('bool_type_field_0');
        $this->assertTrue($result);

        $result = $this->channelFormLib->get_field_type('bool_type_field_1');
        $this->assertFalse($result);
    }
}
