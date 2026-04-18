<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGetListingChannelShortNameFixture extends Sql_structure
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

class SqlStructureGetListingChannelShortNameTest extends TestCase
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
     * It returns false for the explicit zero-string sentinel and keeps the lookup scoped to channel and site IDs.
     *
     * @return void
     */
    public function testGetListingChannelShortNameReturnsFalseForZeroStringChannelName(): void
    {
        $captured = (object) [
            'sql' => [],
        ];

        ee()->setMock('sql_helper', new class($captured) {
            private $captured;

            /**
             * Store SQL capture state for the scenario.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Capture the short-name query and return the zero-string sentinel row.
             *
             * @param string $sql
             * @return array
             */
            public function row($sql): array
            {
                $this->captured->sql[] = $sql;

                return ['channel_name' => '0'];
            }
        });

        $sql = $this->makeSql(17);

        $this->assertFalse($sql->get_listing_channel_short_name(42));
        $this->assertSame(1, count($captured->sql));
        $this->assertStringContainsString('SELECT channel_name FROM exp_channels', $captured->sql[0]);
        $this->assertStringContainsString('WHERE channel_id = 42', $captured->sql[0]);
        $this->assertStringContainsString('AND site_id = 17', $captured->sql[0]);
    }

    /**
     * It captures method-specific subprocess coverage for the happy, zero-string, and missing-row paths.
     *
     * @return void
     */
    public function testGetListingChannelShortNameCoverageSubprocessCoversAllBranches(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-get-listing-channel-short-name-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_get_listing_channel_short_name_subprocess.php';
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
        $this->assertSame('blog', $result['named_result']);
        $this->assertFalse($result['zero_string_result']);
        $this->assertFalse($result['missing_result']);
        $this->assertCount(3, $result['sql_calls']);
        $this->assertStringContainsString('WHERE channel_id = 10', $result['sql_calls'][0]);
        $this->assertStringContainsString('AND site_id = 6', $result['sql_calls'][0]);
        $this->assertStringContainsString('WHERE channel_id = 11', $result['sql_calls'][1]);
        $this->assertStringContainsString('WHERE channel_id = 12', $result['sql_calls'][2]);

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
     * @return SqlStructureGetListingChannelShortNameFixture
     */
    private function makeSql(int $siteId): SqlStructureGetListingChannelShortNameFixture
    {
        $sql = new SqlStructureGetListingChannelShortNameFixture();
        $sql->site_id = $siteId;
        $sql->cache = [];

        return $sql;
    }
}
