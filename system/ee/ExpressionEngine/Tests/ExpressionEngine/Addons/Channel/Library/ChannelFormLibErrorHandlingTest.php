<?php

require_once __DIR__ . '/ChannelFormLibTestBase.php';

class ChannelFormLibErrorHandlingTest extends ChannelFormLibTestBase
{
    public function testAddErrorsReturnsEmptyConditionalsWhenNoErrors()
    {
        // Test with no errors
        $this->channelFormLib->errors = [];
        $this->channelFormLib->field_errors = [];
        $this->channelFormLib->title_fields = ['title', 'url_title'];

        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_add_errors');
        TestReflectionHelper::makeMethodAccessible($method);
        $result = $method->invoke($this->channelFormLib);

        $this->assertArrayHasKey('global_errors', $result);
        $this->assertArrayHasKey('field_errors', $result);
        $this->assertEquals(0, $result['global_errors:count']);
        $this->assertEquals(0, $result['field_errors:count']);
        $this->assertEquals([[]], $result['global_errors']);
        $this->assertEquals([[]], $result['field_errors']);
    }

    public function testAddErrorsHandlesGlobalErrors()
    {
        // Test with global errors
        $this->channelFormLib->errors = [
            'Entry could not be saved',
            'Validation failed'
        ];
        $this->channelFormLib->field_errors = [];
        $this->channelFormLib->title_fields = ['title', 'url_title'];

        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_add_errors');
        TestReflectionHelper::makeMethodAccessible($method);
        $result = $method->invoke($this->channelFormLib);

        $this->assertEquals(2, $result['global_errors:count']);
        $this->assertCount(2, $result['global_errors']);
        $this->assertEquals('Entry could not be saved', $result['global_errors'][0]['error']);
        $this->assertEquals('Validation failed', $result['global_errors'][1]['error']);
    }

    public function testAddErrorsHandlesFieldErrors()
    {
        // Test with field errors
        $this->channelFormLib->errors = [];
        $this->channelFormLib->field_errors = [
            'title' => 'Title is required',
            'url_title' => 'URL title is required',
            'field_id_1' => 'Custom field error'
        ];
        $this->channelFormLib->title_fields = ['title', 'url_title'];
        $this->channelFormLib->custom_field_names = [1 => 'custom_field'];

        // Mock custom fields
        $mockField = new class {
            public $field_label = 'Custom Field';
        };
        $this->channelFormLib->custom_fields = ['custom_field' => $mockField];

        // Mock language for field labels
        $this->setMock('lang', new class {
            public function line($key) {
                $labels = [
                    'title' => 'Title',
                    'url_title' => 'URL Title'
                ];
                return $labels[$key] ?? $key;
            }
        });

        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_add_errors');
        TestReflectionHelper::makeMethodAccessible($method);
        $result = $method->invoke($this->channelFormLib);

        $this->assertEquals(3, $result['field_errors:count']);
        $this->assertCount(3, $result['field_errors']);

        // Check title field error
        $this->assertEquals('title', $result['field_errors'][0]['field']);
        $this->assertEquals('Title is required', $result['field_errors'][0]['error']);

        // Check url_title field error
        $this->assertEquals('url_title', $result['field_errors'][1]['field']);
        $this->assertEquals('URL title is required', $result['field_errors'][1]['error']);

        // Check custom field error
        $this->assertEquals('Custom Field', $result['field_errors'][2]['field']);
        $this->assertEquals('Custom field error', $result['field_errors'][2]['error']);
    }

    public function testAddErrorsCreatesErrorConditionals()
    {
        // Test error conditional generation
        $this->channelFormLib->errors = [];
        $this->channelFormLib->field_errors = [
            'title' => 'Title error',
            'custom_field' => 'Custom error'
        ];
        $this->channelFormLib->title_fields = ['title', 'custom_field'];
        $this->channelFormLib->custom_field_names = [];

        // Mock custom fields
        $this->channelFormLib->custom_fields = [
            'custom_field' => (object)['field_label' => 'Custom Field']
        ];

        // Mock lang function for field labels
        $this->setMock('lang', new class {
            public function line($key) {
                return $key; // Return key as-is for testing
            }
        });

        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_add_errors');
        TestReflectionHelper::makeMethodAccessible($method);
        $result = $method->invoke($this->channelFormLib);

        // Check that error conditionals are created
        $this->assertArrayHasKey('error:title', $result);
        $this->assertArrayHasKey('error:Custom Field', $result); // Uses field label, not field name
        $this->assertEquals('Title error', $result['error:title']);
        $this->assertEquals('Custom error', $result['error:Custom Field']);
    }

    public function testAddErrorsHandlesMixedErrors()
    {
        // Test mix of global and field errors
        $this->channelFormLib->errors = [
            'Form submission failed',
            'Database connection error'
        ];
        $this->channelFormLib->field_errors = [
            'title' => 'Title is required',
            'email' => 'Invalid email format'
        ];
        $this->channelFormLib->title_fields = ['title', 'email'];
        $this->channelFormLib->custom_fields = []; // Initialize as empty array

        $reflection = new ReflectionClass($this->channelFormLib);
        $method = $reflection->getMethod('_add_errors');
        TestReflectionHelper::makeMethodAccessible($method);
        $result = $method->invoke($this->channelFormLib);

        // Check global errors
        $this->assertEquals(2, $result['global_errors:count']);
        $this->assertCount(2, $result['global_errors']);

        // Check field errors
        $this->assertEquals(2, $result['field_errors:count']);
        $this->assertCount(2, $result['field_errors']);

        // Check error conditionals
        $this->assertArrayHasKey('error:title', $result);
        $this->assertArrayHasKey('error:email', $result);
        $this->assertEquals('Title is required', $result['error:title']);
        $this->assertEquals('Invalid email format', $result['error:email']);
    }
}
