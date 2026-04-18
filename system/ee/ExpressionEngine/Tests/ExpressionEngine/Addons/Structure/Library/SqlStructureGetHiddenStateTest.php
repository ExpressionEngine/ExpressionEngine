<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGetHiddenStateFixture extends Sql_structure
{
    /**
     * Avoid constructor side effects in focused method tests.
     *
     * @return void
     */
    public function __construct()
    {
    }
}

class SqlStructureGetHiddenStateTest extends TestCase
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
     * It scopes the hidden-state lookup by entry and site ID and returns the stored flag.
     *
     * @return void
     */
    public function testGetHiddenStateUsesStructureLookupAndReturnsStoredFlag(): void
    {
        $captured = (object) [
            'calls' => [],
        ];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store the shared capture state and assertion helper.
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
             * Capture the selected column for the hidden-state query.
             *
             * @param string $field
             * @return object
             */
            public function select($field): object
            {
                $this->captured->calls[] = [
                    'method' => 'select',
                    'field' => $field,
                ];

                return $this;
            }

            /**
             * Capture the source table for the hidden-state query.
             *
             * @param string $table
             * @return object
             */
            public function from($table): object
            {
                $this->captured->calls[] = [
                    'method' => 'from',
                    'table' => $table,
                ];

                return $this;
            }

            /**
             * Capture the lookup filters for the hidden-state query.
             *
             * @param array|string $field
             * @param mixed|null $value
             * @return object
             */
            public function where($field, $value = null): object
            {
                $this->captured->calls[] = [
                    'method' => 'where',
                    'field' => $field,
                    'value' => $value,
                ];

                return $this;
            }

            /**
             * Return a populated structure row for the exercised lookup.
             *
             * @return object
             */
            public function get(): object
            {
                $this->captured->calls[] = [
                    'method' => 'get',
                ];

                return $this->test->result([['hidden' => 'y']], 1);
            }
        });

        $sql = $this->makeSql(13);

        $this->assertSame('y', $sql->get_hidden_state(42));
        $this->assertSame([
            [
                'method' => 'select',
                'field' => 'hidden',
            ],
            [
                'method' => 'from',
                'table' => 'structure',
            ],
            [
                'method' => 'where',
                'field' => ['entry_id' => 42, 'site_id' => 13],
                'value' => null,
            ],
            [
                'method' => 'get',
            ],
        ], $captured->calls);
    }

    /**
     * It returns the default hidden state when the structure lookup has no matching row.
     *
     * @return void
     */
    public function testGetHiddenStateReturnsDefaultWhenLookupHasNoMatches(): void
    {
        $captured = (object) [
            'calls' => [],
        ];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store the shared capture state and assertion helper.
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
             * Capture the selected column for the empty hidden-state query.
             *
             * @param string $field
             * @return object
             */
            public function select($field): object
            {
                $this->captured->calls[] = [
                    'method' => 'select',
                    'field' => $field,
                ];

                return $this;
            }

            /**
             * Capture the source table for the empty hidden-state query.
             *
             * @param string $table
             * @return object
             */
            public function from($table): object
            {
                $this->captured->calls[] = [
                    'method' => 'from',
                    'table' => $table,
                ];

                return $this;
            }

            /**
             * Capture the lookup filters for the empty hidden-state query.
             *
             * @param array|string $field
             * @param mixed|null $value
             * @return object
             */
            public function where($field, $value = null): object
            {
                $this->captured->calls[] = [
                    'method' => 'where',
                    'field' => $field,
                    'value' => $value,
                ];

                return $this;
            }

            /**
             * Return an empty result set for the exercised lookup.
             *
             * @return object
             */
            public function get(): object
            {
                $this->captured->calls[] = [
                    'method' => 'get',
                ];

                return $this->test->result([], 0);
            }
        });

        $sql = $this->makeSql(21);

        $this->assertSame('n', $sql->get_hidden_state(77));
        $this->assertSame([
            [
                'method' => 'select',
                'field' => 'hidden',
            ],
            [
                'method' => 'from',
                'table' => 'structure',
            ],
            [
                'method' => 'where',
                'field' => ['entry_id' => 77, 'site_id' => 21],
                'value' => null,
            ],
            [
                'method' => 'get',
            ],
        ], $captured->calls);
    }

    /**
     * It confirms exact line and branch coverage for the real method implementation.
     *
     * @return void
     */
    public function testGetHiddenStateCoverageSubprocessReportsFullCoverage(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-get-hidden-state-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_get_hidden_state_subprocess.php';
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
        $this->assertSame('y', $result['stored_result']);
        $this->assertSame('n', $result['missing_result']);
        $this->assertSame([
            [
                'method' => 'select',
                'field' => 'hidden',
            ],
            [
                'method' => 'from',
                'table' => 'structure',
            ],
            [
                'method' => 'where',
                'field' => ['entry_id' => 10, 'site_id' => 4],
                'value' => null,
            ],
            [
                'method' => 'get',
            ],
            [
                'method' => 'select',
                'field' => 'hidden',
            ],
            [
                'method' => 'from',
                'table' => 'structure',
            ],
            [
                'method' => 'where',
                'field' => ['entry_id' => 11, 'site_id' => 4],
                'value' => null,
            ],
            [
                'method' => 'get',
            ],
        ], $result['calls']);

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
     * @return SqlStructureGetHiddenStateFixture
     */
    private function makeSql(int $siteId): SqlStructureGetHiddenStateFixture
    {
        $sql = new SqlStructureGetHiddenStateFixture();
        $sql->site_id = $siteId;
        $sql->cache = [];

        return $sql;
    }

    /**
     * Build a lightweight CI result object for DB query mocks.
     *
     * @param array $rows
     * @param int|null $numRows
     * @return object
     */
    public function result(array $rows, ?int $numRows = null): object
    {
        return new class($rows, $numRows) {
            private $rows;
            public $num_rows;

            /**
             * Store the configured rows and row count.
             *
             * @param array $rows
             * @param int|null $numRows
             * @return void
             */
            public function __construct(array $rows, ?int $numRows)
            {
                $this->rows = $rows;
                $this->num_rows = $numRows ?? count($rows);
            }

            /**
             * Return the configured row count.
             *
             * @return int
             */
            public function num_rows(): int
            {
                return $this->num_rows;
            }

            /**
             * Return the first row as an object, matching CI DB behavior.
             *
             * @return object
             */
            public function row(): object
            {
                return (object) ($this->rows[0] ?? []);
            }
        };
    }
}
