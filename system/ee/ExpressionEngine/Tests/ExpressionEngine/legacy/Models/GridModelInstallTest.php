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
}
