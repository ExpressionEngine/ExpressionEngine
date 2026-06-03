<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGetPidForListingEntryFixture extends Sql_structure
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

class SqlStructureGetPidForListingEntryResult
{
    public $num_rows;
    private $rowValues;
    private $rowCalls;
    private $throwOnRowCall;

    /**
     * Store the configured row payload for the fake database result.
     *
     * @param array $rowValues
     * @param int|null $numRows
     * @param array|null $rowCalls
     * @param bool $throwOnRowCall
     * @return void
     */
    public function __construct(array $rowValues, ?int $numRows = null, ?array &$rowCalls = null, bool $throwOnRowCall = false)
    {
        $this->num_rows = $numRows ?? (empty($rowValues) ? 0 : 1);
        $this->rowValues = $rowValues;
        $this->throwOnRowCall = $throwOnRowCall;

        if ($rowCalls === null) {
            $rowCalls = [];
        }

        $this->rowCalls = &$rowCalls;
    }

    /**
     * Return the requested column value and capture row access.
     *
     * @param string $column
     * @return mixed
     */
    public function row($column)
    {
        $this->rowCalls[] = $column;

        if ($this->throwOnRowCall) {
            throw new RuntimeException('row() should not be called when the parent lookup reports zero rows.');
        }

        return $this->rowValues[$column] ?? null;
    }
}

class SqlStructureGetPidForListingEntryDb
{
    private $results;
    private $captured;
    private $calls = 0;

    /**
     * Queue fake database results for each query.
     *
     * @param array $results
     * @param object $captured
     * @return void
     */
    public function __construct(array $results, object $captured)
    {
        $this->results = $results;
        $this->captured = $captured;
    }

    /**
     * Return the next queued database result for the captured SQL.
     *
     * @param string $sql
     * @return object
     */
    public function query($sql): object
    {
        $this->captured->queries[] = $sql;

        if (! array_key_exists($this->calls, $this->results)) {
            throw new RuntimeException('Unexpected extra database query.');
        }

        return $this->results[$this->calls++];
    }
}

class SqlStructureGetPidForListingEntryTest extends TestCase
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
     * It returns the structure entry ID for a listing entry after querying both collaborators.
     *
     * @return void
     */
    public function testGetPidForListingEntryReturnsParentEntryIdForListingChannel(): void
    {
        $captured = (object) [
            'queries' => [],
        ];
        $channelRowCalls = [];
        $parentRowCalls = [];

        ee()->setMock('db', new SqlStructureGetPidForListingEntryDb([
            new SqlStructureGetPidForListingEntryResult(['channel_id' => 9], 1, $channelRowCalls),
            new SqlStructureGetPidForListingEntryResult(['entry_id' => 55], 1, $parentRowCalls),
        ], $captured));

        $sql = $this->makeSql();

        $this->assertSame(55, $sql->get_pid_for_listing_entry(123));
        $this->assertSame(['channel_id'], $channelRowCalls);
        $this->assertSame(['entry_id'], $parentRowCalls);
        $this->assertSame([
            'SELECT channel_id FROM exp_channel_titles WHERE entry_id = 123 LIMIT 1',
            'SELECT entry_id FROM exp_structure WHERE listing_cid = 9 LIMIT 1',
        ], array_map([$this, 'normalizeSql'], $captured->queries));
    }

    /**
     * It returns false and stops after the channel lookup when the channel row is malformed.
     *
     * @return void
     */
    public function testGetPidForListingEntryReturnsFalseWhenChannelLookupReturnsArray(): void
    {
        $captured = (object) [
            'queries' => [],
        ];
        $channelRowCalls = [];

        ee()->setMock('db', new SqlStructureGetPidForListingEntryDb([
            new SqlStructureGetPidForListingEntryResult(['channel_id' => ['unexpected' => true]], 1, $channelRowCalls),
        ], $captured));

        $sql = $this->makeSql();

        $this->assertFalse($sql->get_pid_for_listing_entry(456));
        $this->assertSame(['channel_id'], $channelRowCalls);
        $this->assertSame([
            'SELECT channel_id FROM exp_channel_titles WHERE entry_id = 456 LIMIT 1',
        ], array_map([$this, 'normalizeSql'], $captured->queries));
    }

    /**
     * It returns false for missing parent rows without asking the empty result for an entry ID.
     *
     * @return void
     */
    public function testGetPidForListingEntryReturnsFalseWhenParentLookupHasNoRows(): void
    {
        $captured = (object) [
            'queries' => [],
        ];
        $channelRowCalls = [];
        $parentRowCalls = [];

        ee()->setMock('db', new SqlStructureGetPidForListingEntryDb([
            new SqlStructureGetPidForListingEntryResult(['channel_id' => 12], 1, $channelRowCalls),
            new SqlStructureGetPidForListingEntryResult(['entry_id' => 999], 0, $parentRowCalls, true),
        ], $captured));

        $sql = $this->makeSql();

        $this->assertFalse($sql->get_pid_for_listing_entry(789));
        $this->assertSame(['channel_id'], $channelRowCalls);
        $this->assertSame([], $parentRowCalls);
        $this->assertSame([
            'SELECT channel_id FROM exp_channel_titles WHERE entry_id = 789 LIMIT 1',
            'SELECT entry_id FROM exp_structure WHERE listing_cid = 12 LIMIT 1',
        ], array_map([$this, 'normalizeSql'], $captured->queries));
    }

    /**
     * It falls through to an empty listing-channel lookup when the first query returns no rows.
     *
     * @return void
     */
    public function testGetPidForListingEntryFallsThroughWhenChannelLookupReturnsNoRows(): void
    {
        $captured = (object) [
            'queries' => [],
        ];
        $channelRowCalls = [];
        $parentRowCalls = [];

        ee()->setMock('db', new SqlStructureGetPidForListingEntryDb([
            new SqlStructureGetPidForListingEntryResult([], 0, $channelRowCalls),
            new SqlStructureGetPidForListingEntryResult(['entry_id' => 999], 0, $parentRowCalls, true),
        ], $captured));

        $sql = $this->makeSql();

        $this->assertFalse($sql->get_pid_for_listing_entry(987));
        $this->assertSame(['channel_id'], $channelRowCalls);
        $this->assertSame([], $parentRowCalls);
        $this->assertSame(
            'SELECT channel_id FROM exp_channel_titles WHERE entry_id = 987 LIMIT 1',
            $this->normalizeSql($captured->queries[0])
        );
        $this->assertSame(
            'SELECT entry_id FROM exp_structure WHERE listing_cid = LIMIT 1',
            $this->normalizeSql($captured->queries[1])
        );
    }

    /**
     * It captures method-specific subprocess coverage for the success and both failure branches.
     *
     * @return void
     */
    public function testGetPidForListingEntryCoverageSubprocessCoversAllBranches(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-get-pid-for-listing-entry-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_get_pid_for_listing_entry_subprocess.php';
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
        $this->assertSame(55, $result['happy_path_result']);
        $this->assertFalse($result['array_channel_result']);
        $this->assertFalse($result['missing_parent_result']);
        $this->assertSame([], $result['missing_parent_row_calls']);
        $this->assertSame([
            'SELECT channel_id FROM exp_channel_titles WHERE entry_id = 10 LIMIT 1',
            'SELECT entry_id FROM exp_structure WHERE listing_cid = 7 LIMIT 1',
            'SELECT channel_id FROM exp_channel_titles WHERE entry_id = 11 LIMIT 1',
            'SELECT channel_id FROM exp_channel_titles WHERE entry_id = 12 LIMIT 1',
            'SELECT entry_id FROM exp_structure WHERE listing_cid = 8 LIMIT 1',
        ], $result['queries']);

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
     * @return SqlStructureGetPidForListingEntryFixture
     */
    private function makeSql(): SqlStructureGetPidForListingEntryFixture
    {
        $sql = new SqlStructureGetPidForListingEntryFixture();
        $sql->site_id = 1;
        $sql->cache = [];

        return $sql;
    }

    /**
     * Collapse whitespace in captured SQL for stable assertions.
     *
     * @param string $sql
     * @return string
     */
    private function normalizeSql(string $sql): string
    {
        return preg_replace('/\s+/', ' ', trim($sql));
    }
}
