<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGenerateSitePagesArrayFixture extends Sql_structure
{
    public $sitePagesFixture = [];
    public $retrievedTitles = [];

    /**
     * Avoid constructor side effects in focused method tests.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Return the configured site_pages payload for the current scenario.
     *
     * @param bool $cache_bust
     * @param bool $override_slash
     * @return array
     */
    public function get_site_pages($cache_bust = false, $override_slash = false)
    {
        return $this->sitePagesFixture;
    }

    /**
     * Return the configured parent title lookup for generated parent URIs.
     *
     * @param int|string $entry_id
     * @return string
     */
    public function retrieve_structure_url_title($entry_id)
    {
        return $this->retrievedTitles[$entry_id] ?? '';
    }
}

class SqlStructureGenerateSitePagesArrayTest extends TestCase
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
     * It treats self-parent rows as top-level entries and keeps blank root listing slugs at `/`.
     *
     * @return void
     */
    public function testGenerateSitePagesArrayHandlesSelfParentGuardAndBlankRootListingSlug(): void
    {
        $_GET = [];

        ee()->setMock('db', $this->makeStructureDb([
            ['entry_id' => 30, 'structure_url_title' => 'SelfRoot', 'parent_id' => 30, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 14],
            ['entry_id' => 40, 'structure_url_title' => '', 'parent_id' => 0, 'channel_id' => 2, 'listing_cid' => 8, 'template_id' => 15],
        ], [
            8 => [
                ['entry_id' => 41, 'url_title' => ''],
            ],
        ]));

        $sql = $this->makeSql(
            [
                'url' => 'https://example.test/',
                'uris' => [999 => '/stale/'],
                'templates' => [41 => 99],
            ]
        );

        $sitePages = $sql->generate_site_pages_array();

        $this->assertSame('https://example.test/', $sitePages['url']);
        $this->assertSame('/selfroot/', $sitePages['uris'][30]);
        $this->assertSame('/', $sitePages['uris'][40]);
        $this->assertSame('/', $sitePages['uris'][41]);
        $this->assertSame(99, $sitePages['templates'][41]);
        $this->assertSame(15, $sitePages['templates'][40]);
    }

    /**
     * It clears stale URIs when Structure has no rows while preserving the surrounding site_pages payload.
     *
     * @return void
     */
    public function testGenerateSitePagesArrayClearsUrisWhenStructureHasNoRows(): void
    {
        $_GET = [];

        ee()->setMock('db', $this->makeStructureDb([], []));

        $sql = $this->makeSql([
            'url' => 'https://example.test/',
            'uris' => [99 => '/stale/'],
            'templates' => [99 => 7],
        ]);

        $sitePages = $sql->generate_site_pages_array();

        $this->assertSame('https://example.test/', $sitePages['url']);
        $this->assertSame([], $sitePages['uris']);
        $this->assertSame([99 => 7], $sitePages['templates']);
    }

    /**
     * It captures aggregate subprocess coverage, including the real debug `die()` path.
     *
     * @return void
     */
    public function testGenerateSitePagesArrayCoverageSubprocessCapturesBlockedRawBranchEdges(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-generate-site-pages-array-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_generate_site_pages_array_subprocess.php';
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
        $this->assertSame('/parent/child/', $result['return_result']['primary']['uris']['2']);
        $this->assertSame('/generated-parent/leaf/', $result['return_result']['primary']['uris']['3']);
        $this->assertSame('/', $result['return_result']['self_parent_and_blank_listing']['uris']['41']);
        $this->assertSame([], $result['return_result']['no_entries']['uris']);
        $this->assertStringContainsString('Existing Parent:', $result['die_result']['output']);
        $this->assertStringContainsString('Gen Parent:', $result['die_result']['output']);
        $this->assertStringContainsString('END generate_site_pages_array', $result['die_result']['output']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertEquals(93.47826086956522, $result['branch_percentage']);
            $this->assertCount(3, $result['uncovered_branches']);
            $this->assertContainsOnly('string', $result['uncovered_branches']);

            foreach ($result['uncovered_branches'] as $branchKey) {
                $this->assertMatchesRegularExpression('/^\\d+:1$/', $branchKey);
            }
        }
    }

    /**
     * Build a Sql_structure fixture without running its constructor.
     *
     * @param array $sitePages
     * @param array $retrievedTitles
     * @return SqlStructureGenerateSitePagesArrayFixture
     */
    private function makeSql(array $sitePages, array $retrievedTitles = []): SqlStructureGenerateSitePagesArrayFixture
    {
        $sql = new SqlStructureGenerateSitePagesArrayFixture();
        $sql->site_id = 1;
        $sql->cache = [];
        $sql->sitePagesFixture = $sitePages;
        $sql->retrievedTitles = $retrievedTitles;

        return $sql;
    }

    /**
     * Build a fluent DB mock for generate_site_pages_array() scenarios.
     *
     * @param array $structureRows
     * @param array $listingRowsByChannelId
     * @return object
     */
    private function makeStructureDb(array $structureRows, array $listingRowsByChannelId)
    {
        return new class($this, $structureRows, $listingRowsByChannelId) {
            private $test;
            private $structureRows;
            private $listingRowsByChannelId;

            /**
             * Store the fake query payloads for the current scenario.
             *
             * @param TestCase $test
             * @param array $structureRows
             * @param array $listingRowsByChannelId
             * @return void
             */
            public function __construct(TestCase $test, array $structureRows, array $listingRowsByChannelId)
            {
                $this->test = $test;
                $this->structureRows = $structureRows;
                $this->listingRowsByChannelId = $listingRowsByChannelId;
            }

            /**
             * Keep the CI DB fluent API intact for select calls.
             *
             * @param string $fields
             * @return object
             */
            public function select($fields = '*')
            {
                return $this;
            }

            /**
             * Keep the CI DB fluent API intact for from calls.
             *
             * @param string $table
             * @return object
             */
            public function from($table)
            {
                return $this;
            }

            /**
             * Keep the CI DB fluent API intact for where calls.
             *
             * @param array|string $field
             * @param mixed|null $value
             * @return object
             */
            public function where($field, $value = null)
            {
                return $this;
            }

            /**
             * Keep the CI DB fluent API intact for join calls.
             *
             * @param string $table
             * @param string $condition
             * @param string $type
             * @return object
             */
            public function join($table, $condition, $type = '')
            {
                return $this;
            }

            /**
             * Return the configured structure rows.
             *
             * @return object
             */
            public function get()
            {
                return $this->test->result($this->structureRows);
            }

            /**
             * Return the configured listing rows for the requested channel.
             *
             * @param string $table
             * @param array|null $where
             * @param int|null $limit
             * @param int|null $offset
             * @return object
             */
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                $channelId = $where['channel_id'] ?? null;
                $rows = $this->listingRowsByChannelId[$channelId] ?? [];

                return $this->test->result($rows);
            }
        };
    }

    /**
     * Build a lightweight CI-style DB result fixture.
     *
     * @param array $rows
     * @param int|null $numRows
     * @return object
     */
    public function result(array $rows, ?int $numRows = null)
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
             * Return the configured rows as arrays.
             *
             * @return array
             */
            public function result_array(): array
            {
                return $this->rows;
            }
        };
    }
}
