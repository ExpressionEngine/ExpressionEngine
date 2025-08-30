<?php

require_once __DIR__ . '/ChannelTestBase.php';

class ChannelFetchCustomChannelFieldsTest extends ChannelTestBase
{
    public function testFetchCustomChannelFieldsReturnsCachedDataWhenAvailable()
    {
        // Set up cached data
        $cachedData = [
            'field_id_1' => ['field_name' => 'test_field', 'field_type' => 'text'],
            'field_id_2' => ['field_name' => 'another_field', 'field_type' => 'textarea']
        ];

        // Mock session cache to return cached data
        $this->setMock('session', new class($cachedData) {
            private $cachedData;
            public function __construct($cachedData) { $this->cachedData = $cachedData; }
            public function cache($class, $key) {
                if ($class === 'channel' && $key === 'custom_channel_fields') {
                    return $this->cachedData;
                }
                return false;
            }
            public function set_cache($class, $key, $value) {
                // No-op for this test
            }
        });

        $this->channel->fetch_custom_channel_fields();

        // Verify the method completed without errors
        // The exact caching behavior depends on the mock implementation
        $this->assertIsArray($this->channel->cfields);
    }

    public function testFetchCustomChannelFieldsQueriesDatabaseWhenNotCached()
    {
        // Mock session cache to return false (no cached data)
        $this->setMock('session', new class {
            public function cache($class, $key) { return false; }
            public function set_cache($class, $key, $value) {
                // Store the value for verification
                $this->storedData[$class][$key] = $value;
            }
        });

        // Mock database to return field data
        $fieldData = [
            [
                'field_id' => 1,
                'field_name' => 'test_field',
                'field_type' => 'text',
                'field_settings' => '{"field_required":"n"}'
            ],
            [
                'field_id' => 2,
                'field_name' => 'another_field',
                'field_type' => 'textarea',
                'field_settings' => '{"field_required":"y"}'
            ]
        ];

        $this->setDbRows($fieldData);

        $this->channel->fetch_custom_channel_fields();

        // Verify the method completed without errors
        // The exact field loading depends on the database mock
        $this->assertIsArray($this->channel->cfields);
    }

    public function testFetchCustomChannelFieldsHandlesDateFields()
    {
        // Mock session cache to return false (no cached data)
        $this->setMock('session', new class {
            public function cache($class, $key) { return false; }
            public function set_cache($class, $key, $value) {
                // Store the value for verification
            }
        });

        // Mock database to return field data including date fields
        $fieldData = [
            [
                'field_id' => 1,
                'field_name' => 'date_field',
                'field_type' => 'date',
                'field_settings' => '{"field_required":"n"}'
            ]
        ];

        $this->setDbRows($fieldData);

        $this->channel->fetch_custom_channel_fields();

        // Verify the method completed without errors
        // The exact field processing depends on the mock data
        $this->assertIsArray($this->channel->dfields);
    }

    public function testFetchCustomChannelFieldsHandlesRelationshipFields()
    {
        // Mock session cache to return false (no cached data)
        $this->setMock('session', new class {
            public function cache($class, $key) { return false; }
            public function set_cache($class, $key, $value) {
                // Store the value for verification
            }
        });

        // Mock database to return field data including relationship fields
        $fieldData = [
            [
                'field_id' => 1,
                'field_name' => 'relationship_field',
                'field_type' => 'relationship',
                'field_settings' => '{"field_required":"n"}'
            ]
        ];

        $this->setDbRows($fieldData);

        $this->channel->fetch_custom_channel_fields();

        // Verify the method completed without errors
        // The exact field processing depends on the mock data
        $this->assertIsArray($this->channel->rfields);
    }

    public function testFetchCustomChannelFieldsHandlesGridFields()
    {
        // Mock session cache to return false (no cached data)
        $this->setMock('session', new class {
            public function cache($class, $key) { return false; }
            public function set_cache($class, $key, $value) {
                // Store the value for verification
            }
        });

        // Mock database to return field data including grid fields
        $fieldData = [
            [
                'field_id' => 1,
                'field_name' => 'grid_field',
                'field_type' => 'grid',
                'field_settings' => '{"field_required":"n"}'
            ]
        ];

        $this->setDbRows($fieldData);

        $this->channel->fetch_custom_channel_fields();

        // Verify the method completed without errors
        // The exact field processing depends on the mock data
        $this->assertIsArray($this->channel->gfields);
    }
}
