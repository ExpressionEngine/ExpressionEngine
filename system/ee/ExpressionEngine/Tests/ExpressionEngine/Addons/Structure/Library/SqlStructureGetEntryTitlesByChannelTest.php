<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGetEntryTitlesByChannelTest extends TestCase
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
     * It returns false for non-numeric channel IDs without querying the database.
     *
     * @return void
     */
    public function testGetEntryTitlesByChannelReturnsFalseForNonNumericChannelIdWithoutQueryingDatabase(): void
    {
        $db = new class {
            public $queries = [];

            /**
             * Fail fast if the guard clause does not stop execution.
             *
             * @param string $sql
             * @return void
             */
            public function query($sql): void
            {
                $this->queries[] = $sql;

                throw new RuntimeException('get_entry_titles_by_channel() should not query the database for non-numeric channel IDs.');
            }
        };
        ee()->setMock('db', $db);

        $sql = $this->makeSql();

        $this->assertFalse($sql->get_entry_titles_by_channel('bad-channel'));
        $this->assertSame([], $db->queries);
    }

    /**
     * It returns ordered entry title pairs for numeric-string channel IDs and scopes the query to the site.
     *
     * @return void
     */
    public function testGetEntryTitlesByChannelReturnsEntryTitlePairsForNumericStringChannelId(): void
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
             * Return a populated result for the channel lookup.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql): object
            {
                $this->captured->queries[] = $sql;

                return $this->test->result(
                    [
                        ['entry_id' => 101, 'title' => 'Alpha'],
                        ['entry_id' => 205, 'title' => 'Zulu'],
                    ],
                    $this->captured
                );
            }
        });

        $sql = $this->makeSql();
        $sql->site_id = 7;

        $this->assertSame(
            [
                ['entry_id' => 101, 'title' => 'Alpha'],
                ['entry_id' => 205, 'title' => 'Zulu'],
            ],
            $sql->get_entry_titles_by_channel('4')
        );
        $this->assertSame(
            ['SELECT entry_id, title FROM exp_channel_titles WHERE channel_id = 4 AND site_id = 7 ORDER BY title'],
            $captured->queries
        );
        $this->assertSame(1, $captured->result_array_calls);
    }

    /**
     * It currently returns an empty array for zero-row results because the row-count check assigns zero.
     *
     * @return void
     */
    public function testGetEntryTitlesByChannelReturnsEmptyArrayWhenQueryFindsNoRows(): void
    {
        $captured = (object) [
            'queries' => [],
            'result_array_calls' => 0,
        ];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store shared capture state for the fake empty result.
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
             * Return a zero-row result for the channel lookup.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql): object
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([], $this->captured, 0);
            }
        });

        $sql = $this->makeSql();
        $sql->site_id = 3;

        $this->assertSame([], $sql->get_entry_titles_by_channel(0));
        $this->assertSame(
            ['SELECT entry_id, title FROM exp_channel_titles WHERE channel_id = 0 AND site_id = 3 ORDER BY title'],
            $captured->queries
        );
        $this->assertSame(1, $captured->result_array_calls);
    }

    /**
     * It confirms method coverage across the supported input and result-shape paths.
     *
     * @return void
     */
    public function testGetEntryTitlesByChannelCoverageSubprocessCapturesCurrentBranchGap(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-get-entry-titles-by-channel-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_get_entry_titles_by_channel_subprocess.php';
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
                'SELECT entry_id, title FROM exp_channel_titles WHERE channel_id = 4 AND site_id = 7 ORDER BY title',
                'SELECT entry_id, title FROM exp_channel_titles WHERE channel_id = 0 AND site_id = 7 ORDER BY title',
            ],
            $result['queries']
        );
        $this->assertSame(
            [
                ['entry_id' => 101, 'title' => 'Alpha'],
                ['entry_id' => 205, 'title' => 'Zulu'],
            ],
            $result['numeric_string_result']
        );
        $this->assertSame([], $result['empty_result']);
        $this->assertFalse($result['invalid_result']);
        $this->assertSame(2, $result['result_array_calls']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(60.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([1, 4], $result['uncovered_paths']);
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
