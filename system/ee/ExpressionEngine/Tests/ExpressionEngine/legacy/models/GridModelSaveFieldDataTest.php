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
 * Test Grid_model::save_field_data() method
 */
class GridModelSaveFieldDataTest extends GridModelTestBase
{
    public function testSaveFieldDataInsertsNewRows()
    {
        $insertBatchCalled = false;
        $insertBatchTable = null;
        $insertBatchData = null;

        $mockResult = new eeDbResultMock([]);

        $mockDb = new class($mockResult, $insertBatchCalled, $insertBatchTable, $insertBatchData) extends eeDbArMock {
            private $mockResult;
            private $insertBatchCalled;
            private $insertBatchTable;
            private $insertBatchData;
            public function __construct($mockResult, &$insertBatchCalled, &$insertBatchTable, &$insertBatchData) {
                $this->mockResult = $mockResult;
                $this->insertBatchCalled = &$insertBatchCalled;
                $this->insertBatchTable = &$insertBatchTable;
                $this->insertBatchData = &$insertBatchData;
            }
            public function select($field = '*') { return $this; }
            public function where($field = null, $value = null) { return $this; }
            public function where_not_in($field, $values) { return $this; }
            public function get($table = null) { return $this->mockResult; }
            public function insert_batch($table, $data) {
                $this->insertBatchCalled = true;
                $this->insertBatchTable = $table;
                $this->insertBatchData = $data;
                return true;
            }
            public function update_batch($table, $data, $key) { return true; }
        };
        ee()->setMock('db', $mockDb);

        $data = [
            'new_row_1' => ['col_id_1' => 'value1', 'row_order' => 0],
            'new_row_2' => ['col_id_1' => 'value2', 'row_order' => 1]
        ];

        $result = $this->model->save_field_data($data, 5, 'channel', 10);

        $this->assertTrue($insertBatchCalled);
        $this->assertEquals('channel_grid_field_5', $insertBatchTable);
        $this->assertCount(2, $insertBatchData);
        $this->assertEquals(10, $insertBatchData[0]['entry_id']);
    }

    public function testSaveFieldDataUpdatesExistingRows()
    {
        // Ensure CLONING_MODE is not defined (it would change behavior)
        if (defined('CLONING_MODE')) {
            $this->markTestSkipped('CLONING_MODE is defined, which changes save_field_data behavior');
        }

        $updateBatchCalled = false;
        $updateBatchData = null;

        // Mock result for deleted rows query - should return empty since we're updating existing rows
        $mockResult = new eeDbResultMock([]);

        // Create mock that properly tracks update_batch calls
        $mockDb = new class($mockResult, $updateBatchCalled, $updateBatchData) extends eeDbArMock {
            private $mockResult;
            private $updateBatchCalled;
            private $updateBatchData;
            public function __construct($mockResult, &$updateBatchCalled, &$updateBatchData) {
                $this->mockResult = $mockResult;
                $this->updateBatchCalled = &$updateBatchCalled;
                $this->updateBatchData = &$updateBatchData;
            }
            public function select($field = '*') { return $this; }
            public function where($field = null, $value = null) { return $this; }
            public function where_not_in($field, $values) { return $this; }
            public function get($table = null) { return $this->mockResult; }
            public function update_batch($table, $data, $key) {
                $this->updateBatchCalled = true;
                $this->updateBatchData = $data;
                return true;
            }
            public function insert_batch($table, $data) { return true; }
        };
        
        // Ensure extensions hook is not active (it could modify the data)
        $mockExtensions = new class {
            public function active_hook($hook) { return false; }
            public function call($hook, ...$args) { return null; }
        };
        
        // Reset mocks to ensure clean state
        ee()->resetMocks();
        ee()->setMock('extensions', $mockExtensions);
        ee()->setMock('db', $mockDb);

        // Create a fresh model instance to avoid any state pollution
        $model = new Grid_model();

        $data = [
            'row_id_1' => ['row_id' => 1, 'col_id_1' => 'updated', 'row_order' => 0],
            'row_id_2' => ['row_id' => 2, 'col_id_1' => 'updated2', 'row_order' => 1]
        ];

        $result = $model->save_field_data($data, 5, 'channel', 10);

        // The method should call update_batch for existing rows (those with row_id_ prefix)
        // Check that update_batch was called with the correct data
        $this->assertTrue($updateBatchCalled, 'update_batch should be called for existing rows');
        $this->assertNotNull($updateBatchData, 'updateBatchData should be set');
        $this->assertCount(2, $updateBatchData, 'Should have 2 updated rows');
        $this->assertEquals(1, $updateBatchData[0]['row_id'], 'First row should have row_id 1');
        $this->assertEquals(2, $updateBatchData[1]['row_id'], 'Second row should have row_id 2');
    }

    public function testSaveFieldDataReturnsDeletedRows()
    {
        $mockResult = new eeDbResultMock([
            ['row_id' => 99]
        ]);

        $mockDb = new class($mockResult) extends eeDbArMock {
            private $mockResult;
            public function __construct($mockResult) {
                $this->mockResult = $mockResult;
            }
            public function select($field = '*') { return $this; }
            public function where($field = null, $value = null) { return $this; }
            public function where_not_in($field, $values) { return $this; }
            public function get($table = null) { return $this->mockResult; }
            public function insert_batch($table, $data) { return true; }
            public function update_batch($table, $data, $key) { return true; }
        };
        ee()->setMock('db', $mockDb);

        $data = [
            'row_id_1' => ['row_id' => 1, 'col_id_1' => 'value', 'row_order' => 0]
        ];

        $result = $this->model->save_field_data($data, 5, 'channel', 10);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(99, $result[0]['row_id']);
    }

    public function testSaveFieldDataHandlesFluidFieldDataId()
    {
        $insertBatchData = null;

        $mockResult = new eeDbResultMock([]);

        $mockDb = new class($mockResult, $insertBatchData) extends eeDbArMock {
            private $mockResult;
            private $insertBatchData;
            public function __construct($mockResult, &$insertBatchData) {
                $this->mockResult = $mockResult;
                $this->insertBatchData = &$insertBatchData;
            }
            public function select($field = '*') { return $this; }
            public function where($field = null, $value = null) { return $this; }
            public function where_not_in($field, $values) { return $this; }
            public function get($table = null) { return $this->mockResult; }
            public function insert_batch($table, $data) {
                $this->insertBatchData = $data;
                return true;
            }
            public function update_batch($table, $data, $key) { return true; }
        };
        ee()->setMock('db', $mockDb);

        $data = [
            'new_row_1' => ['col_id_1' => 'value', 'row_order' => 0]
        ];

        $this->model->save_field_data($data, 5, 'channel', 10, 5);

        $this->assertEquals(5, $insertBatchData[0]['fluid_field_data_id']);
    }
}

// EOF
