<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../SelectTestBase.php';

/**
 * Test Select fieldtype replace_tag method
 */
class SelectReplaceTagTest extends SelectTestBase
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
     * Test replace_tag method with single value
     */
    public function testReplaceTagSingleValue()
    {
        $data = 'option1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        // Should return the data as-is for single select
        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_tag method with tagdata
     */
    public function testReplaceTagWithTagdata()
    {
        $data = 'option1';
        $params = [];
        $tagdata = '<li>{item}</li>';

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        // Should do value-label mapping when tagdata is provided
        $this->assertStringContainsString('<li>Option 1</li>', $result);
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
        $data = 'option & "quote"';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('option & "quote"', $result);
    }

    /**
     * Test replace_tag method with numeric value
     */
    public function testReplaceTagNumericValue()
    {
        $data = '123';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('123', $result);
    }

    /**
     * Test replace_tag method with complex tagdata
     */
    public function testReplaceTagComplexTagdata()
    {
        $data = 'option1';
        $params = [];
        $tagdata = '<div class="item" data-value="{item}">{item}</div>';

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertStringContainsString('<div class="item" data-value="Option 1">Option 1</div>', $result);
    }

    /**
     * Test replace_tag method with parameters
     */
    public function testReplaceTagWithParams()
    {
        $data = 'option1';
        $params = ['limit' => 1];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('option1', $result);
    }
}

// EOF

