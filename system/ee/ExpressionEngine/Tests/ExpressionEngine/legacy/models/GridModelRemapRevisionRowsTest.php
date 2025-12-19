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
 * Test Grid_model::remap_revision_rows() method
 */
class GridModelRemapRevisionRowsTest extends GridModelTestBase
{
    public function testRemapRevisionRowsMapsExistingRows()
    {
        $mockResult = new eeDbResultMock([
            ['row_id' => 1],
            ['row_id' => 2]
        ]);

        $mockDb = new class($mockResult) extends eeDbArMock {
            private $mockResult;
            public function __construct($mockResult) {
                $this->mockResult = $mockResult;
            }
            public function select($field = '*') { return $this; }
            public function from($table = null) { return $this; }
            public function where($field = null, $value = null) { return $this; }
            public function get() { return $this->mockResult; }
        };
        ee()->setMock('db', $mockDb);

        $rows = [
            'row_id_1' => ['col_id_1' => 'value1'],
            'row_id_2' => ['col_id_1' => 'value2'],
            'row_id_99' => ['col_id_1' => 'value3'] // This doesn't exist
        ];

        $result = $this->model->remap_revision_rows($rows, 5, 10, 0, 'channel');

        // Existing rows should keep their keys
        $this->assertArrayHasKey('row_id_1', $result);
        $this->assertArrayHasKey('row_id_2', $result);
        
        // Non-existing row should be remapped to new_row_ prefix
        $hasNewRow = false;
        foreach (array_keys($result) as $key) {
            if (strpos($key, 'new_row_') === 0) {
                $hasNewRow = true;
                break;
            }
        }
        $this->assertTrue($hasNewRow, 'Non-existing row should be remapped to new_row_ prefix');
    }

    public function testRemapRevisionRowsHandlesEmptyExistingRows()
    {
        $mockResult = new eeDbResultMock([]);

        $mockDb = new class($mockResult) extends eeDbArMock {
            private $mockResult;
            public function __construct($mockResult) {
                $this->mockResult = $mockResult;
            }
            public function select($field = '*') { return $this; }
            public function from($table = null) { return $this; }
            public function where($field = null, $value = null) { return $this; }
            public function get() { return $this->mockResult; }
        };
        ee()->setMock('db', $mockDb);

        $rows = [
            'row_id_1' => ['col_id_1' => 'value1']
        ];

        $result = $this->model->remap_revision_rows($rows, 5, 10, 0, 'channel');

        // All rows should be remapped since none exist
        $hasNewRow = false;
        foreach (array_keys($result) as $key) {
            if (strpos($key, 'new_row_') === 0) {
                $hasNewRow = true;
                break;
            }
        }
        $this->assertTrue($hasNewRow, 'All rows should be remapped to new_row_ prefix when none exist');
    }
}

// EOF
