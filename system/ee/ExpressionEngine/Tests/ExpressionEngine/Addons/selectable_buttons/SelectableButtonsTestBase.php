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
require_once __DIR__ . '/../../../eeObjectMock.php';
// Include the custom field helper for encode/decode functions
require_once __DIR__ . '/../../../../../legacy/helpers/custom_field_helper.php';
// Include the OptionFieldtypeTestBase
require_once __DIR__ . '/../OptionFieldtypeTestBase.php';

use PHPUnit\Framework\TestCase;
use Mockery as m;

/**
 * Base test class for Selectable Buttons fieldtype tests
 */
class SelectableButtonsTestBase extends OptionFieldtypeTestBase
{
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->createMockFieldtype();
    }

    /**
     * Create a mock fieldtype instance for Selectable Buttons
     */
    protected function createMockFieldtype()
    {
        // Create mock first and mark display settings as configured
        $fieldtype = m::mock();
        $fieldtype->_display_settings_configured = true;

        // Setup basic fieldtype mocks using base class methods
        $this->setupCommonFieldtypeMocks($fieldtype);

        // Setup multi-value behaviors (selectable buttons supports multiple values)
        $this->setupParseSingleMock($fieldtype, true);
        $this->setupReplaceLengthMock($fieldtype);
        $this->setupRenderTableCellMock($fieldtype, false);

        // Add replace_tag mock for single-value fieldtypes
        $fieldtype->shouldReceive('replace_tag')->andReturnUsing(function($data, $params = [], $tagdata = false) {
            // Apply tagdata if provided
            if ($tagdata) {
                return str_replace('{item}', $data, $tagdata);
            }
            // Single value fieldtypes just return the data
            return $data;
        });

        // Add replace_value mock for multi-value fieldtypes (selectable buttons)
        $fieldtype->shouldReceive('replace_value')->andReturnUsing(function($data, $params = [], $tagdata = false) use ($fieldtype) {
            // replace_value should return raw values, not mapped labels
            // Handle different data types: arrays, pipe-delimited strings, single values

            // Convert data to array format for consistent processing
            if (is_array($data)) {
                $values = $data;
            } elseif (is_string($data) && strpos($data, '|') !== false) {
                $values = explode('|', $data);
            } else {
                // Single value
                $values = [$data];
            }

            // Apply limit parameter if set
            if (isset($params['limit'])) {
                $limit = intval($params['limit']);
                if (count($values) > $limit) {
                    $values = array_slice($values, 0, $limit);
                }
            }

            // Apply tagdata to each individual value if provided
            if ($tagdata) {
                $taggedValues = [];
                foreach ($values as $value) {
                    $taggedValues[] = str_replace('{item}', $value, $tagdata);
                }
                return implode(', ', $taggedValues);
            }

            // Apply markup parameter if set
            if (isset($params['markup']) && ($params['markup'] == 'ol' || $params['markup'] == 'ul')) {
                $entry = '<' . $params['markup'] . '>';
                foreach ($values as $value) {
                    $entry .= '<li>' . $value . '</li>';
                }
                $entry .= '</' . $params['markup'] . '>';
                return $entry;
            }

            // Return comma-separated values
            return implode(', ', $values);
        });

        // Selectable Buttons specific display settings
        $fieldtype->shouldReceive('display_settings')->andReturn([
            'field_options_selectable_buttons' => [
                'label' => 'field_options',
                'group' => 'selectable_buttons',
                'settings' => []
            ]
        ]);

        $fieldtype->shouldReceive('grid_display_settings')->andReturn([
            'field_options' => [
                'label' => 'field_options',
                'group' => 'selectable_buttons',
                'settings' => []
            ]
        ]);

        // Selectable buttons specific display_field mock
        $fieldtype->shouldReceive('display_field')->andReturn('<div>Mock display</div>');

        // Selectable buttons specific save_settings mock
        $fieldtype->shouldReceive('save_settings')->andReturnUsing(function($data) {
            $settings = ['field_options' => isset($data['field_options']) ? $data['field_options'] : []];
            $settings['allow_multiple'] = (isset($data['allow_multiple']) && $data['allow_multiple'] == 'y') ? true : false;
            return $settings;
        });

        // Selectable buttons specific validate mock
        $fieldtype->shouldReceive('validate')->andReturnUsing(function($data) use ($fieldtype) {
            // Simple validation - just return true for valid data, false for invalid
            if (is_array($data) && count($data) > 1) {
                // For single selection, only one item is allowed
                if (!isset($fieldtype->settings['allow_multiple']) || !$fieldtype->settings['allow_multiple']) {
                    return 'ft_multiselect_not_allowed'; // Return error string directly
                }
            }
            return true; // Valid
        });

        // Selectable buttons specific replace_label mock
        $fieldtype->shouldReceive('replace_label')->andReturnUsing(function($data, $params = [], $tagdata = false) use ($fieldtype) {
            // Handle pipe-delimited values for selectable buttons
            if (is_string($data) && strpos($data, '|') !== false) {
                $values = explode('|', $data);

                // Apply limit if specified
                if (isset($params['limit']) && is_numeric($params['limit'])) {
                    $values = array_slice($values, 0, (int)$params['limit']);
                }

                $mappedValues = [];

                foreach ($values as $value) {
                    if (isset($fieldtype->settings['value_label_pairs']) && isset($fieldtype->settings['value_label_pairs'][$value])) {
                        $mappedValue = $fieldtype->settings['value_label_pairs'][$value];
                    } else {
                        $mappedValue = $value; // Use original value if no mapping found
                    }

                    // Apply tagdata to each individual value if provided
                    if ($tagdata) {
                        $mappedValues[] = str_replace('{item}', $mappedValue, $tagdata);
                    } else {
                        $mappedValues[] = $mappedValue;
                    }
                }

                // Handle markup parameter
                if (isset($params['markup']) && in_array($params['markup'], ['ul', 'ol'])) {
                    $markup = $params['markup'];
                    $entry = '<' . $markup . '>';

                    foreach ($mappedValues as $mappedValue) {
                        $entry .= '<li>' . $mappedValue . '</li>';
                    }

                    $entry .= '</' . $markup . '>';
                    return $entry;
                }

                return implode(', ', $mappedValues);
            } else {
                // Handle single value
                if (isset($fieldtype->settings['value_label_pairs']) && isset($fieldtype->settings['value_label_pairs'][$data])) {
                    $data = $fieldtype->settings['value_label_pairs'][$data];
                }

                // Apply tagdata if provided
                if ($tagdata) {
                    return str_replace('{item}', $data, $tagdata);
                }

                return $data;
            }
        });

        return $fieldtype;
    }
}

// EOF
