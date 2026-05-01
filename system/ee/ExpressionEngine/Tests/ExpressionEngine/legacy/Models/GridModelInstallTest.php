<?php

use PHPUnit\Framework\TestCase;

if (!class_exists('CI_Model')) {
    class CI_Model
    {
    }
}

require_once SYSPATH . 'ee/legacy/models/grid_model.php';
if (! function_exists('element')) {
    require_once SYSPATH . 'ee/legacy/helpers/array_helper.php';
}

class GridModelInstallTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testInstallBuildsSchemaAddsKeysCreatesTableAndSeedsContentType(): void
    {
        $state = (object) [
            'calls' => [],
            'capturedColumns' => null,
            'capturedKeys' => [],
            'capturedTables' => [],
            'capturedInserts' => [],
        ];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function add_field($columns)
            {
                $this->state->calls[] = ['dbforge.add_field'];
                $this->state->capturedColumns = $columns;
            }

            public function add_key($key, $primary = false)
            {
                $this->state->calls[] = ['dbforge.add_key', $key, $primary];
                $this->state->capturedKeys[] = [$key, $primary];
            }

            public function create_table($table)
            {
                $this->state->calls[] = ['dbforge.create_table', $table];
                $this->state->capturedTables[] = $table;
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function insert($table, $payload)
            {
                $this->state->calls[] = ['db.insert', $table, $payload];
                $this->state->capturedInserts[] = [$table, $payload];

                return true;
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();
        $model->install();

        $expectedColumns = [
            'col_id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'field_id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true],
            'content_type' => ['type' => 'varchar', 'constraint' => 50],
            'col_order' => ['type' => 'int', 'constraint' => 3, 'unsigned' => true],
            'col_type' => ['type' => 'varchar', 'constraint' => 50],
            'col_label' => ['type' => 'varchar', 'constraint' => 50],
            'col_name' => ['type' => 'varchar', 'constraint' => 32],
            'col_instructions' => ['type' => 'text'],
            'col_required' => ['type' => 'char', 'constraint' => 1],
            'col_search' => ['type' => 'char', 'constraint' => 1],
            'col_width' => ['type' => 'int', 'constraint' => 3, 'unsigned' => true],
            'col_settings' => ['type' => 'text'],
        ];

        $this->assertSame($expectedColumns, $state->capturedColumns);
        $this->assertSame([['col_id', true], ['field_id', false], ['content_type', false]], $state->capturedKeys);
        $this->assertSame(['grid_columns'], $state->capturedTables);
        $this->assertSame([['content_types', ['name' => 'grid']]], $state->capturedInserts);
        $this->assertSame(
            [
                ['load.dbforge'],
                ['dbforge.add_field'],
                ['dbforge.add_key', 'col_id', true],
                ['dbforge.add_key', 'field_id', false],
                ['dbforge.add_key', 'content_type', false],
                ['dbforge.create_table', 'grid_columns'],
                ['db.insert', 'content_types', ['name' => 'grid']],
            ],
            $state->calls
        );
    }

    public function testInstallBubblesCreateTableExceptionAndSkipsContentTypeInsert(): void
    {
        $state = (object) ['insertCallCount' => 0];

        ee()->setMock('load', new class {
            public function dbforge()
            {
            }
        });

        ee()->setMock('dbforge', new class {
            public function add_field($columns)
            {
            }

            public function add_key($key, $primary = false)
            {
            }

            public function create_table($table)
            {
                throw new \RuntimeException('create_table failed');
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function insert($table, $payload)
            {
                $this->state->insertCallCount++;

                return true;
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        try {
            $model->install();
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('create_table failed', $exception->getMessage());
        }

        $this->assertSame(0, $state->insertCallCount);
    }

    public function testUninstallDropsColumnsTableAndDeletesContentTypeWhenNoGridFieldsFound(): void
    {
        $state = (object) ['calls' => []];
        $queryResult = new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function result_array()
            {
                $this->state->calls[] = ['db.result_array'];

                return [];
            }
        };

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state, $queryResult) {
            private $state;
            private $queryResult;

            public function __construct($state, $queryResult)
            {
                $this->state = $state;
                $this->queryResult = $queryResult;
            }

            public function select($column)
            {
                $this->state->calls[] = ['db.select', $column];

                return $this;
            }

            public function distinct()
            {
                $this->state->calls[] = ['db.distinct'];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];

                return $this->queryResult;
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = new class extends \Grid_model {
            public $deleteFieldCalls = [];

            public function delete_field($field_id, $content_type)
            {
                $this->deleteFieldCalls[] = [$field_id, $content_type];
            }
        };

        $model->uninstall();

        $this->assertSame([], $model->deleteFieldCalls);
        $this->assertSame(
            [
                ['db.select', 'field_id'],
                ['db.distinct'],
                ['db.get', 'grid_columns'],
                ['db.result_array'],
                ['load.dbforge'],
                ['dbforge.drop_table', 'grid_columns'],
                ['db.delete', 'content_types', ['name' => 'grid']],
            ],
            $state->calls
        );
    }

    public function testUninstallDeletesEachGridFieldThenDropsColumnsTableAndDeletesContentType(): void
    {
        $state = (object) ['calls' => []];
        $queryResult = new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function result_array()
            {
                $this->state->calls[] = ['db.result_array'];

                return [
                    ['field_id' => 3, 'content_type' => 'channel'],
                    ['field_id' => 7, 'content_type' => 'fluid_field'],
                ];
            }
        };

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state, $queryResult) {
            private $state;
            private $queryResult;

            public function __construct($state, $queryResult)
            {
                $this->state = $state;
                $this->queryResult = $queryResult;
            }

            public function select($column)
            {
                $this->state->calls[] = ['db.select', $column];

                return $this;
            }

            public function distinct()
            {
                $this->state->calls[] = ['db.distinct'];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];

                return $this->queryResult;
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = new class($state) extends \Grid_model {
            private $state;
            public $deleteFieldCalls = [];

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function delete_field($field_id, $content_type)
            {
                $this->state->calls[] = ['model.delete_field', $field_id, $content_type];
                $this->deleteFieldCalls[] = [$field_id, $content_type];
            }
        };

        $model->uninstall();

        $this->assertSame([[3, 'channel'], [7, 'fluid_field']], $model->deleteFieldCalls);
        $this->assertSame(
            [
                ['db.select', 'field_id'],
                ['db.distinct'],
                ['db.get', 'grid_columns'],
                ['db.result_array'],
                ['model.delete_field', 3, 'channel'],
                ['model.delete_field', 7, 'fluid_field'],
                ['load.dbforge'],
                ['dbforge.drop_table', 'grid_columns'],
                ['db.delete', 'content_types', ['name' => 'grid']],
            ],
            $state->calls
        );
    }

    public function testUninstallErrorsWhenResultRowOmitsContentTypeKey(): void
    {
        $state = (object) ['calls' => []];
        $queryResult = new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function result_array()
            {
                $this->state->calls[] = ['db.result_array'];

                return [
                    ['field_id' => 42],
                ];
            }
        };

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state, $queryResult) {
            private $state;
            private $queryResult;

            public function __construct($state, $queryResult)
            {
                $this->state = $state;
                $this->queryResult = $queryResult;
            }

            public function select($column)
            {
                $this->state->calls[] = ['db.select', $column];

                return $this;
            }

            public function distinct()
            {
                $this->state->calls[] = ['db.distinct'];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];

                return $this->queryResult;
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = new class($state) extends \Grid_model {
            private $state;
            public $deleteFieldCalls = [];

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function delete_field($field_id, $content_type)
            {
                $this->state->calls[] = ['model.delete_field', $field_id, $content_type];
                $this->deleteFieldCalls[] = [$field_id, $content_type];
            }
        };

        set_error_handler(static function ($severity, $message, $file, $line) {
            if (strpos($message, 'content_type') !== false) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }

            return false;
        });

        try {
            try {
                $model->uninstall();
                $this->fail('Expected ErrorException was not thrown.');
            } catch (\ErrorException $exception) {
                $this->assertStringContainsString('content_type', $exception->getMessage());
            }
        } finally {
            restore_error_handler();
            $this->assertSame([], $model->deleteFieldCalls);
            $this->assertSame(
                [
                    ['db.select', 'field_id'],
                    ['db.distinct'],
                    ['db.get', 'grid_columns'],
                    ['db.result_array'],
                ],
                $state->calls
            );
        }
    }

    public function testUninstallBubblesGridFieldQueryExceptionAndSkipsDropAndDelete(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function select($column)
            {
                $this->state->calls[] = ['db.select', $column];

                return $this;
            }

            public function distinct()
            {
                $this->state->calls[] = ['db.distinct'];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];
                throw new \RuntimeException('grid lookup failed');
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = new class($state) extends \Grid_model {
            private $state;
            public $deleteFieldCalls = [];

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function delete_field($field_id, $content_type)
            {
                $this->state->calls[] = ['model.delete_field', $field_id, $content_type];
                $this->deleteFieldCalls[] = [$field_id, $content_type];
            }
        };

        try {
            $model->uninstall();
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('grid lookup failed', $exception->getMessage());
        }

        $this->assertSame([], $model->deleteFieldCalls);
        $this->assertSame(
            [
                ['db.select', 'field_id'],
                ['db.distinct'],
                ['db.get', 'grid_columns'],
            ],
            $state->calls
        );
    }

    public function testCreateFieldCreatesTableSchemaAndReturnsTrueWhenTableDoesNotExist(): void
    {
        $state = (object) [
            'calls' => [],
            'capturedColumns' => null,
            'capturedKeys' => [],
            'capturedTables' => [],
        ];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function add_field($columns)
            {
                $this->state->calls[] = ['dbforge.add_field'];
                $this->state->capturedColumns = $columns;
            }

            public function add_key($key, $primary = false)
            {
                $this->state->calls[] = ['dbforge.add_key', $key, $primary];
                $this->state->capturedKeys[] = [$key, $primary];
            }

            public function create_table($table)
            {
                $this->state->calls[] = ['dbforge.create_table', $table];
                $this->state->capturedTables[] = $table;
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function table_exists($table)
            {
                $this->state->calls[] = ['db.table_exists', $table];

                return false;
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        $result = $model->create_field(12, 'channel');

        $expectedColumns = [
            'row_id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'entry_id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true],
            'row_order' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true],
            'fluid_field_data_id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
        ];

        $this->assertTrue($result);
        $this->assertSame($expectedColumns, $state->capturedColumns);
        $this->assertSame([['row_id', true], ['entry_id', false]], $state->capturedKeys);
        $this->assertSame(['channel_grid_field_12'], $state->capturedTables);
        $this->assertSame(
            [
                ['db.table_exists', 'channel_grid_field_12'],
                ['load.dbforge'],
                ['dbforge.add_field'],
                ['dbforge.add_key', 'row_id', true],
                ['dbforge.add_key', 'entry_id', false],
                ['dbforge.create_table', 'channel_grid_field_12'],
            ],
            $state->calls
        );
    }

    public function testCreateFieldReturnsFalseAndSkipsSchemaChangesWhenTableAlreadyExists(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function add_field($columns)
            {
                $this->state->calls[] = ['dbforge.add_field'];
            }

            public function add_key($key, $primary = false)
            {
                $this->state->calls[] = ['dbforge.add_key', $key, $primary];
            }

            public function create_table($table)
            {
                $this->state->calls[] = ['dbforge.create_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function table_exists($table)
            {
                $this->state->calls[] = ['db.table_exists', $table];

                return true;
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        $result = $model->create_field(9, 'fluid_field');

        $this->assertFalse($result);
        $this->assertSame(
            [
                ['db.table_exists', 'fluid_field_grid_field_9'],
            ],
            $state->calls
        );
    }

    public function testCreateFieldBubblesCreateTableExceptionAfterPreparingSchema(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function add_field($columns)
            {
                $this->state->calls[] = ['dbforge.add_field'];
            }

            public function add_key($key, $primary = false)
            {
                $this->state->calls[] = ['dbforge.add_key', $key, $primary];
            }

            public function create_table($table)
            {
                $this->state->calls[] = ['dbforge.create_table', $table];
                throw new \RuntimeException('create_field table creation failed');
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function table_exists($table)
            {
                $this->state->calls[] = ['db.table_exists', $table];

                return false;
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        try {
            $model->create_field(77, 'channel');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('create_field table creation failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.table_exists', 'channel_grid_field_77'],
                ['load.dbforge'],
                ['dbforge.add_field'],
                ['dbforge.add_key', 'row_id', true],
                ['dbforge.add_key', 'entry_id', false],
                ['dbforge.create_table', 'channel_grid_field_77'],
            ],
            $state->calls
        );
    }

    /**
     * It drops every matching Grid data table, then deletes Grid column metadata rows.
     *
     * @return void
     */
    public function testDeleteContentOfTypeDropsEachMatchingTableThenDeletesGridColumnRows(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function list_tables($prefix)
            {
                $this->state->calls[] = ['db.list_tables', $prefix];

                return ['channel_grid_field_12', 'channel_grid_field_99'];
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();
        $model->delete_content_of_type('channel');

        $this->assertSame(
            [
                ['db.list_tables', 'channelgrid_field_'],
                ['load.dbforge'],
                ['dbforge.drop_table', 'channel_grid_field_12'],
                ['dbforge.drop_table', 'channel_grid_field_99'],
                ['db.delete', 'grid_columns', ['content_type' => 'channel']],
            ],
            $state->calls
        );
    }

    /**
     * It deletes Grid column metadata rows even when no matching data tables exist.
     *
     * @return void
     */
    public function testDeleteContentOfTypeDeletesGridColumnRowsWhenNoMatchingTablesExist(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function list_tables($prefix)
            {
                $this->state->calls[] = ['db.list_tables', $prefix];

                return [];
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();
        $model->delete_content_of_type('fluid_field');

        $this->assertSame(
            [
                ['db.list_tables', 'fluid_fieldgrid_field_'],
                ['load.dbforge'],
                ['db.delete', 'grid_columns', ['content_type' => 'fluid_field']],
            ],
            $state->calls
        );
    }

    /**
     * It tolerates a non-iterable table list by surfacing the foreach warning and still deleting metadata.
     *
     * @return void
     */
    public function testDeleteContentOfTypeHandlesNullTableListByStillDeletingMetadata(): void
    {
        $state = (object) ['calls' => [], 'warnings' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function list_tables($prefix)
            {
                $this->state->calls[] = ['db.list_tables', $prefix];

                return null;
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        set_error_handler(function ($severity, $message) use ($state) {
            $state->warnings[] = [$severity, $message];

            return true;
        });

        try {
            $model->delete_content_of_type('channel');
        } finally {
            restore_error_handler();
        }

        $this->assertNotEmpty($state->warnings);
        $this->assertStringContainsString('foreach', $state->warnings[0][1]);
        $this->assertSame(
            [
                ['db.list_tables', 'channelgrid_field_'],
                ['load.dbforge'],
                ['db.delete', 'grid_columns', ['content_type' => 'channel']],
            ],
            $state->calls
        );
    }

    /**
     * It bubbles list_tables failures and performs no cleanup side effects.
     *
     * @return void
     */
    public function testDeleteContentOfTypeBubblesListTablesExceptionAndSkipsDbforgeAndDelete(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function list_tables($prefix)
            {
                $this->state->calls[] = ['db.list_tables', $prefix];
                throw new \RuntimeException('list_tables failed');
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        try {
            $model->delete_content_of_type('channel');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('list_tables failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.list_tables', 'channelgrid_field_'],
            ],
            $state->calls
        );
    }

    /**
     * It bubbles dbforge-loader failures and skips table drops and metadata deletion.
     *
     * @return void
     */
    public function testDeleteContentOfTypeBubblesDbforgeLoaderExceptionAndSkipsCleanup(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
                throw new \RuntimeException('dbforge loader failed');
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function list_tables($prefix)
            {
                $this->state->calls[] = ['db.list_tables', $prefix];

                return ['channel_grid_field_6'];
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        try {
            $model->delete_content_of_type('channel');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('dbforge loader failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.list_tables', 'channelgrid_field_'],
                ['load.dbforge'],
            ],
            $state->calls
        );
    }

    /**
     * It bubbles drop_table failures and skips metadata deletion afterward.
     *
     * @return void
     */
    public function testDeleteContentOfTypeBubblesDropTableExceptionAndSkipsDelete(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
                throw new \RuntimeException('drop_table failed');
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function list_tables($prefix)
            {
                $this->state->calls[] = ['db.list_tables', $prefix];

                return ['channel_grid_field_8', 'channel_grid_field_9'];
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        try {
            $model->delete_content_of_type('channel');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('drop_table failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.list_tables', 'channelgrid_field_'],
                ['load.dbforge'],
                ['dbforge.drop_table', 'channel_grid_field_8'],
            ],
            $state->calls
        );
    }

    /**
     * It bubbles metadata delete failures after all matching tables are dropped.
     *
     * @return void
     */
    public function testDeleteContentOfTypeBubblesDeleteExceptionAfterDroppingTables(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function list_tables($prefix)
            {
                $this->state->calls[] = ['db.list_tables', $prefix];

                return ['fluid_field_grid_field_41', 'fluid_field_grid_field_42'];
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
                throw new \RuntimeException('delete failed');
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        try {
            $model->delete_content_of_type('fluid_field');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('delete failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.list_tables', 'fluid_fieldgrid_field_'],
                ['load.dbforge'],
                ['dbforge.drop_table', 'fluid_field_grid_field_41'],
                ['dbforge.drop_table', 'fluid_field_grid_field_42'],
                ['db.delete', 'grid_columns', ['content_type' => 'fluid_field']],
            ],
            $state->calls
        );
    }

    /**
     * It updates an existing Grid column and passes array settings through unchanged.
     *
     * @return void
     */
    public function testSaveColSettingsUpdatesExistingColumnWithArraySettings(): void
    {
        $state = (object) ['calls' => []];
        $ftApiSettings = [
            'id_field' => 'col_id',
            'type_field' => 'col_type',
            'field_id' => 9,
            'content_type' => 'fluid_field',
        ];
        $column = [
            'field_id' => 9,
            'col_type' => 'text',
            'col_settings' => ['maxl' => 120],
            'col_label' => 'Summary',
        ];

        ee()->setMock('api_channel_fields', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function edit_datatype($colId, $colType, $colSettings, $ftApiSettings)
            {
                $this->state->calls[] = ['api.edit_datatype', $colId, $colType, $colSettings, $ftApiSettings];
            }

            public function setup_handler($colType)
            {
                $this->state->calls[] = ['api.setup_handler', $colType];
            }

            public function set_datatype($colId, $colSettings, $dbInfo, $native, $hasRelationData, $ftApiSettings)
            {
                $this->state->calls[] = ['api.set_datatype', $colId, $colSettings, $dbInfo, $native, $hasRelationData, $ftApiSettings];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function update($table, $payload)
            {
                $this->state->calls[] = ['db.update', $table, $payload];
            }

            public function insert($table, $payload)
            {
                $this->state->calls[] = ['db.insert', $table, $payload];
            }

            public function insert_id()
            {
                $this->state->calls[] = ['db.insert_id'];

                return 0;
            }
        });

        $model = $this->makeGridModelForSaveColSettingsTest($state, $ftApiSettings);
        $returnValue = $model->save_col_settings($column, 44, 'fluid_field');

        $this->assertSame(44, $returnValue);
        $this->assertSame(
            [
                ['model._get_ft_api_settings', 9, 'fluid_field'],
                ['api.edit_datatype', 44, 'text', ['maxl' => 120], $ftApiSettings],
                ['db.where', 'col_id', 44],
                ['db.update', 'grid_columns', $column],
            ],
            $state->calls
        );
    }

    /**
     * It decodes JSON settings before updating an existing Grid column.
     *
     * @return void
     */
    public function testSaveColSettingsDecodesJsonForExistingColumnUpdate(): void
    {
        $state = (object) ['calls' => []];
        $decodedSettings = ['format' => 'horizontal', 'rows' => 3];
        $ftApiSettings = [
            'id_field' => 'col_id',
            'type_field' => 'col_type',
            'field_id' => 12,
            'content_type' => 'channel',
        ];
        $column = [
            'field_id' => 12,
            'col_type' => 'relationship',
            'col_settings' => json_encode($decodedSettings),
            'col_label' => 'Related Entry',
        ];

        ee()->setMock('api_channel_fields', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function edit_datatype($colId, $colType, $colSettings, $ftApiSettings)
            {
                $this->state->calls[] = ['api.edit_datatype', $colId, $colType, $colSettings, $ftApiSettings];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function update($table, $payload)
            {
                $this->state->calls[] = ['db.update', $table, $payload];
            }
        });

        $model = $this->makeGridModelForSaveColSettingsTest($state, $ftApiSettings);
        $returnValue = $model->save_col_settings($column, 91);

        $this->assertSame(91, $returnValue);
        $this->assertSame(
            [
                ['model._get_ft_api_settings', 12, 'channel'],
                ['api.edit_datatype', 91, 'relationship', $decodedSettings, $ftApiSettings],
                ['db.where', 'col_id', 91],
                ['db.update', 'grid_columns', $column],
            ],
            $state->calls
        );
    }

    /**
     * It bubbles edit_datatype failures before persisting existing Grid column updates.
     *
     * @return void
     */
    public function testSaveColSettingsBubblesExistingColumnEditDatatypeFailureBeforeUpdate(): void
    {
        $state = (object) ['calls' => []];
        $ftApiSettings = [
            'id_field' => 'col_id',
            'type_field' => 'col_type',
            'field_id' => 2,
            'content_type' => 'channel',
        ];
        $column = [
            'field_id' => 2,
            'col_type' => 'textarea',
            'col_settings' => ['rows' => 5],
            'col_label' => 'Body',
        ];

        ee()->setMock('api_channel_fields', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function edit_datatype($colId, $colType, $colSettings, $ftApiSettings)
            {
                $this->state->calls[] = ['api.edit_datatype', $colId, $colType, $colSettings, $ftApiSettings];
                throw new \RuntimeException('edit failed');
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function update($table, $payload)
            {
                $this->state->calls[] = ['db.update', $table, $payload];
            }
        });

        $model = $this->makeGridModelForSaveColSettingsTest($state, $ftApiSettings);

        try {
            $model->save_col_settings($column, 13);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('edit failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['model._get_ft_api_settings', 2, 'channel'],
                ['api.edit_datatype', 13, 'textarea', ['rows' => 5], $ftApiSettings],
            ],
            $state->calls
        );
    }

    /**
     * It inserts a new Grid column and configures its fieldtype columns with decoded JSON settings.
     *
     * @return void
     */
    public function testSaveColSettingsInsertsNewColumnAndConfiguresDatatypeWithJsonSettings(): void
    {
        $state = (object) ['calls' => []];
        $decodedSettings = ['allowed_directories' => [4, 5], 'show_existing' => 'y'];
        $ftApiSettings = [
            'id_field' => 'col_id',
            'type_field' => 'col_type',
            'field_id' => 17,
            'content_type' => 'fluid_field',
        ];
        $column = [
            'field_id' => 17,
            'col_type' => 'file',
            'col_settings' => json_encode($decodedSettings),
            'col_label' => 'Attachment',
        ];

        ee()->setMock('api_channel_fields', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function setup_handler($colType)
            {
                $this->state->calls[] = ['api.setup_handler', $colType];
            }

            public function set_datatype($colId, $colSettings, $dbInfo, $native, $hasRelationData, $ftApiSettings)
            {
                $this->state->calls[] = ['api.set_datatype', $colId, $colSettings, $dbInfo, $native, $hasRelationData, $ftApiSettings];
            }

            public function edit_datatype($colId, $colType, $colSettings, $ftApiSettings)
            {
                $this->state->calls[] = ['api.edit_datatype', $colId, $colType, $colSettings, $ftApiSettings];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function insert($table, $payload)
            {
                $this->state->calls[] = ['db.insert', $table, $payload];
            }

            public function insert_id()
            {
                $this->state->calls[] = ['db.insert_id'];

                return 376;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function update($table, $payload)
            {
                $this->state->calls[] = ['db.update', $table, $payload];
            }
        });

        $model = $this->makeGridModelForSaveColSettingsTest($state, $ftApiSettings);
        $returnValue = $model->save_col_settings($column, false, 'fluid_field');

        $this->assertSame(376, $returnValue);
        $this->assertSame(
            [
                ['db.insert', 'grid_columns', $column],
                ['db.insert_id'],
                ['api.setup_handler', 'file'],
                ['model._get_ft_api_settings', 17, 'fluid_field'],
                ['api.set_datatype', 376, $decodedSettings, [], true, false, $ftApiSettings],
            ],
            $state->calls
        );
    }

    /**
     * It bubbles insert failures for new Grid columns and skips fieldtype setup.
     *
     * @return void
     */
    public function testSaveColSettingsBubblesNewColumnInsertFailureBeforeFieldtypeSetup(): void
    {
        $state = (object) ['calls' => []];
        $column = [
            'field_id' => 21,
            'col_type' => 'text',
            'col_settings' => ['maxl' => 255],
            'col_label' => 'Headline',
        ];

        ee()->setMock('api_channel_fields', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function setup_handler($colType)
            {
                $this->state->calls[] = ['api.setup_handler', $colType];
            }

            public function set_datatype($colId, $colSettings, $dbInfo, $native, $hasRelationData, $ftApiSettings)
            {
                $this->state->calls[] = ['api.set_datatype', $colId, $colSettings, $dbInfo, $native, $hasRelationData, $ftApiSettings];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function insert($table, $payload)
            {
                $this->state->calls[] = ['db.insert', $table, $payload];
                throw new \RuntimeException('insert failed');
            }

            public function insert_id()
            {
                $this->state->calls[] = ['db.insert_id'];

                return 0;
            }
        });

        $model = $this->makeGridModelForSaveColSettingsTest($state, [
            'id_field' => 'col_id',
            'type_field' => 'col_type',
            'field_id' => 21,
            'content_type' => 'channel',
        ]);

        try {
            $model->save_col_settings($column);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('insert failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.insert', 'grid_columns', $column],
            ],
            $state->calls
        );
    }

    public function testDeleteColumnsNormalizesScalarColumnIdAndDeletesDatatype(): void
    {
        $state = (object) ['calls' => []];
        $ftApiSettings = [
            'id_field' => 'col_id',
            'type_field' => 'col_type',
            'field_id' => 9,
            'content_type' => 'fluid_field',
        ];

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where_in($column, $values)
            {
                $this->state->calls[] = ['db.where_in', $column, $values];

                return $this;
            }

            public function delete($table)
            {
                $this->state->calls[] = ['db.delete', $table];
            }
        });

        ee()->setMock('api_channel_fields', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function setup_handler($fieldType)
            {
                $this->state->calls[] = ['api.setup_handler', $fieldType];
            }

            public function delete_datatype($columnId, $dbInfo, $ftApiSettings)
            {
                $this->state->calls[] = ['api.delete_datatype', $columnId, $dbInfo, $ftApiSettings];
            }
        });

        $model = $this->makeGridModelForDeleteColumnsTest($state, $ftApiSettings);
        $model->delete_columns(31, [31 => 'text'], 9, 'fluid_field');

        $this->assertSame(
            [
                ['db.where_in', 'col_id', [31]],
                ['db.delete', 'grid_columns'],
                ['api.setup_handler', 'text'],
                ['model._get_ft_api_settings', 9, 'fluid_field'],
                ['api.delete_datatype', 31, [], $ftApiSettings],
            ],
            $state->calls
        );
    }

    public function testDeleteColumnsProcessesEachColumnIdInArrayOrder(): void
    {
        $state = (object) ['calls' => []];
        $ftApiSettings = [
            'id_field' => 'col_id',
            'type_field' => 'col_type',
            'field_id' => 18,
            'content_type' => 'channel',
        ];

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where_in($column, $values)
            {
                $this->state->calls[] = ['db.where_in', $column, $values];

                return $this;
            }

            public function delete($table)
            {
                $this->state->calls[] = ['db.delete', $table];
            }
        });

        ee()->setMock('api_channel_fields', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function setup_handler($fieldType)
            {
                $this->state->calls[] = ['api.setup_handler', $fieldType];
            }

            public function delete_datatype($columnId, $dbInfo, $ftApiSettings)
            {
                $this->state->calls[] = ['api.delete_datatype', $columnId, $dbInfo, $ftApiSettings];
            }
        });

        $model = $this->makeGridModelForDeleteColumnsTest($state, $ftApiSettings);
        $model->delete_columns([12, 14], [12 => 'text', 14 => 'relationship'], 18, 'channel');

        $this->assertSame(
            [
                ['db.where_in', 'col_id', [12, 14]],
                ['db.delete', 'grid_columns'],
                ['api.setup_handler', 'text'],
                ['model._get_ft_api_settings', 18, 'channel'],
                ['api.delete_datatype', 12, [], $ftApiSettings],
                ['api.setup_handler', 'relationship'],
                ['model._get_ft_api_settings', 18, 'channel'],
                ['api.delete_datatype', 14, [], $ftApiSettings],
            ],
            $state->calls
        );
    }

    public function testDeleteColumnsSkipsFieldtypeCallsWhenColumnIdListIsEmpty(): void
    {
        $state = (object) ['calls' => []];
        $ftApiSettings = [
            'id_field' => 'col_id',
            'type_field' => 'col_type',
            'field_id' => 5,
            'content_type' => 'channel',
        ];

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where_in($column, $values)
            {
                $this->state->calls[] = ['db.where_in', $column, $values];

                return $this;
            }

            public function delete($table)
            {
                $this->state->calls[] = ['db.delete', $table];
            }
        });

        ee()->setMock('api_channel_fields', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function setup_handler($fieldType)
            {
                $this->state->calls[] = ['api.setup_handler', $fieldType];
            }

            public function delete_datatype($columnId, $dbInfo, $ftApiSettings)
            {
                $this->state->calls[] = ['api.delete_datatype', $columnId, $dbInfo, $ftApiSettings];
            }
        });

        $model = $this->makeGridModelForDeleteColumnsTest($state, $ftApiSettings);
        $model->delete_columns([], [], 5, 'channel');

        $this->assertSame(
            [
                ['db.where_in', 'col_id', []],
                ['db.delete', 'grid_columns'],
            ],
            $state->calls
        );
    }

    public function testDeleteColumnsBubblesDeleteDatatypeFailureAndStopsRemainingColumns(): void
    {
        $state = (object) ['calls' => []];
        $ftApiSettings = [
            'id_field' => 'col_id',
            'type_field' => 'col_type',
            'field_id' => 6,
            'content_type' => 'fluid_field',
        ];

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where_in($column, $values)
            {
                $this->state->calls[] = ['db.where_in', $column, $values];

                return $this;
            }

            public function delete($table)
            {
                $this->state->calls[] = ['db.delete', $table];
            }
        });

        ee()->setMock('api_channel_fields', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function setup_handler($fieldType)
            {
                $this->state->calls[] = ['api.setup_handler', $fieldType];
            }

            public function delete_datatype($columnId, $dbInfo, $ftApiSettings)
            {
                $this->state->calls[] = ['api.delete_datatype', $columnId, $dbInfo, $ftApiSettings];

                if ($columnId === 4) {
                    throw new \RuntimeException('delete_datatype failed');
                }
            }
        });

        $model = $this->makeGridModelForDeleteColumnsTest($state, $ftApiSettings);

        try {
            $model->delete_columns([4, 5], [4 => 'text', 5 => 'file'], 6, 'fluid_field');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('delete_datatype failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.where_in', 'col_id', [4, 5]],
                ['db.delete', 'grid_columns'],
                ['api.setup_handler', 'text'],
                ['model._get_ft_api_settings', 6, 'fluid_field'],
                ['api.delete_datatype', 4, [], $ftApiSettings],
            ],
            $state->calls
        );
    }

    public function testDeleteColumnsBubblesDeleteFailureBeforeFieldtypeHandlersRun(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where_in($column, $values)
            {
                $this->state->calls[] = ['db.where_in', $column, $values];

                return $this;
            }

            public function delete($table)
            {
                $this->state->calls[] = ['db.delete', $table];
                throw new \RuntimeException('delete failed');
            }
        });

        ee()->setMock('api_channel_fields', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function setup_handler($fieldType)
            {
                $this->state->calls[] = ['api.setup_handler', $fieldType];
            }

            public function delete_datatype($columnId, $dbInfo, $ftApiSettings)
            {
                $this->state->calls[] = ['api.delete_datatype', $columnId, $dbInfo, $ftApiSettings];
            }
        });

        $model = $this->makeGridModelForDeleteColumnsTest($state, [
            'id_field' => 'col_id',
            'type_field' => 'col_type',
            'field_id' => 14,
            'content_type' => 'channel',
        ]);

        try {
            $model->delete_columns([9], [9 => 'text'], 14, 'channel');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('delete failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.where_in', 'col_id', [9]],
                ['db.delete', 'grid_columns'],
            ],
            $state->calls
        );
    }

    /**
     * Verify delete_columns_of_type() groups columns by field and delegates deletes per field.
     *
     * @return void
     */
    public function testDeleteColumnsOfTypeGroupsColumnsByFieldAndDelegatesDeletePerField(): void
    {
        $state = (object) ['calls' => []];
        $gridColumns = [
            ['col_id' => 7, 'col_type' => 'file', 'field_id' => 10, 'content_type' => 'channel'],
            ['col_id' => 8, 'col_type' => 'file', 'field_id' => 10, 'content_type' => 'channel'],
            ['col_id' => 12, 'col_type' => 'file', 'field_id' => 22, 'content_type' => 'fluid_field'],
        ];

        $queryResult = new class($state, $gridColumns) {
            private $state;
            private $gridColumns;

            public function __construct($state, array $gridColumns)
            {
                $this->state = $state;
                $this->gridColumns = $gridColumns;
            }

            public function result_array()
            {
                $this->state->calls[] = ['db.result_array'];

                return $this->gridColumns;
            }
        };

        ee()->setMock('db', new class($state, $queryResult) {
            private $state;
            private $queryResult;

            public function __construct($state, $queryResult)
            {
                $this->state = $state;
                $this->queryResult = $queryResult;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];

                return $this->queryResult;
            }
        });

        $model = $this->makeGridModelForDeleteColumnsOfTypeTest($state);
        $model->delete_columns_of_type('file');

        $this->assertSame(
            [
                ['db.where', 'col_type', 'file'],
                ['db.get', 'grid_columns'],
                ['db.result_array'],
                ['model.delete_columns', [7, 8], [7 => 'file', 8 => 'file', 12 => 'file'], 10, 'channel'],
                ['model.delete_columns', [12], [7 => 'file', 8 => 'file', 12 => 'file'], 22, 'fluid_field'],
            ],
            $state->calls
        );
    }

    /**
     * Verify delete_columns_of_type() is a no-op when no matching columns exist.
     *
     * @return void
     */
    public function testDeleteColumnsOfTypeSkipsDeleteWhenNoMatchingColumnsFound(): void
    {
        $state = (object) ['calls' => []];

        $queryResult = new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function result_array()
            {
                $this->state->calls[] = ['db.result_array'];

                return [];
            }
        };

        ee()->setMock('db', new class($state, $queryResult) {
            private $state;
            private $queryResult;

            public function __construct($state, $queryResult)
            {
                $this->state = $state;
                $this->queryResult = $queryResult;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];

                return $this->queryResult;
            }
        });

        $model = $this->makeGridModelForDeleteColumnsOfTypeTest($state);
        $model->delete_columns_of_type('relationship');

        $this->assertSame(
            [
                ['db.where', 'col_type', 'relationship'],
                ['db.get', 'grid_columns'],
                ['db.result_array'],
            ],
            $state->calls
        );
    }

    /**
     * Verify delete_columns_of_type() bubbles delete failures and stops remaining fields.
     *
     * @return void
     */
    public function testDeleteColumnsOfTypeBubblesDeleteColumnsFailureAndStopsRemainingFields(): void
    {
        $state = (object) ['calls' => []];
        $gridColumns = [
            ['col_id' => 30, 'col_type' => 'textarea', 'field_id' => 3, 'content_type' => 'channel'],
            ['col_id' => 31, 'col_type' => 'textarea', 'field_id' => 4, 'content_type' => 'channel'],
        ];

        $queryResult = new class($state, $gridColumns) {
            private $state;
            private $gridColumns;

            public function __construct($state, array $gridColumns)
            {
                $this->state = $state;
                $this->gridColumns = $gridColumns;
            }

            public function result_array()
            {
                $this->state->calls[] = ['db.result_array'];

                return $this->gridColumns;
            }
        };

        ee()->setMock('db', new class($state, $queryResult) {
            private $state;
            private $queryResult;

            public function __construct($state, $queryResult)
            {
                $this->state = $state;
                $this->queryResult = $queryResult;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];

                return $this->queryResult;
            }
        });

        $model = $this->makeGridModelForDeleteColumnsOfTypeTest($state, 3);

        try {
            $model->delete_columns_of_type('textarea');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('delete_columns failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.where', 'col_type', 'textarea'],
                ['db.get', 'grid_columns'],
                ['db.result_array'],
                ['model.delete_columns', [30], [30 => 'textarea', 31 => 'textarea'], 3, 'channel'],
            ],
            $state->calls
        );
    }

    public function testDeleteFieldDropsExistingDataTableThenDeletesColumnSettings(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function table_exists($table)
            {
                $this->state->calls[] = ['db.table_exists', $table];

                return true;
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        $model->delete_field(12, 'fluid_field');

        $this->assertSame(
            [
                ['db.table_exists', 'fluid_field_grid_field_12'],
                ['load.dbforge'],
                ['dbforge.drop_table', 'fluid_field_grid_field_12'],
                ['db.delete', 'grid_columns', ['field_id' => 12]],
            ],
            $state->calls
        );
    }

    public function testDeleteFieldSkipsDropWhenDataTableDoesNotExistAndStillDeletesColumnSettings(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function table_exists($table)
            {
                $this->state->calls[] = ['db.table_exists', $table];

                return false;
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        $model->delete_field(9, 'channel');

        $this->assertSame(
            [
                ['db.table_exists', 'channel_grid_field_9'],
                ['db.delete', 'grid_columns', ['field_id' => 9]],
            ],
            $state->calls
        );
    }

    public function testDeleteFieldBubblesDropTableExceptionAndSkipsColumnSettingsDelete(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
                throw new \RuntimeException('drop_table failed');
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function table_exists($table)
            {
                $this->state->calls[] = ['db.table_exists', $table];

                return true;
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        try {
            $model->delete_field(5, 'channel');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('drop_table failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.table_exists', 'channel_grid_field_5'],
                ['load.dbforge'],
                ['dbforge.drop_table', 'channel_grid_field_5'],
            ],
            $state->calls
        );
    }

    public function testDeleteFieldBubblesTableExistsExceptionAndSkipsDropAndDelete(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function table_exists($table)
            {
                $this->state->calls[] = ['db.table_exists', $table];
                throw new \RuntimeException('table_exists failed');
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        try {
            $model->delete_field(17, 'channel');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('table_exists failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.table_exists', 'channel_grid_field_17'],
            ],
            $state->calls
        );
    }

    public function testDeleteFieldBubblesDeleteExceptionAfterDropWhenTableExists(): void
    {
        $state = (object) ['calls' => []];

        ee()->setMock('load', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function dbforge()
            {
                $this->state->calls[] = ['load.dbforge'];
            }
        });

        ee()->setMock('dbforge', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function drop_table($table)
            {
                $this->state->calls[] = ['dbforge.drop_table', $table];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function table_exists($table)
            {
                $this->state->calls[] = ['db.table_exists', $table];

                return true;
            }

            public function delete($table, $where)
            {
                $this->state->calls[] = ['db.delete', $table, $where];
                throw new \RuntimeException('delete failed');
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        try {
            $model->delete_field(88, 'fluid_field');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('delete failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.table_exists', 'fluid_field_grid_field_88'],
                ['load.dbforge'],
                ['dbforge.drop_table', 'fluid_field_grid_field_88'],
                ['db.delete', 'grid_columns', ['field_id' => 88]],
            ],
            $state->calls
        );
    }

    public function testGetEntryBuildsExpectedQueryWithExplicitFluidFieldDataIdAndReturnsRows(): void
    {
        $state = (object) ['calls' => []];
        $expectedRows = [
            ['row_id' => 10, 'entry_id' => 123, 'fluid_field_data_id' => 45],
            ['row_id' => 11, 'entry_id' => 123, 'fluid_field_data_id' => 45],
        ];

        $queryResult = new class($state, $expectedRows) {
            private $state;
            private $expectedRows;

            public function __construct($state, array $expectedRows)
            {
                $this->state = $state;
                $this->expectedRows = $expectedRows;
            }

            public function result_array()
            {
                $this->state->calls[] = ['db.result_array'];

                return $this->expectedRows;
            }
        };

        ee()->setMock('db', new class($state, $queryResult) {
            private $state;
            private $queryResult;

            public function __construct($state, $queryResult)
            {
                $this->state = $state;
                $this->queryResult = $queryResult;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];

                return $this->queryResult;
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();
        $returnValue = $model->get_entry(123, 12, 'channel', 45);

        $this->assertSame($expectedRows, $returnValue);
        $this->assertSame(
            [
                ['db.where', 'entry_id', 123],
                ['db.where', 'fluid_field_data_id', 45],
                ['db.get', 'channel_grid_field_12'],
                ['db.result_array'],
            ],
            $state->calls
        );
    }

    public function testGetEntryUsesDefaultFluidFieldDataIdWhenArgumentIsOmitted(): void
    {
        $state = (object) ['calls' => []];
        $expectedRows = [['row_id' => 1]];

        $queryResult = new class($state, $expectedRows) {
            private $state;
            private $expectedRows;

            public function __construct($state, array $expectedRows)
            {
                $this->state = $state;
                $this->expectedRows = $expectedRows;
            }

            public function result_array()
            {
                $this->state->calls[] = ['db.result_array'];

                return $this->expectedRows;
            }
        };

        ee()->setMock('db', new class($state, $queryResult) {
            private $state;
            private $queryResult;

            public function __construct($state, $queryResult)
            {
                $this->state = $state;
                $this->queryResult = $queryResult;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];

                return $this->queryResult;
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();
        $returnValue = $model->get_entry(10, 5, 'fluid_field');

        $this->assertSame($expectedRows, $returnValue);
        $this->assertSame(
            [
                ['db.where', 'entry_id', 10],
                ['db.where', 'fluid_field_data_id', 0],
                ['db.get', 'fluid_field_grid_field_5'],
                ['db.result_array'],
            ],
            $state->calls
        );
    }

    public function testGetEntryBubblesResultArrayException(): void
    {
        $state = (object) ['calls' => []];

        $queryResult = new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function result_array()
            {
                $this->state->calls[] = ['db.result_array'];
                throw new \RuntimeException('result_array failed');
            }
        };

        ee()->setMock('db', new class($state, $queryResult) {
            private $state;
            private $queryResult;

            public function __construct($state, $queryResult)
            {
                $this->state = $state;
                $this->queryResult = $queryResult;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];

                return $this->queryResult;
            }
        });

        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        try {
            $model->get_entry(88, 9, 'channel');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('result_array failed', $exception->getMessage());
        }

        $this->assertSame(
            [
                ['db.where', 'entry_id', 88],
                ['db.where', 'fluid_field_data_id', 0],
                ['db.get', 'channel_grid_field_9'],
                ['db.result_array'],
            ],
            $state->calls
        );
    }

    /**
     * It caches fetched rows and reuses the warm cache on the next call.
     *
     * @return void
     */
    public function testGetEntryRowsCachesRowsAndSkipsSecondQueryWhenCacheIsWarm(): void
    {
        $state = (object) [
            'calls' => [],
            'getCallCount' => 0,
            'fieldSearchCalls' => [],
        ];
        $rows = [
            ['row_id' => 4, 'entry_id' => 101, 'row_order' => 0, 'fluid_field_data_id' => 0, 'col_id_9' => 'first'],
            ['row_id' => 7, 'entry_id' => 101, 'row_order' => 1, 'fluid_field_data_id' => 0, 'col_id_9' => 'second'],
        ];

        ee()->setMock('db', new class($state, $rows) {
            private $state;
            private $rows;

            public function __construct($state, array $rows)
            {
                $this->state = $state;
                $this->rows = $rows;
            }

            public function where_in($column, $values)
            {
                $this->state->calls[] = ['db.where_in', $column, array_values($values)];

                return $this;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function order_by($field, $direction = '', $escape = null)
            {
                $this->state->calls[] = ['db.order_by', $field, $direction, $escape];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];
                $this->state->getCallCount++;

                return new class($this->state, $this->rows) {
                    private $state;
                    private $rows;

                    public function __construct($state, array $rows)
                    {
                        $this->state = $state;
                        $this->rows = $rows;
                    }

                    public function result_array()
                    {
                        $this->state->calls[] = ['db.result_array'];

                        return $this->rows;
                    }
                };
            }

            public function _compile_select($reset = false, $test = false)
            {
                $this->state->calls[] = ['db._compile_select', $reset, $test];

                return 'SELECT * FROM channel_grid_field_12';
            }
        });

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }

            public function getEntryData()
            {
                return [];
            }
        });

        $model = $this->makeGridModelForGetEntryRowsTest(
            $state,
            ['fixed_order' => '', 'search' => [], 'orderby' => '', 'sort' => 'asc']
        );

        $first = $model->get_entry_rows(101, 12, 'channel', ['ignored' => 'value'], false, 0);
        $second = $model->get_entry_rows(101, 12, 'channel', ['ignored' => 'value'], false, 0);

        $this->assertSame(1, $state->getCallCount);
        $this->assertSame($first, $second);
        $this->assertSame('first', $second[101][4]['col_id_9']);
        $this->assertSame('second', $second[101][7]['col_id_9']);
    }

    /**
     * It refreshes cached rows when fluid_field_data_id changes for the same marker.
     *
     * @return void
     */
    public function testGetEntryRowsRefreshesCacheWhenFluidFieldDataIdChanges(): void
    {
        $state = (object) [
            'calls' => [],
            'getCallCount' => 0,
            'fieldSearchCalls' => [],
        ];
        $rowsByCall = [
            [
                ['row_id' => 1, 'entry_id' => 22, 'row_order' => 0, 'fluid_field_data_id' => 5, 'col_id_2' => 'first'],
            ],
            [
                ['row_id' => 2, 'entry_id' => 22, 'row_order' => 0, 'fluid_field_data_id' => 9, 'col_id_2' => 'second'],
            ],
        ];

        ee()->setMock('db', new class($state, $rowsByCall) {
            private $state;
            private $rowsByCall;

            public function __construct($state, array $rowsByCall)
            {
                $this->state = $state;
                $this->rowsByCall = $rowsByCall;
            }

            public function where_in($column, $values)
            {
                $this->state->calls[] = ['db.where_in', $column, array_values($values)];

                return $this;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function order_by($field, $direction = '', $escape = null)
            {
                $this->state->calls[] = ['db.order_by', $field, $direction, $escape];

                return $this;
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];
                $rows = $this->rowsByCall[$this->state->getCallCount] ?? [];
                $this->state->getCallCount++;

                return new class($rows) {
                    private $rows;

                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    public function result_array()
                    {
                        return $this->rows;
                    }
                };
            }

            public function _compile_select($reset = false, $test = false)
            {
                return 'SELECT * FROM channel_grid_field_3';
            }
        });

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }

            public function getEntryData()
            {
                return [];
            }
        });

        $model = $this->makeGridModelForGetEntryRowsTest(
            $state,
            ['fixed_order' => '', 'search' => [], 'orderby' => '', 'sort' => 'asc']
        );

        $model->get_entry_rows([22], 3, 'channel', [], false, 5);
        $second = $model->get_entry_rows([22], 3, 'channel', [], false, 9);

        $this->assertSame(2, $state->getCallCount);
        $this->assertSame('second', $second[22][2]['col_id_2']);
    }

    /**
     * It reuses the same cache marker when only non-database options change.
     *
     * @return void
     */
    public function testGetEntryRowsCacheMarkerIgnoresNonDatabaseOptions(): void
    {
        $state = (object) [
            'getCallCount' => 0,
        ];
        $rows = [
            ['row_id' => 11, 'entry_id' => 42, 'row_order' => 0, 'fluid_field_data_id' => 0, 'col_id_3' => 'alpha'],
        ];

        ee()->setMock('db', new class($state, $rows) {
            private $state;
            private $rows;

            public function __construct($state, array $rows)
            {
                $this->state = $state;
                $this->rows = $rows;
            }

            public function where_in($column, $values)
            {
                return $this;
            }

            public function where($column, $value)
            {
                return $this;
            }

            public function order_by($field, $direction = '', $escape = null)
            {
                return $this;
            }

            public function get($table)
            {
                $this->state->getCallCount++;

                return new class($this->rows) {
                    private $rows;

                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    public function result_array()
                    {
                        return $this->rows;
                    }
                };
            }
        });

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }

            public function getEntryData()
            {
                return [];
            }
        });

        $model = $this->makeGridModelForGetEntryRowsMarkerTest();

        $first = $model->get_entry_rows([42], 12, 'channel', [
            'fixed_order' => '',
            'search' => ['title' => 'news'],
            'orderby' => 'row_order',
            'sort' => 'asc',
            'limit' => 1,
            'offset' => 0,
            'backspace' => 0,
        ], false, 0);
        $second = $model->get_entry_rows([42], 12, 'channel', [
            'fixed_order' => '',
            'search' => ['title' => 'news'],
            'orderby' => 'row_order',
            'sort' => 'asc',
            'limit' => 999,
            'offset' => 45,
            'backspace' => 200,
        ], false, 0);

        $this->assertSame(1, $state->getCallCount);
        $this->assertSame('alpha', $first[42][11]['col_id_3']);
        $this->assertSame('alpha', $second[42][11]['col_id_3']);

        $expectedMarker = md5(json_encode([
            'fixed_order' => false,
            'search' => ['title' => 'news'],
            'orderby' => 'row_order',
            'sort' => 'asc',
        ]));
        $gridData = $model->get_grid_data();
        $this->assertArrayHasKey($expectedMarker, $gridData['channel'][12]);
    }

    /**
     * It creates distinct cache markers when database-impacting options differ.
     *
     * @return void
     */
    public function testGetEntryRowsCacheMarkerChangesWhenDatabaseOptionsChange(): void
    {
        $state = (object) [
            'getCallCount' => 0,
            'rowsByCall' => [
                [
                    ['row_id' => 1, 'entry_id' => 88, 'row_order' => 0, 'fluid_field_data_id' => 0, 'col_id_4' => 'first'],
                ],
                [
                    ['row_id' => 2, 'entry_id' => 88, 'row_order' => 1, 'fluid_field_data_id' => 0, 'col_id_4' => 'second'],
                ],
            ],
        ];

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where_in($column, $values)
            {
                return $this;
            }

            public function where($column, $value)
            {
                return $this;
            }

            public function order_by($field, $direction = '', $escape = null)
            {
                return $this;
            }

            public function get($table)
            {
                $rows = $this->state->rowsByCall[$this->state->getCallCount] ?? [];
                $this->state->getCallCount++;

                return new class($rows) {
                    private $rows;

                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    public function result_array()
                    {
                        return $this->rows;
                    }
                };
            }
        });

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }

            public function getEntryData()
            {
                return [];
            }
        });

        $model = $this->makeGridModelForGetEntryRowsMarkerTest();

        $first = $model->get_entry_rows([88], 12, 'channel', [
            'fixed_order' => '',
            'search' => ['title' => 'alpha'],
            'orderby' => 'row_order',
            'sort' => 'asc',
        ], false, 0);
        $second = $model->get_entry_rows([88], 12, 'channel', [
            'fixed_order' => '',
            'search' => ['title' => 'beta'],
            'orderby' => 'row_order',
            'sort' => 'asc',
            'limit' => 50,
        ], false, 0);

        $this->assertSame(2, $state->getCallCount);
        $this->assertSame('first', $first[88][1]['col_id_4']);
        $this->assertSame('second', $second[88][2]['col_id_4']);

        $firstMarker = md5(json_encode([
            'fixed_order' => false,
            'search' => ['title' => 'alpha'],
            'orderby' => 'row_order',
            'sort' => 'asc',
        ]));
        $secondMarker = md5(json_encode([
            'fixed_order' => false,
            'search' => ['title' => 'beta'],
            'orderby' => 'row_order',
            'sort' => 'asc',
        ]));

        $gridData = $model->get_grid_data();
        $this->assertArrayHasKey($firstMarker, $gridData['channel'][12]);
        $this->assertArrayHasKey($secondMarker, $gridData['channel'][12]);
    }

    /**
     * It returns an empty grid-data cache before any row-loading public API is called.
     *
     * @return void
     */
    public function testGetGridDataReturnsEmptyArrayBeforeRowsAreLoaded(): void
    {
        $model = (new \ReflectionClass(\Grid_model::class))->newInstanceWithoutConstructor();

        $this->assertSame([], $model->get_grid_data());
    }

    /**
     * It exposes the rows cached by get_entry_rows() with stable nested keys.
     *
     * @return void
     */
    public function testGetGridDataReturnsRowsCachedByGetEntryRows(): void
    {
        ee()->setMock('db', new class {
            public function where_in($column, $values)
            {
                return $this;
            }

            public function where($column, $value)
            {
                return $this;
            }

            public function order_by($field, $direction = '', $escape = null)
            {
                return $this;
            }

            public function get($table)
            {
                return new class {
                    public function result_array()
                    {
                        return [
                            ['row_id' => 15, 'entry_id' => 9, 'row_order' => 0, 'fluid_field_data_id' => 0, 'col_id_4' => 'alpha'],
                            ['row_id' => 16, 'entry_id' => 9, 'row_order' => 1, 'fluid_field_data_id' => 0, 'col_id_4' => 'beta'],
                        ];
                    }
                };
            }
        });

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }

            public function getEntryData()
            {
                return [];
            }
        });

        $model = $this->makeGridModelForGetEntryRowsMarkerTest();
        $model->get_entry_rows([9], 12, 'channel', [
            'fixed_order' => '',
            'search' => ['title' => 'alpha'],
            'orderby' => 'row_order',
            'sort' => 'asc',
        ], false, 0);

        $marker = md5(json_encode([
            'fixed_order' => false,
            'search' => ['title' => 'alpha'],
            'orderby' => 'row_order',
            'sort' => 'asc',
        ]));

        $this->assertSame(
            [
                'channel' => [
                    12 => [
                        $marker => [
                            'params' => [
                                'fixed_order' => '',
                                'search' => ['title' => 'alpha'],
                                'orderby' => 'row_order',
                                'sort' => 'asc',
                            ],
                            'fluid_field_data_id' => 0,
                            9 => [
                                15 => [
                                    'row_id' => 15,
                                    'entry_id' => 9,
                                    'row_order' => 0,
                                    'fluid_field_data_id' => 0,
                                    'col_id_4' => 'alpha',
                                ],
                                16 => [
                                    'row_id' => 16,
                                    'entry_id' => 9,
                                    'row_order' => 1,
                                    'fluid_field_data_id' => 0,
                                    'col_id_4' => 'beta',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $model->get_grid_data()
        );
    }

    /**
     * It uses fixed-order and search options and delegates row loading to the grid_query hook.
     *
     * @return void
     */
    public function testGetEntryRowsUsesFixedOrderSearchAndGridQueryHookWhenActive(): void
    {
        $state = (object) [
            'calls' => [],
            'getCallCount' => 0,
            'fieldSearchCalls' => [],
            'gridQueryCalls' => [],
        ];
        $hookRows = [
            ['row_id' => 30, 'entry_id' => 5, 'row_order' => 0, 'fluid_field_data_id' => 0, 'col_id_4' => 'hooked'],
        ];

        ee()->setMock('functions', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function ar_andor_string($value, $column)
            {
                $this->state->calls[] = ['functions.ar_andor_string', $value, $column];
            }
        });

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where_in($column, $values)
            {
                $this->state->calls[] = ['db.where_in', $column, array_values($values)];

                return $this;
            }

            public function where($column, $value)
            {
                $this->state->calls[] = ['db.where', $column, $value];

                return $this;
            }

            public function order_by($field, $direction = '', $escape = null)
            {
                $this->state->calls[] = ['db.order_by', $field, $direction, $escape];

                return $this;
            }

            public function _compile_select($reset = false, $test = false)
            {
                $this->state->calls[] = ['db._compile_select', $reset, $test];

                return 'SELECT row_id FROM channel_grid_field_12';
            }

            public function get($table)
            {
                $this->state->calls[] = ['db.get', $table];
                $this->state->getCallCount++;
                throw new \RuntimeException('db.get should not be called when grid_query hook is active.');
            }
        });

        ee()->setMock('extensions', new class($state, $hookRows) {
            private $state;
            private $hookRows;

            public function __construct($state, array $hookRows)
            {
                $this->state = $state;
                $this->hookRows = $hookRows;
            }

            public function active_hook($name)
            {
                return $name === 'grid_query';
            }

            public function call($name, $entryIds, $fieldId, $contentType, $table, $sql)
            {
                $this->state->gridQueryCalls[] = [$name, $entryIds, $fieldId, $contentType, $table, $sql];

                return $this->hookRows;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }

            public function getEntryData()
            {
                return [];
            }
        });

        $model = $this->makeGridModelForGetEntryRowsTest(
            $state,
            [
                'fixed_order' => '30|10',
                'search' => ['title' => 'alpha'],
                'orderby' => 'random',
                'sort' => 'desc',
            ]
        );

        $result = $model->get_entry_rows([5], 12, 'channel', [], false, 0);

        $this->assertSame('hooked', $result[5][30]['col_id_4']);
        $this->assertSame(0, $state->getCallCount);
        $this->assertContains(['functions.ar_andor_string', '30|10', 'row_id'], $state->calls);
        $this->assertSame(
            ['grid_query', [5], 12, 'channel', 'channel_grid_field_12', 'SELECT row_id FROM channel_grid_field_12'],
            $state->gridQueryCalls[0]
        );
        $this->assertCount(1, $state->fieldSearchCalls);
        $this->assertTrue($state->fieldSearchCalls[0][3]);
    }

    /**
     * It bubbles database query exceptions when the grid_query hook is inactive.
     *
     * @return void
     */
    public function testGetEntryRowsBubblesDatabaseQueryExceptionWhenHookIsInactive(): void
    {
        $state = (object) [
            'calls' => [],
            'getCallCount' => 0,
            'fieldSearchCalls' => [],
        ];

        ee()->setMock('db', new class($state) {
            private $state;

            public function __construct($state)
            {
                $this->state = $state;
            }

            public function where_in($column, $values)
            {
                return $this;
            }

            public function where($column, $value)
            {
                return $this;
            }

            public function order_by($field, $direction = '', $escape = null)
            {
                return $this;
            }

            public function get($table)
            {
                $this->state->getCallCount++;
                throw new \RuntimeException('grid row query failed');
            }

            public function _compile_select($reset = false, $test = false)
            {
                return 'SELECT * FROM channel_grid_field_12';
            }
        });

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }

            public function getEntryData()
            {
                return [];
            }
        });

        $model = $this->makeGridModelForGetEntryRowsTest(
            $state,
            ['fixed_order' => '', 'search' => [], 'orderby' => '', 'sort' => 'asc']
        );

        try {
            $model->get_entry_rows([5], 12, 'channel', [], false, 0);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('grid row query failed', $exception->getMessage());
        }

        $this->assertSame(1, $state->getCallCount);
    }

    /**
     * It overrides cached row data with Live Preview rows and sorts by original row_id descending.
     *
     * @return void
     */
    public function testGetEntryRowsLivePreviewOverrideSortsByOriginalRowIdDescending(): void
    {
        $state = (object) [
            'calls' => [],
            'getCallCount' => 0,
            'fieldSearchCalls' => [],
        ];
        $rows = [
            ['row_id' => 6, 'entry_id' => 50, 'row_order' => 0, 'fluid_field_data_id' => 0, 'col_id_9' => 'db'],
        ];

        ee()->setMock('db', new class($state, $rows) {
            private $state;
            private $rows;

            public function __construct($state, array $rows)
            {
                $this->state = $state;
                $this->rows = $rows;
            }

            public function where_in($column, $values)
            {
                return $this;
            }

            public function where($column, $value)
            {
                return $this;
            }

            public function order_by($field, $direction = '', $escape = null)
            {
                return $this;
            }

            public function get($table)
            {
                $this->state->getCallCount++;

                return new class($this->rows) {
                    private $rows;

                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    public function result_array()
                    {
                        return $this->rows;
                    }
                };
            }

            public function _compile_select($reset = false, $test = false)
            {
                return 'SELECT * FROM channel_grid_field_12';
            }
        });

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return [
                    'entry_id' => 50,
                    'field_id_12' => [
                        'rows' => [
                            2 => ['col_id_9' => 'beta'],
                            10 => ['col_id_9' => 'alpha'],
                        ],
                    ],
                ];
            }
        });

        $model = $this->makeGridModelForGetEntryRowsTest(
            $state,
            ['fixed_order' => '', 'search' => [], 'orderby' => 'row_id', 'sort' => 'desc']
        );

        $result = $model->get_entry_rows([50], 12, 'channel', [], false, 0);
        $rows = array_values($result[50]);

        $this->assertSame(10, $rows[0]['orig_row_id']);
        $this->assertSame(2, $rows[1]['orig_row_id']);
        $this->assertSame(crc32(10), $rows[0]['row_id']);
        $this->assertSame(crc32(2), $rows[1]['row_id']);
    }

    /**
     * It applies preview-data search conditions and removes preview rows that fail all conditions.
     *
     * @return void
     */
    public function testGetEntryRowsLivePreviewSearchRemovesRowsThatFailAllConditions(): void
    {
        $state = (object) [
            'calls' => [],
            'getCallCount' => 0,
            'fieldSearchCalls' => [],
        ];
        $rows = [
            ['row_id' => 1, 'entry_id' => 42, 'row_order' => 0, 'fluid_field_data_id' => '15,33'],
        ];

        ee()->setMock('db', new class($rows) {
            private $rows;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function where_in($column, $values)
            {
                return $this;
            }

            public function where($column, $value)
            {
                return $this;
            }

            public function order_by($field, $direction = '', $escape = null)
            {
                return $this;
            }

            public function get($table)
            {
                return new class($this->rows) {
                    private $rows;

                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    public function result_array()
                    {
                        return $this->rows;
                    }
                };
            }

            public function _compile_select($reset = false, $test = false)
            {
                return 'SELECT * FROM channel_grid_field_9';
            }
        });

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return [
                    'entry_id' => 42,
                    15 => [
                        'fields' => [
                            33 => [[
                                'field_id_9' => [
                                    'rows' => [
                                        'first' => ['col_id_2' => 'drop'],
                                        'second' => ['col_id_2' => 'keep'],
                                    ],
                                ],
                            ]],
                        ],
                    ],
                ];
            }
        });

        $model = $this->makeGridModelForGetEntryRowsTest(
            $state,
            ['fixed_order' => '', 'search' => ['status' => 'open'], 'orderby' => '', 'sort' => 'asc'],
            ["col_id_2 = 'keep'"]
        );

        $result = $model->get_entry_rows([42], 9, 'channel', [], false, '15,33');
        $rows = array_values($result[42]);

        $this->assertCount(1, $rows);
        $this->assertSame('second', $rows[0]['orig_row_id']);
        $this->assertSame('keep', $rows[0]['col_id_2']);
        $this->assertCount(3, $state->fieldSearchCalls);
        $this->assertTrue($state->fieldSearchCalls[0][3]);
        $this->assertFalse($state->fieldSearchCalls[1][3]);
        $this->assertFalse($state->fieldSearchCalls[2][3]);
    }

    /**
     * It sorts Live Preview rows by row_order ascending when orderby is random.
     *
     * @return void
     */
    public function testGetEntryRowsLivePreviewRandomOrderSortsByRowOrderAscending(): void
    {
        $state = (object) [
            'calls' => [],
            'getCallCount' => 0,
            'fieldSearchCalls' => [],
        ];
        $rows = [
            ['row_id' => 2, 'entry_id' => 71, 'row_order' => 0, 'fluid_field_data_id' => 0],
        ];

        ee()->setMock('db', new class($rows) {
            private $rows;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function where_in($column, $values)
            {
                return $this;
            }

            public function where($column, $value)
            {
                return $this;
            }

            public function order_by($field, $direction = '', $escape = null)
            {
                return $this;
            }

            public function get($table)
            {
                return new class($this->rows) {
                    private $rows;

                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    public function result_array()
                    {
                        return $this->rows;
                    }
                };
            }

            public function _compile_select($reset = false, $test = false)
            {
                return 'SELECT * FROM channel_grid_field_4';
            }
        });

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return [
                    'entry_id' => 71,
                    'field_id_4' => [
                        'rows' => [
                            'bbb' => ['col_id_1' => 'second'],
                            'aaa' => ['col_id_1' => 'first'],
                        ],
                    ],
                ];
            }
        });

        $model = $this->makeGridModelForGetEntryRowsTest(
            $state,
            ['fixed_order' => '', 'search' => [], 'orderby' => 'random', 'sort' => 'asc']
        );

        $result = $model->get_entry_rows([71], 4, 'channel', [], false, 0);
        $rows = array_values($result[71]);

        $this->assertSame('bbb', $rows[0]['orig_row_id']);
        $this->assertSame('aaa', $rows[1]['orig_row_id']);
    }

    /**
     * It evaluates preview-data condition comparators through get_entry_rows() search filtering.
     *
     * @dataProvider previewDataConditionComparatorProvider
     * @param string $condition SQL-like condition generated by _field_search().
     * @param mixed $previewValue Live Preview row value used in the preview condition.
     * @param bool $expectedPass Whether previewDataPassesCondition() should keep the row.
     * @return void
     */
    public function testGetEntryRowsLivePreviewSearchEvaluatesPreviewDataConditionComparators(
        string $condition,
        $previewValue,
        bool $expectedPass
    ): void {
        $rows = $this->runPreviewConditionScenarioThroughGetEntryRows($condition, $previewValue);
        if ($expectedPass) {
            $this->assertCount(1, $rows);
            $this->assertSame('candidate', $rows[0]['orig_row_id']);

            return;
        }

        $this->assertCount(0, $rows);
    }

    /**
     * Provide preview-condition vectors that map to comparator and normalization branches.
     *
     * @return array
     */
    public static function previewDataConditionComparatorProvider(): array
    {
        return [
            'like_scalar_match' => ["col_id_2 LIKE '%bet%'", 'alphabet', true],
            'like_scalar_miss' => ["col_id_2 LIKE '%zzz%'", 'alphabet', false],
            'like_array_match' => ["col_id_2 LIKE '%bet%'", ['gamma', 'beta'], true],
            'like_array_miss' => ["col_id_2 LIKE '%zzz%'", ['gamma', 'beta'], false],
            'equals_array_match' => ["col_id_2 = 'keep'", ['drop', 'keep'], true],
            'equals_scalar_miss' => ["col_id_2 = 'keep'", 'drop', false],
            'not_equals_array_true' => ["col_id_2 != 'drop'", ['keep', 'other'], true],
            'not_equals_scalar_false' => ["col_id_2 != 'drop'", 'drop', false],
            'greater_than_true' => ["col_id_2 > '10'", '20', true],
            'less_than_true' => ["col_id_2 < '10'", '2', true],
            'greater_or_equal_false' => ["col_id_2 >= '10'", '9', false],
            'less_or_equal_true' => ["col_id_2 <= '10'", '10', true],
            'is_null_true_for_empty_string' => ["col_id_2 IS NULL", '', true],
            'is_not_null_true_for_value' => ["col_id_2 IS NOT NULL", 'present', true],
            'in_scalar_true' => ["col_id_2 IN ('keep','drop')", 'keep', true],
            'in_scalar_false' => ["col_id_2 IN ('keep','drop')", 'other', false],
            'in_array_true' => ["col_id_2 IN ('keep','drop')", ['other', 'drop'], true],
            'dotted_column_key' => ["t.col_id_2 = 'keep'", 'keep', true],
            'normalized_is_null_or_clause' => ["( col_id_2 = '' OR col_id_2 IS NULL )", '', true],
            'normalized_is_not_null_and_clause' => ["( col_id_2 != '' AND col_id_2 IS NOT NULL )", 'present', true],
            'unsupported_comparison_defaults_false' => ["col_id_2 <> 'keep'", 'keep', false],
        ];
    }

    /**
     * Build a Grid_model instance that records get_entry_rows() parameter handling.
     *
     * @param object $state Shared mutable test state.
     * @param array $validatedOptions Options returned from _validate_params().
     * @param array $fieldSearchConditions Conditions returned from _field_search().
     * @return Grid_model
     */
    private function makeGridModelForGetEntryRowsTest($state, array $validatedOptions, array $fieldSearchConditions = []): \Grid_model
    {
        return new class($state, $validatedOptions, $fieldSearchConditions) extends \Grid_model {
            private $state;
            private $validatedOptions;
            private $fieldSearchConditions;

            public function __construct($state, array $validatedOptions, array $fieldSearchConditions)
            {
                $this->state = $state;
                $this->validatedOptions = $validatedOptions;
                $this->fieldSearchConditions = $fieldSearchConditions;
            }

            protected function _validate_params($params, $field_id, $content_type)
            {
                $this->state->calls[] = ['model._validate_params', $params, $field_id, $content_type];

                return $this->validatedOptions;
            }

            protected function _field_search($search_terms, $field_id, $content_type = 'channel', $set_sql_query = true)
            {
                $this->state->fieldSearchCalls[] = [$search_terms, $field_id, $content_type, $set_sql_query];

                return $this->fieldSearchConditions;
            }
        };
    }

    /**
     * Build a Grid_model instance that preserves marker-relevant options.
     *
     * @return Grid_model
     */
    private function makeGridModelForGetEntryRowsMarkerTest(): \Grid_model
    {
        return new class extends \Grid_model {
            protected function _validate_params($params, $field_id, $content_type)
            {
                return array_merge(
                    [
                        'fixed_order' => '',
                        'search' => [],
                        'orderby' => 'row_order',
                        'sort' => 'asc',
                    ],
                    $params
                );
            }

            protected function _field_search($search_terms, $field_id, $content_type = 'channel', $set_sql_query = true)
            {
                return [];
            }
        };
    }

    /**
     * Run one preview-condition vector via get_entry_rows() and return resulting preview rows.
     *
     * @param string $condition SQL-like condition generated by _field_search().
     * @param mixed $previewValue Live Preview row value used in the preview condition.
     * @return array
     */
    private function runPreviewConditionScenarioThroughGetEntryRows(string $condition, $previewValue): array
    {
        $rows = [
            ['row_id' => 1, 'entry_id' => 42, 'row_order' => 0, 'fluid_field_data_id' => 0],
        ];

        ee()->setMock('db', new class($rows) {
            private $rows;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function where_in($column, $values)
            {
                return $this;
            }

            public function where($column, $value)
            {
                return $this;
            }

            public function order_by($field, $direction = '', $escape = null)
            {
                return $this;
            }

            public function get($table)
            {
                return new class($this->rows) {
                    private $rows;

                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    public function result_array()
                    {
                        return $this->rows;
                    }
                };
            }

            public function _compile_select($reset = false, $test = false)
            {
                return 'SELECT * FROM channel_grid_field_9';
            }
        });

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
        });

        ee()->setMock('LivePreview', new class($previewValue) {
            private $previewValue;

            public function __construct($previewValue)
            {
                $this->previewValue = $previewValue;
            }

            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return [
                    'entry_id' => 42,
                    'field_id_9' => [
                        'rows' => [
                            'candidate' => ['col_id_2' => $this->previewValue],
                        ],
                    ],
                ];
            }
        });

        $state = (object) [
            'calls' => [],
            'getCallCount' => 0,
            'fieldSearchCalls' => [],
        ];
        $model = $this->makeGridModelForGetEntryRowsTest(
            $state,
            ['fixed_order' => '', 'search' => ['status' => 'open'], 'orderby' => '', 'sort' => 'asc'],
            [$condition]
        );

        $result = $model->get_entry_rows([42], 9, 'channel', [], false, 0);

        return array_values($result[42]);
    }

    /**
     * Build a Grid_model instance that records ft-api settings requests.
     *
     * @param object $state Shared mutable test state.
     * @param array $ftApiSettings Settings returned by _get_ft_api_settings().
     * @return Grid_model
     */
    private function makeGridModelForSaveColSettingsTest($state, array $ftApiSettings): \Grid_model
    {
        return new class($state, $ftApiSettings) extends \Grid_model {
            private $state;
            private $ftApiSettings;

            public function __construct($state, $ftApiSettings)
            {
                $this->state = $state;
                $this->ftApiSettings = $ftApiSettings;
            }

            protected function _get_ft_api_settings($field_id, $content_type = 'channel')
            {
                $this->state->calls[] = ['model._get_ft_api_settings', $field_id, $content_type];

                return $this->ftApiSettings;
            }
        };
    }

    /**
     * Build a Grid_model instance that records ft-api settings requests for delete_columns().
     *
     * @param object $state Shared mutable test state.
     * @param array $ftApiSettings Settings returned by _get_ft_api_settings().
     * @return Grid_model
     */
    private function makeGridModelForDeleteColumnsTest($state, array $ftApiSettings): \Grid_model
    {
        return new class($state, $ftApiSettings) extends \Grid_model {
            private $state;
            private $ftApiSettings;

            public function __construct($state, $ftApiSettings)
            {
                $this->state = $state;
                $this->ftApiSettings = $ftApiSettings;
            }

            protected function _get_ft_api_settings($field_id, $content_type = 'channel')
            {
                $this->state->calls[] = ['model._get_ft_api_settings', $field_id, $content_type];

                return $this->ftApiSettings;
            }
        };
    }

    /**
     * Build a Grid_model instance that records delete_columns() calls from delete_columns_of_type().
     *
     * @param object $state Shared mutable test state.
     * @param int|null $throwOnFieldId Field ID that should trigger a RuntimeException.
     * @return Grid_model
     */
    private function makeGridModelForDeleteColumnsOfTypeTest($state, ?int $throwOnFieldId = null): \Grid_model
    {
        return new class($state, $throwOnFieldId) extends \Grid_model {
            private $state;
            private $throwOnFieldId;

            public function __construct($state, ?int $throwOnFieldId)
            {
                $this->state = $state;
                $this->throwOnFieldId = $throwOnFieldId;
            }

            public function delete_columns($column_ids, $column_types, $field_id, $content_type)
            {
                $this->state->calls[] = ['model.delete_columns', $column_ids, $column_types, $field_id, $content_type];

                if ($this->throwOnFieldId !== null && $field_id === $this->throwOnFieldId) {
                    throw new \RuntimeException('delete_columns failed');
                }
            }
        };
    }

}
