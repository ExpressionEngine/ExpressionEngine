<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../RadioTestBase.php';

use Mockery as m;

/**
 * Test for Radio_ft::replace_value() method
 */
class RadioReplaceValueTest extends RadioTestBase
{
    /**
     * @var Radio_ft
     */
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->getMockRadioFieldtypeWithSettings();
    }

    /**
     * Test replace_value with simple value
     */
    public function testReplaceValueSimple()
    {
        $data = 'option1';
        $result = $this->fieldtype->replace_value($data);

        // replace_value is a wrapper for replace_tag, which calls _parse_single
        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_value with different value
     */
    public function testReplaceValueDifferentValue()
    {
        $data = 'option2';
        $result = $this->fieldtype->replace_value($data);
        $this->assertEquals('option2', $result);
    }

    /**
     * Test replace_value with empty string
     */
    public function testReplaceValueEmptyString()
    {
        $data = '';
        $result = $this->fieldtype->replace_value($data);
        $this->assertEquals('', $result);
    }

    /**
     * Test replace_value with null data
     */
    public function testReplaceValueNullData()
    {
        $data = null;
        $result = $this->fieldtype->replace_value($data);
        $this->assertEquals('', $result);
    }

    /**
     * Test replace_value with value that has label mapping
     */
    public function testReplaceValueWithLabelMapping()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'First Option',
                'option2' => 'Second Option',
                'option3' => 'Third Option'
            ]
        ]);

        $data = 'option1';
        $result = $fieldtype->replace_value($data);
        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_value with parameters
     */
    public function testReplaceValueWithParams()
    {
        $data = 'option1';
        $params = ['limit' => 10];
        $result = $this->fieldtype->replace_value($data, $params);
        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_value with tagdata (should not be used for replace_value)
     */
    public function testReplaceValueWithTagdata()
    {
        $data = 'option1';
        $params = [];
        $tagdata = '<li>{item}</li>';
        $result = $this->fieldtype->replace_value($data, $params, $tagdata);
        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_value with numeric value
     */
    public function testReplaceValueNumeric()
    {
        $data = '123';
        $result = $this->fieldtype->replace_value($data);
        $this->assertEquals('123', $result);
    }

    /**
     * Test replace_value with special characters
     */
    public function testReplaceValueSpecialChars()
    {
        $data = 'option_1';
        $result = $this->fieldtype->replace_value($data);
        $this->assertEquals('option_1', $result);
    }

    /**
     * Test that replace_value calls replace_tag internally
     */
    public function testReplaceValueCallsReplaceTag()
    {
        $fieldtype = $this->getMockRadioFieldtypeWithSettings();
        $data = 'option1';

        $result = $fieldtype->replace_value($data);
        $this->assertEquals($data, $result);
    }
}

// EOF
