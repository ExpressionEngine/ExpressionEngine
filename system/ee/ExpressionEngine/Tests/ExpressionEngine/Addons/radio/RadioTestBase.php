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

        // Radio-specific display settings (must be set before common mocks)
        $fieldtype->shouldReceive('display_settings')->withAnyArgs()->andReturn([
            'field_options_radio' => [
                'label' => 'field_options',
                'group' => 'radio',
                'settings' => []
            ]
        ]);

        $fieldtype->shouldReceive('grid_display_settings')->withAnyArgs()->andReturn([
            'field_options_radio' => [
                'label' => 'field_options',
                'group' => 'radio',
                'settings' => []
            ]
        ]);

        // Mark display settings as configured to prevent base class override
        $fieldtype->_display_settings_configured = true;

        // Setup basic fieldtype mocks using base class methods
        $this->setupCommonFieldtypeMocks($fieldtype);

        // Radio-specific mocks
        $fieldtype->shouldReceive('display_field')->andReturn('<div>Mock radio display</div>');
        $fieldtype->shouldReceive('grid_display_field')->andReturn('<div>Mock radio grid display</div>');
        $fieldtype->shouldReceive('get_setting')->andReturn(null);
        $fieldtype->shouldReceive('validate')->andReturn(true);

        // Setup radio-specific behaviors (single-value)
        $this->setupSaveMock($fieldtype, false);
        $this->setupReplaceTagMock($fieldtype, false);
        $this->setupParseSingleMock($fieldtype, false);

        // Radio-specific replace methods
        $fieldtype->shouldReceive('replace_value')->andReturnUsing(function($data, $params = [], $tagdata = false) {
            // replace_value is just a wrapper for replace_tag
            return $data;
        });

        $fieldtype->shouldReceive('replace_label')->andReturnUsing(function($data, $params = [], $tagdata = false) use ($fieldtype) {
            // For radio fieldtype, replace_label handles value-label pairs
            if (isset($fieldtype->settings['value_label_pairs']) && isset($fieldtype->settings['value_label_pairs'][$data])) {
                $data = $fieldtype->settings['value_label_pairs'][$data];
            }

            // Apply tagdata if provided (similar to replace_tag)
            if ($tagdata) {
                return str_replace('{item}', $data, $tagdata);
            }

            return $data;
        });

        // Radio-specific display field mock
        $fieldtype->shouldReceive('_display_field')->andReturnUsing(function($data, $container = 'fieldset') {
            // Mock radio button display
            $field_options = ['option1' => 'Option 1', 'option2' => 'Option 2', 'option3' => 'Option 3'];

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

        // Radio-specific render table cell
        $fieldtype->shouldReceive('renderTableCell')->andReturnUsing(function($data) {
            return $data;
        });

        return $fieldtype;
    }

    /**
     * Get a mock fieldtype with specific settings
     */
    protected function getMockRadioFieldtypeWithSettings($settings = [])
    {
        $fieldtype = m::mock();

        // Radio-specific display settings (must be set before common mocks)
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

        // Setup basic fieldtype mocks using base class methods
        $this->setupCommonFieldtypeMocks($fieldtype, $settings);

        // Radio-specific mocks
        $fieldtype->shouldReceive('display_field')->andReturn('<div>Mock radio display</div>');
        $fieldtype->shouldReceive('grid_display_field')->andReturn('<div>Mock radio grid display</div>');
        $fieldtype->shouldReceive('validate')->andReturn(true);

        // Setup radio-specific behaviors (single-value)
        $this->setupSaveMock($fieldtype, false);
        $this->setupReplaceTagMock($fieldtype, false);
        $this->setupParseSingleMock($fieldtype, false);

        // Override get_setting to handle custom settings
        $fieldtype->shouldReceive('get_setting')->andReturnUsing(function($key) use ($settings) {
            return isset($settings[$key]) ? $settings[$key] : null;
        });

        // Configure processTypograpghy for radio fieldtype
        $fieldtype->shouldReceive('processTypograpghy')->andReturnUsing(function($data) {
            // Mock typography processing - just return data as-is
            return $data;
        });

        // Configure replace_label for radio fieldtype with custom settings
        $fieldtype->shouldReceive('replace_label')->andReturnUsing(function($data, $params = [], $tagdata = false) use ($settings, $fieldtype) {
            // Handle value-label pairs
            if (isset($settings['value_label_pairs']) && isset($settings['value_label_pairs'][$data])) {
                $data = $settings['value_label_pairs'][$data];
            }

            // Process typography (mocked to return data as-is)
            $data = $fieldtype->processTypograpghy($data);

            // Apply tagdata if provided
            if ($tagdata) {
                return str_replace('{item}', $data, $tagdata);
            }

            return $data;
        });

        // Configure replace_value for radio fieldtype
        $fieldtype->shouldReceive('replace_value')->andReturnUsing(function($data, $params = [], $tagdata = false) use ($fieldtype) {
            // replace_value is a simple wrapper for replace_tag
            return $fieldtype->replace_tag($data, $params, $tagdata);
        });

        // Radio-specific display field mock
        $fieldtype->shouldReceive('_display_field')->andReturnUsing(function($data, $container = 'fieldset') {
            $field_options = ['option1' => 'Option 1', 'option2' => 'Option 2', 'option3' => 'Option 3'];

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

        // Radio-specific render table cell
        $fieldtype->shouldReceive('renderTableCell')->andReturnUsing(function($data) {
            return $data;
        });

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
