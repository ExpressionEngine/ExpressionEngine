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
 * Test Grid_model::delete_columns() method
 */
class GridModelDeleteColumnsTest extends GridModelTestBase
{
    public function testDeleteColumnsDeletesFromTable()
    {
        $deleteCalled = false;
        $deleteTable = null;

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['where_in', 'delete'])
            ->getMock();
        $mockDb->method('where_in')->willReturnSelf();
        $mockDb->method('delete')->willReturnCallback(function($table) use (&$deleteCalled, &$deleteTable) {
            $deleteCalled = true;
            $deleteTable = $table;
            return true;
        });
        ee()->setMock('db', $mockDb);

        $deleteDatatypeCalls = [];
        $mockApiChannelFields = new class($deleteDatatypeCalls) {
            private $deleteDatatypeCalls;
            public function __construct(&$deleteDatatypeCalls) {
                $this->deleteDatatypeCalls = &$deleteDatatypeCalls;
            }
            public function setup_handler($field_type) { return true; }
            public function delete_datatype($col_id, $data = [], $ft_api_settings = []) {
                $this->deleteDatatypeCalls[] = $col_id;
                return true;
            }
        };
        ee()->setMock('api_channel_fields', $mockApiChannelFields);

        $this->model->delete_columns([1, 2, 3], [1 => 'text', 2 => 'textarea', 3 => 'select'], 5, 'channel');

        $this->assertTrue($deleteCalled);
        $this->assertEquals('grid_columns', $deleteTable);
        $this->assertCount(3, $deleteDatatypeCalls);
        $this->assertContains(1, $deleteDatatypeCalls);
        $this->assertContains(2, $deleteDatatypeCalls);
        $this->assertContains(3, $deleteDatatypeCalls);
    }

    public function testDeleteColumnsConvertsSingleIdToArray()
    {
        $whereInCalled = false;
        $whereInField = null;
        $whereInValues = null;

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['where_in', 'delete'])
            ->getMock();
        $mockDb->method('where_in')->willReturnCallback(function($field, $values) use (&$whereInCalled, &$whereInField, &$whereInValues) {
            $whereInCalled = true;
            $whereInField = $field;
            $whereInValues = $values;
            return $this;
        });
        $mockDb->method('delete')->willReturn(true);
        ee()->setMock('db', $mockDb);

        $this->model->delete_columns(5, [5 => 'text'], 10, 'channel');

        $this->assertTrue($whereInCalled);
        $this->assertEquals('col_id', $whereInField);
        $this->assertEquals([5], $whereInValues);
    }
}

// EOF
