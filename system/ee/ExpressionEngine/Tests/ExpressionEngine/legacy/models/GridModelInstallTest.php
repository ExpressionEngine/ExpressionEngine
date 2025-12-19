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
 * Test Grid_model::install() method
 */
class GridModelInstallTest extends GridModelTestBase
{
    public function testInstallCreatesTable()
    {
        // Mock db->insert to track calls
        $insertCalled = false;
        $insertData = null;
        $mockDb = $this->getMockBuilder('eeDbArMock')
            ->setMethods(['insert'])
            ->getMock();
        $mockDb->method('insert')->willReturnCallback(function($table, $data) use (&$insertCalled, &$insertData) {
            $insertCalled = true;
            $insertData = $data;
            return true;
        });
        ee()->setMock('db', $mockDb);

        // Call install
        $this->model->install();

        // Verify dbforge was called to create table
        $this->assertNotEmpty($this->mockDbforge->fields);
        $this->assertArrayHasKey('col_id', $this->mockDbforge->fields);
        $this->assertArrayHasKey('field_id', $this->mockDbforge->fields);
        $this->assertArrayHasKey('content_type', $this->mockDbforge->fields);
        
        // Verify content_types insert was called
        $this->assertTrue($insertCalled);
        $this->assertEquals(['name' => 'grid'], $insertData);
    }

    public function testInstallAddsCorrectFields()
    {
        $this->model->install();

        $fields = $this->mockDbforge->fields;
        
        // Verify all required fields are present
        $requiredFields = ['col_id', 'field_id', 'content_type', 'col_order', 'col_type', 
                           'col_label', 'col_name', 'col_instructions', 'col_required', 
                           'col_search', 'col_width', 'col_settings'];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $fields, "Field $field should be defined");
        }

        // Verify col_id is auto_increment
        $this->assertTrue($fields['col_id']['auto_increment']);
        $this->assertEquals('int', $fields['col_id']['type']);
    }

    public function testInstallAddsCorrectKeys()
    {
        $this->model->install();

        $keys = $this->mockDbforge->keys;
        
        // Verify primary key on col_id
        $colIdKey = null;
        foreach ($keys as $key) {
            if ($key['key'] === 'col_id' && $key['primary']) {
                $colIdKey = $key;
                break;
            }
        }
        $this->assertNotNull($colIdKey, 'Primary key on col_id should be set');

        // Verify indexes on field_id and content_type
        $fieldIdKey = null;
        $contentTypeKey = null;
        foreach ($keys as $key) {
            if ($key['key'] === 'field_id' && !$key['primary']) {
                $fieldIdKey = $key;
            }
            if ($key['key'] === 'content_type' && !$key['primary']) {
                $contentTypeKey = $key;
            }
        }
        $this->assertNotNull($fieldIdKey, 'Index on field_id should be set');
        $this->assertNotNull($contentTypeKey, 'Index on content_type should be set');
    }
}

// EOF
