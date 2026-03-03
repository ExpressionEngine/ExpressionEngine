<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../MultiSelectTestBase.php';

/**
 * Test Multi Select fieldtype update method
 */
class MultiSelectUpdateTest extends MultiSelectTestBase
{
    /**
     * Test update method returns true
     */
    public function testUpdateMethodReturnsTrue()
    {
        $version = '1.1.0';

        $result = $this->fieldtype->update($version);

        $this->assertTrue($result);
    }

    /**
     * Test update method with different version strings
     */
    public function testUpdateMethodWithVersions()
    {
        $versions = ['1.0.0', '1.1.0', '2.0.0', '3.5.12'];

        foreach ($versions as $version) {
            $result = $this->fieldtype->update($version);
            $this->assertTrue($result, "Update should return true for version: $version");
        }
    }

    /**
     * Test update method with null version
     */
    public function testUpdateMethodWithNullVersion()
    {
        $version = null;

        $result = $this->fieldtype->update($version);

        $this->assertTrue($result);
    }

    /**
     * Test update method with empty version
     */
    public function testUpdateMethodWithEmptyVersion()
    {
        $version = '';

        $result = $this->fieldtype->update($version);

        $this->assertTrue($result);
    }

    /**
     * Test update method with numeric version
     */
    public function testUpdateMethodWithNumericVersion()
    {
        $version = 1.0;

        $result = $this->fieldtype->update($version);

        $this->assertTrue($result);
    }

    /**
     * Test update method with array version (edge case)
     */
    public function testUpdateMethodWithArrayVersion()
    {
        $version = ['version' => '1.1.0'];

        $result = $this->fieldtype->update($version);

        $this->assertTrue($result);
    }

    /**
     * Test update method with very long version string
     */
    public function testUpdateMethodWithLongVersion()
    {
        $version = str_repeat('1.', 100) . '0';

        $result = $this->fieldtype->update($version);

        $this->assertTrue($result);
    }

    /**
     * Test update method with special characters in version
     */
    public function testUpdateMethodWithSpecialVersion()
    {
        $version = '1.0.0-beta+build.123';

        $result = $this->fieldtype->update($version);

        $this->assertTrue($result);
    }

    /**
     * Test update method always returns boolean true
     */
    public function testUpdateMethodAlwaysReturnsBoolean()
    {
        $version = '1.0.0';

        $result = $this->fieldtype->update($version);

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }
}

// EOF

