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
 * Test Grid_model::search_and_replace() method
 */
class GridModelSearchAndReplaceTest extends GridModelTestBase
{
    public function testSearchAndReplaceUpdatesColumns()
    {
        $queryCalled = false;
        $querySql = null;
        $affectedRows = 5;

        $mockDb = new class($queryCalled, $querySql, $affectedRows) extends eeDbArMock {
            private $queryCalled;
            private $querySql;
            private $affectedRows;
            public function __construct(&$queryCalled, &$querySql, $affectedRows) {
                $this->queryCalled = &$queryCalled;
                $this->querySql = &$querySql;
                $this->affectedRows = $affectedRows;
            }
            public function query($sql) {
                $this->queryCalled = true;
                $this->querySql = $sql;
                return true;
            }
            public function affected_rows() {
                return $this->affectedRows;
            }
        };
        ee()->setMock('db', $mockDb);

        // Mock get_columns_for_field
        $mockModel = $this->getMockBuilder('Grid_model')
            ->setMethods(['get_columns_for_field'])
            ->getMock();
        $mockModel->method('get_columns_for_field')->willReturn([
            1 => ['col_id' => 1, 'col_name' => 'test'],
            2 => ['col_id' => 2, 'col_name' => 'test2']
        ]);

        $result = $mockModel->search_and_replace('channel', 5, 'old', 'new');

        $this->assertTrue($queryCalled);
        $this->assertStringContainsString('REPLACE', $querySql);
        $this->assertStringContainsString('old', $querySql);
        $this->assertStringContainsString('new', $querySql);
        $this->assertEquals($affectedRows, $result);
    }

    public function testSearchAndReplaceReturnsZeroWhenNoColumns()
    {
        $mockModel = $this->getMockBuilder('Grid_model')
            ->setMethods(['get_columns_for_field'])
            ->getMock();
        $mockModel->method('get_columns_for_field')->willReturn([]);

        $result = $mockModel->search_and_replace('channel', 5, 'old', 'new');

        $this->assertEquals(0, $result);
    }

    public function testSearchAndReplaceEscapesValues()
    {
        $querySql = null;
        $mockDb = new class($querySql) extends eeDbArMock {
            private $querySql;
            public function __construct(&$querySql) {
                $this->querySql = &$querySql;
            }
            public function query($sql) {
                $this->querySql = $sql;
                return true;
            }
            public function affected_rows() {
                return 0;
            }
        };
        ee()->setMock('db', $mockDb);

        $mockModel = $this->getMockBuilder('Grid_model')
            ->setMethods(['get_columns_for_field'])
            ->getMock();
        $mockModel->method('get_columns_for_field')->willReturn([
            1 => ['col_id' => 1, 'col_name' => 'test']
        ]);

        $mockModel->search_and_replace('channel', 5, "test'value", "new'value");

        // SQL should contain the values (actual escaping would happen in real DB)
        $this->assertStringContainsString("test'value", $querySql);
        $this->assertStringContainsString("new'value", $querySql);
    }
}

// EOF
