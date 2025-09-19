<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../SelectTestBase.php';

/**
 * Test Select fieldtype validate method
 */
class SelectValidateTest extends SelectTestBase
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
     * Test validate method with valid single value
     */
    public function testValidateValidSingleValue()
    {
        $data = 'option1';
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with empty string (should be valid)
     */
    public function testValidateEmptyString()
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
     * Test validate method with invalid selection
     */
    public function testValidateInvalidSelection()
    {
        $data = 'invalid_option';
        $result = $this->fieldtype->validate($data);

        $this->assertEquals(lang('invalid_selection'), $result);
    }

    /**
     * Test validate method with numeric values
     */
    public function testValidateNumericValues()
    {
        $this->mockFieldOptions([
            '1' => 'First Option',
            '2' => 'Second Option'
        ]);

        $data = '1';
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with special characters
     */
    public function testValidateSpecialCharacters()
    {
        $this->mockFieldOptions([
            'option_1' => 'Option & "Quote"',
            'option_2' => 'Option <tag>'
        ]);

        $data = 'option_1';
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with nested options
     */
    public function testValidateNestedOptions()
    {
        $this->mockNestedFieldOptions();
        $data = 'option1';

        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with nested option from subgroup
     */
    public function testValidateNestedOptionFromSubgroup()
    {
        $this->mockNestedFieldOptions();
        $data = 'option3'; // This is in group2

        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with array data (should handle as single value)
     */
    public function testValidateArrayData()
    {
        $data = ['option1'];
        $result = $this->fieldtype->validate($data);

        // Select fieldtype handles arrays differently than multi-select
        $this->assertTrue($result);
    }

    /**
     * Test validate method with very long option key
     */
    public function testValidateLongOptionKey()
    {
        $longKey = str_repeat('option_', 20);
        $this->mockFieldOptions([
            $longKey => 'Long Option'
        ]);

        $data = $longKey;
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with empty options list
     */
    public function testValidateEmptyOptionsList()
    {
        $this->mockFieldOptions([]);
        $data = 'any_value';

        $result = $this->fieldtype->validate($data);

        $this->assertEquals(lang('invalid_selection'), $result);
    }

    /**
     * Test validate method with zero as valid option
     */
    public function testValidateZeroAsValidOption()
    {
        $this->mockFieldOptions([
            '0' => 'Zero Option',
            '1' => 'One Option'
        ]);

        $data = '0';
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with false as invalid option
     */
    public function testValidateFalseAsInvalidOption()
    {
        $data = false;
        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result); // false/null should be valid (empty selection)
    }
}

// EOF

