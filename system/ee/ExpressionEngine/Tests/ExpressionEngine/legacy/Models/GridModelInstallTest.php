<?php

use PHPUnit\Framework\TestCase;

if (!class_exists('CI_Model')) {
    class CI_Model
    {
    }
}

require_once SYSPATH . 'ee/legacy/models/grid_model.php';

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

}
