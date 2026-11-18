<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureRetrieveStructureUrlTitleFixture extends Sql_structure
{
    /**
     * Avoid constructor side effects in isolated tests.
     *
     * @return void
     */
    public function __construct()
    {
    }
}

class SqlStructureRetrieveStructureUrlTitleTest extends TestCase
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
     * It returns the leaf title without recursion when the parent ID is the zero-string sentinel.
     *
     * @return void
     */
    public function testRetrieveStructureUrlTitleReturnsLeafTitleWithoutRecursingPastZeroStringParent(): void
    {
        $captured = (object) [
            'select' => [],
            'from' => [],
            'where' => [],
            'join' => [],
            'limit' => [],
            'lookups' => [],
        ];

        ee()->setMock('db', new class($captured) {
            private $captured;
            private $entryId;

            /**
             * Store query capture state for the scenario.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Capture the selected fields.
             *
             * @param string $fields
             * @return self
             */
            public function select($fields = '*')
            {
                $this->captured->select[] = $fields;

                return $this;
            }

            /**
             * Capture the table source.
             *
             * @param string $table
             * @return self
             */
            public function from($table)
            {
                $this->captured->from[] = $table;

                return $this;
            }

            /**
             * Capture query constraints and track the current lookup entry.
             *
             * @param string $field
             * @param mixed $value
             * @return self
             */
            public function where($field, $value = null)
            {
                $this->captured->where[] = [$field, $value];

                if ($field === 'structure.entry_id') {
                    $this->entryId = (string) $value;
                }

                return $this;
            }

            /**
             * Capture the channel titles join.
             *
             * @param string $table
             * @param string $condition
             * @param string $type
             * @return self
             */
            public function join($table, $condition, $type = '')
            {
                $this->captured->join[] = [$table, $condition, $type];

                return $this;
            }

            /**
             * Capture the applied result limit.
             *
             * @param int $count
             * @return self
             */
            public function limit($count)
            {
                $this->captured->limit[] = $count;

                return $this;
            }

            /**
             * Return the queued row for the current entry lookup.
             *
             * @return object
             */
            public function get()
            {
                $rows = [
                    '22' => ['structure_url_title' => 'leaf-title', 'parent_id' => '0'],
                ];

                $this->captured->lookups[] = $this->entryId;

                return new class($rows[$this->entryId] ?? []) {
                    private $row;

                    /**
                     * Store the row payload for the lookup.
                     *
                     * @param array $row
                     * @return void
                     */
                    public function __construct(array $row)
                    {
                        $this->row = $row;
                    }

                    /**
                     * Return the current row as an array.
                     *
                     * @return array
                     */
                    public function row_array(): array
                    {
                        return $this->row;
                    }
                };
            }
        });

        $sql = $this->makeSql(9);

        $this->assertSame('leaf-title', $sql->retrieve_structure_url_title(22));
        $this->assertSame(['22'], $captured->lookups);
        $this->assertSame(
            ['structure.structure_url_title, structure.parent_id'],
            $captured->select
        );
        $this->assertSame(['structure'], $captured->from);
        $this->assertSame(
            [
                ['structure.entry_id', 22],
                ['structure.site_id', 9],
                ['channel_titles.site_id', 9],
            ],
            $captured->where
        );
        $this->assertSame(
            [['channel_titles', 'channel_titles.entry_id = structure.entry_id', '']],
            $captured->join
        );
        $this->assertSame([1], $captured->limit);
    }

    /**
     * It returns null when the structure row is missing for the current site.
     *
     * @return void
     */
    public function testRetrieveStructureUrlTitleReturnsNullWhenEntryLookupIsEmpty(): void
    {
        $captured = (object) [
            'lookups' => [],
            'where' => [],
        ];

        ee()->setMock('db', new class($captured) {
            private $captured;
            private $entryId;

            /**
             * Store query capture state for the scenario.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Support the select call chain.
             *
             * @param string $fields
             * @return self
             */
            public function select($fields = '*')
            {
                return $this;
            }

            /**
             * Support the from call chain.
             *
             * @param string $table
             * @return self
             */
            public function from($table)
            {
                return $this;
            }

            /**
             * Capture query constraints and track the missing entry lookup.
             *
             * @param string $field
             * @param mixed $value
             * @return self
             */
            public function where($field, $value = null)
            {
                $this->captured->where[] = [$field, $value];

                if ($field === 'structure.entry_id') {
                    $this->entryId = (string) $value;
                }

                return $this;
            }

            /**
             * Support the join call chain.
             *
             * @param string $table
             * @param string $condition
             * @param string $type
             * @return self
             */
            public function join($table, $condition, $type = '')
            {
                return $this;
            }

            /**
             * Support the limit call chain.
             *
             * @param int $count
             * @return self
             */
            public function limit($count)
            {
                return $this;
            }

            /**
             * Return an empty row set for the lookup.
             *
             * @return object
             */
            public function get()
            {
                $this->captured->lookups[] = $this->entryId;

                return new class {
                    /**
                     * Return the missing-row payload.
                     *
                     * @return array
                     */
                    public function row_array(): array
                    {
                        return [];
                    }
                };
            }
        });

        $sql = $this->makeSql(4);

        $this->assertNull($sql->retrieve_structure_url_title(404));
        $this->assertSame(['404'], $captured->lookups);
        $this->assertSame(
            [
                ['structure.entry_id', 404],
                ['structure.site_id', 4],
                ['channel_titles.site_id', 4],
            ],
            $captured->where
        );
    }

    /**
     * It captures method-specific subprocess coverage for recursive, leaf, and missing lookup paths.
     *
     * @return void
     */
    public function testRetrieveStructureUrlTitleCoverageSubprocessCoversAllBranches(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-retrieve-structure-url-title-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_retrieve_structure_url_title_subprocess.php';
        $command = escapeshellarg(PHP_BINARY) . ' -d xdebug.mode=coverage ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($outputFile);

        $result = json_decode(file_get_contents($outputFile), true);
        @unlink($outputFile);

        $this->assertIsArray($result);
        $this->assertSame(
            realpath(PATH_ADDONS . 'structure/sql.structure.php'),
            $result['real_module_path']
        );
        $this->assertSame('root/parent/child', $result['recursive_result']);
        $this->assertSame('leaf', $result['leaf_result']);
        $this->assertNull($result['missing_result']);
        $this->assertSame(['3', '2', '1', '4', '999'], $result['lookups']);
        $this->assertSame([1, 1, 1, 1, 1], $result['limit_calls']);
        $this->assertSame(
            ['structure.structure_url_title, structure.parent_id'],
            array_values(array_unique($result['select_calls']))
        );
        $this->assertSame(['structure'], array_values(array_unique($result['from_calls'])));
        $this->assertSame(
            [['channel_titles', 'channel_titles.entry_id = structure.entry_id', '']],
            array_values(array_unique($result['join_calls'], SORT_REGULAR))
        );

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_branches']);
        }
    }

    /**
     * Build a Sql_structure fixture without running its constructor.
     *
     * @param int $siteId
     * @return SqlStructureRetrieveStructureUrlTitleFixture
     */
    private function makeSql(int $siteId): SqlStructureRetrieveStructureUrlTitleFixture
    {
        $sql = new SqlStructureRetrieveStructureUrlTitleFixture();
        $sql->site_id = $siteId;
        $sql->cache = [];

        return $sql;
    }
}
