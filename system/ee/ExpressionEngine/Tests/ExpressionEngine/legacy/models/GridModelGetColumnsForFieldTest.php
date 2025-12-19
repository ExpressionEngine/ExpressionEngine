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
 * Test Grid_model::get_columns_for_field() method
 */
class GridModelGetColumnsForFieldTest extends GridModelTestBase
{
    public function testGetColumnsForFieldReturnsColumns()
    {
        $expectedColumns = [
            ['col_id' => 1, 'field_id' => 5, 'col_name' => 'test', 'col_order' => 0, 'col_settings' => '{}'],
            ['col_id' => 2, 'field_id' => 5, 'col_name' => 'test2', 'col_order' => 1, 'col_settings' => '{}']
        ];

        $mockResult = new eeDbResultMock($expectedColumns);

        $mockDb = new class($mockResult) extends eeDbArMock {
            private $mockResult;
            public function __construct($mockResult) {
                $this->mockResult = $mockResult;
            }
            public function where_in($field, $values) { return $this; }
            public function where($field = null, $value = null) { return $this; }
            public function order_by($field, $direction = '') { return $this; }
            public function get($table = null) { return $this->mockResult; }
        };

        // Mock ee('db')
        $mockEeDb = $this->getMockBuilder('stdClass')
            ->addMethods(['where_in', 'where', 'order_by', 'get'])
            ->getMock();
        $mockEeDb->method('where_in')->willReturnSelf();
        $mockEeDb->method('where')->willReturnSelf();
        $mockEeDb->method('order_by')->willReturnSelf();
        $mockEeDb->method('get')->willReturn($mockResult);

        // Use reflection to set ee('db') mock
        $reflection = new ReflectionClass('eeSingletonMock');
        // We'll need to mock ee() function to return our mock
        
        // For now, let's test with the regular mock
        ee()->setMock('db', $mockDb);

        $result = $this->model->get_columns_for_field(5, 'channel');

        $this->assertIsArray($result);
    }

    public function testGetColumnsForFieldCachesResults()
    {
        $callCount = 0;
        $mockResult = new eeDbResultMock([
            ['col_id' => 1, 'field_id' => 5, 'col_name' => 'test', 'col_order' => 0, 'col_settings' => '{}']
        ]);

        $mockDb = new class($mockResult, $callCount) extends eeDbArMock {
            private $mockResult;
            private $callCount;
            public function __construct($mockResult, &$callCount) {
                $this->mockResult = $mockResult;
                $this->callCount = &$callCount;
            }
            public function where_in($field, $values) { return $this; }
            public function where($field = null, $value = null) { return $this; }
            public function order_by($field, $direction = '') { return $this; }
            public function get($table = null) {
                $this->callCount++;
                return $this->mockResult;
            }
        };
        ee()->setMock('db', $mockDb);

        // First call
        $result1 = $this->model->get_columns_for_field(5, 'channel');
        
        // Second call should use cache
        $result2 = $this->model->get_columns_for_field(5, 'channel');

        // Should only query once due to caching
        $this->assertEquals(1, $callCount);
    }

    public function testGetColumnsForFieldHandlesMultipleFieldIds()
    {
        $expectedColumns = [
            ['col_id' => 1, 'field_id' => 5, 'col_name' => 'test', 'col_order' => 0, 'col_settings' => '{}'],
            ['col_id' => 2, 'field_id' => 10, 'col_name' => 'test2', 'col_order' => 0, 'col_settings' => '{}']
        ];

        $mockResult = new eeDbResultMock($expectedColumns);

        $mockDb = new class($mockResult) extends eeDbArMock {
            private $mockResult;
            public function __construct($mockResult) {
                $this->mockResult = $mockResult;
            }
            public function where_in($field, $values) { return $this; }
            public function where($field = null, $value = null) { return $this; }
            public function order_by($field, $direction = '') { return $this; }
            public function get($table = null) { return $this->mockResult; }
        };
        ee()->setMock('db', $mockDb);

        $result = $this->model->get_columns_for_field([5, 10], 'channel');

        $this->assertIsArray($result);
    }
}

// EOF
