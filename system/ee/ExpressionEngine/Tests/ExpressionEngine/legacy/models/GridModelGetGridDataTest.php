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
 * Test Grid_model::get_grid_data() method
 */
class GridModelGetGridDataTest extends GridModelTestBase
{
    public function testGetGridDataReturnsGridData()
    {
        // Set grid data using reflection
        $reflection = new ReflectionClass($this->model);
        $gridDataProperty = $reflection->getProperty('_grid_data');
        $gridDataProperty->setAccessible(true);
        
        $testData = [
            'channel' => [
                5 => [
                    'marker' => [
                        10 => [
                            1 => ['row_id' => 1, 'entry_id' => 10]
                        ]
                    ]
                ]
            ]
        ];
        $gridDataProperty->setValue($this->model, $testData);

        $result = $this->model->get_grid_data();

        $this->assertEquals($testData, $result);
    }

    public function testGetGridDataReturnsEmptyArrayWhenNoData()
    {
        $result = $this->model->get_grid_data();

        $this->assertEquals([], $result);
    }
}

// EOF
