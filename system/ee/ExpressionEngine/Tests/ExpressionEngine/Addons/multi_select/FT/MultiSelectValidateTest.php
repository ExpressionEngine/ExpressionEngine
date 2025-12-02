<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../MultiSelectTestBase.php';

/**
 * Test Multi Select fieldtype validate method
 */
class MultiSelectValidateTest extends MultiSelectTestBase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->mockFieldOptions([
            'option1' => 'Option 1',
            'option2' => 'Option 2',
            'option3' => 'Option 3'
        ]);
    }

    /**
     * Test validate method with valid data
     */
    public function testValidateValidData()
    {
        $data = 'option1|option2';
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with single valid value
     */
    public function testValidateSingleValue()
    {
        $data = 'option1';
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with empty data
     */
    public function testValidateEmptyData()
    {
        $data = '';
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with null data
     */
    public function testValidateNullData()
    {
        $data = null;
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with array data
     */
    public function testValidateArrayData()
    {
        $data = ['option1', 'option2'];
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with invalid selection
     */
    public function testValidateInvalidSelection()
    {
        $data = 'invalid_option';
        $result = $this->fieldtype->validate($data);

        $this->assertEquals(lang('invalid_selection'), $result);
    }

    /**
     * Test validate method with mixed valid and invalid selections
     */
    public function testValidateMixedValidInvalid()
    {
        $data = 'option1|invalid_option';
        $result = $this->fieldtype->validate($data);

        $this->assertEquals(lang('invalid_selection'), $result);
    }

    /**
     * Test validate method with only invalid selections
     */
    public function testValidateOnlyInvalid()
    {
        $data = 'invalid1|invalid2';
        $result = $this->fieldtype->validate($data);

        $this->assertEquals(lang('invalid_selection'), $result);
    }

    /**
     * Test validate method with special characters in options
     */
    public function testValidateSpecialCharacters()
    {
        $this->mockFieldOptions([
            'option_1' => 'Option & "Quote"',
            'option_2' => 'Option <tag>'
        ]);

        $data = 'option_1|option_2';
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with numeric option keys
     */
    public function testValidateNumericKeys()
    {
        $this->mockFieldOptions([
            '1' => 'First Option',
            '2' => 'Second Option'
        ]);

        $data = '1|2';
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with empty array
     */
    public function testValidateEmptyArray()
    {
        $data = [];
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with array containing empty values
     */
    public function testValidateArrayWithEmptyValues()
    {
        $data = ['option1', '', 'option2'];
        $result = $this->fieldtype->validate($data);

        $this->assertEquals(lang('invalid_selection'), $result);
    }
}

// EOF

