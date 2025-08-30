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
