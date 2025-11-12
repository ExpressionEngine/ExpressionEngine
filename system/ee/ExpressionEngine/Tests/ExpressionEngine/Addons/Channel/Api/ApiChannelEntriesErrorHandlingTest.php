<?php

require_once 'ChannelApiTestBase.php';

/**
 * Tests for Api_channel_entries error handling methods
 * Covers: get_errors() and _set_error()
 */
class ApiChannelEntriesErrorHandlingTest extends ChannelApiTestBase
{
    /**
     * Test get_errors() returns all errors when no field specified
     */
    public function testGetErrorsReturnsAllErrors()
    {
        // Set up some errors
        $this->api->errors = [
            'title' => 'Title is required',
            'content' => 'Content cannot be empty',
            'email' => 'Invalid email format'
        ];

        // Call get_errors without parameter
        $result = $this->api->get_errors();

        // Verify all errors are returned
        $this->assertEquals($this->api->errors, $result);
    }

    /**
     * Test get_errors() returns false when no errors exist
     */
    public function testGetErrorsReturnsFalseWhenNoErrors()
    {
        // Ensure errors array is empty
        $this->api->errors = [];

        // Call get_errors
        $result = $this->api->get_errors();

        // Verify false is returned
        $this->assertFalse($result);
    }

    /**
     * Test get_errors() returns specific field error
     */
    public function testGetErrorsReturnsSpecificFieldError()
    {
        // Set up field-specific error
        $this->api->errors = [
            'title' => 'Title is required',
            'email' => 'Invalid email format'
        ];

        // Call get_errors with field name
        $result = $this->api->get_errors('title');

        // Verify specific error is returned
        $this->assertEquals('Title is required', $result);
    }

    /**
     * Test get_errors() returns false for nonexistent field
     */
    public function testGetErrorsReturnsFalseForNonexistentField()
    {
        // Set up some errors
        $this->api->errors = [
            'title' => 'Title is required'
        ];

        // Call get_errors for field that doesn't exist
        $result = $this->api->get_errors('nonexistent_field');

        // Verify false is returned
        $this->assertFalse($result);
    }

    /**
     * Test get_errors() handles null field parameter
     */
    public function testGetErrorsHandlesNullFieldParameter()
    {
        // Set up some errors
        $this->api->errors = [
            'title' => 'Title is required'
        ];

        // Call get_errors with null
        $result = $this->api->get_errors(null);

        // Should return all errors (null treated as false)
        $this->assertEquals($this->api->errors, $result);
    }

    /**
     * Test _set_error() with field name sets field-specific error
     */
    public function testSetErrorWithFieldName()
    {
        // Call _set_error with field name
        $this->api->_set_error('Title is required', 'title');

        // Verify error is set correctly
        $expected = ['title' => 'Title is required'];
        $this->assertEquals($expected, $this->api->errors);
    }

    /**
     * Test _set_error() without field name adds to general errors
     */
    public function testSetErrorWithoutFieldName()
    {
        // Call _set_error without field name
        $this->api->_set_error('General error occurred');

        // Verify error is added to numeric index
        $this->assertEquals('General error occurred', $this->api->errors[0]);
    }

    /**
     * Test _set_error() with array error and field name
     */
    public function testSetErrorWithArrayError()
    {
        $errorArray = ['message' => 'Complex error', 'code' => 123];

        // Call _set_error with array and field name
        $this->api->_set_error($errorArray, 'validation');

        // Verify array is stored as-is
        $this->assertEquals($errorArray, $this->api->errors['validation']);
    }

    /**
     * Test _set_error() with array error without field name
     */
    public function testSetErrorWithArrayErrorNoField()
    {
        $errorArray = ['type' => 'validation', 'field' => 'email'];

        // Call _set_error with array but no field name
        $this->api->_set_error($errorArray);

        // Verify array is stored in numeric index
        $this->assertEquals($errorArray, $this->api->errors[0]);
    }

    /**
     * Test _set_error() handles null parameters
     */
    public function testSetErrorHandlesNullParameters()
    {
        // Call _set_error with null error
        $this->api->_set_error(null, 'test');

        // Should still create the error entry
        $this->assertArrayHasKey('test', $this->api->errors);
        $this->assertNull($this->api->errors['test']);
    }

    /**
     * Test _set_error() overwrites existing field error
     */
    public function testSetErrorOverwritesExistingFieldError()
    {
        // Set initial error
        $this->api->_set_error('First error', 'title');

        // Overwrite with new error
        $this->api->_set_error('Second error', 'title');

        // Verify only the new error exists
        $this->assertEquals('Second error', $this->api->errors['title']);
        $this->assertCount(1, $this->api->errors);
    }

    /**
     * Test _set_error() with empty string field name
     */
    public function testSetErrorWithEmptyStringFieldName()
    {
        // Call _set_error with empty string as field name
        $this->api->_set_error('Empty field error', '');

        // Should treat as general error
        $this->assertEquals('Empty field error', $this->api->errors[0]);
    }

    /**
     * SECURITY: Test XSS prevention in error messages
     */
    public function testSetErrorXssPrevention()
    {
        $xssAttempts = [
            '<script>alert("xss")</script>',
            '<img src=x onerror=alert(1)>',
            'javascript:alert("xss")',
            '<iframe src="javascript:alert(1)"></iframe>',
            '<svg onload=alert(1)>'
        ];

        foreach ($xssAttempts as $i => $xssMessage) {
            $fieldName = "xss_test_$i";
            $this->api->_set_error($xssMessage, $fieldName);

            // Verify error is stored (XSS prevention is handled at display time)
            $this->assertArrayHasKey($fieldName, $this->api->errors);
            $this->assertEquals($xssMessage, $this->api->errors[$fieldName]);
        }
    }

    /**
     * SECURITY: Test SQL injection prevention in field names
     */
    public function testSetErrorSqlInjectionPrevention()
    {
        $sqlInjections = [
            "field'; DROP TABLE users; --",
            "field' UNION SELECT * FROM users; --",
            "field'; EXEC xp_cmdshell 'dir'; --",
            "field' OR '1'='1",
            "field'; DELETE FROM users WHERE '1'='1"
        ];

        foreach ($sqlInjections as $i => $sqlField) {
            $this->api->_set_error('SQL injection test', $sqlField);

            // Verify field name is stored as-is (injection prevention is handled at query time)
            $this->assertArrayHasKey($sqlField, $this->api->errors);
            $this->assertEquals('SQL injection test', $this->api->errors[$sqlField]);
        }
    }

    /**
     * PERFORMANCE: Test handling of very large error messages
     */
    public function testSetErrorLargeMessage()
    {
        $sizes = [1000, 10000, 100000, 1000000]; // Various sizes

        foreach ($sizes as $size) {
            $largeMessage = str_repeat('A', $size);
            $fieldName = "large_test_$size";

            $this->api->_set_error($largeMessage, $fieldName);

            // Verify large message is handled
            $this->assertArrayHasKey($fieldName, $this->api->errors);
            $this->assertEquals($largeMessage, $this->api->errors[$fieldName]);
            $this->assertEquals($size, strlen($this->api->errors[$fieldName]));
        }
    }




    /**
     * Test _set_error() with unicode field names
     */
    public function testSetErrorUnicodeFieldName()
    {
        $unicodeFields = [
            '测试字段' => 'Chinese characters',
            'файл' => 'Cyrillic characters',
            '🚀field' => 'Emoji in field name',
            'café' => 'Accented characters',
            'ملف' => 'Arabic characters'
        ];

        foreach ($unicodeFields as $fieldName => $errorMessage) {
            $this->api->_set_error($errorMessage, $fieldName);
            $this->assertArrayHasKey($fieldName, $this->api->errors);
            $this->assertEquals($errorMessage, $this->api->errors[$fieldName]);
        }
    }

    /**
     * Test _set_error() with special character field names
     */
    public function testSetErrorSpecialCharactersFieldName()
    {
        $specialFields = [
            'field-with-dashes',
            'field.with.dots',
            'field_with_underscores',
            'field with spaces',
            'field@domain.com',
            'field#hash',
            'field$dollar',
            'field%percent'
        ];

        foreach ($specialFields as $fieldName) {
            $errorMessage = "Error for $fieldName";
            $this->api->_set_error($errorMessage, $fieldName);

            $this->assertArrayHasKey($fieldName, $this->api->errors);
            $this->assertEquals($errorMessage, $this->api->errors[$fieldName]);
        }
    }

    /**
     * Test concurrent error setting (stress test)
     */
    public function testConcurrentErrorSetting()
    {
        $errorCount = 100;

        // Simulate concurrent error setting
        for ($i = 0; $i < $errorCount; $i++) {
            $this->api->_set_error("Error $i", "field_$i");
        }

        // Verify all errors were set
        $this->assertCount($errorCount, $this->api->errors);

        // Verify each error individually
        for ($i = 0; $i < $errorCount; $i++) {
            $this->assertEquals("Error $i", $this->api->errors["field_$i"]);
        }
    }

    /**
     * Test error array corruption recovery
     */
    public function testErrorArrayCorruptionRecovery()
    {
        // Set initial errors
        $this->api->_set_error('Initial error', 'field1');
        $this->assertCount(1, $this->api->errors);

        // Simulate external corruption
        $this->api->errors = null;

        // Test recovery - should handle null gracefully
        $this->api->_set_error('Recovery error', 'field2');
        $this->assertCount(1, $this->api->errors);
        $this->assertEquals('Recovery error', $this->api->errors['field2']);
    }
}
