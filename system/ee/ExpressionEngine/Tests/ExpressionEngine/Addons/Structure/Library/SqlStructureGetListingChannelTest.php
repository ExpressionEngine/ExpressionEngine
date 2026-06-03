<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';
require_once PATH_ADDONS . 'structure/Conduit/StaticCache.php';

use ExpressionEngine\Structure\Conduit\StaticCache;
use PHPUnit\Framework\TestCase;

class SqlStructureGetListingChannelFixture extends Sql_structure
{
    public $listingEntryIds = [];
    public $parentIds = [];
    public $parentCalls = [];

    /**
     * Avoid constructor side effects in isolated tests.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Return the configured listing entry IDs for the scenario.
     *
     * @return mixed
     */
    public function get_listing_entry_ids()
    {
        return $this->listingEntryIds;
    }

    /**
     * Return the configured parent ID and capture the lookup.
     *
     * @param int $entry_id
     * @param string $default
     * @return int|false
     */
    public function get_parent_id($entry_id, $default = 'home')
    {
        $this->parentCalls[] = [$entry_id, $default];

        return $this->parentIds[$entry_id] ?? false;
    }
}

class SqlStructureGetListingChannelTest extends TestCase
{
    /**
     * Reset singleton mocks between test runs.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
        StaticCache::clear();
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
     * It returns false for non-numeric entry IDs before touching collaborators.
     *
     * @return void
     */
    public function testGetListingChannelReturnsFalseForNonNumericEntryIdWithoutTouchingCollaborators(): void
    {
        ee()->setMock('sql_helper', new class {
            /**
             * Fail fast if the helper is reached through the guard clause.
             *
             * @param string $sql
             * @return void
             */
            public function row($sql)
            {
                throw new RuntimeException('get_listing_channel() should not query sql_helper for non-numeric entry IDs.');
            }
        });
        ee()->setMock('db', new class {
            /**
             * Fail fast if the channel validation query is reached through the guard clause.
             *
             * @param string $table
             * @param array|null $where
             * @param int|null $limit
             * @return void
             */
            public function get_where($table, $where = null, $limit = null)
            {
                throw new RuntimeException('get_listing_channel() should not query db for non-numeric entry IDs.');
            }
        });

        $sql = $this->makeSql(1);

        $this->assertFalse($sql->get_listing_channel('abc'));
        $this->assertSame([], $sql->parentCalls);
    }

    /**
     * It bypasses parent lookups when listing IDs are unavailable and returns false for a zero channel ID.
     *
     * @return void
     */
    public function testGetListingChannelBypassesParentLookupWhenListingIdsAreNotArray(): void
    {
        $captured = (object) [
            'sql' => null,
            'channel_queries' => 0,
            'updates' => [],
        ];

        ee()->setMock('sql_helper', new class($captured) {
            private $captured;

            /**
             * Store SQL state for the current scenario.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Return a zero listing channel row for the raw entry lookup.
             *
             * @param string $sql
             * @return array
             */
            public function row($sql): array
            {
                $this->captured->sql = $sql;

                return ['listing_cid' => 0];
            }
        });
        ee()->setMock('db', new class($captured) {
            private $captured;
            private $where = [];

            /**
             * Store SQL side-effect capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Capture any unexpected channel existence checks.
             *
             * @param string $table
             * @param array|null $where
             * @param int|null $limit
             * @return object
             */
            public function get_where($table, $where = null, $limit = null): object
            {
                $this->captured->channel_queries++;

                return new class {
                    /**
                     * Return an empty payload for unexpected channel checks.
                     *
                     * @return array
                     */
                    public function result_array(): array
                    {
                        return [];
                    }
                };
            }

            /**
             * Capture any unexpected update filters.
             *
             * @param string $field
             * @param mixed $value
             * @return object
             */
            public function where($field, $value = null): object
            {
                $this->where[$field] = $value;

                return $this;
            }

            /**
             * Capture any unexpected stale-channel cleanup updates.
             *
             * @param string $table
             * @param array $data
             * @return bool
             */
            public function update($table, $data = []): bool
            {
                $this->captured->updates[] = [
                    'table' => $table,
                    'where' => $this->where,
                    'data' => $data,
                ];
                $this->where = [];

                return true;
            }
        });

        $sql = $this->makeSql(9);
        $sql->listingEntryIds = false;

        $this->assertFalse($sql->get_listing_channel(17));
        $this->assertSame([], $sql->parentCalls);
        $this->assertStringContainsString('WHERE entry_id = 17', $captured->sql);
        $this->assertStringContainsString('AND site_id = 9', $captured->sql);
        $this->assertSame(0, $captured->channel_queries);
        $this->assertSame([], $captured->updates);
    }

    /**
     * It falls back to the original listing entry when the resolved parent ID is falsey and caches the result.
     *
     * @return void
     */
    public function testGetListingChannelFallsBackToOriginalEntryIdWhenListingParentIsFalsyAndCachesResult(): void
    {
        $captured = (object) [
            'sql_calls' => [],
            'channel_queries' => 0,
        ];

        ee()->setMock('sql_helper', new class($captured) {
            private $captured;

            /**
             * Store SQL capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Return a valid listing channel for the original entry ID.
             *
             * @param string $sql
             * @return array
             */
            public function row($sql): array
            {
                $this->captured->sql_calls[] = $sql;

                return ['listing_cid' => 8];
            }
        });
        ee()->setMock('db', new class($captured) {
            private $captured;

            /**
             * Store SQL side-effect capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Return the referenced channel row.
             *
             * @param string $table
             * @param array|null $where
             * @param int|null $limit
             * @return object
             */
            public function get_where($table, $where = null, $limit = null): object
            {
                $this->captured->channel_queries++;

                return new class {
                    /**
                     * Return the referenced channel payload.
                     *
                     * @return array
                     */
                    public function result_array(): array
                    {
                        return [['channel_id' => 8]];
                    }
                };
            }
        });

        $sql = $this->makeSql(2);
        $sql->listingEntryIds = [20 => 20];
        $sql->parentIds = [20 => 0];

        $this->assertSame(8, $sql->get_listing_channel(20));
        $this->assertSame([[20, 'home']], $sql->parentCalls);
        $this->assertCount(1, $captured->sql_calls);
        $this->assertStringContainsString('WHERE entry_id = 20', $captured->sql_calls[0]);
        $this->assertStringContainsString('AND site_id = 2', $captured->sql_calls[0]);
        $this->assertSame(1, $captured->channel_queries);

        ee()->setMock('sql_helper', new class {
            /**
             * Fail fast if a cached lookup still reaches sql_helper.
             *
             * @param string $sql
             * @return void
             */
            public function row($sql)
            {
                throw new RuntimeException('A cached listing channel lookup should not call sql_helper again.');
            }
        });
        ee()->setMock('db', new class {
            /**
             * Fail fast if a cached lookup still validates channels.
             *
             * @param string $table
             * @param array|null $where
             * @param int|null $limit
             * @return void
             */
            public function get_where($table, $where = null, $limit = null)
            {
                throw new RuntimeException('A cached listing channel lookup should not hit the database again.');
            }
        });

        $this->assertSame(8, $sql->get_listing_channel(20));
    }

    /**
     * It clears stale listing channels against the resolved parent entry and returns false.
     *
     * @return void
     */
    public function testGetListingChannelClearsStaleChannelForResolvedParentEntry(): void
    {
        $captured = (object) [
            'sql' => null,
            'channel_where' => null,
            'updates' => [],
        ];

        ee()->setMock('sql_helper', new class($captured) {
            private $captured;

            /**
             * Store SQL capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Return a stale listing channel ID for the resolved parent lookup.
             *
             * @param string $sql
             * @return array
             */
            public function row($sql): array
            {
                $this->captured->sql = $sql;

                return ['listing_cid' => 8];
            }
        });
        ee()->setMock('db', new class($captured) {
            private $captured;
            private $where = [];

            /**
             * Store SQL side-effect capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Return an empty result so the cleanup branch runs.
             *
             * @param string $table
             * @param array|null $where
             * @param int|null $limit
             * @return object
             */
            public function get_where($table, $where = null, $limit = null): object
            {
                $this->captured->channel_where = $where;

                return new class {
                    /**
                     * Return an empty payload for the missing channel.
                     *
                     * @return array
                     */
                    public function result_array(): array
                    {
                        return [];
                    }
                };
            }

            /**
             * Capture the cleanup filters.
             *
             * @param string $field
             * @param mixed $value
             * @return object
             */
            public function where($field, $value = null): object
            {
                $this->where[$field] = $value;

                return $this;
            }

            /**
             * Capture the stale-channel cleanup update.
             *
             * @param string $table
             * @param array $data
             * @return bool
             */
            public function update($table, $data = []): bool
            {
                $this->captured->updates[] = [
                    'table' => $table,
                    'where' => $this->where,
                    'data' => $data,
                ];
                $this->where = [];

                return true;
            }
        });

        $sql = $this->makeSql(4);
        $sql->listingEntryIds = [30 => 30];
        $sql->parentIds = [30 => 5];

        $this->assertFalse($sql->get_listing_channel(30));
        $this->assertSame([[30, 'home']], $sql->parentCalls);
        $this->assertStringContainsString('WHERE entry_id = 5', $captured->sql);
        $this->assertSame(['channel_id' => 8], $captured->channel_where);
        $this->assertSame([
            [
                'table' => 'structure',
                'where' => ['entry_id' => 5, 'site_id' => 4],
                'data' => ['listing_cid' => 0],
            ],
        ], $captured->updates);
    }

    /**
     * It returns false without validating channels when the structure lookup returns no row.
     *
     * @return void
     */
    public function testGetListingChannelReturnsFalseWhenLookupRowIsMissing(): void
    {
        $captured = (object) [
            'sql' => null,
            'channel_queries' => 0,
        ];

        ee()->setMock('sql_helper', new class($captured) {
            private $captured;

            /**
             * Store SQL capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Return no structure row for the listing channel lookup.
             *
             * @param string $sql
             * @return null
             */
            public function row($sql)
            {
                $this->captured->sql = $sql;

                return null;
            }
        });
        ee()->setMock('db', new class($captured) {
            private $captured;

            /**
             * Store SQL side-effect capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Capture any unexpected channel existence checks.
             *
             * @param string $table
             * @param array|null $where
             * @param int|null $limit
             * @return object
             */
            public function get_where($table, $where = null, $limit = null): object
            {
                $this->captured->channel_queries++;

                return new class {
                    /**
                     * Return an empty payload for unexpected channel checks.
                     *
                     * @return array
                     */
                    public function result_array(): array
                    {
                        return [];
                    }
                };
            }
        });

        $sql = $this->makeSql(7);

        $this->assertFalse($sql->get_listing_channel(44));
        $this->assertStringContainsString('WHERE entry_id = 44', $captured->sql);
        $this->assertStringContainsString('AND site_id = 7', $captured->sql);
        $this->assertSame(0, $captured->channel_queries);
    }

    /**
     * It confirms method coverage across the reachable line and decision branches.
     *
     * @return void
     */
    public function testGetListingChannelCoverageSubprocessReportsFullReachableCoverage(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-get-listing-channel-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_get_listing_channel_subprocess.php';
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
        $this->assertFalse($result['non_numeric_result']);
        $this->assertFalse($result['non_array_listing_ids_result']);
        $this->assertSame(8, $result['falsy_parent_result']);
        $this->assertSame(8, $result['cached_result']);
        $this->assertFalse($result['stale_channel_result']);
        $this->assertFalse($result['missing_row_result']);
        $this->assertSame([
            20 => [
                ['entry_id' => 20, 'default' => 'home'],
                ['entry_id' => 20, 'default' => 'home'],
            ],
            30 => [
                ['entry_id' => 30, 'default' => 'home'],
            ],
        ], $result['parent_calls']);
        $this->assertSame([
            'table' => 'structure',
            'where' => ['entry_id' => 5, 'site_id' => 6],
            'data' => ['listing_cid' => 0],
        ], $result['stale_channel_update']);

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
     * @return SqlStructureGetListingChannelFixture
     */
    private function makeSql(int $siteId): SqlStructureGetListingChannelFixture
    {
        $sql = new SqlStructureGetListingChannelFixture();
        $sql->site_id = $siteId;
        $sql->cache = [];

        return $sql;
    }
}
