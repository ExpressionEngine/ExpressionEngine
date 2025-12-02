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
 * Test for Radio_ft::update() method
 */
class RadioUpdateTest extends RadioTestBase
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
     * Test update with version string
     */
    public function testUpdateWithVersion()
    {
        $version = '1.1.0';
        $result = $this->fieldtype->update($version);
        $this->assertTrue($result);
    }

    /**
     * Test update with different version string
     */
    public function testUpdateWithDifferentVersion()
    {
        $version = '2.0.0';
        $result = $this->fieldtype->update($version);
        $this->assertTrue($result);
    }

    /**
     * Test update with empty version string
     */
    public function testUpdateWithEmptyVersion()
    {
        $version = '';
        $result = $this->fieldtype->update($version);
        $this->assertTrue($result);
    }

    /**
     * Test update with null version
     */
    public function testUpdateWithNullVersion()
    {
        $version = null;
        $result = $this->fieldtype->update($version);
        $this->assertTrue($result);
    }

    /**
     * Test update with numeric version
     */
    public function testUpdateWithNumericVersion()
    {
        $version = 123;
        $result = $this->fieldtype->update($version);
        $this->assertTrue($result);
    }

    /**
     * Test that update always returns true
     */
    public function testUpdateAlwaysReturnsTrue()
    {
        // Test with various version inputs to ensure it always returns true
        $testVersions = [
            '1.0.0',
            '2.1.3',
            '0.9.0',
            '',
            null,
            123,
            '1.0',
            'latest',
            'dev-master'
        ];

        foreach ($testVersions as $version) {
            $result = $this->fieldtype->update($version);
            $this->assertTrue($result, "Should always return true for version: " . var_export($version, true));
        }
    }

    /**
     * Test update method signature
     */
    public function testUpdateMethodSignature()
    {
        // Verify the update method exists in the Radio_ft class file
        $filePath = PATH_ADDONS . 'radio/ft.radio.php';
        $this->assertFileExists($filePath, 'Radio_ft class file should exist');

        $fileContents = file_get_contents($filePath);
        $this->assertStringContainsString('public function update(', $fileContents, 'Radio_ft should have update method');

        // Verify the update method returns true (we know this from the implementation)
        $this->assertTrue($this->fieldtype->update('1.0.0'), 'Update method should return true');
    }
}

// EOF
