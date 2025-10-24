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
 * Test Selectable Buttons fieldtype replace_value method
 */
class SelectableButtonsReplaceValueTest extends SelectableButtonsTestBase
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
     * Test replace_value method with single value
     */
    public function testReplaceValueSingleValue()
    {
        $data = 'option1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        // Should return the raw value processed through _parse_single
        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_value method with pipe-delimited data
     */
    public function testReplaceValuePipeDelimited()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        // Should return the raw values
        $this->assertEquals('option1, option2', $result);
    }

    /**
     * Test replace_value method with array data
     */
    public function testReplaceValueArrayData()
    {
        $data = ['option1', 'option2'];
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('option1, option2', $result);
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
     * Test replace_value method with special characters
     */
    public function testReplaceValueSpecialCharacters()
    {
        $data = 'option & "quote"|option2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('option & "quote", option2', $result);
    }

    /**
     * Test replace_value method with unmapped values
     */
    public function testReplaceValueUnmappedValues()
    {
        $data = 'unknown1|unknown2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('unknown1, unknown2', $result);
    }

    /**
     * Test replace_value method with mixed mapped and unmapped values
     */
    public function testReplaceValueMixedValues()
    {
        $data = 'option1|unknown|option2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('option1, unknown, option2', $result);
    }

    /**
     * Test replace_value method with limit parameter
     */
    public function testReplaceValueWithLimit()
    {
        $data = 'option1|option2|option3';
        $params = ['limit' => 2];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('option1, option2', $result);
    }

    /**
     * Test replace_value method with markup parameter
     */
    public function testReplaceValueWithMarkup()
    {
        $data = 'option1|option2';
        $params = ['markup' => 'ol'];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertStringContainsString('<ol>', $result);
        $this->assertStringContainsString('<li>option1</li>', $result);
        $this->assertStringContainsString('<li>option2</li>', $result);
        $this->assertStringContainsString('</ol>', $result);
    }

    /**
     * Test replace_value method with tagdata
     */
    public function testReplaceValueWithTagdata()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = '<span>{item}</span>';

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertStringContainsString('<span>option1</span>', $result);
        $this->assertStringContainsString('<span>option2</span>', $result);
    }

    /**
     * Test replace_value method with numeric values
     */
    public function testReplaceValueNumericValues()
    {
        $this->mockFieldOptions([
            '1' => 'First Option',
            '2' => 'Second Option'
        ]);

        $data = '1|2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertEquals('1, 2', $result);
    }

    /**
     * Test replace_value method with complex tagdata
     */
    public function testReplaceValueComplexTagdata()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = '<div class="item" data-value="{item}">{item}</div>';

        $result = $this->fieldtype->replace_value($data, $params, $tagdata);

        $this->assertStringContainsString('<div class="item" data-value="option1">option1</div>', $result);
        $this->assertStringContainsString('<div class="item" data-value="option2">option2</div>', $result);
    }
}

// EOF
