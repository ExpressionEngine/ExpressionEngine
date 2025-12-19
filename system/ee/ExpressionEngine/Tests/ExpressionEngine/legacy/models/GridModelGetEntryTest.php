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
 * Test Grid_model::get_entry() method
 */
class GridModelGetEntryTest extends GridModelTestBase
{
    public function testGetEntryReturnsRowData()
    {
        $expectedRows = [
            ['row_id' => 1, 'entry_id' => 10, 'row_order' => 0],
            ['row_id' => 2, 'entry_id' => 10, 'row_order' => 1]
        ];

        $mockResult = new eeDbResultMock($expectedRows);

        $whereCalls = [];
        $getTable = null;
        $mockDb = new class($mockResult, $whereCalls, $getTable) extends eeDbArMock {
            private $mockResult;
            private $whereCalls;
            private $getTable;
            public function __construct($mockResult, &$whereCalls, &$getTable) {
                $this->mockResult = $mockResult;
                $this->whereCalls = &$whereCalls;
                $this->getTable = &$getTable;
            }
            public function where($field = null, $value = null) {
                $this->whereCalls[] = ['field' => $field, 'value' => $value];
                return $this;
            }
            public function get($table = null) {
                $this->getTable = $table;
                return $this->mockResult;
            }
        };
        ee()->setMock('db', $mockDb);

        $result = $this->model->get_entry(10, 5, 'channel');

        $this->assertEquals($expectedRows, $result);
        $this->assertEquals('channel_grid_field_5', $getTable);
        $this->assertCount(2, $whereCalls);
        $this->assertEquals('entry_id', $whereCalls[0]['field']);
        $this->assertEquals(10, $whereCalls[0]['value']);
        $this->assertEquals('fluid_field_data_id', $whereCalls[1]['field']);
        $this->assertEquals(0, $whereCalls[1]['value']);
    }

    public function testGetEntryWithFluidFieldDataId()
    {
        $expectedRows = [
            ['row_id' => 1, 'entry_id' => 10, 'fluid_field_data_id' => 5]
        ];

        $mockResult = new eeDbResultMock($expectedRows);

        $whereCalls = [];
        $mockDb = new class($mockResult, $whereCalls) extends eeDbArMock {
            private $mockResult;
            private $whereCalls;
            public function __construct($mockResult, &$whereCalls) {
                $this->mockResult = $mockResult;
                $this->whereCalls = &$whereCalls;
            }
            public function where($field = null, $value = null) {
                $this->whereCalls[] = ['field' => $field, 'value' => $value];
                return $this;
            }
            public function get($table = null) {
                return $this->mockResult;
            }
        };
        ee()->setMock('db', $mockDb);

        $result = $this->model->get_entry(10, 5, 'channel', 5);

        $this->assertEquals($expectedRows, $result);
        $this->assertEquals(5, $whereCalls[1]['value']);
    }

    public function testGetEntryReturnsEmptyArrayWhenNoRows()
    {
        $mockResult = new eeDbResultMock([]);

        $mockDb = new class($mockResult) extends eeDbArMock {
            private $mockResult;
            public function __construct($mockResult) {
                $this->mockResult = $mockResult;
            }
            public function where($field = null, $value = null) { return $this; }
            public function get($table = null) { return $this->mockResult; }
        };
        ee()->setMock('db', $mockDb);

        $result = $this->model->get_entry(10, 5, 'channel');

        $this->assertEquals([], $result);
    }
}

// EOF
