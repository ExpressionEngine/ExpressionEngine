<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibGetFieldIdTest extends ChannelFormLibTestBase
{
    public function testGetFieldIdReturnsCorrectFieldIdForValidField()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_id' ? 42 : $default;
            }

            public function getValues() {
                return ['field_id' => 42, 'field_type' => 'text'];
            }
        };

        $this->channelFormLib->custom_fields['test_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_id('test_field');
        $this->assertEquals(42, $result);
    }

    public function testGetFieldIdReturnsEmptyArrayForTitleFields()
    {
        $this->channelFormLib->custom_fields['title'] = new stdClass();
        $this->channelFormLib->title_fields = ['title', 'url_title'];

        $result = $this->channelFormLib->get_field_id('title');
        $this->assertEquals([], $result);
    }

    public function testGetFieldIdReturnsEmptyArrayForNonExistentField()
    {
        $this->channelFormLib->custom_fields = [];
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_id('nonexistent_field');
        $this->assertEquals([], $result);
    }

    public function testGetFieldIdWithMultipleFields()
    {
        $mockField1 = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_id' ? 1 : $default;
            }

            public function getValues() {
                return ['field_id' => 1, 'field_type' => 'text'];
            }
        };

        $mockField2 = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_id' ? 2 : $default;
            }

            public function getValues() {
                return ['field_id' => 2, 'field_type' => 'select'];
            }
        };

        $mockField3 = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_id' ? 3 : $default;
            }

            public function getValues() {
                return ['field_id' => 3, 'field_type' => 'textarea'];
            }
        };

        $this->channelFormLib->custom_fields = [
            'text_field' => $mockField1,
            'select_field' => $mockField2,
            'textarea_field' => $mockField3
        ];
        $this->channelFormLib->title_fields = [];

        $this->assertEquals(1, $this->channelFormLib->get_field_id('text_field'));
        $this->assertEquals(2, $this->channelFormLib->get_field_id('select_field'));
        $this->assertEquals(3, $this->channelFormLib->get_field_id('textarea_field'));
    }

    public function testGetFieldIdWithNumericFieldNames()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_id' ? 99 : $default;
            }

            public function getValues() {
                return ['field_id' => 99, 'field_type' => 'text'];
            }
        };

        $this->channelFormLib->custom_fields['123'] = $mockField;
        $this->channelFormLib->title_fields = [];

        // Test with string numeric field name
        $result = $this->channelFormLib->get_field_id('123');
        $this->assertEquals(99, $result);

        // Test with integer field name
        $result = $this->channelFormLib->get_field_id(123);
        $this->assertEquals(99, $result);
    }

    public function testGetFieldIdWithSpecialCharactersInFieldNames()
    {
        $fieldNames = [
            'field-with-dashes',
            'field_with_underscores',
            'field.with.dots',
            'field[with]brackets'
        ];

        foreach ($fieldNames as $index => $fieldName) {
            $fieldId = $index + 10;

            $mockField = new class($fieldId) {
                private $id;

                public function __construct($id) {
                    $this->id = $id;
                }

                public function getProperty($key, $default = null) {
                    return $key === 'field_id' ? $this->id : $default;
                }

                public function getValues() {
                    return ['field_id' => $this->id, 'field_type' => 'text'];
                }
            };

            $this->channelFormLib->custom_fields[$fieldName] = $mockField;
        }

        $this->channelFormLib->title_fields = [];

        $this->assertEquals(10, $this->channelFormLib->get_field_id('field-with-dashes'));
        $this->assertEquals(11, $this->channelFormLib->get_field_id('field_with_underscores'));
        $this->assertEquals(12, $this->channelFormLib->get_field_id('field.with.dots'));
        $this->assertEquals(13, $this->channelFormLib->get_field_id('field[with]brackets'));
    }

    public function testGetFieldIdWithEmptyCustomFields()
    {
        $this->channelFormLib->custom_fields = [];
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_id('any_field');
        $this->assertEquals([], $result);
    }

    public function testGetFieldIdWithNullCustomFields()
    {
        $this->channelFormLib->custom_fields = null;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_id('any_field');
        $this->assertEquals([], $result);
    }

    public function testGetFieldIdWithLargeFieldIds()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_id' ? 99999 : $default;
            }

            public function getValues() {
                return ['field_id' => 99999, 'field_type' => 'text'];
            }
        };

        $this->channelFormLib->custom_fields['large_id_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_id('large_id_field');
        $this->assertEquals(99999, $result);
    }

    public function testGetFieldIdWithZeroFieldId()
    {
        $mockField = new class {
            public function getProperty($key, $default = null) {
                return $key === 'field_id' ? 0 : $default;
            }

            public function getValues() {
                return ['field_id' => 0, 'field_type' => 'text'];
            }
        };

        $this->channelFormLib->custom_fields['zero_id_field'] = $mockField;
        $this->channelFormLib->title_fields = [];

        $result = $this->channelFormLib->get_field_id('zero_id_field');
        $this->assertEquals(0, $result);
    }
}
