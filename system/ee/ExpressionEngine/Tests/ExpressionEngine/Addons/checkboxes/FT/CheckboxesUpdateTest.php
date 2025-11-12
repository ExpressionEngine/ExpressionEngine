<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../CheckboxesTestBase.php';

/**
 * Test for Checkboxes_ft::update() method
 */
class CheckboxesUpdateTest extends CheckboxesTestBase
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
     * Test update method always returns true
     */
    public function testUpdateAlwaysReturnsTrue()
    {
        $versions = [
            '1.0.0',
            '1.0.1',
            '1.1.0',
            '2.0.0',
            '2.1.5',
            '3.0.0-beta.1',
            '4.0.0-rc.2',
            '5.0.0',
            '',
            null,
            'invalid.version.format',
            '1.0',
            '1.0.0.0'
        ];

        foreach ($versions as $version) {
            $result = $this->fieldtype->update($version);
            $this->assertTrue($result, "Update should return true for version: '{$version}'");
        }
    }

    /**
     * Test update with string version
     */
    public function testUpdateWithStringVersion()
    {
        $result = $this->fieldtype->update('1.0.0');
        $this->assertTrue($result);
        $this->assertIsBool($result);
    }

    /**
     * Test update with numeric version
     */
    public function testUpdateWithNumericVersion()
    {
        $result = $this->fieldtype->update('1.0.0');
        $this->assertTrue($result);
    }

    /**
     * Test update with empty string
     */
    public function testUpdateWithEmptyString()
    {
        $result = $this->fieldtype->update('');
        $this->assertTrue($result);
    }

    /**
     * Test update with null version
     */
    public function testUpdateWithNullVersion()
    {
        $result = $this->fieldtype->update(null);
        $this->assertTrue($result);
    }

    /**
     * Test update with special characters in version
     */
    public function testUpdateWithSpecialCharacters()
    {
        $result = $this->fieldtype->update('1.0.0-beta.1+build.123');
        $this->assertTrue($result);
    }

    /**
     * Test update with very long version string
     */
    public function testUpdateWithLongVersionString()
    {
        $longVersion = str_repeat('1', 1000) . '.0.0';
        $result = $this->fieldtype->update($longVersion);
        $this->assertTrue($result);
    }

    /**
     * Test update with unicode characters in version
     */
    public function testUpdateWithUnicodeVersion()
    {
        $unicodeVersion = '1.0.0-测试';
        $result = $this->fieldtype->update($unicodeVersion);
        $this->assertTrue($result);
    }

    /**
     * Test update method doesn't modify fieldtype state
     */
    public function testUpdateDoesNotModifyState()
    {
        $originalFieldName = $this->fieldtype->field_name;
        $originalFieldId = $this->fieldtype->field_id;
        $originalSettings = $this->fieldtype->settings;

        $this->fieldtype->update('2.0.0');

        $this->assertEquals($originalFieldName, $this->fieldtype->field_name);
        $this->assertEquals($originalFieldId, $this->fieldtype->field_id);
        $this->assertEquals($originalSettings, $this->fieldtype->settings);
    }

    /**
     * Test update method can be called multiple times
     */
    public function testUpdateMultipleCalls()
    {
        $result1 = $this->fieldtype->update('1.0.0');
        $result2 = $this->fieldtype->update('1.1.0');
        $result3 = $this->fieldtype->update('2.0.0');

        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertTrue($result3);
    }

    /**
     * Test update method behavior consistency
     */
    public function testUpdateConsistency()
    {
        $version = '1.0.0';

        // Call multiple times to ensure consistent behavior
        for ($i = 0; $i < 10; $i++) {
            $result = $this->fieldtype->update($version);
            $this->assertTrue($result);
        }
    }
}

// EOF
