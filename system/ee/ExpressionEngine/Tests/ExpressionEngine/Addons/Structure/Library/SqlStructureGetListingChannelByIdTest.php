<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGetListingChannelByIdFixture extends Sql_structure
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

class SqlStructureGetListingChannelByIdTest extends TestCase
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
     * It queries structure by entry and site ID and returns the raw stored listing channel value.
     *
     * @return void
     */
    public function testGetListingChannelByIdUsesEntryAndSiteFiltersAndReturnsStoredValue(): void
    {
        $captured = (object) [
            'calls' => [],
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
             * Capture the structure lookup and return a zero-like stored listing channel ID.
             *
             * @param string $table
             * @param array|null $where
             * @return object
             */
            public function get_where($table, $where = null): object
            {
                $this->captured->calls[] = [
                    'table' => $table,
                    'where' => $where,
                ];

                return $this->test->result([['listing_cid' => '0']]);
            }
        });

        $sql = $this->makeSql(13);

        $this->assertSame('0', $sql->get_listing_channel_by_id(42));
        $this->assertSame([
            [
                'table' => 'structure',
                'where' => ['entry_id' => 42, 'site_id' => 13],
            ],
        ], $captured->calls);
    }

    /**
     * It returns false when the structure lookup produces no matching rows.
     *
     * @return void
     */
    public function testGetListingChannelByIdReturnsFalseWhenStructureRowIsMissing(): void
    {
        $captured = (object) [
            'calls' => [],
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
             * Capture the structure lookup and return an empty result set.
             *
             * @param string $table
             * @param array|null $where
             * @return object
             */
            public function get_where($table, $where = null): object
            {
                $this->captured->calls[] = [
                    'table' => $table,
                    'where' => $where,
                ];

                return $this->test->result([], 0);
            }
        });

        $sql = $this->makeSql(21);

        $this->assertFalse($sql->get_listing_channel_by_id(77));
        $this->assertSame([
            [
                'table' => 'structure',
                'where' => ['entry_id' => 77, 'site_id' => 21],
            ],
        ], $captured->calls);
    }

    /**
     * It captures method-specific subprocess coverage for the happy, zero-like, and missing-row paths.
     *
     * @return void
     */
    public function testGetListingChannelByIdCoverageSubprocessCoversAllBranches(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-get-listing-channel-by-id-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_get_listing_channel_by_id_subprocess.php';
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
        $this->assertSame(8, $result['populated_result']);
        $this->assertSame('0', $result['zero_result']);
        $this->assertFalse($result['missing_result']);
        $this->assertSame([
            [
                'table' => 'structure',
                'where' => ['entry_id' => 10, 'site_id' => 4],
            ],
            [
                'table' => 'structure',
                'where' => ['entry_id' => 11, 'site_id' => 4],
            ],
            [
                'table' => 'structure',
                'where' => ['entry_id' => 12, 'site_id' => 4],
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
     * @return SqlStructureGetListingChannelByIdFixture
     */
    private function makeSql(int $siteId): SqlStructureGetListingChannelByIdFixture
    {
        $sql = new SqlStructureGetListingChannelByIdFixture();
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
            private $numRows;

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
                $this->numRows = $numRows ?? count($rows);
            }

            /**
             * Return the configured row count.
             *
             * @return int
             */
            public function num_rows(): int
            {
                return $this->numRows;
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
