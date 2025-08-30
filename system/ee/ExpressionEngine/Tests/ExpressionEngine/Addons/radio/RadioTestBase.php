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
 * Base test class for Radio fieldtype tests
 */
abstract class RadioTestBase extends OptionFieldtypeTestBase
{
    /**
     * Create a mock fieldtype instance specific to Radio fieldtype
     */
    protected function createMockFieldtype()
    {
        $fieldtype = m::mock();
        $fieldtype->shouldReceive('display_field')->andReturn('<div>Mock radio display</div>');
        $fieldtype->shouldReceive('grid_display_field')->andReturn('<div>Mock radio grid display</div>');
        $fieldtype->shouldReceive('get_setting')->andReturn(null);
        $fieldtype->shouldReceive('validate')->andReturn(true);
        $fieldtype->shouldReceive('save')->andReturnUsing(function($data) {
            // Radio fieldtype doesn't have array data, so just return the data as-is
            return $data;
        });
        $fieldtype->shouldReceive('replace_tag')->andReturnUsing(function($data, $params = [], $tagdata = false) {
            // Radio fieldtype uses _parse_single directly
            return $data;
        });
        $fieldtype->shouldReceive('replace_value')->andReturnUsing(function($data, $params = [], $tagdata = false) {
            // replace_value is just a wrapper for replace_tag
            return $data;
        });
        $fieldtype->shouldReceive('replace_label')->andReturnUsing(function($data, $params = [], $tagdata = false) {
            // For radio fieldtype, replace_label handles value-label pairs
            if (isset($this->settings['value_label_pairs']) && isset($this->settings['value_label_pairs'][$data])) {
                return $this->settings['value_label_pairs'][$data];
            }
            return $data;
        });
        $fieldtype->shouldReceive('accepts_content_type')->andReturn(true);
        $fieldtype->shouldReceive('update')->andReturn(true);
        $fieldtype->shouldReceive('_get_historic_field_options')->andReturn(['option1' => 'Option 1']);
        $fieldtype->shouldReceive('_get_field_options')->andReturn(['option1' => 'Option 1']);
        $fieldtype->shouldReceive('_display_field')->andReturnUsing(function($data, $container = 'fieldset') {
            // Mock radio button display
            $field_options = ['option1' => 'Option 1', 'option2' => 'Option 2', 'option3' => 'Option 3'];
            $selected = $data;

            $r = '';

            foreach ($field_options as $key => $value) {
                $checked = ($key == $data) ? ' checked' : '';
                $r .= '<label><input type="radio" name="' . $this->field_name . '" value="' . $key . '"' . $checked . '> ' . $value . '</label>';
            }

            if ($container === 'fieldset') {
                $r = '<fieldset class="radio-btn-wrap">' . $r . '</fieldset>';
            }

            return $r;
        });
        $fieldtype->shouldReceive('_parse_single')->andReturnUsing(function($data, $params = []) {
            // Radio fieldtype processes single values
            if (is_array($data) && !empty($data)) {
                $data = $data[0];
            } elseif (is_array($data) && empty($data)) {
                return '';
            }

            // Handle value-label pairs
            if (isset($this->settings['value_label_pairs']) && isset($this->settings['value_label_pairs'][$data])) {
                $data = $this->settings['value_label_pairs'][$data];
            }

            return $data;
        });
        $fieldtype->shouldReceive('display_settings')->andReturn([
            'field_options_radio' => [
                'label' => 'field_options',
                'group' => 'radio',
                'settings' => []
            ]
        ]);
        $fieldtype->shouldReceive('grid_display_settings')->andReturn([
            'field_options_radio' => [
                'label' => 'field_options',
                'group' => 'radio',
                'settings' => []
            ]
        ]);
        $fieldtype->shouldReceive('renderTableCell')->andReturnUsing(function($data) {
            // Simulate renderTableCell calling _parse_single for radio fieldtype
            return $data;
        });

        $fieldtype->field_name = $this->mockFieldName;
        $fieldtype->field_id = $this->mockFieldId;
        $fieldtype->settings = [];
        $fieldtype->settings_vars = [
            'field_text_direction' => 'rtl',
            'field_pre_populate' => 'n',
            'field_list_items' => [],
            'field_pre_field_id' => '',
            'field_pre_channel_id' => ''
        ];

        return $fieldtype;
    }

    /**
     * Get a mock fieldtype with specific settings
     */
    protected function getMockRadioFieldtypeWithSettings($settings = [])
    {
        $fieldtype = $this->createMockFieldtype();

        // Override settings
        $fieldtype->settings = array_merge($fieldtype->settings_vars, $settings);

        $fieldtype->shouldReceive('get_setting')->andReturnUsing(function($key) use ($settings) {
            return isset($settings[$key]) ? $settings[$key] : null;
        });

        // Configure processTypograpghy for radio fieldtype
        $fieldtype->shouldReceive('processTypograpghy')->andReturnUsing(function($data) {
            // Mock typography processing - just return data as-is
            return $data;
        });

        // Configure replace_tag for radio fieldtype (single value, no pipe splitting)
        $fieldtype->shouldReceive('replace_tag')->andReturnUsing(function($data, $params = [], $tagdata = false) {
            // Radio fieldtype replace_tag just returns the data (single value)
            return $data;
        });

        // Configure replace_label for radio fieldtype
        $fieldtype->shouldReceive('replace_label')->andReturnUsing(function($data, $params = [], $tagdata = false) use ($settings) {
            // Handle value-label pairs
            if (isset($settings['value_label_pairs']) && isset($settings['value_label_pairs'][$data])) {
                $data = $settings['value_label_pairs'][$data];
            }

            // Process typography (mocked to return data as-is)
            $data = $this->processTypograpghy($data);

            // Call replace_tag with processed data and params
            return $this->replace_tag($data, $params, $tagdata);
        });

        // Configure replace_value for radio fieldtype
        $fieldtype->shouldReceive('replace_value')->andReturnUsing(function($data, $params = [], $tagdata = false) {
            // replace_value is a simple wrapper for replace_tag
            return $this->replace_tag($data, $params, $tagdata);
        });

        // Configure update for radio fieldtype
        $fieldtype->shouldReceive('update')->andReturn(true);

        return $fieldtype;
    }

    /**
     * Mock the _get_field_options method for Radio fieldtype
     */
    protected function mockGetFieldOptions($returnValue = [])
    {
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_field_options')->andReturn($returnValue);
        return $mock;
    }

    /**
     * Mock the _get_historic_field_options method for Radio fieldtype
     */
    protected function mockGetHistoricFieldOptions($returnValue = [])
    {
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->andReturn($returnValue);
        return $mock;
    }
}

// EOF
