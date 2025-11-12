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
 * Test Select fieldtype replace_label method
 */
class SelectReplaceLabelTest extends SelectTestBase
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
     * Test replace_label method with mapped value
     */
    public function testReplaceLabelMappedValue()
    {
        $data = 'option1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        // Should return the label, not the value
        $this->assertEquals('Option 1', $result);
    }

    /**
     * Test replace_label method with unmapped value
     */
    public function testReplaceLabelUnmappedValue()
    {
        $data = 'unknown_option';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        // Should return the original data if no mapping found
        $this->assertEquals('unknown_option', $result);
    }

    /**
     * Test replace_label method with empty data
     */
    public function testReplaceLabelEmptyData()
    {
        $data = '';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertEquals('', $result);
    }

    /**
     * Test replace_label method with null data
     */
    public function testReplaceLabelNullData()
    {
        $data = null;
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertEquals('', $result);
    }

    /**
     * Test replace_label method with numeric value
     */
    public function testReplaceLabelNumericValue()
    {
        $this->mockFieldOptions([
            '1' => 'First Option',
            '2' => 'Second Option'
        ]);

        $data = '1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertEquals('First Option', $result);
    }

    /**
     * Test replace_label method with special characters in label
     */
    public function testReplaceLabelSpecialCharacters()
    {
        $this->mockFieldOptions([
            'option1' => 'Option & "Quote"',
            'option2' => 'Option <tag>'
        ]);

        $data = 'option1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertEquals('Option & "Quote"', $result);
    }

    /**
     * Test replace_label method with parameters
     */
    public function testReplaceLabelWithParams()
    {
        $data = 'option1';
        $params = ['some_param' => 'value'];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertEquals('Option 1', $result);
    }

    /**
     * Test replace_label method with tagdata
     */
    public function testReplaceLabelWithTagdata()
    {
        $data = 'option1';
        $params = [];
        $tagdata = '<strong>{item}</strong>';

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertStringContainsString('<strong>Option 1</strong>', $result);
    }

    /**
     * Test replace_label method with nested options
     */
    public function testReplaceLabelNestedOptions()
    {
        $this->mockNestedFieldOptions();
        $data = 'option1';

        $result = $this->fieldtype->replace_label($data, [], false);

        $this->assertEquals('Option 1', $result);
    }

    /**
     * Test replace_label method with zero value
     */
    public function testReplaceLabelZeroValue()
    {
        $this->mockFieldOptions([
            '0' => 'Zero Option',
            '1' => 'One Option'
        ]);

        $data = '0';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertEquals('Zero Option', $result);
    }

    /**
     * Test replace_label method with very long label
     */
    public function testReplaceLabelLongLabel()
    {
        $longLabel = str_repeat('A very long label ', 10);
        $this->mockFieldOptions([
            'option1' => $longLabel
        ]);

        $data = 'option1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertEquals($longLabel, $result);
    }

    /**
     * Test replace_label method with boolean value
     */
    public function testReplaceLabelBooleanValue()
    {
        $this->mockFieldOptions([
            '1' => 'True Option',
            '0' => 'False Option'
        ]);

        $data = '1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertEquals('True Option', $result);
    }
}

// EOF

