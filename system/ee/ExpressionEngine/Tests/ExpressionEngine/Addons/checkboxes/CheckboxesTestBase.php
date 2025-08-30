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
        $this->markDisplaySettingsConfigured($fieldtype);

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
        // Create mock and mark display settings as configured BEFORE calling base class methods
        $fieldtype = m::mock()->makePartial();

        // Mark as configured to prevent base class from setting up display_settings
        $this->markDisplaySettingsConfigured($fieldtype);

        // Set up display settings for checkboxes BEFORE calling base class methods
        $fieldtype->shouldReceive('display_settings')->withAnyArgs()->andReturn(['field_options_checkboxes' => ['label' => 'field_options', 'group' => 'checkboxes', 'settings' => []]]);
        $fieldtype->shouldReceive('grid_display_settings')->withAnyArgs()->andReturn(['field_options_checkboxes' => ['label' => 'field_options', 'group' => 'checkboxes', 'settings' => []]]);

        // Setup basic fieldtype mocks using base class methods
        $this->setupCommonFieldtypeMocks($fieldtype, $settings);

        // Multi-value specific mocks
        $fieldtype->shouldReceive('display_field')->andReturn('<div>Mock display</div>');
        $fieldtype->shouldReceive('grid_display_field')->andReturn('<div>Mock grid display</div>');
        $fieldtype->shouldReceive('validate')->andReturn(true);

        // Setup multi-value behaviors
        $this->setupSaveMock($fieldtype, true);
        $this->setupParseSingleMock($fieldtype, true);
        $this->setupReplaceTagMock($fieldtype, true);
        $this->setupParseMultiMock($fieldtype);

        // Mock _display_nested_form method
        $fieldtype->shouldReceive('_display_nested_form')->andReturnUsing(function($fields, $values = []) use ($fieldtype) {
            $disabled = isset($fieldtype->settings['field_disabled']) && $fieldtype->settings['field_disabled'] ? ' disabled' : '';

            // Recursive function to handle nested structures
            $renderFields = function($fields, $values, $disabled) use (&$renderFields) {
                $output = '';
                foreach ($fields as $key => $value) {
                    if (is_array($value) && isset($value['name']) && isset($value['children'])) {
                        // Handle complex nested structure (groups with name and children)
                        $output .= '<div class="group">' . htmlspecialchars($value['name']) . '</div>';
                        $output .= $renderFields($value['children'], $values, $disabled);
                    } elseif (is_array($value)) {
                        // Handle nested structure (groups)
                        $output .= '<div class="group">' . htmlspecialchars($key) . '</div>';
                        $output .= $renderFields($value, $values, $disabled);
                    } else {
                        // Handle flat structure (actual options)
                        $checked = in_array($key, $values) ? ' checked' : '';
                        $output .= '<label><input type="checkbox" name="test_field[]" value="' . $key . '"' . $checked . $disabled . ' class="form_checkbox"> ' . htmlspecialchars($value) . '</label>';
                    }
                }
                return $output;
            };

            return $renderFields($fields, $values, $disabled);
        });

        // Mock _flatten method
        $fieldtype->shouldReceive('_flatten')->andReturnUsing(function($options) {
            if (!is_array($options)) {
                return $options;
            }

            $flattened = [];

            // Recursive function to flatten nested structures
            $flattenRecursive = function($options, $prefix = '') use (&$flattenRecursive, &$flattened) {
                foreach ($options as $key => $value) {
                    if (is_array($value) && isset($value['name']) && isset($value['children'])) {
                        // Add the group name
                        $flattened[$key] = $value['name'];

                        // Recursively process children
                        $children = isset($value['children']) ? $value['children'] : [];
                        if (is_array($children)) {
                            $flattenRecursive($children);
                        }
                    } elseif (is_array($value)) {
                        // Handle simple nested structure
                        $flattened[$key] = $key; // Add group name
                        $flattenRecursive($value);
                    } else {
                        // Handle flat structure (actual options)
                        $flattened[$key] = $value;
                    }
                }
            };

            $flattenRecursive($options);
            return $flattened;
        });

        // Mock renderTableCell method
        $fieldtype->shouldReceive('renderTableCell')->andReturnUsing(function($data, $fieldId, $entry) use ($fieldtype) {
            // Parse the data
            if (is_array($data)) {
                $values = $data;
            } elseif (is_string($data) && strpos($data, '|') !== false) {
                $values = explode('|', $data);
            } else {
                $values = [$data];
            }

            // Map values to labels if value_label_pairs exist
            $mappedValues = [];
            if (isset($fieldtype->settings['value_label_pairs']) && is_array($fieldtype->settings['value_label_pairs'])) {
                foreach ($values as $value) {
                    if (isset($fieldtype->settings['value_label_pairs'][$value])) {
                        $mappedValues[] = $fieldtype->settings['value_label_pairs'][$value];
                    } else {
                        $mappedValues[] = $value;
                    }
                }
            } else {
                $mappedValues = $values;
            }

            return implode(', ', $mappedValues);
        });

        // Mock replace_length method
        $fieldtype->shouldReceive('replace_length')->andReturnUsing(function($data, $params, $tagdata) {
            if (empty($data)) {
                return 0; // Empty data
            } elseif (is_array($data)) {
                return count($data);
            } elseif (is_string($data) && strpos($data, '|') !== false) {
                // Handle escaped pipes - split on non-escaped pipes
                $values = preg_split('/(?<!\\\\)\|/', $data);
                $values = array_filter($values, function($value) {
                    return !empty($value) || $value === '0'; // Keep '0' but filter empty strings
                });
                return count($values);
            }
            return 1; // Single value
        });

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
