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
 * Test Grid_model::update_grid_search() method
 */
class GridModelUpdateGridSearchTest extends GridModelTestBase
{
    public function testUpdateGridSearchUpdatesSearchableFields()
    {
        // Mock ee('Model')->get('ChannelField')
        $mockField = $this->getMockBuilder('stdClass')
            ->addMethods(['getDataStorageTable'])
            ->getMock();
        $mockField->field_id = 5;
        $mockField->field_search = 'y';
        $mockField->method('getDataStorageTable')->willReturn('channel_data');

        $mockModelCollection = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'fields', 'filter', 'all'])
            ->getMock();
        $mockModelCollection->method('get')->willReturnSelf();
        $mockModelCollection->method('fields')->willReturnSelf();
        $mockModelCollection->method('filter')->willReturnSelf();
        $mockModelCollection->method('all')->willReturn([$mockField]);

        $mockModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        $mockModel->method('get')->willReturn($mockModelCollection);
        ee()->setMock('Model', $mockModel);

        // Mock get_columns_for_field
        $mockModelGrid = $this->getMockBuilder('Grid_model')
            ->setMethods(['get_columns_for_field'])
            ->getMock();
        $mockModelGrid->method('get_columns_for_field')->willReturn([
            1 => ['col_id' => 1, 'col_name' => 'test', 'col_search' => 'y']
        ]);

        $mockResult = new eeDbResultMock([
            ['row_id' => 1, 'entry_id' => 10, 'col_id_1' => 'test value']
        ]);

        $updateBatchCalled = false;
        $mockDb = new class($mockResult, $updateBatchCalled) extends eeDbArMock {
            private $mockResult;
            private $updateBatchCalled;
            public function __construct($mockResult, &$updateBatchCalled) {
                $this->mockResult = $mockResult;
                $this->updateBatchCalled = &$updateBatchCalled;
            }
            public function select() { return $this; }
            public function where($field = null, $value = null) { return $this; }
            public function get($table = null) { return $this->mockResult; }
            public function update($table, $data = null, $where = null) { return true; }
            public function update_batch($table, $data, $key) {
                $this->updateBatchCalled = true;
                return true;
            }
        };
        ee()->setMock('db', $mockDb);

        // Mock helper function
        if (!function_exists('encode_multi_field')) {
            function encode_multi_field($data) {
                return $data;
            }
        }

        $mockModelGrid->update_grid_search([5]);

        $this->assertTrue($updateBatchCalled);
    }

    public function testUpdateGridSearchHandlesUnsearchableFields()
    {
        $mockField = $this->getMockBuilder('stdClass')
            ->addMethods(['getDataStorageTable'])
            ->getMock();
        $mockField->field_id = 5;
        $mockField->field_search = ''; // Empty string evaluates to false
        $mockField->method('getDataStorageTable')->willReturn('channel_data');

        $mockModelCollection = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'fields', 'filter', 'all'])
            ->getMock();
        $mockModelCollection->method('get')->willReturnSelf();
        $mockModelCollection->method('fields')->willReturnSelf();
        $mockModelCollection->method('filter')->willReturnSelf();
        $mockModelCollection->method('all')->willReturn([$mockField]);

        $mockModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        $mockModel->method('get')->willReturn($mockModelCollection);
        ee()->setMock('Model', $mockModel);

        // Track update calls - it's called per table in unsearchable array
        // The method uses ee()->db->update() which should use our mock
        $updateCalls = [];
        $mockDb = new class($updateCalls) extends eeDbArMock {
            private $updateCalls;
            public function __construct(&$updateCalls) {
                $this->updateCalls = &$updateCalls;
            }
            public function update($table, $data = null, $where = null) {
                $this->updateCalls[] = ['table' => $table, 'data' => $data];
                return true;
            }
            public function select($field = '*') { return $this; }
            public function where_in($field, $values) { return $this; }
            public function where($field = null, $value = null) { return $this; }
            public function order_by($field, $direction = '') { return $this; }
            public function get($table = null) { return new eeDbResultMock([]); }
        };
        ee()->setMock('db', $mockDb);
        
        // Also need to ensure ee('db') returns the same mock for get_columns_for_field
        // The model uses ee('db') in get_columns_for_field which goes through ee()->__get('db')
        // So setting ee()->db should work, but we need to ensure ee('db') also works
        // Actually, looking at the code, get_columns_for_field uses ee('db') which should resolve
        // through the eeObjectMock mechanism. Let's ensure the mock is properly set.

        $this->model->update_grid_search([5]);

        // The update should be called when field_search is 'n' (unsearchable)
        // It loops through $unsearchable array and calls update for each table
        // Since we have one field with field_search='n', update should be called once
        $this->assertGreaterThanOrEqual(1, count($updateCalls), 'Update should be called to clear search data for unsearchable fields. Got: ' . count($updateCalls));
    }

    public function testUpdateGridSearchHandlesEmptyFields()
    {
        $mockModelCollection = $this->getMockBuilder('stdClass')
            ->addMethods(['get', 'fields', 'filter', 'all'])
            ->getMock();
        $mockModelCollection->method('get')->willReturnSelf();
        $mockModelCollection->method('fields')->willReturnSelf();
        $mockModelCollection->method('filter')->willReturnSelf();
        $mockModelCollection->method('all')->willReturn([]);

        $mockModel = $this->getMockBuilder('stdClass')
            ->addMethods(['get'])
            ->getMock();
        $mockModel->method('get')->willReturn($mockModelCollection);
        ee()->setMock('Model', $mockModel);

        // Should not throw error
        $this->model->update_grid_search([999]);
        
        $this->assertTrue(true); // Test passes if no exception thrown
    }
}

// EOF
