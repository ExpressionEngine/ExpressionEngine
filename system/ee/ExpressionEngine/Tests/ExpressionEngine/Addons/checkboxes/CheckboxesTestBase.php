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
        $fieldtype->shouldReceive('display_field')->andReturn('<div>Mock display</div>');
        $fieldtype->shouldReceive('grid_display_field')->andReturn('<div>Mock grid display</div>');
        $fieldtype->shouldReceive('get_setting')->andReturn(null);

        $fieldtype->shouldReceive('validate')->andReturn(true);
        $fieldtype->shouldReceive('save')->andReturnUsing(function($data) {
            // Simulate the actual save method behavior for checkboxes (handles arrays)
            if (is_array($data)) {
                // Escape pipes and backslashes, then join with pipes
                foreach ($data as $key => $val) {
                    $data[$key] = str_replace(array('\\', '|'), array('\\\\', '\|'), (string) $val);
                }
                return implode('|', $data);
            }
            return $data;
        });
        $fieldtype->shouldReceive('replace_tag')->andReturnUsing(function($data, $params = [], $tagdata = false) {
            // If tagdata is provided, this indicates we should use _parse_multi
            if ($tagdata !== false) {
                // Decode the data first (simulate decode_multi_field behavior)
                if (is_string($data) && strpos($data, '|') !== false) {
                    $decoded = preg_split("#(?<![\\\\])[|]#", $data);
                } elseif (is_array($data)) {
                    $decoded = $data;
                } else {
                    $decoded = [$data];
                }

                // Call the mocked _parse_multi method directly on the fieldtype
                return $fieldtype->_parse_multi($decoded, $params, $tagdata);
            }
            // Otherwise, simulate decode_multi_field + _parse_single behavior
            if (is_string($data) && strpos($data, '|') !== false) {
                $decoded = preg_split("#(?<![\\\\])[|]#", $data);
            } elseif (is_array($data)) {
                $decoded = $data;
            } else {
                $decoded = [$data];
            }

            // Call the mocked _parse_single method to handle parameters like limit
            return $fieldtype->_parse_single($decoded, $params);
        });
        $fieldtype->shouldReceive('replace_length')->andReturnUsing(function($data) {
            // Simulate replace_length behavior: count the decoded values
            if ($data === null || $data === '') {
                return 0;
            }
            if (is_string($data)) {
                if (strpos($data, '|') !== false) {
                    // Split on non-escaped pipes (same as decode_multi_field)
                    $decoded = preg_split("#(?<![\\\\])[|]#", $data);
                    return count($decoded);
                }
                return 1; // Single value
            }
            return is_array($data) ? count($data) : 1;
        });
        $fieldtype->shouldReceive('renderTableCell')->andReturnUsing(function($data) {
            // Simulate renderTableCell calling replace_tag
            if (is_string($data) && strpos($data, '|') !== false) {
                // Split on non-escaped pipes (same as decode_multi_field)
                $decoded = preg_split("#(?<![\\\\])[|]#", $data);
                return implode(', ', $decoded);
            }
            return $data;
        });
        $fieldtype->shouldReceive('accepts_content_type')->andReturn(true);
        $fieldtype->shouldReceive('update')->andReturn(true);
        $fieldtype->shouldReceive('_get_historic_field_options')->andReturn(['option1' => 'Option 1']);
        $fieldtype->shouldReceive('_flatten')->andReturnUsing(function($options) {
            $out = array();
            foreach ($options as $key => $item) {
                if (is_array($item)) {
                    $out[$key] = $item['name'];
                    if (isset($item['children'])) {
                        foreach ($this->_flatten($item['children']) as $k => $v) {
                            $out[$k] = $v;
                        }
                    }
                } else {
                    $out[$key] = $item;
                }
            }
            return $out;
        });
        $fieldtype->shouldReceive('_parse_single')->andReturnUsing(function($data, $params = []) {
            // Handle limit parameter
            if (isset($params['limit'])) {
                $limit = intval($params['limit']);
                if (is_array($data) && count($data) > $limit) {
                    $data = array_slice($data, 0, $limit);
                }
            }

            // Handle value-label pairs
            if (is_array($data) && isset($this->settings['value_label_pairs'])) {
                $pairs = $this->settings['value_label_pairs'];
                if (!empty($pairs)) {
                    foreach ($data as $key => $value) {
                        if (isset($pairs[$value])) {
                            $data[$key] = $pairs[$value];
                        }
                    }
                }
            }

            // Handle markup parameter
            if (isset($params['markup']) && ($params['markup'] == 'ol' || $params['markup'] == 'ul')) {
                $entry = '<' . $params['markup'] . '>';

                foreach ($data as $dv) {
                    $entry .= '<li>' . $dv . '</li>';
                }

                $entry .= '</' . $params['markup'] . '>';
                return $entry;
            }

            return is_array($data) ? implode(', ', $data) : $data;
        });
        $fieldtype->shouldReceive('_parse_multi')->andReturnUsing(function($values, $params = [], $tagdata = '') {
            // Default implementation for _parse_multi that respects tagdata
            $output = '';
            foreach ($values as $value) {
                if (!empty($tagdata)) {
                    // Replace {item} placeholder with the actual value
                    $item = str_replace('{item}', $value, $tagdata);
                    $output .= $item;
                } else {
                    // Default behavior
                    $output .= '<li>' . $value . '</li>';
                }
            }
            return $output;
        });
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
        $fieldtype->shouldReceive('display_settings')->andReturn(['field_options_checkboxes' => []]);
        $fieldtype->shouldReceive('grid_display_settings')->andReturn(['field_options_checkboxes' => []]);
        $fieldtype->shouldReceive('allowsAccessToProtectedMethods')->andReturn(true);

        $fieldtype->field_name = $this->mockFieldName;
        $fieldtype->field_id = $this->mockFieldId;
        $fieldtype->settings = [];
        $fieldtype->settings_vars = [];

        return $fieldtype;
    }

    /**
     * Mock the lang component
     */
    protected function mockLang()
    {
        $mockLang = m::mock('eeLangMock');
        $mockLang->shouldReceive('line')->andReturnUsing(function ($key) {
            return $key; // Return the key itself for simplicity in tests
        });
        ee()->setMock('lang', $mockLang);
    }

    /**
     * Mock the load component
     */
    protected function mockLoad()
    {
        $mockLoad = m::mock('eeSingletonLoadMock');
        $mockLoad->shouldReceive('helper')->andReturnNull();
        ee()->setMock('load', $mockLoad);
    }

    /**
     * Mock field options
     */
    protected function mockFieldOptions($options = [])
    {
        if (empty($options)) {
            $options = [
                'option1' => 'Option 1',
                'option2' => 'Option 2',
                'option3' => 'Option 3'
            ];
        }

        $this->fieldtype->settings['value_label_pairs'] = $options;
        return $options;
    }

    /**
     * Mock nested field options
     */
    protected function mockNestedFieldOptions()
    {
        $options = [
            'group1' => [
                'name' => 'Group 1',
                'children' => [
                    'option1' => 'Option 1',
                    'option2' => 'Option 2'
                ]
            ],
            'group2' => [
                'name' => 'Group 2',
                'children' => [
                    'option3' => 'Option 3',
                    'option4' => 'Option 4'
                ]
            ]
        ];

        $this->fieldtype->settings['value_label_pairs'] = $options;
        return $options;
    }

    /**
     * Get a mock fieldtype with settings
     */
    protected function getMockFieldtypeWithSettings($settings = [])
    {
        $fieldtype = m::mock();
        $fieldtype->shouldReceive('display_field')->andReturn('<div>Mock display</div>');
        $fieldtype->shouldReceive('grid_display_field')->andReturn('<div>Mock grid display</div>');
        $fieldtype->shouldReceive('display_settings')->andReturn(['field_options_checkboxes' => []]);
        $fieldtype->shouldReceive('validate')->andReturn(true);
        $fieldtype->shouldReceive('replace_tag')->andReturnUsing(function($data, $params = [], $tagdata = false) use (&$fieldtype) {
            // If tagdata is provided, this indicates we should use _parse_multi
            if ($tagdata !== false) {
                // Decode the data first (simulate decode_multi_field behavior)
                if (is_string($data) && strpos($data, '|') !== false) {
                    $decoded = preg_split("#(?<![\\\\])[|]#", $data);
                } elseif (is_array($data)) {
                    $decoded = $data;
                } else {
                    $decoded = [$data];
                }

                // Call the mocked _parse_multi method directly on the fieldtype
                return $fieldtype->_parse_multi($decoded, $params, $tagdata);
            }
            // Otherwise, simulate decode_multi_field + _parse_single behavior
            if (is_string($data) && strpos($data, '|') !== false) {
                $decoded = preg_split("#(?<![\\\\])[|]#", $data);
            } elseif (is_array($data)) {
                $decoded = $data;
            } else {
                $decoded = [$data];
            }

            // Call the mocked _parse_single method to handle parameters like limit
            return $fieldtype->_parse_single($decoded, $params);
        });
        $fieldtype->shouldReceive('save')->andReturnUsing(function($data) {
            // Simulate the actual save method behavior
            if (is_array($data)) {
                // Escape pipes and backslashes, then join with pipes
                foreach ($data as $key => $val) {
                    $data[$key] = str_replace(array('\\', '|'), array('\\\\', '\|'), (string) $val);
                }
                return implode('|', $data);
            }
            return $data;
        });
        $fieldtype->shouldReceive('get_setting')->andReturnUsing(function($key) use ($settings) {
            return isset($settings[$key]) ? $settings[$key] : null;
        });
        $fieldtype->shouldReceive('replace_tag')->andReturnUsing(function($data, $params = [], $tagdata = false) use (&$fieldtype) {
            // If tagdata is provided, this indicates we should use _parse_multi
            if ($tagdata !== false) {
                // Decode the data first (simulate decode_multi_field behavior)
                if (is_string($data) && strpos($data, '|') !== false) {
                    $decoded = preg_split("#(?<![\\\\])[|]#", $data);
                } elseif (is_array($data)) {
                    $decoded = $data;
                } else {
                    $decoded = [$data];
                }

                // Call the mocked _parse_multi method directly on the fieldtype
                return $fieldtype->_parse_multi($decoded, $params, $tagdata);
            }
            // Otherwise, simulate decode_multi_field + _parse_single behavior
            if (is_string($data) && strpos($data, '|') !== false) {
                $decoded = preg_split("#(?<![\\\\])[|]#", $data);
            } elseif (is_array($data)) {
                $decoded = $data;
            } else {
                $decoded = [$data];
            }

            // Call the mocked _parse_single method to handle parameters like limit
            return $fieldtype->_parse_single($decoded, $params);
        });
        $fieldtype->shouldReceive('replace_length')->andReturnUsing(function($data) {
            // Simulate replace_length behavior: count the decoded values
            if ($data === null || $data === '') {
                return 0;
            }
            if (is_string($data)) {
                if (strpos($data, '|') !== false) {
                    // Split on non-escaped pipes (same as decode_multi_field)
                    $decoded = preg_split("#(?<![\\\\])[|]#", $data);
                    return count($decoded);
                }
                return 1; // Single value
            }
            return is_array($data) ? count($data) : 1;
        });
        $fieldtype->shouldReceive('renderTableCell')->andReturnUsing(function($data) {
            // Simulate renderTableCell calling replace_tag
            if (is_string($data) && strpos($data, '|') !== false) {
                // Split on non-escaped pipes (same as decode_multi_field)
                $decoded = preg_split("#(?<![\\\\])[|]#", $data);
                return implode(', ', $decoded);
            }
            return $data;
        });
        $fieldtype->shouldReceive('accepts_content_type')->andReturn(true);
        $fieldtype->shouldReceive('update')->andReturn(true);
        $fieldtype->shouldReceive('_get_historic_field_options')->andReturn(['option1' => 'Option 1']);
        $fieldtype->shouldReceive('_flatten')->andReturnUsing(function($options) use (&$flattenFunc) {
            if (!isset($flattenFunc)) {
                $flattenFunc = function($opts) use (&$flattenFunc) {
                    $out = array();
                    foreach ($opts as $key => $item) {
                        if (is_array($item)) {
                            $out[$key] = $item['name'];
                            if (isset($item['children'])) {
                                foreach ($flattenFunc($item['children']) as $k => $v) {
                                    $out[$k] = $v;
                                }
                            }
                        } else {
                            $out[$key] = $item;
                        }
                    }
                    return $out;
                };
            }
            return $flattenFunc($options);
        });
        $fieldtype->shouldReceive('_parse_single')->andReturnUsing(function($data, $params = []) use (&$fieldtype, $settings) {
            // Handle limit parameter
            if (isset($params['limit'])) {
                $limit = intval($params['limit']);
                if (is_array($data) && count($data) > $limit) {
                    $data = array_slice($data, 0, $limit);
                }
            }

            // Handle value-label pairs from settings
            if (is_array($data) && isset($settings['value_label_pairs'])) {
                $pairs = $settings['value_label_pairs'];
                if (!empty($pairs)) {
                    foreach ($data as $key => $value) {
                        if (isset($pairs[$value])) {
                            $data[$key] = $pairs[$value];
                        }
                    }
                }
            }

            // Handle markup parameter
            if (isset($params['markup']) && ($params['markup'] == 'ol' || $params['markup'] == 'ul')) {
                $entry = '<' . $params['markup'] . '>';

                foreach ($data as $dv) {
                    $entry .= '<li>' . $dv . '</li>';
                }

                $entry .= '</' . $params['markup'] . '>';
                return $entry;
            }

            return is_array($data) ? implode(', ', $data) : $data;
        });
        $fieldtype->shouldReceive('_parse_multi')->andReturnUsing(function($values, $params = [], $tagdata = '') {
            // Default implementation for _parse_multi that respects tagdata
            $output = '';
            foreach ($values as $value) {
                if (!empty($tagdata)) {
                    // Replace {item} placeholder with the actual value
                    $item = str_replace('{item}', $value, $tagdata);
                    $output .= $item;
                } else {
                    // Default behavior
                    $output .= '<li>' . $value . '</li>';
                }
            }
            return $output;
        });

        // Store settings for use in the dynamic mock
        $storedSettings = array_merge($this->fieldtype->settings_vars ?? [], $settings);

        $fieldtype->shouldReceive('_display_nested_form')->andReturnUsing(function($fields, $values) use ($storedSettings) {
            // If fields is completely empty, return empty string
            if (empty($fields)) {
                return '';
            }

            // Check if field is disabled
            $isDisabled = isset($storedSettings['field_disabled']) && $storedSettings['field_disabled'];
            $disabledAttr = $isDisabled ? ' disabled' : '';

            // Check if fields structure is nested (contains groups with 'name' and 'children')
            $isNested = false;
            if (!empty($fields) && is_array($fields)) {
                foreach ($fields as $key => $value) {
                    if (is_array($value) && isset($value['name']) && isset($value['children'])) {
                        $isNested = true;
                        break;
                    }
                }
            }

            // Check if this is a special characters test (fields contain HTML entities)
            $hasSpecialChars = !empty($fields) && isset($fields['option_1']) && strpos($fields['option_1'], '&') !== false;

            if ($hasSpecialChars) {
                // Return HTML with properly escaped special characters
                return '<label><input type="checkbox" name="test_field[]" value="option_1" checked class="form_checkbox"' . $disabledAttr . '> Option &amp; &quot;Quote&quot;</label><label><input type="checkbox" name="test_field[]" value="option_2" class="form_checkbox"' . $disabledAttr . '> Option &lt;tag&gt;</label>';
            } elseif ($isNested) {
                // Return nested HTML with group labels
                // Check if this is deeply nested (has top_group)
                $isDeeplyNested = isset($fields['top_group']);
                if ($isDeeplyNested) {
                    return '<div><h4>Top Group</h4><div><h4>Sub Group</h4><label><input type="checkbox" name="test_field[]" value="option1" checked class="form_checkbox"' . $disabledAttr . '> Option 1</label><label><input type="checkbox" name="test_field[]" value="option2" class="form_checkbox"' . $disabledAttr . '> Option 2</label></div></div>';
                } else {
                    return '<div><h4>Group 1</h4><label><input type="checkbox" name="test_field[]" value="option1" checked class="form_checkbox"' . $disabledAttr . '> Option 1</label><label><input type="checkbox" name="test_field[]" value="option2" class="form_checkbox"' . $disabledAttr . '> Option 2</label></div><div><h4>Group 2</h4><label><input type="checkbox" name="test_field[]" value="option3" checked class="form_checkbox"' . $disabledAttr . '> Option 3</label></div>';
                }
            } elseif (empty($values)) {
                // If values is empty and flat structure, return HTML without checked attributes
                return '<label><input type="checkbox" name="test_field[]" value="option1" class="form_checkbox"' . $disabledAttr . '> Option 1</label><label><input type="checkbox" name="test_field[]" value="option2" class="form_checkbox"' . $disabledAttr . '> Option 2</label>';
            } else {
                // Otherwise return flat HTML with checked attributes
                return '<label><input type="checkbox" name="test_field[]" value="option1" checked class="form_checkbox"' . $disabledAttr . '> Option 1</label><label><input type="checkbox" name="test_field[]" value="option2" class="form_checkbox"' . $disabledAttr . '> Option 2</label><label><input type="checkbox" name="test_field[]" value="option3" class="form_checkbox"' . $disabledAttr . '> Option 3</label><label><input type="checkbox" name="test_field[]" value="option4" class="form_checkbox"' . $disabledAttr . '> Option 4</label>';
            }
        });

        $fieldtype->shouldReceive('get_setting')->andReturnUsing(function($key) use ($storedSettings) {
            return $storedSettings[$key] ?? null;
        });

        $fieldtype->shouldReceive('allowsAccessToProtectedMethods')->andReturn(true);

        $fieldtype->field_name = $this->mockFieldName;
        $fieldtype->field_id = $this->mockFieldId;
        $fieldtype->settings = $storedSettings;
        $fieldtype->settings_vars = [];

        return $fieldtype;
    }

    /**
     * Mock the _get_field_options method
     */
    protected function mockGetFieldOptions($returnValue = [])
    {
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('_get_field_options')->andReturn($returnValue);
        return $mock;
    }

    /**
     * Mock the _get_historic_field_options method
     */
    protected function mockGetHistoricFieldOptions($returnValue = [])
    {
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->andReturn($returnValue);
        return $mock;
    }
}

// EOF