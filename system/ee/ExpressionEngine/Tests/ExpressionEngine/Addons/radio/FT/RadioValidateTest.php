<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../RadioTestBase.php';

use Mockery as m;

/**
 * Test for Radio_ft::validate() method
 */
class RadioValidateTest extends RadioTestBase
{
    /**
     * @var Radio_ft
     */
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->getMockRadioFieldtypeWithSettings();
    }

    /**
     * Test validate with valid single value
     */
    public function testValidateValidSingleValue()
    {
        $fieldOptions = $this->mockFieldOptions();
        $data = 'option1';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with valid value from nested options
     */
    public function testValidateValidNestedValue()
    {
        $fieldOptions = $this->mockNestedFieldOptions();
        $data = 'option1';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with invalid value
     */
    public function testValidateInvalidValue()
    {
        $fieldOptions = $this->mockFieldOptions();
        $data = 'invalid_option';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertEquals('invalid_selection', $result);
    }

    /**
     * Test validate with empty string
     */
    public function testValidateEmptyString()
    {
        $fieldOptions = $this->mockFieldOptions();
        $data = '';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with null data
     */
    public function testValidateNullData()
    {
        $fieldOptions = $this->mockFieldOptions();
        $data = null;

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with false data
     */
    public function testValidateFalseData()
    {
        $fieldOptions = $this->mockFieldOptions();
        $data = false;

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with value from array option
     */
    public function testValidateArrayOptionValue()
    {
        $fieldOptions = [
            'option1' => ['value' => 'custom_value', 'name' => 'Option 1'],
            'option2' => 'Option 2'
        ];
        $data = 'custom_value';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with filter_url setting (skips validation)
     */
    public function testValidateWithFilterUrl()
    {
        $fieldOptions = $this->mockFieldOptions();
        $data = 'invalid_option';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        $mock->shouldReceive('get_setting')->with('filter_url', null)->andReturn('http://example.com/filter');
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with array option missing 'value' key
     */
    public function testValidateArrayOptionMissingValue()
    {
        $fieldOptions = [
            'option1' => ['name' => 'Option 1'], // Missing 'value' key
            'option2' => 'Option 2'
        ];
        $data = 'option1';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with mixed array and string options
     */
    public function testValidateMixedArrayStringOptions()
    {
        $fieldOptions = [
            'option1' => ['value' => 'custom_value', 'name' => 'Option 1'],
            'option2' => 'Option 2',
            'option3' => ['value' => 'another_custom', 'name' => 'Option 3'],
            'option4' => 'Simple Option 4'
        ];
        $data = 'another_custom';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with special characters in option values
     */
    public function testValidateSpecialCharacters()
    {
        $fieldOptions = [
            'option1' => 'Option with & < > " \'',
            'option2' => 'Option with éñüñ',
            'option3' => 'Option with spaces and tabs'
        ];
        $data = 'option2';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with numeric keys and values
     */
    public function testValidateNumericKeysValues()
    {
        $fieldOptions = [
            0 => 'Zero',
            1 => 'One',
            '2' => 'Two as string',
            3 => 'Pi'
        ];
        $data = 0;

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with very large number of options
     */
    public function testValidateLargeOptionSet()
    {
        $fieldOptions = [];
        for ($i = 0; $i < 1000; $i++) {
            $fieldOptions['option' . $i] = 'Option ' . $i;
        }
        $data = 'option500';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with empty field options
     */
    public function testValidateEmptyFieldOptions()
    {
        $fieldOptions = [];
        $data = 'any_value';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertEquals('invalid_selection', $result);
    }

    /**
     * Test validate with malformed array options
     */
    public function testValidateMalformedArrayOptions()
    {
        $fieldOptions = [
            'option1' => [], // Empty array
            'option2' => null, // Null value
            'option3' => false, // Boolean false
            'option4' => 0, // Zero
        ];
        $data = 'option1';

        // Mock the _get_historic_field_options method
        $mock = m::mock(\Radio_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        @$mock->field_name = $this->mockFieldName;
        @$mock->field_id = $this->mockFieldId;
        @$mock->settings = $this->fieldtype->settings;

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }
}

// EOF
