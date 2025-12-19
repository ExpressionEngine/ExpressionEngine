<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/GridModelTestBase.php';

/**
 * Test Grid_model::create_field() method
 */
class GridModelCreateFieldTest extends GridModelTestBase
{
    public function testCreateFieldCreatesTableWhenNotExists()
    {
        $tableExists = false;
        $createTableCalled = false;
        $createTableName = null;

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['table_exists'])
            ->getMock();
        $mockDb->method('table_exists')->willReturn($tableExists);
        ee()->setMock('db', $mockDb);

        $mockDbforge = new class($createTableCalled, $createTableName) {
            private $createTableCalled;
            private $createTableName;
            public $fields = [];
            public $keys = [];
            public function __construct(&$createTableCalled, &$createTableName) {
                $this->createTableCalled = &$createTableCalled;
                $this->createTableName = &$createTableName;
            }
            public function add_field($fields) { 
                $this->fields = array_merge($this->fields, $fields); 
                return $this; 
            }
            public function add_key($key, $primary = false) { 
                $this->keys[] = ['key' => $key, 'primary' => $primary]; 
                return $this; 
            }
            public function create_table($table) { 
                $this->createTableCalled = true;
                $this->createTableName = $table;
                return true; 
            }
        };
        ee()->setMock('dbforge', $mockDbforge);

        $result = $this->model->create_field(5, 'channel');

        $this->assertTrue($result);
        $this->assertTrue($createTableCalled);
        $this->assertEquals('channel_grid_field_5', $createTableName);
    }

    public function testCreateFieldReturnsFalseWhenTableExists()
    {
        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['table_exists'])
            ->getMock();
        $mockDb->method('table_exists')->willReturn(true);
        ee()->setMock('db', $mockDb);

        $result = $this->model->create_field(5, 'channel');

        $this->assertFalse($result);
    }

    public function testCreateFieldAddsRequiredFields()
    {
        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['table_exists'])
            ->getMock();
        $mockDb->method('table_exists')->willReturn(false);
        ee()->setMock('db', $mockDb);

        $this->model->create_field(5, 'channel');

        $fields = $this->mockDbforge->fields;
        $requiredFields = ['row_id', 'entry_id', 'row_order', 'fluid_field_data_id'];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $fields, "Field $field should be defined");
        }

        // Verify row_id is auto_increment
        $this->assertTrue($fields['row_id']['auto_increment']);
    }

    public function testCreateFieldAddsCorrectKeys()
    {
        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['table_exists'])
            ->getMock();
        $mockDb->method('table_exists')->willReturn(false);
        ee()->setMock('db', $mockDb);

        $this->model->create_field(5, 'channel');

        $keys = $this->mockDbforge->keys;
        
        // Verify primary key on row_id
        $rowIdKey = null;
        foreach ($keys as $key) {
            if ($key['key'] === 'row_id' && $key['primary']) {
                $rowIdKey = $key;
                break;
            }
        }
        $this->assertNotNull($rowIdKey, 'Primary key on row_id should be set');

        // Verify index on entry_id
        $entryIdKey = null;
        foreach ($keys as $key) {
            if ($key['key'] === 'entry_id' && !$key['primary']) {
                $entryIdKey = $key;
                break;
            }
        }
        $this->assertNotNull($entryIdKey, 'Index on entry_id should be set');
    }

    public function testCreateFieldGeneratesCorrectTableName()
    {
        $tableNames = [];
        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['table_exists'])
            ->getMock();
        $mockDb->method('table_exists')->willReturnCallback(function($table) use (&$tableNames) {
            $tableNames[] = $table;
            return false;
        });
        ee()->setMock('db', $mockDb);

        $this->model->create_field(10, 'channel');
        $this->model->create_field(20, 'structure');

        $this->assertContains('channel_grid_field_10', $tableNames);
        $this->assertContains('structure_grid_field_20', $tableNames);
    }
}

// EOF
