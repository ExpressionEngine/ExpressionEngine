<?php

require_once __DIR__ . '/ApiChannelFormChannelEntriesTestBase.php';

/**
 * Test class for Api_channel_form_channel_entries::_pre_prepare_data() method
 */
class ApiChannelFormChannelEntriesPrePrepareDataTest extends ApiChannelFormChannelEntriesTestBase
{
    /**
     * Test _pre_prepare_data returns early when not in edit mode
     */
    public function testPrePrepareDataReturnsEarlyWhenNotInEditMode()
    {
        // Setup test data with some field values
        $data = $this->createTestData([
            'field_id_1' => 'value1',
            'field_id_2' => 'value2',
            'field_id_3' => 'value3'
        ]);

        // Ensure we're NOT in edit mode
        $this->setEditMode(false);

        // Set up custom fields (should be ignored since not in edit mode)
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', false), // isset = false
            $this->createCustomField(2, 'test_field_2', 'text', true),  // isset = true
            $this->createCustomField(3, 'test_field_3', 'text', false)  // isset = false
        ];
        $this->setupCustomFields($customFields);

        // Store original data for comparison
        $originalData = $data;

        // Call the method
        $this->apiChannelFormChannelEntries->_pre_prepare_data($data);

        // Verify data is unchanged (early return)
        $this->assertEquals($originalData, $data);
        $this->assertEquals('value1', $data['field_id_1']);
        $this->assertEquals('value2', $data['field_id_2']);
        $this->assertEquals('value3', $data['field_id_3']);
    }

    /**
     * Test _pre_prepare_data removes fields with isset=false in edit mode
     */
    public function testPrePrepareDataRemovesFieldsWithIssetFalseInEditMode()
    {
        // Setup test data
        $data = $this->createTestData([
            'field_id_1' => 'value1',  // isset = false, should be removed
            'field_id_2' => 'value2',  // isset = true, should be kept
            'field_id_3' => 'value3',  // isset = false, should be removed
            'field_id_4' => 'value4'   // isset = true, should be kept
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields with mixed isset values
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', false), // isset = false
            $this->createCustomField(2, 'test_field_2', 'text', true),  // isset = true
            $this->createCustomField(3, 'test_field_3', 'text', false), // isset = false
            $this->createCustomField(4, 'test_field_4', 'text', true)   // isset = true
        ];
        $this->setupCustomFields($customFields);

        // Call the method
        $this->apiChannelFormChannelEntries->_pre_prepare_data($data);

        // Verify fields with isset=false were removed
        $this->assertArrayNotHasKey('field_id_1', $data);
        $this->assertArrayNotHasKey('field_id_3', $data);

        // Verify fields with isset=true were kept
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertArrayHasKey('field_id_4', $data);
        $this->assertEquals('value2', $data['field_id_2']);
        $this->assertEquals('value4', $data['field_id_4']);
    }

    /**
     * Test _pre_prepare_data with all fields having isset=true
     */
    public function testPrePrepareDataWithAllFieldsIssetTrue()
    {
        // Setup test data
        $data = $this->createTestData([
            'field_id_1' => 'value1',
            'field_id_2' => 'value2',
            'field_id_3' => 'value3'
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields all with isset=true
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', true),
            $this->createCustomField(2, 'test_field_2', 'text', true),
            $this->createCustomField(3, 'test_field_3', 'text', true)
        ];
        $this->setupCustomFields($customFields);

        // Store original data
        $originalData = $data;

        // Call the method
        $this->apiChannelFormChannelEntries->_pre_prepare_data($data);

        // Verify all fields are preserved
        $this->assertEquals($originalData, $data);
        $this->assertEquals('value1', $data['field_id_1']);
        $this->assertEquals('value2', $data['field_id_2']);
        $this->assertEquals('value3', $data['field_id_3']);
    }

    /**
     * Test _pre_prepare_data with all fields having isset=false
     */
    public function testPrePrepareDataWithAllFieldsIssetFalse()
    {
        // Setup test data
        $data = $this->createTestData([
            'field_id_1' => 'value1',
            'field_id_2' => 'value2',
            'field_id_3' => 'value3'
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields all with isset=false
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', false),
            $this->createCustomField(2, 'test_field_2', 'text', false),
            $this->createCustomField(3, 'test_field_3', 'text', false)
        ];
        $this->setupCustomFields($customFields);

        // Call the method
        $this->apiChannelFormChannelEntries->_pre_prepare_data($data);

        // Verify all fields were removed
        $this->assertArrayNotHasKey('field_id_1', $data);
        $this->assertArrayNotHasKey('field_id_2', $data);
        $this->assertArrayNotHasKey('field_id_3', $data);

        // Verify other data is preserved
        $this->assertEquals('Test Entry', $data['title']);
        $this->assertEquals('test-entry', $data['url_title']);
        $this->assertArrayHasKey('entry_date', $data);
        $this->assertArrayHasKey('channel_id', $data);
    }

    /**
     * Test _pre_prepare_data with empty custom fields array
     */
    public function testPrePrepareDataWithEmptyCustomFields()
    {
        // Setup test data
        $data = $this->createTestData([
            'field_id_1' => 'value1',
            'field_id_2' => 'value2'
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up empty custom fields array
        $this->setupCustomFields([]);

        // Store original data
        $originalData = $data;

        // Call the method
        $this->apiChannelFormChannelEntries->_pre_prepare_data($data);

        // Verify data is unchanged (no fields to process)
        $this->assertEquals($originalData, $data);
    }

    /**
     * Test _pre_prepare_data with fields not present in data array
     */
    public function testPrePrepareDataWithMissingFields()
    {
        // Setup test data with only some fields
        $data = $this->createTestData([
            'field_id_1' => 'value1'
            // field_id_2 and field_id_3 are not present
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', false), // isset = false
            $this->createCustomField(2, 'test_field_2', 'text', false), // isset = false
            $this->createCustomField(3, 'test_field_3', 'text', true)   // isset = true
        ];
        $this->setupCustomFields($customFields);

        // Call the method
        $this->apiChannelFormChannelEntries->_pre_prepare_data($data);

        // Verify only field_id_1 was removed (since isset=false)
        $this->assertArrayNotHasKey('field_id_1', $data);

        // Verify field_id_2 and field_id_3 were not affected (since they weren't present)
        $this->assertArrayNotHasKey('field_id_2', $data);
        $this->assertArrayNotHasKey('field_id_3', $data);
    }

    /**
     * Test _pre_prepare_data preserves non-field data
     */
    public function testPrePrepareDataPreservesNonFieldData()
    {
        // Setup test data with mixed field and non-field data
        $data = $this->createTestData([
            'field_id_1' => 'field_value1',
            'field_id_2' => 'field_value2',
            'regular_field' => 'regular_value',
            'another_regular' => 'another_value',
            'checkbox_fields' => 'field1|field2'
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', false), // isset = false
            $this->createCustomField(2, 'test_field_2', 'text', true)   // isset = true
        ];
        $this->setupCustomFields($customFields);

        // Call the method
        $this->apiChannelFormChannelEntries->_pre_prepare_data($data);

        // Verify field_id_1 was removed
        $this->assertArrayNotHasKey('field_id_1', $data);

        // Verify field_id_2 was kept
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertEquals('field_value2', $data['field_id_2']);

        // Verify non-field data is preserved
        $this->assertEquals('regular_value', $data['regular_field']);
        $this->assertEquals('another_value', $data['another_regular']);
        $this->assertEquals('field1|field2', $data['checkbox_fields']);
        $this->assertEquals('Test Entry', $data['title']);
    }

    /**
     * Test _pre_prepare_data with different field types
     */
    public function testPrePrepareDataWithDifferentFieldTypes()
    {
        // Setup test data
        $data = $this->createTestData([
            'field_id_1' => 'text_value',      // text field, isset=false
            'field_id_2' => 'textarea_value',  // textarea field, isset=true
            'field_id_3' => 'select_value',    // select field, isset=false
            'field_id_4' => 'checkbox_value'   // checkbox field, isset=true
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields with different types
        $customFields = [
            $this->createCustomField(1, 'text_field', 'text', false),
            $this->createCustomField(2, 'textarea_field', 'textarea', true),
            $this->createCustomField(3, 'select_field', 'select', false),
            $this->createCustomField(4, 'checkbox_field', 'checkboxes', true)
        ];
        $this->setupCustomFields($customFields);

        // Call the method
        $this->apiChannelFormChannelEntries->_pre_prepare_data($data);

        // Verify text and select fields were removed (isset=false)
        $this->assertArrayNotHasKey('field_id_1', $data);
        $this->assertArrayNotHasKey('field_id_3', $data);

        // Verify textarea and checkbox fields were kept (isset=true)
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertArrayHasKey('field_id_4', $data);
        $this->assertEquals('textarea_value', $data['field_id_2']);
        $this->assertEquals('checkbox_value', $data['field_id_4']);
    }
}
