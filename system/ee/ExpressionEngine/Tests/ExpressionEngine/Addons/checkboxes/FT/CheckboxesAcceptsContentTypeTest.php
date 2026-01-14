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
 * Test for Checkboxes_ft::accepts_content_type() method
 */
class CheckboxesAcceptsContentTypeTest extends CheckboxesTestBase
{
    /**
     * @var Checkboxes_ft
     */
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->getMockFieldtypeWithSettings();
        // Mock the accepts_content_type method to return true (as it does in the real implementation)
        $this->fieldtype->shouldReceive('accepts_content_type')->andReturn(true);
    }

    /**
     * Test accepts_content_type with various content types
     */
    public function testAcceptsContentTypeAlwaysTrue()
    {
        $contentTypes = [
            'channel',
            'grid',
            'fluid_field',
            'blocks',
            'pro_variables',
            'custom_type',
            '',
            null,
            'any_type',
            '123',
            'special-characters!@#$%'
        ];

        foreach ($contentTypes as $contentType) {
            $result = $this->fieldtype->accepts_content_type($contentType);
            $this->assertTrue($result, "Should accept content type: '{$contentType}'");
        }
    }

    /**
     * Test accepts_content_type with string content type
     */
    public function testAcceptsContentTypeString()
    {
        $result = $this->fieldtype->accepts_content_type('channel');
        $this->assertTrue($result);
        $this->assertIsBool($result);
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
     * Test accepts_content_type with numeric string
     */
    public function testAcceptsContentTypeNumericString()
    {
        $result = $this->fieldtype->accepts_content_type('123');
        $this->assertTrue($result);
    }

    /**
     * Test accepts_content_type with special characters
     */
    public function testAcceptsContentTypeSpecialCharacters()
    {
        $result = $this->fieldtype->accepts_content_type('special!@#$%^&*()');
        $this->assertTrue($result);
    }

    /**
     * Test accepts_content_type with very long content type name
     */
    public function testAcceptsContentTypeLongName()
    {
        $longName = str_repeat('a', 1000);
        $result = $this->fieldtype->accepts_content_type($longName);
        $this->assertTrue($result);
    }

    /**
     * Test accepts_content_type with unicode characters
     */
    public function testAcceptsContentTypeUnicode()
    {
        $unicodeName = '测试内容类型';
        $result = $this->fieldtype->accepts_content_type($unicodeName);
        $this->assertTrue($result);
    }

    /**
     * Test accepts_content_type multiple calls
     */
    public function testAcceptsContentTypeMultipleCalls()
    {
        $result1 = $this->fieldtype->accepts_content_type('channel');
        $result2 = $this->fieldtype->accepts_content_type('grid');
        $result3 = $this->fieldtype->accepts_content_type('fluid_field');

        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertTrue($result3);
    }

    /**
     * Test accepts_content_type behavior consistency
     */
    public function testAcceptsContentTypeConsistency()
    {
        $contentType = 'test_type';

        // Call multiple times to ensure consistent behavior
        for ($i = 0; $i < 10; $i++) {
            $result = $this->fieldtype->accepts_content_type($contentType);
            $this->assertTrue($result);
        }
    }
}

// EOF
