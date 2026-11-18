<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGetMemberSettingsTest extends TestCase
{
    /**
     * Reset singleton mocks between test runs.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    /**
     * Confirm the test exercises the real add-on file from PATH_ADDONS.
     *
     * @return void
     */
    public function testSqlStructureLoadsFromPathAddonsTargetFile(): void
    {
        $reflection = new ReflectionClass('Sql_structure');

        $this->assertSame(
            realpath(PATH_ADDONS . 'structure/sql.structure.php'),
            $reflection->getFileName()
        );
    }

    /**
     * It loads the current member row and decodes the stored nav state JSON.
     *
     * @return void
     */
    public function testGetMemberSettingsReturnsDecodedSettingsForCurrentMember(): void
    {
        $captured = (object) [
            'table' => null,
            'where' => null,
            'limit' => null,
            'row_array_calls' => 0,
        ];

        ee()->setMock('session', new class {
            /**
             * Return the current member identifier.
             *
             * @param string $key
             * @return int
             */
            public function userdata($key): int
            {
                return 9;
            }
        });
        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store the shared capture state for the fake query result.
             *
             * @param object $captured
             * @param TestCase $test
             * @return void
             */
            public function __construct(object $captured, TestCase $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            /**
             * Return a single saved member settings row.
             *
             * @param string $table
             * @param array $where
             * @param int $limit
             * @param int|null $offset
             * @return object
             */
            public function get_where($table, $where = null, $limit = null, $offset = null): object
            {
                $this->captured->table = $table;
                $this->captured->where = $where;
                $this->captured->limit = $limit;

                return $this->test->result(
                    [[
                        'site_id' => 1,
                        'member_id' => 9,
                        'theme' => 'expanded',
                        'nav_state' => '{"collapsed":[1,2],"pinned":true}',
                    ]],
                    $this->captured
                );
            }
        });

        $sql = $this->makeSql();

        $settings = $sql->get_member_settings();

        $this->assertSame('structure_members', $captured->table);
        $this->assertSame(
            ['site_id' => 1, 'member_id' => 9],
            $captured->where
        );
        $this->assertSame(1, $captured->limit);
        $this->assertSame(1, $captured->row_array_calls);
        $this->assertSame(1, $settings['site_id']);
        $this->assertSame(9, $settings['member_id']);
        $this->assertSame('expanded', $settings['theme']);
        $this->assertIsObject($settings['nav_state']);
        $this->assertSame([1, 2], $settings['nav_state']->collapsed);
        $this->assertTrue($settings['nav_state']->pinned);
    }

    /**
     * It returns null without hydrating a row when no member settings exist.
     *
     * @return void
     */
    public function testGetMemberSettingsReturnsNullWithoutHydratingMissingRow(): void
    {
        $captured = (object) [
            'table' => null,
            'where' => null,
            'limit' => null,
            'row_array_calls' => 0,
        ];

        ee()->setMock('session', new class {
            /**
             * Return the current member identifier.
             *
             * @param string $key
             * @return int
             */
            public function userdata($key): int
            {
                return 27;
            }
        });
        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store the shared capture state for the fake empty query result.
             *
             * @param object $captured
             * @param TestCase $test
             * @return void
             */
            public function __construct(object $captured, TestCase $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            /**
             * Return a zero-row result while leaving row hydration unreachable.
             *
             * @param string $table
             * @param array $where
             * @param int $limit
             * @param int|null $offset
             * @return object
             */
            public function get_where($table, $where = null, $limit = null, $offset = null): object
            {
                $this->captured->table = $table;
                $this->captured->where = $where;
                $this->captured->limit = $limit;

                return $this->test->result(
                    [[
                        'site_id' => 1,
                        'member_id' => 27,
                        'nav_state' => '{"ignored":true}',
                    ]],
                    $this->captured,
                    0
                );
            }
        });

        $sql = $this->makeSql();

        $this->assertNull($sql->get_member_settings());
        $this->assertSame('structure_members', $captured->table);
        $this->assertSame(
            ['site_id' => 1, 'member_id' => 27],
            $captured->where
        );
        $this->assertSame(1, $captured->limit);
        $this->assertSame(0, $captured->row_array_calls);
    }

    /**
     * Create a Sql_structure instance without running its constructor.
     *
     * @return Sql_structure
     */
    private function makeSql(): Sql_structure
    {
        $sql = (new ReflectionClass('Sql_structure'))->newInstanceWithoutConstructor();
        $sql->site_id = 1;
        $sql->cache = [];

        return $sql;
    }

    /**
     * Build a fake database result object for the method under test.
     *
     * @param array $rows
     * @param object $captured
     * @param int|null $numRows
     * @return object
     */
    public function result(array $rows, object $captured, ?int $numRows = null): object
    {
        return new class($rows, $captured, $numRows) {
            private $rows;
            private $captured;
            public $num_rows;

            /**
             * Store the configured rows and row count.
             *
             * @param array $rows
             * @param object $captured
             * @param int|null $numRows
             * @return void
             */
            public function __construct(array $rows, object $captured, ?int $numRows)
            {
                $this->rows = $rows;
                $this->captured = $captured;
                $this->num_rows = $numRows ?? count($rows);
            }

            /**
             * Return the configured row payload and record hydration.
             *
             * @return array
             */
            public function row_array(): array
            {
                $this->captured->row_array_calls++;

                return $this->rows[0] ?? [];
            }
        };
    }
}
