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

/**
 * Test for Checkboxes_ft::replace_length() method
 */
class CheckboxesReplaceLengthTest extends CheckboxesTestBase
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
     * Test replace_length with multiple values
     */
    public function testReplaceLengthMultipleValues()
    {
        $data = 'option1|option2|option3';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_length($data, $params, $tagdata);

        $this->assertEquals(3, $result);
    }

    /**
     * Test replace_length with single value
     */
    public function testReplaceLengthSingleValue()
    {
        $data = 'option1';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_length($data, $params, $tagdata);

        $this->assertEquals(1, $result);
    }

    /**
     * Test replace_length with empty data
     */
    public function testReplaceLengthEmptyData()
    {
        $data = '';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_length($data, $params, $tagdata);

        $this->assertEquals(0, $result);
    }

    /**
     * Test replace_length with null data
     */
    public function testReplaceLengthNullData()
    {
        $data = null;
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_length($data, $params, $tagdata);

        $this->assertEquals(0, $result);
    }

    /**
     * Test replace_length with array data
     */
    public function testReplaceLengthArrayData()
    {
        $data = ['option1', 'option2'];
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_length($data, $params, $tagdata);

        $this->assertEquals(2, $result);
    }

    /**
     * Test replace_length with encoded data containing pipes
     */
    public function testReplaceLengthWithEncodedPipes()
    {
        // Test with data that contains escaped pipes
        $data = 'option1\|with\|pipes|option2';
        $params = [];
        $tagdata = false;

        $result = $this->fieldtype->replace_length($data, $params, $tagdata);

        $this->assertEquals(2, $result);
    }

    /**
     * Test replace_length with params (should be ignored)
     */
    public function testReplaceLengthWithParams()
    {
        $data = 'option1|option2';
        $params = ['some_param' => 'value'];
        $tagdata = false;

        $result = $this->fieldtype->replace_length($data, $params, $tagdata);

        $this->assertEquals(2, $result);
    }

    /**
     * Test replace_length with tagdata (should be ignored)
     */
    public function testReplaceLengthWithTagdata()
    {
        $data = 'option1|option2';
        $params = [];
        $tagdata = '{item}';

        $result = $this->fieldtype->replace_length($data, $params, $tagdata);

        $this->assertEquals(2, $result);
    }
}

// EOF
