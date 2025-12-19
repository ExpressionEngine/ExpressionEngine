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
 * Test Grid_model::uninstall() method
 */
class GridModelUninstallTest extends GridModelTestBase
{
    public function testUninstallDeletesFieldsAndDropsTable()
    {
        // Mock db->select->distinct->get->result_array to return field IDs
        $mockResult = new eeDbResultMock([
            ['field_id' => 1, 'content_type' => 'channel'],
            ['field_id' => 2, 'content_type' => 'channel']
        ]);

        // Track delete_field calls by checking dbforge drop_table calls
        $dropTableCalls = [];
        $mockDbforge = new class($dropTableCalls) {
            private $dropTableCalls;
            public function __construct(&$dropTableCalls) {
                $this->dropTableCalls = &$dropTableCalls;
            }
            public function drop_table($table) {
                $this->dropTableCalls[] = $table;
                return true;
            }
        };
        ee()->setMock('dbforge', $mockDbforge);

        // Mock db chain
        $mockDb = new class($mockResult) extends eeDbArMock {
            private $mockResult;
            public function __construct($mockResult) {
                $this->mockResult = $mockResult;
            }
            public function select($field = '*') { return $this; }
            public function distinct() { return $this; }
            public function get($table = null) { return $this->mockResult; }
            public function delete($table, $where = null) { return true; }
            public function table_exists($table) { return false; }
        };
        
        ee()->setMock('db', $mockDb);

        $this->model->uninstall();

        // Verify delete_field was called (indirectly via drop_table calls)
        // Should have drop_table called for each field table plus grid_columns
        $this->assertGreaterThanOrEqual(1, count($dropTableCalls));
    }

    public function testUninstallDropsGridColumnsTable()
    {
        $mockResult = new eeDbResultMock([]);

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['select', 'distinct', 'get', 'delete'])
            ->getMock();
        
        $mockDb->method('select')->willReturnSelf();
        $mockDb->method('distinct')->willReturnSelf();
        $mockDb->method('get')->willReturn($mockResult);
        $mockDb->method('delete')->willReturn(true);
        
        ee()->setMock('db', $mockDb);

        $dropTableCalled = false;
        $dropTableName = null;
        $mockDbforge = $this->getMockBuilder('stdClass')
            ->addMethods(['drop_table'])
            ->getMock();
        $mockDbforge->method('drop_table')->willReturnCallback(function($table) use (&$dropTableCalled, &$dropTableName) {
            $dropTableCalled = true;
            $dropTableName = $table;
            return true;
        });
        ee()->setMock('dbforge', $mockDbforge);

        $this->model->uninstall();

        $this->assertTrue($dropTableCalled);
        $this->assertEquals('grid_columns', $dropTableName);
    }

    public function testUninstallDeletesContentType()
    {
        $mockResult = new eeDbResultMock([]);

        $deleteCalled = false;
        $deleteTable = null;
        $deleteWhere = null;
        
        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['select', 'distinct', 'get', 'delete'])
            ->getMock();
        
        $mockDb->method('select')->willReturnSelf();
        $mockDb->method('distinct')->willReturnSelf();
        $mockDb->method('get')->willReturn($mockResult);
        $mockDb->method('delete')->willReturnCallback(function($table, $where) use (&$deleteCalled, &$deleteTable, &$deleteWhere) {
            $deleteCalled = true;
            $deleteTable = $table;
            $deleteWhere = $where;
            return true;
        });
        
        ee()->setMock('db', $mockDb);

        $this->model->uninstall();

        $this->assertTrue($deleteCalled);
        $this->assertEquals('content_types', $deleteTable);
        $this->assertEquals(['name' => 'grid'], $deleteWhere);
    }
}

// EOF
