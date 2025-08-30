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
 * Test Multi Select fieldtype replace_tag method
 */
class MultiSelectReplaceTagTest extends MultiSelectTestBase
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
     * Test replace_tag method with pipe-delimited data
     */
    public function testReplaceTagPipeDelimited()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        // Should return mapped labels
        $this->assertEquals('Option 1, Option 2', $result);
    }

    /**
     * Test replace_tag method with single value
     */
    public function testReplaceTagSingleValue()
    {
        $data = 'option1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('Option 1', $result);
    }

    /**
     * Test replace_tag method with array data
     */
    public function testReplaceTagArrayData()
    {
        $data = ['option1', 'option2'];
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('Option 1, Option 2', $result);
    }

    /**
     * Test replace_tag method with tagdata
     */
    public function testReplaceTagWithTagdata()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = '<li>{item}</li>';

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        // Should use _parse_multi with tagdata
        $this->assertStringContainsString('<li>option1</li>', $result);
        $this->assertStringContainsString('<li>option2</li>', $result);
    }

    /**
     * Test replace_tag method with limit parameter
     */
    public function testReplaceTagWithLimit()
    {
        $data = 'option1|option2|option3';
        $params = ['limit' => 2];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('Option 1, Option 2', $result);
    }

    /**
     * Test replace_tag method with markup parameter
     */
    public function testReplaceTagWithMarkup()
    {
        $data = 'option1|option2';
        $params = ['markup' => 'ol'];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertStringContainsString('<ol>', $result);
        $this->assertStringContainsString('<li>Option 1</li>', $result);
        $this->assertStringContainsString('<li>Option 2</li>', $result);
        $this->assertStringContainsString('</ol>', $result);
    }

    /**
     * Test replace_tag method with empty data
     */
    public function testReplaceTagEmptyData()
    {
        $data = '';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('', $result);
    }

    /**
     * Test replace_tag method with null data
     */
    public function testReplaceTagNullData()
    {
        $data = null;
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('', $result);
    }

    /**
     * Test replace_tag method with special characters
     */
    public function testReplaceTagSpecialCharacters()
    {
        $this->mockFieldOptions([
            'option1' => 'Option & "Quote"',
            'option2' => 'Option <tag>'
        ]);

        $data = 'option1|option2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('Option & "Quote", Option <tag>', $result);
    }

    /**
     * Test replace_tag method with numeric values
     */
    public function testReplaceTagNumericValues()
    {
        $this->mockFieldOptions([
            '1' => 'First Option',
            '2' => 'Second Option'
        ]);

        $data = '1|2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('First Option, Second Option', $result);
    }

    /**
     * Test replace_tag method with unmapped values
     */
    public function testReplaceTagUnmappedValues()
    {
        $data = 'unknown1|unknown2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('unknown1, unknown2', $result);
    }

    /**
     * Test replace_tag method with mixed mapped and unmapped values
     */
    public function testReplaceTagMixedValues()
    {
        $data = 'option1|unknown|option2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('Option 1, unknown, Option 2', $result);
    }

    /**
     * Test replace_tag method with complex tagdata
     */
    public function testReplaceTagComplexTagdata()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = '<div class="item" data-value="{item}">{item}</div>';

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertStringContainsString('<div class="item" data-value="option1">option1</div>', $result);
        $this->assertStringContainsString('<div class="item" data-value="option2">option2</div>', $result);
    }
}

// EOF

