<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../SelectableButtonsTestBase.php';

/**
 * Test Selectable Buttons fieldtype replace_label method
 */
class SelectableButtonsReplaceLabelTest extends SelectableButtonsTestBase
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
     * Test replace_label method with single value
     */
    public function testReplaceLabelSingleValue()
    {
        $data = 'option1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        // Should return the label for the value
        $this->assertEquals('Option 1', $result);
    }

    /**
     * Test replace_label method with pipe-delimited data
     */
    public function testReplaceLabelPipeDelimited()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        // Should return mapped labels
        $this->assertEquals('Option 1, Option 2', $result);
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
     * Test replace_label method with tagdata
     */
    public function testReplaceLabelWithTagdata()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = '<strong>{item}</strong>';

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertStringContainsString('<strong>Option 1</strong>', $result);
        $this->assertStringContainsString('<strong>Option 2</strong>', $result);
    }

    /**
     * Test replace_label method with limit parameter
     */
    public function testReplaceLabelWithLimit()
    {
        $data = 'option1|option2|option3';
        $params = ['limit' => 2];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertEquals('Option 1, Option 2', $result);
    }

    /**
     * Test replace_label method with markup parameter
     */
    public function testReplaceLabelWithMarkup()
    {
        $data = 'option1|option2';
        $params = ['markup' => 'ul'];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>Option 1</li>', $result);
        $this->assertStringContainsString('<li>Option 2</li>', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    /**
     * Test replace_label method with mixed mapped and unmapped values
     */
    public function testReplaceLabelMixedValues()
    {
        $data = 'option1|unknown|option2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_label($data, $params, $tagdata);

        $this->assertEquals('Option 1, unknown, Option 2', $result);
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
}

// EOF

