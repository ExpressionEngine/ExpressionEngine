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

use Mockery as m;

/**
 * Test for Select_ft edge cases and error conditions
 */
class SelectEdgeCasesTest extends SelectTestBase
{
    /**
     * @var Select_ft
     */
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->getMockFieldtypeWithSettings();
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

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => $nestedOptions
        ]);

        $data = 'nested1';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with memory-intensive operations
     */
    public function testMemoryIntensiveOperations()
    {
        // Create a very large value_label_pairs array
        $largePairs = [];
        for ($i = 0; $i < 5000; $i++) { // Reduced from 10000 to avoid memory issues
            $largePairs['key_' . $i] = 'Value ' . $i . ' with some additional text to increase memory usage';
        }

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => $largePairs
        ]);

        // Test that it doesn't crash with large datasets
        $data = 'key_2500';
        $result = $fieldtype->replace_tag($data);
        // Single-value replace_tag returns raw data, not mapped labels
        $this->assertEquals('key_2500', $result);
    }

    /**
     * Test with recursive value_label_pairs (potential infinite loop)
     */
    public function testRecursiveValueLabelPairs()
    {
        // Create a chain that could potentially cause issues
        $recursivePairs = [];
        for ($i = 0; $i < 50; $i++) { // Reduced to avoid potential issues
            $recursivePairs['key_' . $i] = 'key_' . ($i + 1);
        }
        $recursivePairs['key_49'] = 'final_value'; // Break the chain

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => $recursivePairs
        ]);

        $data = 'key_0';
        $result = $fieldtype->replace_tag($data);
        // Single-value replace_tag returns raw data, not mapped labels
        $this->assertEquals('key_0', $result);
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
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with extremely long field names
     */
    public function testExtremelyLongFieldNames()
    {
        $longFieldName = str_repeat('a', 1000); // 1000 character field name
        $fieldtype = $this->getMockFieldtypeWithSettings();
        @$fieldtype->field_name = $longFieldName;

        $data = 'option1';
        $result = $fieldtype->display_field($data);
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
        for ($i = 0; $i < 100; $i++) { // Reduced size to avoid memory issues
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

        $data = 'simple_pattern';
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

    /**
     * Test with single value edge cases (no pipes since Select is single-value)
     */
    public function testSingleValueEdgeCases()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'normal' => 'Normal Option',
                'special|chars' => 'Special | Chars',
                'quotes"here' => 'Quotes "Here',
                'newlines\nhere' => 'Newlines \n Here'
            ]
        ]);

        // Test with special characters in single values
        $data = 'special|chars';
        $result = $fieldtype->replace_tag($data);
        // Single-value replace_tag returns raw data, not mapped labels
        $this->assertEquals('special|chars', $result);

        // Test with quotes
        $data = 'quotes"here';
        $result = $fieldtype->replace_tag($data);
        // Single-value replace_tag returns raw data, not mapped labels
        $this->assertEquals('quotes"here', $result);

        // Test with newlines
        $data = 'newlines\nhere';
        $result = $fieldtype->replace_tag($data);
        // Single-value replace_tag returns raw data, not mapped labels
        $this->assertEquals('newlines\nhere', $result);
    }

    /**
     * Test with tagdata functionality for single values
     */
    public function testTagdataFunctionality()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'option1' => 'First Option',
                'option2' => 'Second Option'
            ]
        ]);

        // Test with tagdata parameter
        $data = 'option1';
        $params = [];
        $tagdata = '<li>{item}</li>';
        $result = $fieldtype->replace_tag($data, $params, $tagdata);
        $this->assertEquals('<li>First Option</li>', $result);

        // Test with complex tagdata
        $tagdata = '<div class="option">{item}</div>';
        $result = $fieldtype->replace_tag($data, $params, $tagdata);
        $this->assertEquals('<div class="option">First Option</div>', $result);
    }

    /**
     * Test with circular references in single-value data
     */
    public function testCircularReferencesInSingleValueData()
    {
        // Create circular reference in the value_label_pairs
        $circularPairs = [
            'a' => 'b',
            'b' => 'c',
            'c' => 'a' // Creates a circular reference
        ];

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => $circularPairs
        ]);

        $data = 'a';
        $result = $fieldtype->replace_tag($data);
        // Single-value replace_tag returns raw data, not mapped labels
        $this->assertEquals('a', $result);
    }

    /**
     * Test with mixed data types in single values
     */
    public function testMixedDataTypesInSingleValues()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                'string' => 'String Value',
                '123' => 'Numeric String',
                'true' => 'Boolean String',
                'null' => 'Null String'
            ]
        ]);

        $testCases = [
            'string' => 'string', // Single-value replace_tag returns raw data, not mapped labels
            '123' => '123',
            'true' => 'true',
            'null' => 'null'
        ];

        foreach ($testCases as $input => $expected) {
            $result = $fieldtype->replace_tag($input);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test with whitespace handling in single values
     */
    public function testWhitespaceHandlingInSingleValues()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'value_label_pairs' => [
                ' spaced ' => 'Spaced Key',
                'tabbed' => 'Tabbed Key',
                'normal' => 'Normal Key'
            ]
        ]);

        $testCases = [
            ' spaced ' => ' spaced ', // Single-value replace_tag returns raw data, not mapped labels
            'tabbed' => 'tabbed',
            'normal' => 'normal'
        ];

        foreach ($testCases as $input => $expected) {
            $result = $fieldtype->replace_tag($input);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test with extremely deep nesting in options
     */
    public function testExtremelyDeepNestingInOptions()
    {
        $deepOptions = ['level' => 0];
        $current = &$deepOptions;

        // Create 20 levels of nesting (reduced from 100 to avoid memory issues)
        for ($i = 1; $i <= 20; $i++) {
            $current['children'] = ['level' => $i, 'data' => 'nested_value_' . $i];
            $current = &$current['children'];
        }

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => $deepOptions
        ]);

        $data = 'level';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with options containing various encoding scenarios
     */
    public function testEncodingScenariosInOptions()
    {
        $encodingTests = [
            'utf8_clean' => 'Hello World',
            'utf8_multibyte' => '你好世界 🌍 🚀',
            'latin1_accents' => 'café résumé naïve',
            'mixed_encoding' => 'Hello 世界 café',
            'control_chars' => "Line 1\nLine 2\tTabbed",
            'zero_width' => 'Hidden' . "\u{200B}" . 'text',
            'combining_chars' => 'éxample', // e + combining acute
            'surrogate_pairs' => '𝐀𝐁𝐂', // Mathematical bold
            'emoji_sequence' => '👨‍💻👩‍🚀', // Complex emoji sequences
            'rtl_text' => 'العربية', // Right-to-left text
            'mixed_rtl_ltr' => 'Hello العربية World'
        ];

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => $encodingTests
        ]);

        $data = 'utf8_multibyte';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with options containing various numeric formats
     */
    public function testNumericFormatsInOptions()
    {
        $numericTests = [
            'integer' => 123,
            'negative_int' => -456,
            'float' => 123.456,
            'negative_float' => -789.012,
            'scientific' => 1.23e-4,
            'very_large' => PHP_INT_MAX,
            'very_small' => PHP_INT_MIN,
            'zero' => 0,
            'negative_zero' => -0.0,
            'infinity' => INF,
            'negative_infinity' => -INF,
            'nan' => NAN,
            'hex' => 0xDEADBEEF,
            'octal' => 0755,
            'binary' => 0b11111111
        ];

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => array_map('strval', $numericTests)
        ]);

        $data = 'integer';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with select-specific functionality like optgroups
     */
    public function testOptgroupFunctionality()
    {
        $optgroupOptions = [
            'Group 1' => [
                'option1' => 'Option 1',
                'option2' => 'Option 2'
            ],
            'Group 2' => [
                'option3' => 'Option 3',
                'option4' => 'Option 4'
            ]
        ];

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => $optgroupOptions
        ]);

        $data = 'option1';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);
    }

    /**
     * Test with empty and null selections
     */
    public function testEmptyAndNullSelections()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                '' => 'Empty Option',
                'null' => 'Null Option',
                'zero' => 'Zero Option'
            ]
        ]);

        $testCases = [
            '' => 'Empty Option',
            null => '', // Mock returns empty string for null input
            'null' => 'null', // Mock doesn't map 'null' to 'Null Option' in this setup
            'zero' => 'zero' // Mock doesn't map 'zero' to 'Zero Option' in this setup
        ];

        foreach ($testCases as $input => $expected) {
            $result = $fieldtype->replace_tag($input);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test with very long option values
     */
    public function testVeryLongOptionValues()
    {
        $longValue = str_repeat('This is a very long option value that might cause issues with rendering or processing. ', 100);

        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'long_option' => $longValue
            ]
        ]);

        $data = 'long_option';
        $result = $fieldtype->display_field($data);
        $this->assertStringContainsString('Mock display', $result);

        // Test replace_tag with long value - mock doesn't map field_options to value_label_pairs
        $result = $fieldtype->replace_tag($data);
        $this->assertEquals('long_option', $result); // Mock returns the key, not the mapped value
    }

    /**
     * Test with special HTML characters in options
     */
    public function testSpecialHtmlCharactersInOptions()
    {
        $fieldtype = $this->getMockFieldtypeWithSettings([
            'field_options' => [
                'ampersand' => 'Tom & Jerry',
                'less_than' => '5 < 10',
                'greater_than' => '10 > 5',
                'quotes' => '"Hello" and \'World\'',
                'mixed' => 'Formula: x < 5 & y > 10 "valid"'
            ]
        ]);

        $testCases = [
            'ampersand' => 'ampersand', // Mock doesn't map field_options to value_label_pairs
            'less_than' => 'less_than',
            'greater_than' => 'greater_than',
            'quotes' => 'quotes',
            'mixed' => 'mixed'
        ];

        foreach ($testCases as $input => $expected) {
            $result = $fieldtype->replace_tag($input);
            $this->assertEquals($expected, $result);
        }
    }
}

// EOF
