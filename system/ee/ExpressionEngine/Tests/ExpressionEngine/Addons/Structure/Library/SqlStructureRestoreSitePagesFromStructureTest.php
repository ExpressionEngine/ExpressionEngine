<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureRestoreSitePagesFromStructureFixture extends Sql_structure
{
    public $sitePagesFixture = [];
    public $structureChannelsFixture = [];

    /**
     * Avoid constructor side effects in focused method tests.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Return the configured site_pages fixture.
     *
     * @param bool $cache_bust
     * @param bool $override_slash
     * @return array
     */
    public function get_site_pages($cache_bust = false, $override_slash = false): array
    {
        return $this->sitePagesFixture;
    }

    /**
     * Return the configured Structure channel defaults.
     *
     * @param string $type
     * @param string $channel_id
     * @param string $order
     * @param bool $selector
     * @return array
     */
    public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false): array
    {
        return $this->structureChannelsFixture;
    }
}

class SqlStructureRestoreSitePagesFromStructureDbMock
{
    private $captured;
    private $channelIdsByEntryId;
    private $failOnQuery;

    /**
     * Store shared query capture state for the restore-site-pages tests.
     *
     * @param object $captured
     * @param array $channelIdsByEntryId
     * @param bool $failOnQuery
     * @return void
     */
    public function __construct(object $captured, array $channelIdsByEntryId = [], bool $failOnQuery = false)
    {
        $this->captured = $captured;
        $this->channelIdsByEntryId = $channelIdsByEntryId;
        $this->failOnQuery = $failOnQuery;
    }

    /**
     * Return the configured channel lookup result for a missing-template entry.
     *
     * @param string $sql
     * @return eeDbResultMock
     */
    public function query($sql): eeDbResultMock
    {
        $this->captured->queries[] = $sql;

        if ($this->failOnQuery) {
            throw new RuntimeException('restore_site_pages_from_structure() should not query channel titles when templates are already present.');
        }

        preg_match("/entry_id = '([^']+)'/", $sql, $matches);
        $entryId = $matches[1] ?? null;
        $channelId = null;

        if ($entryId !== null && array_key_exists($entryId, $this->channelIdsByEntryId)) {
            $channelId = $this->channelIdsByEntryId[$entryId];
        }

        if ($channelId === null) {
            return new eeDbResultMock([]);
        }

        return new eeDbResultMock([['channel_id' => $channelId]]);
    }

    /**
     * Capture the site scoping applied before the update.
     *
     * @param string $field
     * @param mixed|null $value
     * @return self
     */
    public function where($field, $value = null): self
    {
        $this->captured->where_calls[] = [$field, $value];

        return $this;
    }

    /**
     * Capture the serialized site_pages payload written to the sites table.
     *
     * @param string $table
     * @param array|null $data
     * @param mixed|null $where
     * @return bool
     */
    public function update($table, $data = null, $where = null): bool
    {
        $this->captured->updates[] = [
            'table' => $table,
            'data' => $data,
            'where' => $where,
        ];

        return true;
    }
}

class SqlStructureRestoreSitePagesFromStructureTest extends TestCase
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
     * It fills missing template IDs from the entry channel defaults and persists the encoded payload.
     *
     * @return void
     */
    public function testRestoreSitePagesFromStructurePopulatesMissingTemplatesAndPersistsEncodedSitePages(): void
    {
        $captured = (object) [
            'queries' => [],
            'where_calls' => [],
            'updates' => [],
        ];

        ee()->setMock('functions', new class {
            /**
             * Return a stable site URL for the serialized payload.
             *
             * @param int $includeIndex
             * @param int $includeQuery
             * @return string
             */
            public function fetch_site_index($includeIndex = 1, $includeQuery = 0): string
            {
                return 'https://example.com/';
            }
        });
        ee()->setMock('db', new SqlStructureRestoreSitePagesFromStructureDbMock($captured, ['11' => 9]));

        $sql = $this->makeSql(
            [
                'uris' => [10 => '/existing/', 11 => '/needs-template/'],
                'templates' => [10 => 2],
            ],
            [
                9 => ['template_id' => 7],
            ],
            3
        );

        $sql->restore_site_pages_from_structure();

        $this->assertCount(1, $captured->queries);
        $this->assertStringContainsString("site_id = '3'", $captured->queries[0]);
        $this->assertStringContainsString("entry_id = '11'", $captured->queries[0]);
        $this->assertSame([['site_id', 3]], $captured->where_calls);
        $this->assertCount(1, $captured->updates);
        $this->assertSame('sites', $captured->updates[0]['table']);

        $decoded = $this->decodePersistedSitePages($captured->updates[0]);

        $this->assertSame([10 => '/existing/', 11 => '/needs-template/'], $decoded[3]['uris']);
        $this->assertSame([10 => 2, 11 => 7], $decoded[3]['templates']);
        $this->assertSame('https://example.com/', $decoded[3]['url']);
    }

    /**
     * It keeps existing template mappings and skips the fallback channel lookup branch.
     *
     * @return void
     */
    public function testRestoreSitePagesFromStructurePreservesExistingTemplatesWithoutChannelLookup(): void
    {
        $captured = (object) [
            'queries' => [],
            'where_calls' => [],
            'updates' => [],
        ];

        ee()->setMock('functions', new class {
            /**
             * Return a stable site URL for the serialized payload.
             *
             * @param int $includeIndex
             * @param int $includeQuery
             * @return string
             */
            public function fetch_site_index($includeIndex = 1, $includeQuery = 0): string
            {
                return 'https://stable.example.com/';
            }
        });
        ee()->setMock('db', new SqlStructureRestoreSitePagesFromStructureDbMock($captured, [], true));

        $sql = $this->makeSql(
            [
                'uris' => [22 => '/already-mapped/'],
                'templates' => [22 => 33],
            ],
            [
                99 => ['template_id' => 1000],
            ],
            5
        );

        $sql->restore_site_pages_from_structure();

        $this->assertSame([], $captured->queries);
        $this->assertSame([['site_id', 5]], $captured->where_calls);
        $this->assertCount(1, $captured->updates);

        $decoded = $this->decodePersistedSitePages($captured->updates[0]);

        $this->assertSame([22 => '/already-mapped/'], $decoded[5]['uris']);
        $this->assertSame([22 => 33], $decoded[5]['templates']);
        $this->assertSame('https://stable.example.com/', $decoded[5]['url']);
    }

    /**
     * It persists an empty site_pages structure without attempting any per-entry channel lookups.
     *
     * @return void
     */
    public function testRestoreSitePagesFromStructurePersistsEmptyBoundaryPayloadWithoutQueries(): void
    {
        $captured = (object) [
            'queries' => [],
            'where_calls' => [],
            'updates' => [],
        ];

        ee()->setMock('functions', new class {
            /**
             * Return a stable site URL for the serialized payload.
             *
             * @param int $includeIndex
             * @param int $includeQuery
             * @return string
             */
            public function fetch_site_index($includeIndex = 1, $includeQuery = 0): string
            {
                return 'https://empty.example.com/';
            }
        });
        ee()->setMock('db', new SqlStructureRestoreSitePagesFromStructureDbMock($captured, [], true));

        $sql = $this->makeSql(
            [
                'uris' => [],
                'templates' => [],
            ],
            [],
            8
        );

        $sql->restore_site_pages_from_structure();

        $this->assertSame([], $captured->queries);
        $this->assertSame([['site_id', 8]], $captured->where_calls);
        $this->assertCount(1, $captured->updates);

        $decoded = $this->decodePersistedSitePages($captured->updates[0]);

        $this->assertSame([], $decoded[8]['uris']);
        $this->assertSame([], $decoded[8]['templates']);
        $this->assertSame('https://empty.example.com/', $decoded[8]['url']);
    }

    /**
     * It confirms full line coverage and records the remaining non-reachable foreach branch edge from Xdebug.
     *
     * @return void
     */
    public function testRestoreSitePagesFromStructureCoverageSubprocessReportsRemainingForeachBranchGap(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-restore-site-pages-from-structure-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_restore_site_pages_from_structure_subprocess.php';
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
        $this->assertSame([10 => 2, 11 => 7], $result['missing_template_result'][3]['templates']);
        $this->assertSame(
            ["SELECT channel_id FROM exp_channel_titles WHERE site_id = '3' AND entry_id = '11'"],
            $result['missing_template_queries']
        );
        $this->assertSame([], $result['existing_template_queries']);
        $this->assertSame([], $result['empty_queries']);
        $this->assertSame([], $result['empty_result'][8]['uris']);
        $this->assertSame([['site_id', 8]], $result['empty_where_calls']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(83.33333333333334, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame(['0:1'], $result['uncovered_branches']);
        }
    }

    /**
     * Build a focused Sql_structure fixture without running the production constructor.
     *
     * @param array $sitePagesFixture
     * @param array $structureChannelsFixture
     * @param int $siteId
     * @return SqlStructureRestoreSitePagesFromStructureFixture
     */
    private function makeSql(array $sitePagesFixture, array $structureChannelsFixture, int $siteId): SqlStructureRestoreSitePagesFromStructureFixture
    {
        $sql = new SqlStructureRestoreSitePagesFromStructureFixture();
        $sql->sitePagesFixture = $sitePagesFixture;
        $sql->structureChannelsFixture = $structureChannelsFixture;
        $sql->site_id = $siteId;
        $sql->cache = [];

        return $sql;
    }

    /**
     * Decode the persisted serialized site_pages payload from a captured update call.
     *
     * @param array $update
     * @return array
     */
    private function decodePersistedSitePages(array $update): array
    {
        return unserialize(base64_decode($update['data']['site_pages']));
    }
}
