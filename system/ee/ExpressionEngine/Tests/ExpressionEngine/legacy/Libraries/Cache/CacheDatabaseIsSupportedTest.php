<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\Cache;

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

class CacheDatabaseIsSupportedTest extends CacheDatabaseTestBase
{
    /**
     * Test that is_supported returns true when table exists
     */
    public function testIsSupportedReturnsTrueWhenTableExists()
    {
        ee()->db->tableExistsReturn = true;

        $driver = $this->makeDatabaseDriver();
        $result = $driver->is_supported();

        $this->assertTrue($result);
    }

    /**
     * Test that is_supported returns false when table is missing
     */
    public function testIsSupportedReturnsFalseWhenTableMissing()
    {
        ee()->db->tableExistsReturn = false;

        $driver = $this->makeDatabaseDriver();
        $result = $driver->is_supported();

        $this->assertFalse($this->mockDbforge->createTableCalled);
        $this->assertFalse($result);
    }

    /**
     * Test that is_supported returns false when no database connection
     */
    public function testIsSupportedReturnsFalseWhenNoDatabaseConnection()
    {
        // Remove the db mock to simulate no connection
        ee()->setMock('db', null);

        $driver = $this->makeDatabaseDriver();
        $result = $driver->is_supported();

        $this->assertFalse($result);
    }

    /**
     * Test that is_supported returns false when table doesn't exist
     * (table creation is now handled by installer/updater, not the driver)
     */
    public function testIsSupportedReturnsFalseWhenTableDoesNotExist()
    {
        ee()->db->tableExistsReturn = false;

        $driver = $this->makeDatabaseDriver();
        $result = $driver->is_supported();

        $this->assertFalse($this->mockDbforge->createTableCalled);
        $this->assertFalse($result);
    }

    /**
     * Test that is_supported checks for the correct table name
     */
    public function testIsSupportedUsesCorrectTableName()
    {
        $driver = $this->makeDatabaseDriver();
        
        // Use reflection to get the protected _cache_table property
        $cacheTable = $this->getProtectedProperty($driver, '_cache_table');
        
        $this->assertEquals('cache', $cacheTable);
    }
}

