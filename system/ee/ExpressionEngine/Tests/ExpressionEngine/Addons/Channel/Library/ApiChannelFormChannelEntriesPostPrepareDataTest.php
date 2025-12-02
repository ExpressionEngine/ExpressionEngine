<?php

require_once __DIR__ . '/ApiChannelFormChannelEntriesTestBase.php';

/**
 * Test class for Api_channel_form_channel_entries::_post_prepare_data() method
 */
class ApiChannelFormChannelEntriesPostPrepareDataTest extends ApiChannelFormChannelEntriesTestBase
{
    /**
     * Test _post_prepare_data filters fields even when not in edit mode
     */
    public function testPostPrepareDataFiltersFieldsWhenNotInEditMode()
    {
        // Setup test data
        $data = $this->createTestData([
            'field_id_1' => 'value1',      // Valid - exists in DB
            'field_id_2' => 'value2',      // Valid - exists in DB
            'field_id_999' => 'invalid',   // Invalid - doesn't exist in DB
            'field_ft_1' => 'text',        // Should be removed (field_ft pattern)
            'field_dt_1' => 'timestamp',   // Should be removed (field_dt pattern)
            'regular_field' => 'keep'      // Should be kept (not field pattern)
        ]);

        // Ensure we're NOT in edit mode
        $this->setEditMode(false);

        // Mock database fields - only field_id_1 and field_id_2 exist
        $this->mockDatabaseListFields(['field_id_1', 'field_id_2']);

        // Call the method
        $this->apiChannelFormChannelEntries->_post_prepare_data($data);

        // Verify valid fields are kept
        $this->assertArrayHasKey('field_id_1', $data);
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertEquals('value1', $data['field_id_1']);
        $this->assertEquals('value2', $data['field_id_2']);

        // Verify invalid field patterns are removed
        $this->assertArrayNotHasKey('field_id_999', $data);
        $this->assertArrayNotHasKey('field_ft_1', $data);
        $this->assertArrayNotHasKey('field_dt_1', $data);

        // Verify non-field data is preserved
        $this->assertArrayHasKey('regular_field', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals('keep', $data['regular_field']);
        $this->assertEquals('Test Entry', $data['title']);
    }

    /**
     * Test _post_prepare_data filters out invalid field patterns in non-edit mode
     */
    public function testPostPrepareDataFiltersInvalidFieldPatternsInNonEditMode()
    {
        // Setup test data with various field patterns
        $data = $this->createTestData([
            'field_id_1' => 'value1',      // Valid - exists in DB
            'field_id_2' => 'value2',      // Valid - exists in DB
            'field_id_999' => 'invalid',   // Invalid - doesn't exist in DB
            'field_ft_1' => 'text',        // Should be removed (field_ft pattern)
            'field_dt_1' => 'timestamp',   // Should be removed (field_dt pattern)
            'regular_field' => 'keep',     // Should be kept (not field pattern)
            'title' => 'Test Entry'        // Should be kept (not field pattern)
        ]);

        // Ensure we're NOT in edit mode
        $this->setEditMode(false);

        // Mock database fields - only field_id_1 and field_id_2 exist
        $this->mockDatabaseListFields(['field_id_1', 'field_id_2']);

        // Call the method
        $this->apiChannelFormChannelEntries->_post_prepare_data($data);

        // Verify valid fields are kept
        $this->assertArrayHasKey('field_id_1', $data);
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertEquals('value1', $data['field_id_1']);
        $this->assertEquals('value2', $data['field_id_2']);

        // Verify invalid field patterns are removed
        $this->assertArrayNotHasKey('field_id_999', $data);
        $this->assertArrayNotHasKey('field_ft_1', $data);
        $this->assertArrayNotHasKey('field_dt_1', $data);

        // Verify non-field data is preserved
        $this->assertArrayHasKey('regular_field', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals('keep', $data['regular_field']);
        $this->assertEquals('Test Entry', $data['title']);
    }

    /**
     * Test _post_prepare_data in edit mode with checkbox field preservation
     */
    public function testPostPrepareDataInEditModeWithCheckboxPreservation()
    {
        // Setup test data
        $data = $this->createTestData([
            'field_id_1' => 'text_value',
            'field_id_2' => '',  // Empty checkbox field - should be preserved
            'checkbox_fields' => 'test_field_2'  // Indicates field_id_2 is a checkbox
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', true),
            $this->createCustomField(2, 'test_field_2', 'checkboxes', true)  // isset = true
        ];
        $this->setupCustomFields($customFields);

        // Mock database fields
        $this->mockDatabaseListFields(['field_id_1', 'field_id_2']);

        // Call the method
        $this->apiChannelFormChannelEntries->_post_prepare_data($data);

        // Verify checkbox field is preserved with blank value
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertEquals('', $data['field_id_2']);

        // Verify other fields are preserved
        $this->assertArrayHasKey('field_id_1', $data);
        $this->assertEquals('text_value', $data['field_id_1']);
    }

    /**
     * Test _post_prepare_data restores original values for isset=false fields
     */
    public function testPostPrepareDataRestoresOriginalValuesForIssetFalseFields()
    {
        // Setup test data - missing field_id_2 (isset=false)
        $data = $this->createTestData([
            'field_id_1' => 'value1'
            // field_id_2 is missing (isset=false)
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', true),   // isset = true
            $this->createCustomField(2, 'test_field_2', 'text', false)   // isset = false
        ];
        $this->setupCustomFields($customFields);

        // Mock channel_form entry method to return original value
        $this->channel_form->setEntryMock('test_field_2', 'original_value_2');

        // Mock database fields
        $this->mockDatabaseListFields(['field_id_1', 'field_id_2']);

        // Call the method
        $this->apiChannelFormChannelEntries->_post_prepare_data($data);

        // Verify field_id_1 is preserved
        $this->assertArrayHasKey('field_id_1', $data);
        $this->assertEquals('value1', $data['field_id_1']);

        // Verify field_id_2 is restored with original value
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertEquals('original_value_2', $data['field_id_2']);
    }

    /**
     * Test _post_prepare_data handles entry method returning false
     */
    public function testPostPrepareDataHandlesEntryMethodReturningFalse()
    {
        // Setup test data - missing field_id_2 (isset=false)
        $data = $this->createTestData([
            'field_id_1' => 'value1'
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', true),
            $this->createCustomField(2, 'test_field_2', 'text', false)  // isset = false
        ];
        $this->setupCustomFields($customFields);

        // Mock channel_form entry method to return false (no original value)
        // By default, the mock returns false for any field not explicitly mocked

        // Mock database fields
        $this->mockDatabaseListFields(['field_id_1', 'field_id_2']);

        // Call the method
        $this->apiChannelFormChannelEntries->_post_prepare_data($data);

        // Verify field_id_1 is preserved
        $this->assertArrayHasKey('field_id_1', $data);
        $this->assertEquals('value1', $data['field_id_1']);

        // Verify field_id_2 is set to empty string when no original value
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertEquals('', $data['field_id_2']);
    }

    /**
     * Test _post_prepare_data with complex checkbox fields scenario
     */
    public function testPostPrepareDataWithComplexCheckboxScenario()
    {
        // Setup test data with multiple checkbox fields
        $data = $this->createTestData([
            'field_id_1' => 'text_value',
            'field_id_2' => '',  // Empty checkbox - should be preserved
            'field_id_3' => 'existing_value',  // Checkbox with value - should be kept
            'checkbox_fields' => 'test_field_2|test_field_3'  // Both are checkboxes
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', true),
            $this->createCustomField(2, 'test_field_2', 'checkboxes', true),  // isset = true
            $this->createCustomField(3, 'test_field_3', 'checkboxes', true)   // isset = true
        ];
        $this->setupCustomFields($customFields);

        // Mock database fields
        $this->mockDatabaseListFields(['field_id_1', 'field_id_2', 'field_id_3']);

        // Call the method
        $this->apiChannelFormChannelEntries->_post_prepare_data($data);

        // Verify all fields are preserved
        $this->assertArrayHasKey('field_id_1', $data);
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertArrayHasKey('field_id_3', $data);

        $this->assertEquals('text_value', $data['field_id_1']);
        $this->assertEquals('', $data['field_id_2']);  // Empty checkbox preserved
        $this->assertEquals('existing_value', $data['field_id_3']);  // Existing value kept
    }

    /**
     * Test _post_prepare_data with mixed field types and isset values
     */
    public function testPostPrepareDataWithMixedFieldTypesAndIssetValues()
    {
        // Setup test data
        $data = $this->createTestData([
            'field_id_1' => 'text_value',     // isset=true, keep
            'field_id_2' => '',              // isset=true checkbox, preserve
            'field_id_3' => 'select_value',  // isset=true, keep
            'checkbox_fields' => 'test_field_2'
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields with mixed isset values
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', true),       // isset = true
            $this->createCustomField(2, 'test_field_2', 'checkboxes', true), // isset = true
            $this->createCustomField(3, 'test_field_3', 'select', true),     // isset = true
            $this->createCustomField(4, 'test_field_4', 'textarea', false)   // isset = false
        ];
        $this->setupCustomFields($customFields);

        // Mock channel_form entry method for field_4
        $this->channel_form->setEntryMock('test_field_4', 'restored_textarea_value');

        // Mock database fields
        $this->mockDatabaseListFields(['field_id_1', 'field_id_2', 'field_id_3', 'field_id_4']);

        // Call the method
        $this->apiChannelFormChannelEntries->_post_prepare_data($data);

        // Verify isset=true fields are preserved
        $this->assertArrayHasKey('field_id_1', $data);
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertArrayHasKey('field_id_3', $data);
        $this->assertEquals('text_value', $data['field_id_1']);
        $this->assertEquals('', $data['field_id_2']);
        $this->assertEquals('select_value', $data['field_id_3']);

        // Verify isset=false field is restored
        $this->assertArrayHasKey('field_id_4', $data);
        $this->assertEquals('restored_textarea_value', $data['field_id_4']);
    }

    /**
     * Test _post_prepare_data with empty checkbox_fields
     */
    public function testPostPrepareDataWithEmptyCheckboxFields()
    {
        // Setup test data
        $data = $this->createTestData([
            'field_id_1' => 'value1',
            'field_id_2' => 'value2',
            'checkbox_fields' => ''  // Empty checkbox fields
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', true),
            $this->createCustomField(2, 'test_field_2', 'checkboxes', true)
        ];
        $this->setupCustomFields($customFields);

        // Mock database fields
        $this->mockDatabaseListFields(['field_id_1', 'field_id_2']);

        // Call the method
        $this->apiChannelFormChannelEntries->_post_prepare_data($data);

        // Verify both fields are preserved (no special checkbox handling)
        $this->assertArrayHasKey('field_id_1', $data);
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertEquals('value1', $data['field_id_1']);
        $this->assertEquals('value2', $data['field_id_2']);
    }

    /**
     * Test _post_prepare_data with missing checkbox_fields key
     */
    public function testPostPrepareDataWithMissingCheckboxFieldsKey()
    {
        // Setup test data without checkbox_fields key
        $data = $this->createTestData([
            'field_id_1' => 'value1',
            'field_id_2' => 'value2'
            // No checkbox_fields key
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', true),
            $this->createCustomField(2, 'test_field_2', 'checkboxes', true)
        ];
        $this->setupCustomFields($customFields);

        // Mock database fields
        $this->mockDatabaseListFields(['field_id_1', 'field_id_2']);

        // Call the method
        $this->apiChannelFormChannelEntries->_post_prepare_data($data);

        // Verify both fields are preserved
        $this->assertArrayHasKey('field_id_1', $data);
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertEquals('value1', $data['field_id_1']);
        $this->assertEquals('value2', $data['field_id_2']);
    }

    /**
     * Test _post_prepare_data preserves data integrity
     */
    public function testPostPrepareDataPreservesDataIntegrity()
    {
        // Setup comprehensive test data
        $data = $this->createTestData([
            'field_id_1' => 'value1',
            'field_id_2' => 'value2',
            'field_id_999' => 'invalid',  // Should be removed
            'field_ft_1' => 'text',       // Should be removed
            'regular_field' => 'keep',
            'checkbox_fields' => 'test_field_2'
        ]);

        // Set edit mode
        $this->setEditMode(true, 1);

        // Set up custom fields
        $customFields = [
            $this->createCustomField(1, 'test_field_1', 'text', true),
            $this->createCustomField(2, 'test_field_2', 'checkboxes', true)
        ];
        $this->setupCustomFields($customFields);

        // Mock database fields
        $this->mockDatabaseListFields(['field_id_1', 'field_id_2']);

        // Call the method
        $this->apiChannelFormChannelEntries->_post_prepare_data($data);

        // Verify valid fields are kept
        $this->assertArrayHasKey('field_id_1', $data);
        $this->assertArrayHasKey('field_id_2', $data);
        $this->assertEquals('value1', $data['field_id_1']);
        $this->assertEquals('value2', $data['field_id_2']);

        // Verify invalid fields are removed
        $this->assertArrayNotHasKey('field_id_999', $data);
        $this->assertArrayNotHasKey('field_ft_1', $data);

        // Verify non-field data is preserved
        $this->assertArrayHasKey('regular_field', $data);
        $this->assertArrayHasKey('checkbox_fields', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals('keep', $data['regular_field']);
        $this->assertEquals('test_field_2', $data['checkbox_fields']);
        $this->assertEquals('Test Entry', $data['title']);
    }
}
