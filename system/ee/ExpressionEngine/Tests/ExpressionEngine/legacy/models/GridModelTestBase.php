<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/core/Model.php';

// Load array helper for element() function
if (!function_exists('element')) {
    require_once SYSPATH . 'ee/legacy/helpers/array_helper.php';
}

require_once SYSPATH . 'ee/legacy/models/grid_model.php';

use PHPUnit\Framework\TestCase;

/**
 * Base test class for Grid_model tests
 */
class GridModelTestBase extends TestCase
{
    protected $model;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset mocks
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }

        // Mock db with where_in support
        $this->mockDb = new class extends eeDbArMock {
            public function where_in($field, $values) {
                if (!isset($this->whereInConditions)) {
                    $this->whereInConditions = [];
                }
                $this->whereInConditions[$field] = (array) $values;
                return $this;
            }
        };
        ee()->setMock('db', $this->mockDb);

        // Mock dbforge
        $this->mockDbforge = new class {
            public $fields = [];
            public $keys = [];
            public function add_field($fields) { $this->fields = array_merge($this->fields, $fields); return $this; }
            public function add_key($key, $primary = false) { $this->keys[] = ['key' => $key, 'primary' => $primary]; return $this; }
            public function create_table($table) { return true; }
            public function drop_table($table) { return true; }
        };
        ee()->setMock('dbforge', $this->mockDbforge);

        // Mock load
        $this->mockLoad = new class {
            public function dbforge() { return ee()->dbforge; }
            public function helper($helper) { return; }
            public function model($model) { return; }
        };
        ee()->setMock('load', $this->mockLoad);

        // Mock api_channel_fields
        $this->mockApiChannelFields = new class {
            public function setup_handler($field_type) { return true; }
            public function set_datatype($col_id, $settings, $data = [], $create = true, $modify = false, $ft_api_settings = []) { return true; }
            public function edit_datatype($col_id, $field_type, $settings, $ft_api_settings = []) { return true; }
            public function delete_datatype($col_id, $data = [], $ft_api_settings = []) { return true; }
        };
        ee()->setMock('api_channel_fields', $this->mockApiChannelFields);

        // Mock extensions
        $this->mockExtensions = new class {
            public function active_hook($hook) { return false; }
            public function call($hook, ...$args) { return null; }
        };
        ee()->setMock('extensions', $this->mockExtensions);

        // Mock input
        $this->mockInput = new class {
            public function post($item) { return false; }
        };
        ee()->setMock('input', $this->mockInput);

        // Mock functions
        $this->mockFunctions = new class {
            public function ar_andor_string($str, $field) { return; }
        };
        ee()->setMock('functions', $this->mockFunctions);

        // Mock channel_model
        $this->mockChannelModel = new class {
            public function field_search_sql($terms, $field_name) {
                return "$field_name LIKE '%" . addslashes($terms) . "%'";
            }
        };
        ee()->setMock('channel_model', $this->mockChannelModel);

        // Create model instance
        $this->model = new Grid_model();
    }

    protected function tearDown(): void
    {
        // Reset mocks
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
        parent::tearDown();
    }
}

// EOF
