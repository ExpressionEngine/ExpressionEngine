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

}
