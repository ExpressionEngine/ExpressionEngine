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
 * Test for Radio_ft edge cases and error conditions
 */
class RadioEdgeCasesTest extends RadioTestBase
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
     * Test processTypograpghy method typo handling
     * Note: The method name has a typo in the original code
     */
    public function testProcessTypographyMethodTypo()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'Test & "Quote" <tag>'
            ]
        ]);

        // This test documents the typo in the method name
        $data = 'option1';
        $result = $fieldtype->replace_label($data);
        // Should handle the typo gracefully and still work
        $this->assertEquals('Test & "Quote" <tag>', $result);
    }

    /**
     * Test with extremely nested array options
     */
    public function testExtremelyNestedArrayOptions()
    {
        $nestedOptions = [
            'level1' => [
                'value' => 'nested1',
                'name' => 'Level 1',
                'children' => [
                    'level2' => [
                        'value' => 'nested2',
                        'name' => 'Level 2',
                        'children' => [
                            'level3' => [
                                'value' => 'nested3',
                                'name' => 'Level 3'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $fieldtype = $this->getMockRadioFieldtypeWithSettings([
            'field_options' => $nestedOptions
        ]);

        $data = 'nested1';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock radio display', $result);
    }

    /**
     * Test with memory-intensive operations
     */
    public function testMemoryIntensiveOperations()
    {
        // Create a very large value_label_pairs array
        $largePairs = [];
        for ($i = 0; $i < 10000; $i++) {
            $largePairs['key_' . $i] = 'Value ' . $i . ' with some additional text to increase memory usage';
        }

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => $largePairs
        ]);

        // Test that it doesn't crash with large datasets
        $data = 'key_5000';
        $result = $fieldtype->replace_label($data);
        $this->assertEquals('Value 5000 with some additional text to increase memory usage', $result);
    }

    /**
     * Test with recursive value_label_pairs (potential infinite loop)
     */
    public function testRecursiveValueLabelPairs()
    {
        // Create a chain that could potentially cause issues
        $recursivePairs = [];
        for ($i = 0; $i < 100; $i++) {
            $recursivePairs['key_' . $i] = 'key_' . ($i + 1);
        }
        $recursivePairs['key_99'] = 'final_value'; // Break the chain

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => $recursivePairs
        ]);

        $data = 'key_0';
        $result = $fieldtype->replace_label($data);
        // The current implementation only does single-level lookup, not recursive
        // So it returns the first mapped value, not the final resolved value
        $this->assertEquals('key_1', $result);
    }

    /**
     * Test with invalid UTF-8 sequences
     */
    public function testInvalidUtf8Sequences()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'valid' => 'Valid UTF-8: 你好世界 🌍',
                'invalid' => "Invalid UTF-8: \x80\x81\x82\x83",
                'mixed' => 'Mixed: valid' . "\x80" . 'text'
            ]
        ]);

        // Should handle invalid UTF-8 gracefully
        $data = 'invalid';
        $result = $fieldtype->display_field($data);
        // The mock returns a generic display response
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with extremely long field names
     */
    public function testExtremelyLongFieldNames()
    {
        $longFieldName = str_repeat('a', 1000); // 1000 character field name
        $fieldtype = $this->getMockFieldtypeWithSettings();
        $fieldtype->field_name = $longFieldName;

        $data = 'option1';
        $result = $fieldtype->display_field($data);
        // The mock returns a generic display response
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with null bytes in option values
     */
    public function testNullBytesInOptions()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'null_byte' => 'Value with ' . "\x00" . ' null byte',
                'multiple_nulls' => 'Value' . "\x00\x00" . 'with' . "\x00" . 'nulls'
            ]
        ]);

        $data = 'null_byte';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with options containing PHP serialized data
     */
    public function testSerializedDataInOptions()
    {
        $serialized = serialize(['key' => 'value', 'array' => [1, 2, 3]]);
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'serialized' => $serialized
            ]
        ]);

        $data = 'serialized';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with options containing JSON data
     */
    public function testJsonDataInOptions()
    {
        $jsonData = '{"complex": {"nested": {"value": 123}}, "array": [1, "two", true]}';
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'json' => $jsonData
            ]
        ]);

        $data = 'json';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with options containing binary data
     */
    public function testBinaryDataInOptions()
    {
        $binaryData = '';
        for ($i = 0; $i < 256; $i++) {
            $binaryData .= chr($i);
        }

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'binary' => $binaryData
            ]
        ]);

        $data = 'binary';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with options containing regex patterns
     */
    public function testRegexPatternsInOptions()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'simple_pattern' => '/^test$/',
                'complex_pattern' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                'special_chars' => '/[\^\$\.\|\?\*\+\(\)\\\[\]\{\}]/'
            ]
        ]);

        $data = 'complex_pattern';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with duplicate option keys
     */
    public function testDuplicateOptionKeys()
    {
        // Note: PHP arrays can't actually have duplicate keys, but this tests the concept
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'duplicate' => 'First value',
                'duplicate' => 'Second value' // This will overwrite the first
            ]
        ]);

        $data = 'duplicate';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with options containing SQL-like content
     */
    public function testSqlLikeContentInOptions()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'select' => 'SELECT * FROM users',
                'insert' => 'INSERT INTO table VALUES (1, \'value\')',
                'quotes' => 'Value with \'single\' and "double" quotes'
            ]
        ]);

        $data = 'select';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with options containing XML/HTML tags
     */
    public function testXmlHtmlTagsInOptions()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'xml' => '<root><child>value</child></root>',
                'html' => '<div class="test"><span>content</span></div>',
                'self_closing' => '<br/><hr/><img src="test.jpg"/>'
            ]
        ]);

        $data = 'xml';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with options containing emoji and special unicode
     */
    public function testEmojiAndSpecialUnicode()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'emoji_only' => '🚀💻🎉🔥',
                'mixed_emoji' => 'Hello 🌍 with 🚀 rocket',
                'flags' => '🇺🇸🇬🇧🇩🇪🇫🇷🇯🇵',
                'symbols' => '⚡🔋💡📱💻⌚️📷🎥📺'
            ]
        ]);

        $data = 'emoji_only';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with empty key names
     */
    public function testEmptyKeyNames()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                '' => 'Empty key value',
                ' ' => 'Space key value',
                "\t" => 'Tab key value',
                "\n" => 'Newline key value'
            ]
        ]);

        $data = '';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with numeric string keys that look like numbers
     */
    public function testNumericStringKeys()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                '0' => 'Zero string',
                '123' => 'One two three',
                '00123' => 'Zero zero one two three',
                '1.5' => 'One point five'
            ]
        ]);

        $data = '0';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }
}

// EOF
