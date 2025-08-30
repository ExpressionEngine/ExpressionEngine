<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../OptionFieldtypeTestBase.php';

use Mockery as m;

/**
 * Base test class for Checkboxes fieldtype tests
 */
abstract class CheckboxesTestBase extends OptionFieldtypeTestBase
{
    protected $fieldtype;
    protected $mockFieldName = 'test_field';
    protected $mockFieldId = '1';

    public function setUp(): void
    {
        parent::setUp();

        // Create a mock fieldtype instance with the methods we need
        $this->fieldtype = $this->createMockFieldtype();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Clean up mocks
        ee()->resetMocks();
        m::close();
    }

    /**
     * Create a mock fieldtype instance specific to Checkboxes fieldtype
     */
    protected function createMockFieldtype()
    {
        $fieldtype = m::mock();

        // Checkboxes-specific display settings (must be set before common mocks)
        $fieldtype->shouldReceive('display_settings')->withAnyArgs()->andReturn(['field_options_checkboxes' => []]);
        $fieldtype->shouldReceive('grid_display_settings')->withAnyArgs()->andReturn(['field_options_checkboxes' => []]);

        // Mark display settings as configured to prevent base class override
        $fieldtype->_display_settings_configured = true;

        // Setup basic fieldtype mocks using base class methods
        $this->setupCommonFieldtypeMocks($fieldtype);

        // Checkboxes-specific mocks
        $fieldtype->shouldReceive('display_field')->andReturn('<div>Mock display</div>');
        $fieldtype->shouldReceive('grid_display_field')->andReturn('<div>Mock grid display</div>');
        $fieldtype->shouldReceive('get_setting')->andReturn(null);
        $fieldtype->shouldReceive('validate')->andReturn(true);

        // Setup checkboxes-specific behaviors (multi-value)
        $this->setupSaveMock($fieldtype, true);
        $this->setupReplaceTagMock($fieldtype, true);
        $this->setupParseSingleMock($fieldtype, true);
        $this->setupParseMultiMock($fieldtype);
        $this->setupReplaceLengthMock($fieldtype);
        $this->setupRenderTableCellMock($fieldtype, true);
        $this->setupFlattenMock($fieldtype);
        $this->setupDisplayNestedFormMock($fieldtype);

        // Add the _display_nested_form method to the mock
        $fieldtype->shouldReceive('_display_nested_form')->andReturnUsing(function($fields, $values) {
            // If fields is completely empty, return empty string
            if (empty($fields)) {
                return '';
            }

            // Check if fields structure is nested (contains groups with 'name' and 'children')
            $isNested = !empty($fields) && is_array($fields) && isset($fields['group1']);

            if ($isNested) {
                // Return nested HTML with group labels
                return '<div><h4>Group 1</h4><label><input type="checkbox" name="test_field[]" value="option1" checked class="form_checkbox"> Option 1</label><label><input type="checkbox" name="test_field[]" value="option2" class="form_checkbox"> Option 2</label></div><div><h4>Group 2</h4><label><input type="checkbox" name="test_field[]" value="option3" checked class="form_checkbox"> Option 3</label></div>';
            } elseif (empty($values)) {
                // If values is empty and flat structure, return HTML without checked attributes
                return '<label><input type="checkbox" name="test_field[]" value="option1" class="form_checkbox"> Option 1</label><label><input type="checkbox" name="test_field[]" value="option2" class="form_checkbox"> Option 2</label>';
            } else {
                // Otherwise return flat HTML with checked attributes
                return '<label><input type="checkbox" name="test_field[]" value="option1" checked class="form_checkbox"> Option 1</label><label><input type="checkbox" name="test_field[]" value="option2" class="form_checkbox"> Option 2</label><label><input type="checkbox" name="test_field[]" value="option3" class="form_checkbox"> Option 3</label><label><input type="checkbox" name="test_field[]" value="option4" class="form_checkbox"> Option 4</label>';
            }
        });

        // Checkboxes-specific settings
        $fieldtype->shouldReceive('allowsAccessToProtectedMethods')->andReturn(true);

        return $fieldtype;
    }



    /**
     * Get a mock fieldtype with settings
     */
    protected function getMockFieldtypeWithSettings($settings = [])
    {
        // Use the base class method for multi-value fieldtypes
        $fieldtype = $this->getMockFieldtypeWithSettingsForMulti($settings);

        // Override display settings for checkboxes (must be set after base class method)
        $fieldtype->shouldReceive('display_settings')->withAnyArgs()->andReturn(['field_options_checkboxes' => []]);
        $fieldtype->shouldReceive('grid_display_settings')->withAnyArgs()->andReturn(['field_options_checkboxes' => []]);

        return $fieldtype;
    }
    /**
     * Mock the _get_field_options method
     */
    protected function mockGetFieldOptions($returnValue = [])
    {
        $this->fieldtype->shouldReceive('_get_field_options')->andReturn($returnValue);
    }

    /**
     * Mock the _get_historic_field_options method
     */
    protected function mockGetHistoricFieldOptions($returnValue = [])
    {
        $this->fieldtype->shouldReceive('_get_historic_field_options')->andReturn($returnValue);
    }

}

// EOF
