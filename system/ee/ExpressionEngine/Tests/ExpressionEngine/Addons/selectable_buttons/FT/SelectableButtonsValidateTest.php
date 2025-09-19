<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../SelectableButtonsTestBase.php';

/**
 * Test Selectable Buttons fieldtype validate method
 */
class SelectableButtonsValidateTest extends SelectableButtonsTestBase
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
     * Test validate method with valid data (single selection)
     */
    public function testValidateValidSingleSelection()
    {
        $data = ['option1'];
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with valid data (multiple selections)
     */
    public function testValidateValidMultipleSelections()
    {
        $data = ['option1', 'option2'];
        $this->fieldtype->settings['allow_multiple'] = true;

        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with multiple selections when only single allowed
     */
    public function testValidateMultipleWhenSingleAllowed()
    {
        $data = ['option1', 'option2'];
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->validate($data);

        $this->assertEquals(lang('ft_multiselect_not_allowed'), $result);
    }

    /**
     * Test validate method with single selection when multiple allowed
     */
    public function testValidateSingleWhenMultipleAllowed()
    {
        $data = ['option1'];
        $this->fieldtype->settings['allow_multiple'] = true;

        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with invalid selection
     */
    public function testValidateInvalidSelection()
    {
        $data = ['invalid_option'];
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->validate($data);

        $this->assertEquals(lang('invalid_selection'), $result);
    }

    /**
     * Test validate method with mixed valid and invalid selections
     */
    public function testValidateMixedValidInvalid()
    {
        $data = ['option1', 'invalid_option'];
        $this->fieldtype->settings['allow_multiple'] = true;

        $result = $this->fieldtype->validate($data);

        $this->assertEquals(lang('invalid_selection'), $result);
    }

    /**
     * Test validate method with empty array
     */
    public function testValidateEmptyArray()
    {
        $data = [];
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with null data
     */
    public function testValidateNullData()
    {
        $data = null;
        $this->fieldtype->settings['allow_multiple'] = false;

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

        $data = ['option_1'];
        $this->fieldtype->settings['allow_multiple'] = false;

        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
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

        $data = ['1', '2'];
        $this->fieldtype->settings['allow_multiple'] = true;

        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result);
    }

    /**
     * Test validate method with allow_multiple setting as string
     */
    public function testValidateAllowMultipleAsString()
    {
        $data = ['option1', 'option2'];
        $this->fieldtype->settings['allow_multiple'] = 'y'; // String value

        $result = $this->fieldtype->validate($data);

        $this->assertTrue($result); // Should be treated as true
    }

    /**
     * Test validate method with allow_multiple setting as empty string
     */
    public function testValidateAllowMultipleAsEmptyString()
    {
        $data = ['option1', 'option2'];
        $this->fieldtype->settings['allow_multiple'] = ''; // Empty string

        $result = $this->fieldtype->validate($data);

        $this->assertEquals(lang('ft_multiselect_not_allowed'), $result); // Should be treated as false
    }

    /**
     * Test validate method with missing allow_multiple setting
     */
    public function testValidateMissingAllowMultipleSetting()
    {
        $data = ['option1', 'option2'];
        // Remove allow_multiple setting entirely
        unset($this->fieldtype->settings['allow_multiple']);

        $result = $this->fieldtype->validate($data);

        $this->assertEquals(lang('ft_multiselect_not_allowed'), $result); // Should default to false
    }
}

// EOF

