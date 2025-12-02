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
 * Test Select fieldtype replace_value method
 */
class SelectReplaceValueTest extends SelectTestBase
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
     * Test replace_value method returns the raw value
     */
    public function testReplaceValueReturnsRawValue()
    {
        $data = 'option1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        // Should return the raw value, not the label
        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_value method with empty data
     */
    public function testReplaceValueEmptyData()
    {
        $data = '';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('', $result);
    }

    /**
     * Test replace_value method with null data
     */
    public function testReplaceValueNullData()
    {
        $data = null;
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('', $result);
    }

    /**
     * Test replace_value method with numeric value
     */
    public function testReplaceValueNumericValue()
    {
        $data = '123';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('123', $result);
    }

    /**
     * Test replace_value method with special characters
     */
    public function testReplaceValueSpecialCharacters()
    {
        $data = 'option & "quote"';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('option & "quote"', $result);
    }

    /**
     * Test replace_value method with parameters
     */
    public function testReplaceValueWithParams()
    {
        $data = 'option1';
        $params = ['some_param' => 'value'];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_value method with tagdata
     */
    public function testReplaceValueWithTagdata()
    {
        $data = 'option1';
        $params = [];
        $tagdata = '<span>{item}</span>';

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertStringContainsString('<span>option1</span>', $result);
    }

    /**
     * Test replace_value method with boolean value
     */
    public function testReplaceValueBooleanValue()
    {
        $data = '1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('1', $result);
    }

    /**
     * Test replace_value method with zero value
     */
    public function testReplaceValueZeroValue()
    {
        $data = '0';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('0', $result);
    }

    /**
     * Test replace_value method with very long value
     */
    public function testReplaceValueLongValue()
    {
        $longValue = str_repeat('option_', 20);
        $data = $longValue;
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals($longValue, $result);
    }
}

// EOF

