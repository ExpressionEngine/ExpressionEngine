<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibFetchCustomFieldsTest extends ChannelFormLibTestBase
{
    public function testFetchCustomFieldsPopulatesCustomFieldsArray()
    {
        $mockField1 = new class {
            public $field_id = 1;
            public $field_name = 'field_one';
            public $field_type = 'text';
        };

        $mockField2 = new class {
            public $field_id = 2;
            public $field_name = 'field_two';
            public $field_type = 'textarea';
        };

        $mockChannel = new class([$mockField1, $mockField2]) {
            public $channel_id = 1;
            public $channel_name = 'test_channel';
            private $fields;

            public function __construct($fields) {
                $this->fields = $fields;
            }

            public function getAllCustomFields() {
                return $this->fields;
            }
        };

        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->file_fields = ['file']; // Manually set required property
        $this->channelFormLib->custom_field_names = []; // Initialize as array
        $this->channelFormLib->fetch_custom_fields();

        $this->assertArrayHasKey('field_one', $this->channelFormLib->custom_fields);
        $this->assertArrayHasKey('field_two', $this->channelFormLib->custom_fields);
        $this->assertEquals(1, $this->channelFormLib->custom_fields['field_one']->field_id);
        $this->assertEquals(2, $this->channelFormLib->custom_fields['field_two']->field_id);
    }

    public function testFetchCustomFieldsPopulatesCustomFieldNamesArray()
    {
        $mockField = new class {
            public $field_id = 5;
            public $field_name = 'test_field';
            public $field_type = 'text';
        };

        $mockChannel = new class([$mockField]) {
            public $channel_id = 1;
            public $channel_name = 'test_channel';
            private $fields;

            public function __construct($fields) {
                $this->fields = $fields;
            }

            public function getAllCustomFields() {
                return $this->fields;
            }
        };

        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->custom_field_names = []; // Initialize as array
        $this->channelFormLib->file_fields = ['file']; // Initialize as array
        $this->channelFormLib->fetch_custom_fields();

        $this->assertArrayHasKey(5, $this->channelFormLib->custom_field_names);
        $this->assertEquals('test_field', $this->channelFormLib->custom_field_names[5]);
    }

    public function testFetchCustomFieldsSetsFileFlagForFileFields()
    {
        $mockField = new class {
            public $field_id = 3;
            public $field_name = 'file_field';
            public $field_type = 'file';
        };

        $mockChannel = new class([$mockField]) {
            public $channel_id = 1;
            public $channel_name = 'test_channel';
            private $fields;

            public function __construct($fields) {
                $this->fields = $fields;
            }

            public function getAllCustomFields() {
                return $this->fields;
            }
        };

        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->file_fields = ['file']; // Initialize as array
        $this->channelFormLib->file = false; // Initialize file flag
        $this->channelFormLib->fetch_custom_fields();

        $this->assertTrue($this->channelFormLib->file);
    }

    public function testFetchCustomFieldsDoesNotSetFileFlagForNonFileFields()
    {
        $mockField = new class {
            public $field_id = 4;
            public $field_name = 'text_field';
            public $field_type = 'text';
        };

        $mockChannel = new class([$mockField]) {
            public $channel_id = 1;
            public $channel_name = 'test_channel';
            private $fields;

            public function __construct($fields) {
                $this->fields = $fields;
            }

            public function getAllCustomFields() {
                return $this->fields;
            }
        };

        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->file_fields = ['file']; // Initialize as array
        $this->channelFormLib->file = false; // Initialize file flag
        $this->channelFormLib->fetch_custom_fields();

        $this->assertFalse($this->channelFormLib->file);
    }

    public function testFetchCustomFieldsHandlesEmptyCustomFields()
    {
        $mockChannel = new class([]) {
            public $channel_id = 1;
            public $channel_name = 'test_channel';
            private $fields;

            public function __construct($fields) {
                $this->fields = $fields;
            }

            public function getAllCustomFields() {
                return $this->fields;
            }
        };

        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->file_fields = ['file']; // Initialize as array
        $this->channelFormLib->custom_field_names = []; // Initialize as array
        $this->channelFormLib->custom_fields = []; // Initialize as array
        $this->channelFormLib->file = false; // Initialize file flag
        $this->channelFormLib->fetch_custom_fields();

        $this->assertEquals([], $this->channelFormLib->custom_fields);
        $this->assertEquals([], $this->channelFormLib->custom_field_names);
        $this->assertFalse($this->channelFormLib->file);
    }

    public function testFetchCustomFieldsHandlesMixedFieldTypes()
    {
        $mockFields = [
            new class {
                public $field_id = 1;
                public $field_name = 'text_field';
                public $field_type = 'text';
            },
            new class {
                public $field_id = 2;
                public $field_name = 'file_field';
                public $field_type = 'file';
            },
            new class {
                public $field_id = 3;
                public $field_name = 'select_field';
                public $field_type = 'select';
            }
        ];

        $mockChannel = new class($mockFields) {
            public $channel_id = 1;
            public $channel_name = 'test_channel';
            private $fields;

            public function __construct($fields) {
                $this->fields = $fields;
            }

            public function getAllCustomFields() {
                return $this->fields;
            }
        };

        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->file_fields = ['file']; // Initialize as array
        $this->channelFormLib->custom_field_names = []; // Initialize as array
        $this->channelFormLib->file = false; // Initialize file flag
        $this->channelFormLib->fetch_custom_fields();

        $this->assertCount(3, $this->channelFormLib->custom_fields);
        $this->assertArrayHasKey('text_field', $this->channelFormLib->custom_fields);
        $this->assertArrayHasKey('file_field', $this->channelFormLib->custom_fields);
        $this->assertArrayHasKey('select_field', $this->channelFormLib->custom_fields);

        // Check that file flag is set because we have a file field
        $this->assertTrue($this->channelFormLib->file);

        // Check custom_field_names mapping
        $this->assertEquals('text_field', $this->channelFormLib->custom_field_names[1]);
        $this->assertEquals('file_field', $this->channelFormLib->custom_field_names[2]);
        $this->assertEquals('select_field', $this->channelFormLib->custom_field_names[3]);
    }

    public function testFetchCustomFieldsOverwritesExistingData()
    {
        // Set up existing data
        $this->channelFormLib->custom_fields = ['old_field' => 'old_value'];
        $this->channelFormLib->custom_field_names = [999 => 'old_name'];
        $this->channelFormLib->file = true;

        $mockField = new class {
            public $field_id = 1;
            public $field_name = 'new_field';
            public $field_type = 'text';
        };

        $mockChannel = new class([$mockField]) {
            public $channel_id = 1;
            public $channel_name = 'test_channel';
            private $fields;

            public function __construct($fields) {
                $this->fields = $fields;
            }

            public function getAllCustomFields() {
                return $this->fields;
            }
        };

        $this->channelFormLib->channel = $mockChannel;
        $this->channelFormLib->file_fields = ['file']; // Initialize as array
        $this->channelFormLib->fetch_custom_fields();

        // Method adds to existing arrays, doesn't replace them
        $this->assertArrayHasKey('old_field', $this->channelFormLib->custom_fields); // Old data remains
        $this->assertArrayHasKey('new_field', $this->channelFormLib->custom_fields); // New data is added
        $this->assertArrayHasKey(999, $this->channelFormLib->custom_field_names); // Old data remains
        $this->assertArrayHasKey(1, $this->channelFormLib->custom_field_names); // New data is added
        $this->assertTrue($this->channelFormLib->file); // File flag remains true from old data
    }
}
