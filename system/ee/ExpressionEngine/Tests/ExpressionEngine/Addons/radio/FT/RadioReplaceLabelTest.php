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
 * Test for Radio_ft::replace_label() method
 */
class RadioReplaceLabelTest extends RadioTestBase
{
    /**
     * @var Radio_ft
     */
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->getMockFieldtypeWithSettings();
    }

    /**
     * Test replace_label with value that has label mapping
     */
    public function testReplaceLabelWithMapping()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'First Option',
                'option2' => 'Second Option',
                'option3' => 'Third Option'
            ]
        ]);

        $data = 'option1';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('First Option', $result);
    }

    /**
     * Test replace_label with value that doesn't have label mapping
     */
    public function testReplaceLabelWithoutMapping()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'First Option',
                'option2' => 'Second Option'
            ]
        ]);

        $data = 'option3';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('option3', $result);
    }

    /**
     * Test replace_label with empty value
     */
    public function testReplaceLabelEmptyValue()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'First Option'
            ]
        ]);

        $data = '';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('', $result);
    }

    /**
     * Test replace_label with null value
     */
    public function testReplaceLabelNullValue()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'First Option'
            ]
        ]);

        $data = null;
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('', $result);
    }

    /**
     * Test replace_label with numeric value
     */
    public function testReplaceLabelNumericValue()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                '1' => 'First Option',
                '2' => 'Second Option'
            ]
        ]);

        $data = '1';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('First Option', $result);
    }

    /**
     * Test replace_label with special characters in label
     */
    public function testReplaceLabelSpecialChars()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'Option & "Quote" <tag>'
            ]
        ]);

        $data = 'option1';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('Option & "Quote" <tag>', $result);
    }

    /**
     * Test replace_label with empty value_label_pairs setting
     */
    public function testReplaceLabelEmptyPairs()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => []
        ]);

        $data = 'option1';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_label with null value_label_pairs setting
     */
    public function testReplaceLabelNullPairs()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => null
        ]);

        $data = 'option1';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('option1', $result);
    }

    /**
     * Test replace_label with parameters
     */
    public function testReplaceLabelWithParams()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'First Option'
            ]
        ]);

        $data = 'option1';
        $params = ['limit' => 5];
        $result = $fieldtype->replace_label($data, $params);
        $this->assertEquals('First Option', $result);
    }

    /**
     * Test replace_label with tagdata parameter
     */
    public function testReplaceLabelWithTagdata()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'First Option'
            ]
        ]);

        $data = 'option1';
        $params = [];
        $tagdata = '<li>{item}</li>';
        $result = $fieldtype->replace_label($data, $params, $tagdata);
        $this->assertStringContainsString('<li>First Option</li>', $result);
    }

    /**
     * Test that replace_label calls replace_tag internally
     */
    public function testReplaceLabelCallsReplaceTag()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'First Option'
            ]
        ]);

        $data = 'option1';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('First Option', $result);
    }

    /**
     * Test replace_label with very long label values
     */
    public function testReplaceLabelLongValues()
    {
        $longLabel = str_repeat('Very long label text ', 100); // ~2000 characters
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => $longLabel
            ]
        ]);

        $data = 'option1';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals($longLabel, $result);
    }

    /**
     * Test replace_label with HTML entities in labels
     */
    public function testReplaceLabelHtmlEntities()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'Label with &amp; &lt; &gt; &quot; entities',
                'option2' => 'Label with &#39; &#x27; entities'
            ]
        ]);

        $data = 'option1';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('Label with &amp; &lt; &gt; &quot; entities', $result);
    }

    /**
     * Test replace_label with multiline labels
     */
    public function testReplaceLabelMultiline()
    {
        $multilineLabel = "Line 1\nLine 2\nLine 3";
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => $multilineLabel
            ]
        ]);

        $data = 'option1';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals($multilineLabel, $result);
    }

    /**
     * Test replace_label with JSON-like content in labels
     */
    public function testReplaceLabelJsonContent()
    {
        $jsonLabel = '{"key": "value", "array": [1, 2, 3]}';
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => $jsonLabel
            ]
        ]);

        $data = 'option1';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals($jsonLabel, $result);
    }

    /**
     * Test replace_label with circular reference in value_label_pairs
     */
    public function testReplaceLabelCircularReference()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'option2',
                'option2' => 'option1' // Circular reference
            ]
        ]);

        $data = 'option1';
        $result = $fieldtype->replace_label($data);
        // Should not cause infinite loop, just return the mapped value
        $this->assertEquals('option2', $result);
    }

    /**
     * Test replace_label with very large value_label_pairs array
     */
    public function testReplaceLabelLargePairsArray()
    {
        $largePairs = [];
        for ($i = 0; $i < 1000; $i++) {
            $largePairs['key' . $i] = 'Value ' . $i;
        }
        $largePairs['target'] = 'Target Value';

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => $largePairs
        ]);

        $data = 'target';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('Target Value', $result);
    }

    /**
     * Test replace_label with boolean and numeric keys
     */
    public function testReplaceLabelBooleanNumericKeys()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                true => 'True value',
                false => 'False value',
                0 => 'Zero value',
                1 => 'One value',
                'true' => 'String true',
                'false' => 'String false'
            ]
        ]);

        // In PHP, boolean true gets converted to integer 1 when used as array key
        $result1 = $fieldtype->replace_label(true);
        $this->assertEquals('One value', $result1); // true becomes 1

        $result2 = $fieldtype->replace_label(0);
        $this->assertEquals('Zero value', $result2);

        $result3 = $fieldtype->replace_label('true');
        $this->assertEquals('String true', $result3);
    }

    /**
     * Test replace_label with complex nested array in value_label_pairs
     */
    public function testReplaceLabelComplexNestedPairs()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'simple' => 'Simple Value',
                'array' => ['not', 'a', 'string'], // Array as value
                'object' => (object)['key' => 'value'], // Object as value
                'null' => null,
                'empty' => ''
            ]
        ]);

        $result1 = $fieldtype->replace_label('simple');
        $this->assertEquals('Simple Value', $result1);

        $result2 = $fieldtype->replace_label('null');
        $this->assertEquals('null', $result2); // Null is treated as a string key, not null value

        $result3 = $fieldtype->replace_label('empty');
        $this->assertEquals('', $result3);
    }
}

// EOF
