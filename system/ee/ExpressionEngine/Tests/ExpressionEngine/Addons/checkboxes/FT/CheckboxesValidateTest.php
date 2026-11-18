<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../CheckboxesTestBase.php';

use Mockery as m;

/**
 * Test for Checkboxes_ft::validate() method
 */
class CheckboxesValidateTest extends CheckboxesTestBase
{
    /**
     * @var Checkboxes_ft
     */
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->getMockFieldtypeWithSettings();
    }

    /**
     * Test validate with valid single value
     */
    public function testValidateValidSingleValue()
    {
        $fieldOptions = $this->mockFieldOptions();
        $data = 'option1';

        // Mock the _get_historic_field_options method
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        $mock->shouldReceive('validate')->andReturn(true);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with valid array of values
     */
    public function testValidateValidArrayValues()
    {
        $fieldOptions = $this->mockFieldOptions();
        $data = ['option1', 'option2'];

        // Mock the _get_historic_field_options method
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

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
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

        $result = $mock->validate($data);

        $this->assertEquals('invalid_selection', $result);
    }

    /**
     * Test validate with mixed valid and invalid values
     */
    public function testValidateMixedValidInvalidValues()
    {
        $fieldOptions = $this->mockFieldOptions();
        $data = ['option1', 'invalid_option'];

        // Mock the _get_historic_field_options method
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

        $result = $mock->validate($data);

        $this->assertEquals('invalid_selection', $result);
    }

    /**
     * Test validate with empty data
     */
    public function testValidateEmptyData()
    {
        $fieldOptions = $this->mockFieldOptions();
        $data = '';

        // Mock the _get_historic_field_options method
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

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
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with nested field options
     */
    public function testValidateWithNestedOptions()
    {
        $fieldOptions = $this->mockNestedFieldOptions();
        $data = 'option1';

        // Mock the _get_historic_field_options method
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

        $result = $mock->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate with nested field options and invalid value
     */
    public function testValidateWithNestedOptionsInvalidValue()
    {
        $fieldOptions = $this->mockNestedFieldOptions();
        $data = 'invalid_nested_option';

        // Mock the _get_historic_field_options method
        $mock = m::mock(Checkboxes_ft::class)->makePartial();
        $mock->shouldReceive('_get_historic_field_options')->with($data)->andReturn($fieldOptions);
        $mock->shouldReceive('validate')->andReturn(true);
        $this->seedFieldtypeIdentity($mock, $this->fieldtype->settings, $this->fieldtype->settings_vars ?? []);

        $result = $mock->validate($data);

        $this->assertEquals('invalid_selection', $result);
    }
}

// EOF
