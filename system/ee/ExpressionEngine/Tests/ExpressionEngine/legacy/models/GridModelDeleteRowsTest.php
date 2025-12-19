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
 * Test Grid_model::delete_rows() method
 */
class GridModelDeleteRowsTest extends GridModelTestBase
{
    public function testDeleteRowsDeletesRows()
    {
        $deleteCalled = false;
        $deleteTable = null;

        $mockDb = new class($deleteCalled, $deleteTable) extends eeDbArMock {
            private $deleteCalled;
            private $deleteTable;
            public function __construct(&$deleteCalled, &$deleteTable) {
                $this->deleteCalled = &$deleteCalled;
                $this->deleteTable = &$deleteTable;
            }
            public function where_in($field, $values) {
                return $this;
            }
            public function delete($table, $where = null) {
                $this->deleteCalled = true;
                $this->deleteTable = $table;
                return true;
            }
        };
        ee()->setMock('db', $mockDb);

        $this->model->delete_rows([1, 2, 3], 5, 'channel');

        $this->assertTrue($deleteCalled);
        $this->assertEquals('channel_grid_field_5', $deleteTable);
    }

    public function testDeleteRowsSkipsWhenEmpty()
    {
        $deleteCalled = false;

        $mockDb = new class($deleteCalled) extends eeDbArMock {
            private $deleteCalled;
            public function __construct(&$deleteCalled) {
                $this->deleteCalled = &$deleteCalled;
            }
            public function where_in($field, $values) {
                $this->deleteCalled = true;
                return $this;
            }
            public function delete($table, $where = null) {
                $this->deleteCalled = true;
                return true;
            }
        };
        ee()->setMock('db', $mockDb);

        $this->model->delete_rows([], 5, 'channel');

        $this->assertFalse($deleteCalled);
    }
}

// EOF
