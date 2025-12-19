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
 * Test Grid_model::save_col_settings() method
 */
class GridModelSaveColSettingsTest extends GridModelTestBase
{
    public function testSaveColSettingsCreatesNewColumn()
    {
        $insertCalled = false;
        $insertData = null;
        $insertId = 123;

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['insert', 'insert_id', 'where', 'update'])
            ->getMock();
        $mockDb->method('insert')->willReturnCallback(function($table, $data) use (&$insertCalled, &$insertData) {
            $insertCalled = true;
            $insertData = $data;
            return true;
        });
        $mockDb->method('insert_id')->willReturn($insertId);
        $mockDb->method('where')->willReturnSelf();
        $mockDb->method('update')->willReturn(true);
        ee()->setMock('db', $mockDb);

        $setupHandlerCalled = false;
        $setDatatypeCalled = false;
        $mockApiChannelFields = new class($setupHandlerCalled, $setDatatypeCalled) {
            private $setupHandlerCalled;
            private $setDatatypeCalled;
            public function __construct(&$setupHandlerCalled, &$setDatatypeCalled) {
                $this->setupHandlerCalled = &$setupHandlerCalled;
                $this->setDatatypeCalled = &$setDatatypeCalled;
            }
            public function setup_handler($field_type) {
                $this->setupHandlerCalled = true;
                return true;
            }
            public function set_datatype($col_id, $settings, $data = [], $create = true, $modify = false, $ft_api_settings = []) {
                $this->setDatatypeCalled = true;
                return true;
            }
            public function edit_datatype($col_id, $field_type, $settings, $ft_api_settings = []) {
                return true;
            }
        };
        ee()->setMock('api_channel_fields', $mockApiChannelFields);

        $column = [
            'field_id' => 5,
            'content_type' => 'channel',
            'col_type' => 'text',
            'col_label' => 'Test Column',
            'col_name' => 'test_column',
            'col_settings' => ['foo' => 'bar']
        ];

        $result = $this->model->save_col_settings($column, false, 'channel');

        $this->assertEquals($insertId, $result);
        $this->assertTrue($insertCalled);
        $this->assertEquals($column, $insertData);
        $this->assertTrue($setupHandlerCalled);
        $this->assertTrue($setDatatypeCalled);
    }

    public function testSaveColSettingsUpdatesExistingColumn()
    {
        $updateCalled = false;
        $updateTable = null;
        $updateData = null;

        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['where', 'update', 'insert', 'insert_id'])
            ->getMock();
        $mockDb->method('where')->willReturnSelf();
        $mockDb->method('update')->willReturnCallback(function($table, $data) use (&$updateCalled, &$updateTable, &$updateData) {
            $updateCalled = true;
            $updateTable = $table;
            $updateData = $data;
            return true;
        });
        ee()->setMock('db', $mockDb);

        $editDatatypeCalled = false;
        $mockApiChannelFields = new class($editDatatypeCalled) {
            private $editDatatypeCalled;
            public function __construct(&$editDatatypeCalled) {
                $this->editDatatypeCalled = &$editDatatypeCalled;
            }
            public function edit_datatype($col_id, $field_type, $settings, $ft_api_settings = []) {
                $this->editDatatypeCalled = true;
                return true;
            }
            public function setup_handler($field_type) { return true; }
            public function set_datatype($col_id, $settings, $data = [], $create = true, $modify = false, $ft_api_settings = []) { return true; }
        };
        ee()->setMock('api_channel_fields', $mockApiChannelFields);

        $column = [
            'field_id' => 5,
            'content_type' => 'channel',
            'col_type' => 'text',
            'col_label' => 'Updated Column',
            'col_name' => 'test_column',
            'col_settings' => ['foo' => 'baz']
        ];

        $result = $this->model->save_col_settings($column, 10, 'channel');

        $this->assertEquals(10, $result);
        $this->assertTrue($updateCalled);
        $this->assertEquals('grid_columns', $updateTable);
        $this->assertEquals($column, $updateData);
        $this->assertTrue($editDatatypeCalled);
    }

    public function testSaveColSettingsHandlesJsonSettings()
    {
        $insertId = 123;
        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['insert', 'insert_id'])
            ->getMock();
        $mockDb->method('insert')->willReturn(true);
        $mockDb->method('insert_id')->willReturn($insertId);
        ee()->setMock('db', $mockDb);

        $setDatatypeSettings = null;
        $mockApiChannelFields = new class($setDatatypeSettings) {
            private $setDatatypeSettings;
            public function __construct(&$setDatatypeSettings) {
                $this->setDatatypeSettings = &$setDatatypeSettings;
            }
            public function setup_handler($field_type) { return true; }
            public function set_datatype($col_id, $settings, $data = [], $create = true, $modify = false, $ft_api_settings = []) {
                $this->setDatatypeSettings = $settings;
                return true;
            }
            public function edit_datatype($col_id, $field_type, $settings, $ft_api_settings = []) { return true; }
        };
        ee()->setMock('api_channel_fields', $mockApiChannelFields);

        $column = [
            'field_id' => 5,
            'col_type' => 'text',
            'col_settings' => json_encode(['foo' => 'bar'])
        ];

        $this->model->save_col_settings($column, false, 'channel');

        $this->assertEquals(['foo' => 'bar'], $setDatatypeSettings);
    }
}

// EOF
