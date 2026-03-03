<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../RadioTestBase.php';

use Mockery as m;

/**
 * Test for Radio_ft::accepts_content_type() method
 */
class RadioAcceptsContentTypeTest extends RadioTestBase
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
     * Test accepts_content_type with any content type
     */
    public function testAcceptsContentTypeAny()
    {
        $result = $this->fieldtype->accepts_content_type('text');
        $this->assertTrue($result);
    }

    /**
     * Test accepts_content_type with empty string
     */
    public function testAcceptsContentTypeEmptyString()
    {
        $result = $this->fieldtype->accepts_content_type('');
        $this->assertTrue($result);
    }

    /**
     * Test accepts_content_type with null
     */
    public function testAcceptsContentTypeNull()
    {
        $result = $this->fieldtype->accepts_content_type(null);
        $this->assertTrue($result);
    }

    /**
     * Test accepts_content_type with numeric content type
     */
    public function testAcceptsContentTypeNumeric()
    {
        $result = $this->fieldtype->accepts_content_type(123);
        $this->assertTrue($result);
    }

    /**
     * Test accepts_content_type with various content types
     */
    public function testAcceptsContentTypeVarious()
    {
        $contentTypes = [
            'text',
            'html',
            'markdown',
            'json',
            'xml',
            'channel',
            'grid',
            'fluid'
        ];

        foreach ($contentTypes as $contentType) {
            $result = $this->fieldtype->accepts_content_type($contentType);
            $this->assertTrue($result, "Should accept content type: $contentType");
        }
    }

    /**
     * Test that accepts_content_type always returns true
     */
    public function testAcceptsContentTypeAlwaysTrue()
    {
        // Test with various inputs to ensure it always returns true
        $testCases = [
            'text',
            '',
            null,
            123,
            'any_random_string',
            'special_chars_!@#$%',
            true,
            false,
            [],
            new stdClass()
        ];

        foreach ($testCases as $testCase) {
            $result = $this->fieldtype->accepts_content_type($testCase);
            $this->assertTrue($result, "Should always return true for input: " . var_export($testCase, true));
        }
    }
}

// EOF
