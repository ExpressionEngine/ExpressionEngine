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
 * Test Grid_model::delete_columns_of_type() method
 */
class GridModelDeleteColumnsOfTypeTest extends GridModelTestBase
{
    public function testDeleteColumnsOfTypeDeletesAllColumnsOfType()
    {
        $mockResult = new eeDbResultMock([
            ['col_id' => 1, 'field_id' => 5, 'content_type' => 'channel', 'col_type' => 'text'],
            ['col_id' => 2, 'field_id' => 5, 'content_type' => 'channel', 'col_type' => 'text'],
            ['col_id' => 3, 'field_id' => 10, 'content_type' => 'channel', 'col_type' => 'text']
        ]);

        $whereInCalls = [];
        $mockDb = new class($mockResult, $whereInCalls) extends eeDbArMock {
            private $mockResult;
            private $whereInCalls;
            public function __construct($mockResult, &$whereInCalls) {
                $this->mockResult = $mockResult;
                $this->whereInCalls = &$whereInCalls;
            }
            public function where($field = null, $value = null) { return $this; }
            public function get($table = null) { return $this->mockResult; }
            public function where_in($field, $values) {
                $this->whereInCalls[] = ['field' => $field, 'values' => $values];
                return $this;
            }
            public function delete($table, $where = null) { return true; }
        };
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

        $this->model->delete_columns_of_type('text');

        // Verify delete_columns was called (indirectly via where_in and delete_datatype calls)
        $this->assertGreaterThanOrEqual(1, count($whereInCalls));
        $this->assertGreaterThanOrEqual(3, count($deleteDatatypeCalls));
    }

    public function testDeleteColumnsOfTypeHandlesEmptyResult()
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

        $deleteDatatypeCalled = false;
        $mockApiChannelFields = new class($deleteDatatypeCalled) {
            private $deleteDatatypeCalled;
            public function __construct(&$deleteDatatypeCalled) {
                $this->deleteDatatypeCalled = &$deleteDatatypeCalled;
            }
            public function setup_handler($field_type) { return true; }
            public function delete_datatype($col_id, $data = [], $ft_api_settings = []) {
                $this->deleteDatatypeCalled = true;
                return true;
            }
        };
        ee()->setMock('api_channel_fields', $mockApiChannelFields);

        $this->model->delete_columns_of_type('text');

        $this->assertFalse($deleteDatatypeCalled);
    }
}

// EOF
