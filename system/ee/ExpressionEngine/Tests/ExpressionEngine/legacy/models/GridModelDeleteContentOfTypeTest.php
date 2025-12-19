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
 * Test Grid_model::delete_content_of_type() method
 */
class GridModelDeleteContentOfTypeTest extends GridModelTestBase
{
    public function testDeleteContentOfTypeDropsAllTables()
    {
        $dropTableCalls = [];

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['list_tables', 'delete'])
            ->getMock();
        $mockDb->method('list_tables')->willReturn([
            'channel_grid_field_1',
            'channel_grid_field_2',
            'channel_grid_field_3'
        ]);
        $mockDb->method('delete')->willReturn(true);
        ee()->setMock('db', $mockDb);

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

        $this->model->delete_content_of_type('channel');

        $this->assertCount(3, $dropTableCalls);
        $this->assertContains('channel_grid_field_1', $dropTableCalls);
        $this->assertContains('channel_grid_field_2', $dropTableCalls);
        $this->assertContains('channel_grid_field_3', $dropTableCalls);
    }

    public function testDeleteContentOfTypeDeletesColumns()
    {
        $deleteCalled = false;
        $deleteTable = null;
        $deleteWhere = null;

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['list_tables', 'delete'])
            ->getMock();
        $mockDb->method('list_tables')->willReturn([]);
        $mockDb->method('delete')->willReturnCallback(function($table, $where) use (&$deleteCalled, &$deleteTable, &$deleteWhere) {
            $deleteCalled = true;
            $deleteTable = $table;
            $deleteWhere = $where;
            return true;
        });
        ee()->setMock('db', $mockDb);

        $this->model->delete_content_of_type('channel');

        $this->assertTrue($deleteCalled);
        $this->assertEquals('grid_columns', $deleteTable);
        $this->assertEquals(['content_type' => 'channel'], $deleteWhere);
    }

    public function testDeleteContentOfTypeHandlesEmptyTableList()
    {
        $dropTableCalls = [];

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['list_tables', 'delete'])
            ->getMock();
        $mockDb->method('list_tables')->willReturn([]);
        $mockDb->method('delete')->willReturn(true);
        ee()->setMock('db', $mockDb);

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

        $this->model->delete_content_of_type('channel');

        $this->assertCount(0, $dropTableCalls);
    }
}

// EOF
