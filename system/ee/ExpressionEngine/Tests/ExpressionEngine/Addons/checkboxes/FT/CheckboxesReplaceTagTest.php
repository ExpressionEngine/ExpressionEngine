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
 * Test for Checkboxes_ft::replace_tag() method
 */
class CheckboxesReplaceTagTest extends CheckboxesTestBase
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
     * Test replace_tag with simple data and no tagdata
     */
    public function testReplaceTagSimpleDataNoTagdata()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        // Should return the decoded data as a comma-separated string
        $this->assertEquals('option1, option2', $result);
    }

    /**
     * Test replace_tag with encoded data
     */
    public function testReplaceTagWithEncodedData()
    {
        $data = encode_multi_field(['option1', 'option2']);
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        // Should return the data as a comma-separated string
        $this->assertEquals('option1, option2', $result);
    }

    /**
     * Test replace_tag with tagdata (variable pair)
     */
    public function testReplaceTagWithTagdata()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = '{item}<br>';

        // Mock the _parse_multi method on the existing fieldtype mock
        $this->fieldtype->shouldReceive('_parse_multi')
             ->with(['option1', 'option2'], $params, $tagdata)
             ->andReturn('option1<br>option2<br>');

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('option1<br>option2<br>', $result);
    }

    /**
     * Test replace_tag with empty data
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
     * Test replace_tag with single value
     */
    public function testReplaceTagSingleValue()
    {
        $data = 'option1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_tag with array data
     */
    public function testReplaceTagArrayData()
    {
        $data = ['option1', 'option2'];
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        $this->assertEquals('option1, option2', $result);
    }

    /**
     * Test replace_tag with params
     */
    public function testReplaceTagWithParams()
    {
        $data = 'option1|option2';
        $params = ['limit' => 1];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        // Should return only the first item due to limit
        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_tag with value-label pairs
     */
    public function testReplaceTagWithValueLabelPairs()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = false;

        // Set up value-label pairs in settings
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'Option One',
                'option2' => 'Option Two'
            ]
        ]);

        $result = $fieldtype->replace_tag($data, $params, $tagdata);

        // Should return the labels
        $this->assertEquals('Option One, Option Two', $result);
    }

    /**
     * Test replace_tag with markup parameter
     */
    public function testReplaceTagWithMarkup()
    {
        $data = 'option1|option2';
        $params = ['markup' => 'ul'];
        $tagdata = false;

        $result = $this->fieldtype->replace_tag($data, $params, $tagdata);

        // Should return unordered list markup
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>option1</li>', $result);
        $this->assertStringContainsString('<li>option2</li>', $result);
        $this->assertStringContainsString('</ul>', $result);
    }
}

// EOF
