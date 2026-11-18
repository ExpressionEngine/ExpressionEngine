<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGetStatusColorsTest extends TestCase
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
     * It maps each status name to its configured highlight color.
     *
     * @return void
     */
    public function testGetStatusColorsReturnsMappedHighlightsWhenRowsExist(): void
    {
        $captured = (object) [
            'queries' => [],
            'result_array_calls' => 0,
        ];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store shared capture state for the fake query result.
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
             * Return a populated statuses result for the wrapper method.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql): object
            {
                $this->captured->queries[] = $sql;

                return $this->test->result(
                    [
                        ['status' => 'open', 'highlight' => '#fff'],
                        ['status' => 'closed', 'highlight' => '#000'],
                    ],
                    $this->captured
                );
            }
        });

        $sql = $this->makeSql();

        $this->assertSame(
            ['open' => '#fff', 'closed' => '#000'],
            $sql->get_status_colors()
        );
        $this->assertSame(
            ['SELECT status, highlight FROM exp_statuses'],
            $captured->queries
        );
        $this->assertSame(1, $captured->result_array_calls);
    }

    /**
     * It returns an empty map and never hydrates rows when no statuses exist.
     *
     * @return void
     */
    public function testGetStatusColorsReturnsEmptyArrayWithoutHydratingRowsWhenNoStatusesExist(): void
    {
        $captured = (object) [
            'queries' => [],
            'result_array_calls' => 0,
        ];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store shared capture state for the fake empty query result.
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
             * Return a zero-row result while leaving placeholder rows unreachable.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql): object
            {
                $this->captured->queries[] = $sql;

                return $this->test->result(
                    [
                        ['status' => 'ignored', 'highlight' => '#111'],
                    ],
                    $this->captured,
                    0
                );
            }
        });

        $sql = $this->makeSql();

        $this->assertSame([], $sql->get_status_colors());
        $this->assertSame(
            ['SELECT status, highlight FROM exp_statuses'],
            $captured->queries
        );
        $this->assertSame(0, $captured->result_array_calls);
    }

    /**
     * It returns an empty map when the result payload is empty after a positive row count.
     *
     * @return void
     */
    public function testGetStatusColorsReturnsEmptyArrayWhenPositiveRowCountHasNoPayload(): void
    {
        $captured = (object) [
            'queries' => [],
            'result_array_calls' => 0,
        ];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store shared capture state for the fake inconsistent query result.
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
             * Return a positive row count paired with an empty payload.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql): object
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([], $this->captured, 1);
            }
        });

        $sql = $this->makeSql();

        $this->assertSame([], $sql->get_status_colors());
        $this->assertSame(
            ['SELECT status, highlight FROM exp_statuses'],
            $captured->queries
        );
        $this->assertSame(1, $captured->result_array_calls);
    }

    /**
     * It confirms method coverage across the supported row-shape paths.
     *
     * @return void
     */
    public function testGetStatusColorsCoverageSubprocessCoversBothPaths(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-get-status-colors-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_get_status_colors_subprocess.php';
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

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
        $this->assertSame(
            [
                'SELECT status, highlight FROM exp_statuses',
                'SELECT status, highlight FROM exp_statuses',
                'SELECT status, highlight FROM exp_statuses',
            ],
            $result['queries']
        );
        $this->assertSame(
            ['open' => '#fff', 'closed' => '#000'],
            $result['populated_result']
        );
        $this->assertSame([], $result['positive_empty_result']);
        $this->assertSame([], $result['empty_result']);
        $this->assertSame(2, $result['result_array_calls']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(75.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([2], $result['uncovered_paths']);
        }
    }

    /**
     * Build a Sql_structure instance without running its constructor.
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
     * Build a lightweight query result double for the test database mocks.
     *
     * @param array $rows Rows returned from the fake query.
     * @param object $captured Shared call counters for the fake result.
     * @param int|null $numRows Optional row count override.
     * @return object
     */
    public function result(array $rows, object $captured, ?int $numRows = null): object
    {
        return new class($rows, $captured, $numRows) {
            private $rows;
            private $captured;
            public $num_rows;

            /**
             * Store the fake row payload and row-count contract.
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
             * Return the configured row count for branch selection.
             *
             * @return int
             */
            public function num_rows(): int
            {
                return $this->num_rows;
            }

            /**
             * Return the fake rows and record hydration.
             *
             * @return array
             */
            public function result_array(): array
            {
                $this->captured->result_array_calls++;

                return $this->rows;
            }
        };
    }
}
