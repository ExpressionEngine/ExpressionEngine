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
 * Test Grid_model::delete_field() method
 */
class GridModelDeleteFieldTest extends GridModelTestBase
{
    public function testDeleteFieldDropsTableWhenExists()
    {
        $dropTableCalled = false;
        $dropTableName = null;

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['table_exists', 'delete'])
            ->getMock();
        $mockDb->method('table_exists')->willReturn(true);
        $mockDb->method('delete')->willReturn(true);
        ee()->setMock('db', $mockDb);

        $mockDbforge = new class($dropTableCalled, $dropTableName) {
            private $dropTableCalled;
            private $dropTableName;
            public function __construct(&$dropTableCalled, &$dropTableName) {
                $this->dropTableCalled = &$dropTableCalled;
                $this->dropTableName = &$dropTableName;
            }
            public function drop_table($table) {
                $this->dropTableCalled = true;
                $this->dropTableName = $table;
                return true;
            }
        };
        ee()->setMock('dbforge', $mockDbforge);

        $this->model->delete_field(5, 'channel');

        $this->assertTrue($dropTableCalled);
        $this->assertEquals('channel_grid_field_5', $dropTableName);
    }

    public function testDeleteFieldSkipsDropWhenTableNotExists()
    {
        $dropTableCalled = false;

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['table_exists', 'delete'])
            ->getMock();
        $mockDb->method('table_exists')->willReturn(false);
        $mockDb->method('delete')->willReturn(true);
        ee()->setMock('db', $mockDb);

        $mockDbforge = new class($dropTableCalled) {
            private $dropTableCalled;
            public function __construct(&$dropTableCalled) {
                $this->dropTableCalled = &$dropTableCalled;
            }
            public function drop_table($table) {
                $this->dropTableCalled = true;
                return true;
            }
        };
        ee()->setMock('dbforge', $mockDbforge);

        $this->model->delete_field(5, 'channel');

        $this->assertFalse($dropTableCalled);
    }

    public function testDeleteFieldDeletesColumns()
    {
        $deleteCalled = false;
        $deleteTable = null;
        $deleteWhere = null;

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['table_exists', 'delete'])
            ->getMock();
        $mockDb->method('table_exists')->willReturn(false);
        $mockDb->method('delete')->willReturnCallback(function($table, $where) use (&$deleteCalled, &$deleteTable, &$deleteWhere) {
            $deleteCalled = true;
            $deleteTable = $table;
            $deleteWhere = $where;
            return true;
        });
        ee()->setMock('db', $mockDb);

        $this->model->delete_field(5, 'channel');

        $this->assertTrue($deleteCalled);
        $this->assertEquals('grid_columns', $deleteTable);
        $this->assertEquals(['field_id' => 5], $deleteWhere);
    }
}

// EOF
