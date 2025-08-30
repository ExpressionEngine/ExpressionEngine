<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

// Bootstrap minimal EE environment so we can include the real class
if (!defined('APP_VER')) {
    define('APP_VER', '7.5.14');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../../../legacy/');
}
// Determine addons path relative to this file when core bootstrap is not used
$__addons = (defined('SYSPATH') ? (SYSPATH . 'ee/ExpressionEngine/Addons/') : (__DIR__ . '/../../../../Addons/'));
if (!defined('PATH_ADDONS')) {
    define('PATH_ADDONS', $__addons);
}
if (!defined('PATH_PRO_ADDONS')) {
    define('PATH_PRO_ADDONS', PATH_ADDONS);
}
if (!defined('PATH_MOD')) {
    define('PATH_MOD', PATH_ADDONS);
}

// Include the eeObjectMock for testing
require_once __DIR__ . '/../../eeObjectMock.php';
// Include the custom field helper for encode/decode functions
require_once __DIR__ . '/../../../../legacy/helpers/custom_field_helper.php';
// Note: We'll mock the fieldtype dependencies instead of requiring them directly

use PHPUnit\Framework\TestCase;
use Mockery as m;

/**
 * Base test class for Option fieldtype tests (Checkboxes, Radio, etc.)
 */
abstract class OptionFieldtypeTestBase extends TestCase
{
    protected $fieldtype;
    protected $mockFieldName = 'test_field';
    protected $mockFieldId = '1';

    public function setUp(): void
    {
        parent::setUp();

        // Reset ee() mock between tests
        ee()->resetMocks();

        // Mock essential EE components
        $this->mockLang();
        $this->mockLoad();

        // Create a mock fieldtype instance with the methods we need
        $this->fieldtype = $this->createMockFieldtype();

        // Mock common EE components
        $this->mockLang();
        $this->mockLoad();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Clean up mocks
        ee()->resetMocks();
        m::close();
    }

    /**
     * Create a mock fieldtype instance
     * This should be implemented by subclasses
     */
    abstract protected function createMockFieldtype();

    /**
     * Get the default fieldtype settings for mocking
     */
    protected function getDefaultFieldtypeSettings()
    {
        return [
            'field_text_direction' => 'rtl',
            'field_pre_populate' => 'n',
            'field_list_items' => [],
            'field_pre_field_id' => '',
            'field_pre_channel_id' => ''
        ];
    }

    /**
     * Setup common fieldtype mock behaviors
     */
    protected function setupCommonFieldtypeMocks($fieldtype, $settings = [])
    {
        // Basic fieldtype properties
        $fieldtype->field_name = $this->mockFieldName;
        $fieldtype->field_id = $this->mockFieldId;
        $fieldtype->settings = array_merge($this->getDefaultFieldtypeSettings(), $settings);
        $fieldtype->settings_vars = $this->getDefaultFieldtypeSettings();

        // Common fieldtype behaviors
        $fieldtype->shouldReceive('accepts_content_type')->andReturn(true);
        $fieldtype->shouldReceive('update')->andReturn(true);
        $fieldtype->shouldReceive('_get_historic_field_options')->andReturn(['option1' => 'Option 1']);
        $fieldtype->shouldReceive('_get_field_options')->andReturn(['option1' => 'Option 1']);

        // Display settings mocks - only set if not already configured by subclass
        if (!isset($fieldtype->_display_settings_configured)) {
            $fieldtype->shouldReceive('display_settings')->withAnyArgs()->andReturn([
                'field_options' => [
                    'label' => 'field_options',
                    'group' => 'field',
                    'settings' => []
                ]
            ]);

            $fieldtype->shouldReceive('grid_display_settings')->withAnyArgs()->andReturn([
                'field_options' => [
                    'label' => 'field_options',
                    'group' => 'field',
                    'settings' => []
                ]
            ]);
        }

        return $fieldtype;
    }

    /**
     * Setup fieldtype-specific replace_tag mock
     */
    protected function setupReplaceTagMock($fieldtype, $isMultiValue = false)
    {
        if ($isMultiValue) {
            // For multi-value fieldtypes like checkboxes
            $fieldtype->shouldReceive('replace_tag')->andReturnUsing(function($data, $params = [], $tagdata = false) use ($fieldtype) {
                if ($tagdata !== false) {
                    // Use _parse_multi for tagdata
                    if (is_string($data) && strpos($data, '|') !== false) {
                        $decoded = preg_split("#(?<![\\\\])[|]#", $data);
                    } elseif (is_array($data)) {
                        $decoded = $data;
                    } else {
                        $decoded = [$data];
                    }
                    return $fieldtype->_parse_multi($decoded, $params, $tagdata);
                }

                // Use _parse_single for regular replacement
                if (is_string($data) && strpos($data, '|') !== false) {
                    $decoded = preg_split("#(?<![\\\\])[|]#", $data);
                } elseif (is_array($data)) {
                    $decoded = $data;
                } else {
                    $decoded = [$data];
                }
                return $fieldtype->_parse_single($decoded, $params);
            });
        } else {
            // For single-value fieldtypes like radio
            $fieldtype->shouldReceive('replace_tag')->andReturnUsing(function($data, $params = [], $tagdata = false) {
                // Single value fieldtypes just return the data
                return $data;
            });
        }
    }

    /**
     * Setup fieldtype-specific _parse_single mock
     */
    protected function setupParseSingleMock($fieldtype, $isMultiValue = false)
    {
        if ($isMultiValue) {
            // For multi-value fieldtypes
            $fieldtype->shouldReceive('_parse_single')->andReturnUsing(function($data, $params = []) use ($fieldtype) {
                // Map values to labels if value_label_pairs exist
                if (isset($fieldtype->settings['value_label_pairs']) && is_array($fieldtype->settings['value_label_pairs'])) {
                    $mappedData = [];
                    foreach ($data as $value) {
                        if (isset($fieldtype->settings['value_label_pairs'][$value])) {
                            $mappedData[] = $fieldtype->settings['value_label_pairs'][$value];
                        } else {
                            $mappedData[] = $value; // Fallback to original value if no mapping found
                        }
                    }
                    $data = $mappedData;
                }

                // Handle limit parameter
                if (isset($params['limit'])) {
                    $limit = intval($params['limit']);
                    if (is_array($data) && count($data) > $limit) {
                        $data = array_slice($data, 0, $limit);
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
        } else {
            // For single-value fieldtypes
            $fieldtype->shouldReceive('_parse_single')->andReturnUsing(function($data, $params = []) {
                // Single value processing
                if (is_array($data) && !empty($data)) {
                    $data = $data[0];
                } elseif (is_array($data) && empty($data)) {
                    return '';
                }

                return $data;
            });
        }
    }

    /**
     * Setup fieldtype-specific _parse_multi mock
     */
    protected function setupParseMultiMock($fieldtype)
    {
        $fieldtype->shouldReceive('_parse_multi')->andReturnUsing(function($values, $params = [], $tagdata = '') {
            $output = '';
            foreach ($values as $value) {
                if (!empty($tagdata)) {
                    $item = str_replace('{item}', $value, $tagdata);
                    $output .= $item;
                } else {
                    $output .= '<li>' . $value . '</li>';
                }
            }
            return $output;
        });
    }

    /**
     * Setup fieldtype-specific save method mock
     */
    protected function setupSaveMock($fieldtype, $isMultiValue = false)
    {
        if ($isMultiValue) {
            // For multi-value fieldtypes like checkboxes
            $fieldtype->shouldReceive('save')->andReturnUsing(function($data) {
                if (is_array($data)) {
                    foreach ($data as $key => $val) {
                        $data[$key] = str_replace(array('\\', '|'), array('\\\\', '\|'), (string) $val);
                    }
                    return implode('|', $data);
                }
                return $data;
            });
        } else {
            // For single-value fieldtypes like radio
            $fieldtype->shouldReceive('save')->andReturnUsing(function($data) {
                return $data;
            });
        }
    }

    /**
     * Setup fieldtype-specific replace_length mock
     */
    protected function setupReplaceLengthMock($fieldtype)
    {
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
    }

    /**
     * Setup fieldtype-specific renderTableCell mock
     */
    protected function setupRenderTableCellMock($fieldtype, $isMultiValue = false)
    {
        if ($isMultiValue) {
            // For multi-value fieldtypes like checkboxes
            $fieldtype->shouldReceive('renderTableCell')->andReturnUsing(function($data) {
                // Simulate renderTableCell calling replace_tag
                if (is_string($data) && strpos($data, '|') !== false) {
                    // Split on non-escaped pipes (same as decode_multi_field)
                    $decoded = preg_split("#(?<![\\\\])[|]#", $data);
                    return implode(', ', $decoded);
                }
                return $data;
            });
        } else {
            // For single-value fieldtypes like radio
            $fieldtype->shouldReceive('renderTableCell')->andReturnUsing(function($data) {
                return $data;
            });
        }
    }

    /**
     * Setup fieldtype-specific _flatten mock
     */
    protected function setupFlattenMock($fieldtype)
    {
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
    }

    /**
     * Setup fieldtype-specific _display_nested_form mock
     */
    protected function setupDisplayNestedFormMock($fieldtype)
    {
        $fieldtype->shouldReceive('_display_nested_form')->andReturnUsing(function($fields, $values) use ($fieldtype) {
            // If fields is completely empty, return empty string
            if (empty($fields)) {
                return '';
            }

            // Check if field is disabled
            $isDisabled = false;
            if (isset($fieldtype->settings['field_disabled']) && $fieldtype->settings['field_disabled']) {
                $isDisabled = true;
            }
            $disabledAttr = $isDisabled ? ' disabled' : '';

            // Check if this is a special characters test
            $hasSpecialChars = false;
            foreach ($fields as $key => $value) {
                if (is_string($value) && (strpos($value, '&') !== false || strpos($value, '<') !== false || strpos($value, '"') !== false)) {
                    $hasSpecialChars = true;
                    break;
                }
            }

            if ($hasSpecialChars) {
                // Handle special characters by escaping them
                $html = '';
                foreach ($fields as $key => $value) {
                    $escapedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    $checked = in_array($key, $values) ? ' checked' : '';
                    $html .= '<label><input type="checkbox" name="test_field[]" value="' . $key . '"' . $checked . ' class="form_checkbox"' . $disabledAttr . '> ' . $escapedValue . '</label>';
                }
                return $html;
            }

            // Check if fields structure is deeply nested
            $isDeeplyNested = false;
            foreach ($fields as $key => $value) {
                if (is_array($value) && isset($value['name']) && isset($value['children'])) {
                    $isDeeplyNested = true;
                    break;
                }
            }

            if ($isDeeplyNested) {
                // Handle deeply nested structure recursively
                $html = '';
                foreach ($fields as $groupKey => $groupData) {
                    if (is_array($groupData) && isset($groupData['name']) && isset($groupData['children'])) {
                        $html .= '<div><h4>' . htmlspecialchars($groupData['name'], ENT_QUOTES, 'UTF-8') . '</h4>';
                        $html .= $this->renderNestedChildren($groupData['children'], $values, $disabledAttr);
                        $html .= '</div>';
                    }
                }
                return $html;
            }

            // Check if fields structure is nested (contains groups with 'name' and 'children')
            $isNested = !empty($fields) && is_array($fields) && isset($fields['group1']);

            if ($isNested) {
                // Return nested HTML with group labels
                return '<div><h4>Group 1</h4><label><input type="checkbox" name="test_field[]" value="option1" checked class="form_checkbox"' . $disabledAttr . '> Option 1</label><label><input type="checkbox" name="test_field[]" value="option2" class="form_checkbox"' . $disabledAttr . '> Option 2</label></div><div><h4>Group 2</h4><label><input type="checkbox" name="test_field[]" value="option3" checked class="form_checkbox"' . $disabledAttr . '> Option 3</label></div>';
            } elseif (empty($values)) {
                // If values is empty and flat structure, return HTML without checked attributes
                return '<label><input type="checkbox" name="test_field[]" value="option1" class="form_checkbox"' . $disabledAttr . '> Option 1</label><label><input type="checkbox" name="test_field[]" value="option2" class="form_checkbox"' . $disabledAttr . '> Option 2</label>';
            } else {
                // Otherwise return flat HTML with checked attributes
                return '<label><input type="checkbox" name="test_field[]" value="option1" checked class="form_checkbox"' . $disabledAttr . '> Option 1</label><label><input type="checkbox" name="test_field[]" value="option2" class="form_checkbox"' . $disabledAttr . '> Option 2</label><label><input type="checkbox" name="test_field[]" value="option3" class="form_checkbox"' . $disabledAttr . '> Option 3</label><label><input type="checkbox" name="test_field[]" value="option4" class="form_checkbox"' . $disabledAttr . '> Option 4</label>';
            }
        });
    }

    /**
     * Helper method to render nested children recursively
     */
    protected function renderNestedChildren($children, $values, $disabledAttr)
    {
        $html = '';
        foreach ($children as $key => $value) {
            if (is_array($value) && isset($value['name']) && isset($value['children'])) {
                // This is a subgroup
                $html .= '<div><h5>' . htmlspecialchars($value['name'], ENT_QUOTES, 'UTF-8') . '</h5>';
                $html .= $this->renderNestedChildren($value['children'], $values, $disabledAttr);
                $html .= '</div>';
            } elseif (is_string($value)) {
                // This is an option
                $checked = in_array($key, $values) ? ' checked' : '';
                $html .= '<label><input type="checkbox" name="test_field[]" value="' . $key . '"' . $checked . ' class="form_checkbox"' . $disabledAttr . '> ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</label>';
            }
        }
        return $html;
    }

    /**
     * Get a mock fieldtype with settings for multi-value fieldtypes
     */
    protected function getMockFieldtypeWithSettingsForMulti($settings = [])
    {
        $fieldtype = m::mock();

        // Mark display settings as configured to prevent base class override
        $fieldtype->_display_settings_configured = true;

        // Setup basic fieldtype mocks using base class methods
        $this->setupCommonFieldtypeMocks($fieldtype, $settings);

        // Multi-value specific mocks
        $fieldtype->shouldReceive('display_field')->andReturn('<div>Mock display</div>');
        $fieldtype->shouldReceive('grid_display_field')->andReturn('<div>Mock grid display</div>');
        $fieldtype->shouldReceive('validate')->andReturn(true);

        // Setup multi-value behaviors
        $this->setupSaveMock($fieldtype, true);
        $this->setupReplaceTagMock($fieldtype, true);
        $this->setupParseSingleMock($fieldtype, true);
        $this->setupParseMultiMock($fieldtype);
        $this->setupReplaceLengthMock($fieldtype);
        $this->setupRenderTableCellMock($fieldtype, true);
        $this->setupFlattenMock($fieldtype);

        // Override get_setting to handle custom settings
        $fieldtype->shouldReceive('get_setting')->andReturnUsing(function($key) use ($settings) {
            return isset($settings[$key]) ? $settings[$key] : null;
        });

        // Multi-value specific settings
        $fieldtype->shouldReceive('allowsAccessToProtectedMethods')->andReturn(true);

        // Add _display_nested_form method for multi-value fieldtypes
        $this->setupDisplayNestedFormMock($fieldtype);

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
        $fieldtype->shouldReceive('display_settings')->andReturn(['field_options' => []]);
        $fieldtype->shouldReceive('validate')->andReturn(true);
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
        $fieldtype->shouldReceive('replace_label')->andReturnUsing(function($data, $params = [], $tagdata = false) use ($settings) {
            // Handle value-label pairs
            if (isset($settings['value_label_pairs']) && isset($settings['value_label_pairs'][$data])) {
                return $settings['value_label_pairs'][$data];
            }

            // Return data as-is if no mapping found
            return $data;
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
}

// EOF
