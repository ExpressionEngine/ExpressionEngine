<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureSetListingDataFixture extends Sql_structure
{
    public $captured;
    public $sitePagesFixture = [];
    public $settingsFixture = ['add_trailing_slash' => 'y'];

    /**
     * Store shared capture state without running add-on constructor logic.
     *
     * @param object $captured
     * @return void
     */
    public function __construct(object $captured)
    {
        $this->captured = $captured;
    }

    /**
     * Return the configured site pages fixture while tracking call arguments.
     *
     * @param bool $cache_bust
     * @param bool $override_slash
     * @return array
     */
    public function get_site_pages($cache_bust = false, $override_slash = false)
    {
        $this->captured->getSitePagesCalls[] = [$cache_bust, $override_slash];

        return $this->sitePagesFixture;
    }

    /**
     * Return the configured Structure settings fixture.
     *
     * @return array
     */
    public function get_settings()
    {
        return $this->settingsFixture;
    }

    /**
     * Capture the site_pages payload passed by set_listing_data().
     *
     * @param mixed $site_id
     * @param array $site_pages
     * @return void
     */
    public function set_site_pages($site_id, $site_pages)
    {
        $this->captured->setSitePagesCalls[] = [
            'site_id' => $site_id,
            'site_pages' => $site_pages,
        ];
    }

    /**
     * Track root-node refreshes triggered after persistence.
     *
     * @return void
     */
    public function update_root_node()
    {
        $this->captured->updateRootNodeCalls++;
    }
}

class SqlStructureSetListingDataTest extends TestCase
{
    /**
     * Reset EE mocks and configure the minimal site context for each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
        ee()->setMock('config', new FakeConfig());
        ee()->config->items = [
            'site_id' => 4,
        ];
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
     * It fetches site pages when none are provided and updates existing rows.
     *
     * @return void
     */
    public function testSetListingDataFetchesSitePagesAndUpdatesExistingListing(): void
    {
        $captured = $this->makeCapture();
        ee()->setMock('db', $this->makeDbMock($captured, [true]));

        $sql = $this->makeSql($captured, [
            'url' => '/',
            'uris' => [10 => '/existing-parent/'],
            'templates' => [10 => 8],
        ]);

        $sql->set_listing_data([
            'site_id' => 4,
            'entry_id' => 55,
            'channel_id' => 7,
            'parent_id' => 10,
            'parent_uri' => '/parent/',
            'uri' => '/child/',
            'template_id' => 22,
            'listing_cid' => 3,
            'hidden' => 'n',
        ]);

        $this->assertSame([[true, false]], $captured->getSitePagesCalls);
        $this->assertCount(1, $captured->setSitePagesCalls);
        $this->assertSame(4, $captured->setSitePagesCalls[0]['site_id']);
        $this->assertSame('/existing-parent/', $captured->setSitePagesCalls[0]['site_pages']['uris'][10]);
        $this->assertSame('/parent/child/', $captured->setSitePagesCalls[0]['site_pages']['uris'][55]);
        $this->assertSame(8, $captured->setSitePagesCalls[0]['site_pages']['templates'][10]);
        $this->assertSame(22, $captured->setSitePagesCalls[0]['site_pages']['templates'][55]);
        $this->assertSame(1, $captured->getWhereCalls);
        $this->assertSame('structure_listings', $captured->updateTable);
        $this->assertSame('entry_id = 55', $captured->updateWhere);
        $this->assertSame('child', $captured->updateData['uri']);
        $this->assertArrayNotHasKey('entry_id', $captured->updateData);
        $this->assertArrayNotHasKey('listing_cid', $captured->updateData);
        $this->assertArrayNotHasKey('parent_uri', $captured->updateData);
        $this->assertArrayNotHasKey('hidden', $captured->updateData);
        $this->assertSame(['UPDATE_SQL'], $captured->queries);
        $this->assertSame(1, $captured->updateRootNodeCalls);
    }

    /**
     * It uses provided site pages, skips the fetch, and inserts new rows.
     *
     * @return void
     */
    public function testSetListingDataUsesProvidedSitePagesAndInsertsNewListing(): void
    {
        $captured = $this->makeCapture();
        ee()->setMock('db', $this->makeDbMock($captured, [false]));

        $sql = $this->makeSql($captured, [
            'url' => '/',
            'uris' => [999 => '/unused/'],
            'templates' => [999 => 2],
        ]);

        $providedSitePages = [
            'url' => '/',
            'uris' => [11 => '/provided-parent/'],
            'templates' => [11 => 9],
        ];

        $sql->set_listing_data([
            'site_id' => 4,
            'entry_id' => 99,
            'channel_id' => 7,
            'parent_id' => 11,
            'parent_uri' => '/provided-parent/',
            'uri' => '/branch/',
            'template_id' => 15,
            'listing_cid' => 6,
            'hidden' => 'y',
        ], $providedSitePages);

        $this->assertSame([], $captured->getSitePagesCalls);
        $this->assertCount(1, $captured->setSitePagesCalls);
        $this->assertSame(4, $captured->setSitePagesCalls[0]['site_id']);
        $this->assertSame('/provided-parent/', $captured->setSitePagesCalls[0]['site_pages']['uris'][11]);
        $this->assertSame('/provided-parent/branch/', $captured->setSitePagesCalls[0]['site_pages']['uris'][99]);
        $this->assertSame(9, $captured->setSitePagesCalls[0]['site_pages']['templates'][11]);
        $this->assertSame(15, $captured->setSitePagesCalls[0]['site_pages']['templates'][99]);
        $this->assertSame(1, $captured->getWhereCalls);
        $this->assertSame('structure_listings', $captured->insertTable);
        $this->assertSame(99, $captured->insertData['entry_id']);
        $this->assertSame('branch', $captured->insertData['uri']);
        $this->assertArrayNotHasKey('listing_cid', $captured->insertData);
        $this->assertArrayNotHasKey('parent_uri', $captured->insertData);
        $this->assertArrayNotHasKey('hidden', $captured->insertData);
        $this->assertSame(['INSERT_SQL'], $captured->queries);
        $this->assertSame(1, $captured->updateRootNodeCalls);
    }

    /**
     * It confirms subprocess coverage reaches every executable line and branch.
     *
     * @return void
     */
    public function testSetListingDataCoverageSubprocessCoversAllLinesAndBranches(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-set-listing-data-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_set_listing_data_subprocess.php';
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
        $this->assertSame([[true, false]], $result['fetched_get_site_pages_calls']);
        $this->assertSame([], $result['provided_get_site_pages_calls']);
        $this->assertSame('/parent/child/', $result['fetched_site_pages']['uris'][55]);
        $this->assertSame('/provided-parent/branch/', $result['provided_site_pages']['uris'][99]);
        $this->assertSame('child', $result['updated_row']['uri']);
        $this->assertSame('branch', $result['inserted_row']['uri']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_branches']);
        }
    }

    /**
     * Build shared capture state for one set_listing_data() scenario.
     *
     * @return object
     */
    private function makeCapture(): object
    {
        return (object) [
            'getSitePagesCalls' => [],
            'setSitePagesCalls' => [],
            'updateRootNodeCalls' => 0,
            'queries' => [],
            'getWhereCalls' => 0,
            'updateTable' => null,
            'updateData' => null,
            'updateWhere' => null,
            'insertTable' => null,
            'insertData' => null,
        ];
    }

    /**
     * Build a DB mock that drives update or insert behavior by sequence.
     *
     * @param object $captured
     * @param array $existingRowSequence
     * @return object
     */
    private function makeDbMock(object $captured, array $existingRowSequence)
    {
        return new class($captured, $existingRowSequence) extends FakeDb {
            private $captured;
            private $existingRowSequence;
            private $position = 0;

            /**
             * Store capture state and row-existence sequence.
             *
             * @param object $captured
             * @param array $existingRowSequence
             * @return void
             */
            public function __construct(object $captured, array $existingRowSequence)
            {
                $this->captured = $captured;
                $this->existingRowSequence = $existingRowSequence;
            }

            /**
             * Return the next configured structure_listings lookup result.
             *
             * @param string $table
             * @param array|null $where
             * @param int|null $limit
             * @param int|null $offset
             * @return eeDbResultMock
             */
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                $this->captured->getWhereCalls++;
                $exists = $this->existingRowSequence[$this->position] ?? end($this->existingRowSequence);
                $this->position++;

                if ($table === 'structure_listings' && $exists) {
                    return new eeDbResultMock([['entry_id' => $where['entry_id']]]);
                }

                return new eeDbResultMock([]);
            }

            /**
             * Capture the update payload for the existing-row branch.
             *
             * @param string $table
             * @param array $data
             * @param string $where
             * @return string
             */
            public function update_string($table, $data, $where)
            {
                $this->captured->updateTable = $table;
                $this->captured->updateData = $data;
                $this->captured->updateWhere = $where;

                return 'UPDATE_SQL';
            }

            /**
             * Capture the insert payload for the new-row branch.
             *
             * @param string $table
             * @param array $data
             * @return string
             */
            public function insert_string($table, $data)
            {
                $this->captured->insertTable = $table;
                $this->captured->insertData = $data;

                return 'INSERT_SQL';
            }

            /**
             * Record the SQL generated for the final persistence call.
             *
             * @param string $sql
             * @return eeDbResultMock
             */
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return new eeDbResultMock([]);
            }
        };
    }

    /**
     * Build a constructor-free Sql_structure fixture for one scenario.
     *
     * @param object $captured
     * @param array $sitePagesFixture
     * @return SqlStructureSetListingDataFixture
     */
    private function makeSql(object $captured, array $sitePagesFixture): SqlStructureSetListingDataFixture
    {
        $sql = new SqlStructureSetListingDataFixture($captured);
        $sql->sitePagesFixture = $sitePagesFixture;
        $sql->site_id = 4;
        $sql->cache = [];

        return $sql;
    }
}
