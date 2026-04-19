<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureUtilityFixture extends Sql_structure
{
    private $fixtureSettings;
    private $fixtureSitePages;

    public function __construct(array $settings = [], array $sitePages = [])
    {
        $this->fixtureSettings = $settings;
        $this->fixtureSitePages = $sitePages;
    }

    public function get_settings()
    {
        return $this->fixtureSettings;
    }

    public function get_site_pages($cache_bust = false, $force = false)
    {
        return $this->fixtureSitePages;
    }
}

class SqlStructureUtilityMethodsTest extends TestCase
{
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

    public function testStringUtilityMethods()
    {
        $sql = new SqlStructureUtilityFixture();

        $this->assertSame('HelloWorld', $sql->create_uri('Hello World!'));
        $this->assertSame('/parent/child/', $sql->create_page_uri('/parent', 'child'));
        $this->assertSame('/parent/child/', $sql->create_full_uri('/parent', 'child'));
        $this->assertSame(2, $sql->count_segments('/alpha/beta/'));
        $this->assertNull($sql->count_segments(''));
        $this->assertSame('gamma', $sql->get_slug('/alpha/beta/gamma'));
        $this->assertSame(['alpha', 'beta', 'gamma'], $sql->get_slug('/alpha/beta/gamma', true));
        $this->assertFalse($sql->get_slug(false));
        $this->assertSame(2, $sql->get_parent_uri_depth('/a/b/'));
        $this->assertSame(0, $sql->get_parent_uri_depth(null));
    }

    public function testGetSlugPreservesFalseyBoundaryInputsThatAreNotFalse()
    {
        $sql = new SqlStructureUtilityFixture();

        $this->assertSame('', $sql->get_slug(''));
        $this->assertSame('', $sql->get_slug('/'));
        $this->assertSame([''], $sql->get_slug('/', true));
        $this->assertSame('0', $sql->get_slug(0));
    }

    public function testGetParentUriDepthPreservesEmptySegmentsAtBoundaryInputs()
    {
        $sql = new SqlStructureUtilityFixture();

        $this->assertSame(1, $sql->get_parent_uri_depth('/'));
        $this->assertSame(1, $sql->get_parent_uri_depth(''));
        $this->assertSame(3, $sql->get_parent_uri_depth('/alpha//beta/'));
        $this->assertSame(2, $sql->get_parent_uri_depth('alpha/beta'));
    }

    public function testReindexAtOnePreservesOriginalKeysWhileAddingOneBasedCopies()
    {
        $sql = new SqlStructureUtilityFixture();
        $rows = [
            ['entry_id' => 10],
            ['entry_id' => 11],
        ];

        $this->assertSame([
            0 => ['entry_id' => 10],
            1 => ['entry_id' => 10],
            2 => ['entry_id' => 11],
        ], $sql->reindex_at_one($rows));
    }

    public function testReindexAtOneReturnsEmptyArrayForEmptyInput()
    {
        $sql = new SqlStructureUtilityFixture();

        $this->assertSame([], $sql->reindex_at_one([]));
    }

    public function testReindexAtOneWarnsAndReturnsOriginalValueForNonIterableInput()
    {
        $sql = new SqlStructureUtilityFixture();
        $warning = null;

        set_error_handler(function ($number, $message) use (&$warning) {
            $warning = [$number, $message];

            return true;
        });

        try {
            $result = $sql->reindex_at_one(null);
        } finally {
            restore_error_handler();
        }

        $this->assertNull($result);
        $this->assertSame(E_WARNING, $warning[0]);
        $this->assertStringContainsString('foreach', $warning[1]);
    }

    public function testReindexAtOneAddsOneBasedCopiesWithoutRemovingSparseKeys()
    {
        $sql = new SqlStructureUtilityFixture();
        $rows = [
            5 => ['entry_id' => 50],
            9 => ['entry_id' => 90],
        ];

        $this->assertSame([
            5 => ['entry_id' => 50],
            9 => ['entry_id' => 90],
            1 => ['entry_id' => 50],
            2 => ['entry_id' => 90],
        ], $sql->reindex_at_one($rows));
    }

    public function testGetUriAndThemeUrlAndSiteId()
    {
        $config = new class {
            public $items = ['site_id' => 2, 'theme_folder_url' => 'https://cdn.example.com/themes'];
            public function item($key)
            {
                return $this->items[$key] ?? null;
            }
            public function slash_item($key)
            {
                return rtrim($this->items[$key] ?? '', '/') . '/';
            }
        };

        ee()->setMock('config', $config);
        ee()->setMock('uri', new class {
            public function uri_string()
            {
                return 'docs//intro/P20';
            }
        });

        $sql = new SqlStructureUtilityFixture(['add_trailing_slash' => 'y']);
        $this->assertSame('/docs/intro/', $sql->get_uri());
        $this->assertSame(2, $sql->get_site_id());

        $first = $sql->theme_url();
        $second = $sql->theme_url();
        $expectedThemeUrl = (defined('URL_THEMES') ? URL_THEMES : 'https://cdn.example.com/themes/third_party/') . 'structure/';
        $this->assertSame($expectedThemeUrl, $first);
        $this->assertSame($first, $second);

        $config->items['site_id'] = 'abc';
        $this->assertSame(1, $sql->get_site_id());

        ee()->setMock('uri', new class {
            public function uri_string()
            {
                return 'P12';
            }
        });
        $this->assertSame('/', $sql->get_uri());
    }

    public function testGetUriFallsBackToHomepageWhenPaginationConsumesEntireUriWithoutTrailingSlash()
    {
        ee()->setMock('uri', new class {
            public function uri_string()
            {
                return 'P3';
            }
        });

        $sql = new SqlStructureUtilityFixture(['add_trailing_slash' => 'n']);

        $this->assertSame('/', $sql->get_uri());
    }

    /**
     * It captures method-specific subprocess coverage for both theme URL source branches and the cache-hit return path.
     *
     * @return void
     */
    public function testThemeUrlCoverageSubprocessCoversConfigAndUrlThemesBranches(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-theme-url-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_theme_url_subprocess.php';
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($outputFile) . ' 2>&1';

        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertFileExists($outputFile);

        $result = json_decode(file_get_contents($outputFile), true);
        @unlink($outputFile);

        $this->assertIsArray($result);
        $this->assertFalse($result['url_themes_defined_before']);
        $this->assertSame(
            realpath(PATH_ADDONS . 'structure/sql.structure.php'),
            $result['real_module_path']
        );
        $this->assertSame('https://cdn.example.com/themes/third_party/structure/', $result['fallback_first']);
        $this->assertSame($result['fallback_first'], $result['fallback_second']);
        $this->assertSame(1, $result['fallback_config_calls']);
        $this->assertSame('https://themes.example/structure/', $result['constant_result']);
        $this->assertSame(0, $result['constant_config_calls']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(100.0, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertSame([], $result['uncovered_paths']);
        }
    }

    public function testModuleAndExtensionInstallChecksAndModuleId()
    {
        $cache = new class {
            public $store = [];
            public function get($key)
            {
                return $this->store[$key] ?? false;
            }
            public function save($key, $value)
            {
                $this->store[$key] = $value;
                return true;
            }
        };

        $db = new class {
            public $moduleRows;
            public $extensionRows = 1;
            public function __construct()
            {
                $this->moduleRows = [(object) ['module_id' => 55]];
            }
            public function query($sql)
            {
                if (strpos($sql, 'exp_extensions') !== false) {
                    return new class($this->extensionRows) {
                        public $num_rows;
                        public function __construct($count)
                        {
                            $this->num_rows = $count;
                        }
                    };
                }

                return new class($this->moduleRows) {
                    private $rows;
                    public function __construct($rows)
                    {
                        $this->rows = $rows;
                    }
                    public function result()
                    {
                        return $this->rows;
                    }
                };
            }
        };

        ee()->setMock('cache', $cache);
        ee()->setMock('db', $db);

        $sql = new SqlStructureUtilityFixture();
        $this->assertTrue($sql->module_is_installed());
        $this->assertSame(55, $sql->get_module_id());
        $this->assertTrue($sql->extension_is_installed());

        unset($cache->store['/Structure/module_id_query']);
        $db->moduleRows = [];
        $db->extensionRows = 0;
        $this->assertFalse($sql->module_is_installed());
        $this->assertFalse($sql->get_module_id());
        $this->assertFalse($sql->extension_is_installed());
    }

    /**
     * It queries the enabled Structure extension and maps positive row counts to booleans.
     *
     * @return void
     */
    public function testExtensionIsInstalledQueriesEnabledStructureExtensionAndUsesNumRowsBoundary(): void
    {
        $captured = (object) [
            'queries' => [],
        ];

        $db = new class($captured) {
            public $extensionRows = 1;
            private $captured;

            /**
             * Store the query capture object for later assertions.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Capture the extension query and return the configured row count.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return new class($this->extensionRows) {
                    public $num_rows;

                    /**
                     * Store the number of extension rows for the fake result.
                     *
                     * @param int $count
                     * @return void
                     */
                    public function __construct(int $count)
                    {
                        $this->num_rows = $count;
                    }
                };
            }
        };

        ee()->setMock('db', $db);

        $sql = new SqlStructureUtilityFixture();

        $this->assertTrue($sql->extension_is_installed());

        $db->extensionRows = 0;

        $this->assertFalse($sql->extension_is_installed());
        $this->assertSame([
            "SELECT * FROM exp_extensions WHERE class = 'Structure_ext' AND enabled='y'",
            "SELECT * FROM exp_extensions WHERE class = 'Structure_ext' AND enabled='y'",
        ], $captured->queries);
    }

    /**
     * It returns true from cached module rows without querying or saving again.
     *
     * @return void
     */
    public function testModuleIsInstalledReturnsTrueFromCachedModuleRowsWithoutQueryingDatabase(): void
    {
        $moduleRows = [(object) ['module_id' => 55]];
        $captured = (object) [
            'query_count' => 0,
            'saved' => [],
        ];

        ee()->setMock('cache', new class($moduleRows, $captured) {
            private $moduleRows;
            private $captured;

            /**
             * Store the cached rows and capture state for the fake cache.
             *
             * @param array $moduleRows
             * @param object $captured
             * @return void
             */
            public function __construct(array $moduleRows, object $captured)
            {
                $this->moduleRows = $moduleRows;
                $this->captured = $captured;
            }

            /**
             * Return the pre-populated cache value for the Structure module id lookup.
             *
             * @param string $key
             * @return array
             */
            public function get($key): array
            {
                return $this->moduleRows;
            }

            /**
             * Record any unexpected save attempt for later assertions.
             *
             * @param string $key
             * @param mixed $value
             * @return bool
             */
            public function save($key, $value): bool
            {
                $this->captured->saved[] = [$key, $value];

                return true;
            }
        });
        ee()->setMock('db', new class($captured) {
            private $captured;

            /**
             * Store shared capture state for database assertions.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Count any unexpected query that slips past the populated cache.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->query_count++;

                throw new RuntimeException('Database should not be queried when module rows are cached.');
            }
        });

        $sql = new SqlStructureUtilityFixture();

        $this->assertTrue($sql->module_is_installed());
        $this->assertSame(0, $captured->query_count);
        $this->assertSame([], $captured->saved);
    }

    /**
     * It queries and caches populated module rows on a cache miss before returning true.
     *
     * @return void
     */
    public function testModuleIsInstalledQueriesAndCachesModuleRowsOnCacheMiss(): void
    {
        $moduleRows = [(object) ['module_id' => 55]];
        $captured = (object) [
            'saved' => [],
            'queries' => [],
        ];

        ee()->setMock('cache', new class($captured) {
            private $captured;

            /**
             * Store shared capture state for cache assertions.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Simulate a cache miss for the Structure module id lookup.
             *
             * @param string $key
             * @return bool
             */
            public function get($key): bool
            {
                return false;
            }

            /**
             * Capture the saved module rows for later assertions.
             *
             * @param string $key
             * @param mixed $value
             * @return bool
             */
            public function save($key, $value): bool
            {
                $this->captured->saved[] = [$key, $value];

                return true;
            }
        });
        ee()->setMock('db', new class($moduleRows, $captured) {
            private $moduleRows;
            private $captured;

            /**
             * Store the fake database result rows and capture state.
             *
             * @param array $moduleRows
             * @param object $captured
             * @return void
             */
            public function __construct(array $moduleRows, object $captured)
            {
                $this->moduleRows = $moduleRows;
                $this->captured = $captured;
            }

            /**
             * Return the module id query result for the cache-miss path.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return new class($this->moduleRows) {
                    private $rows;

                    /**
                     * Store module rows returned from the fake query.
                     *
                     * @param array $rows
                     * @return void
                     */
                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    /**
                     * Return the canned module rows.
                     *
                     * @return array
                     */
                    public function result(): array
                    {
                        return $this->rows;
                    }
                };
            }
        });

        $sql = new SqlStructureUtilityFixture();

        $this->assertTrue($sql->module_is_installed());
        $this->assertSame(
            ["SELECT module_id FROM exp_modules WHERE module_name = 'Structure'"],
            $captured->queries
        );
        $this->assertSame(
            [['/Structure/module_id_query', $moduleRows]],
            $captured->saved
        );
    }

    /**
     * It caches empty module rows on a cache miss before returning false.
     *
     * @return void
     */
    public function testModuleIsInstalledCachesEmptyResultsAndReturnsFalseWhenModuleIsMissing(): void
    {
        $moduleRows = [];
        $captured = (object) [
            'saved' => [],
            'queries' => [],
        ];

        ee()->setMock('cache', new class($captured) {
            private $captured;

            /**
             * Store shared capture state for cache assertions.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Simulate a cache miss for the missing-module path.
             *
             * @param string $key
             * @return bool
             */
            public function get($key): bool
            {
                return false;
            }

            /**
             * Capture the saved empty module rows for later assertions.
             *
             * @param string $key
             * @param mixed $value
             * @return bool
             */
            public function save($key, $value): bool
            {
                $this->captured->saved[] = [$key, $value];

                return true;
            }
        });
        ee()->setMock('db', new class($moduleRows, $captured) {
            private $moduleRows;
            private $captured;

            /**
             * Store the fake database result rows and capture state.
             *
             * @param array $moduleRows
             * @param object $captured
             * @return void
             */
            public function __construct(array $moduleRows, object $captured)
            {
                $this->moduleRows = $moduleRows;
                $this->captured = $captured;
            }

            /**
             * Return the empty module id query result for the cache-miss path.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return new class($this->moduleRows) {
                    private $rows;

                    /**
                     * Store module rows returned from the fake query.
                     *
                     * @param array $rows
                     * @return void
                     */
                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    /**
                     * Return the canned module rows.
                     *
                     * @return array
                     */
                    public function result(): array
                    {
                        return $this->rows;
                    }
                };
            }
        });

        $sql = new SqlStructureUtilityFixture();

        $this->assertFalse($sql->module_is_installed());
        $this->assertSame(
            ["SELECT module_id FROM exp_modules WHERE module_name = 'Structure'"],
            $captured->queries
        );
        $this->assertSame(
            [['/Structure/module_id_query', $moduleRows]],
            $captured->saved
        );
    }

    /**
     * It returns the cached Structure module id without querying or saving again.
     *
     * @return void
     */
    public function testGetModuleIdReturnsCachedModuleIdWithoutQueryingDatabase(): void
    {
        $moduleRows = [(object) ['module_id' => 55]];
        $captured = (object) [
            'query_count' => 0,
            'saved' => [],
        ];

        ee()->setMock('cache', new class($moduleRows, $captured) {
            private $moduleRows;
            private $captured;

            /**
             * Store the cached rows and capture state for the fake cache.
             *
             * @param array $moduleRows
             * @param object $captured
             * @return void
             */
            public function __construct(array $moduleRows, object $captured)
            {
                $this->moduleRows = $moduleRows;
                $this->captured = $captured;
            }

            /**
             * Return the pre-populated cache value for the Structure module id lookup.
             *
             * @param string $key
             * @return array
             */
            public function get($key): array
            {
                return $this->moduleRows;
            }

            /**
             * Record any unexpected save attempt for later assertions.
             *
             * @param string $key
             * @param mixed $value
             * @return bool
             */
            public function save($key, $value): bool
            {
                $this->captured->saved[] = [$key, $value];

                return true;
            }
        });
        ee()->setMock('db', new class($captured) {
            private $captured;

            /**
             * Store shared capture state for database assertions.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Count any unexpected query that slips past the populated cache.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->query_count++;

                throw new RuntimeException('Database should not be queried when module rows are cached.');
            }
        });

        $sql = new SqlStructureUtilityFixture();

        $this->assertSame(55, $sql->get_module_id());
        $this->assertSame(0, $captured->query_count);
        $this->assertSame([], $captured->saved);
    }

    /**
     * It queries and caches populated module rows on a cache miss before returning the module id.
     *
     * @return void
     */
    public function testGetModuleIdQueriesAndCachesModuleRowsOnCacheMiss(): void
    {
        $moduleRows = [(object) ['module_id' => 55]];
        $captured = (object) [
            'saved' => [],
            'queries' => [],
        ];

        ee()->setMock('cache', new class($captured) {
            private $captured;

            /**
             * Store shared capture state for cache assertions.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Simulate a cache miss for the Structure module id lookup.
             *
             * @param string $key
             * @return bool
             */
            public function get($key): bool
            {
                return false;
            }

            /**
             * Capture the saved module rows for later assertions.
             *
             * @param string $key
             * @param mixed $value
             * @return bool
             */
            public function save($key, $value): bool
            {
                $this->captured->saved[] = [$key, $value];

                return true;
            }
        });
        ee()->setMock('db', new class($moduleRows, $captured) {
            private $moduleRows;
            private $captured;

            /**
             * Store the fake database result rows and capture state.
             *
             * @param array $moduleRows
             * @param object $captured
             * @return void
             */
            public function __construct(array $moduleRows, object $captured)
            {
                $this->moduleRows = $moduleRows;
                $this->captured = $captured;
            }

            /**
             * Return the module id query result for the cache-miss path.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return new class($this->moduleRows) {
                    private $rows;

                    /**
                     * Store module rows returned from the fake query.
                     *
                     * @param array $rows
                     * @return void
                     */
                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    /**
                     * Return the canned module rows.
                     *
                     * @return array
                     */
                    public function result(): array
                    {
                        return $this->rows;
                    }
                };
            }
        });

        $sql = new SqlStructureUtilityFixture();

        $this->assertSame(55, $sql->get_module_id());
        $this->assertSame(
            ["SELECT module_id FROM exp_modules WHERE module_name = 'Structure'"],
            $captured->queries
        );
        $this->assertSame(
            [['/Structure/module_id_query', $moduleRows]],
            $captured->saved
        );
    }

    /**
     * It caches empty module rows on a cache miss and returns false when no Structure module is found.
     *
     * @return void
     */
    public function testGetModuleIdCachesEmptyRowsAndReturnsFalseWhenModuleLookupHasNoMatches(): void
    {
        $moduleRows = [];
        $captured = (object) [
            'saved' => [],
            'queries' => [],
        ];

        ee()->setMock('cache', new class($captured) {
            private $captured;

            /**
             * Store shared capture state for cache assertions.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Simulate a cache miss for the missing-module path.
             *
             * @param string $key
             * @return bool
             */
            public function get($key): bool
            {
                return false;
            }

            /**
             * Capture the saved empty module rows for later assertions.
             *
             * @param string $key
             * @param mixed $value
             * @return bool
             */
            public function save($key, $value): bool
            {
                $this->captured->saved[] = [$key, $value];

                return true;
            }
        });
        ee()->setMock('db', new class($moduleRows, $captured) {
            private $moduleRows;
            private $captured;

            /**
             * Store the fake database result rows and capture state.
             *
             * @param array $moduleRows
             * @param object $captured
             * @return void
             */
            public function __construct(array $moduleRows, object $captured)
            {
                $this->moduleRows = $moduleRows;
                $this->captured = $captured;
            }

            /**
             * Return the empty module id query result for the cache-miss path.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return new class($this->moduleRows) {
                    private $rows;

                    /**
                     * Store module rows returned from the fake query.
                     *
                     * @param array $rows
                     * @return void
                     */
                    public function __construct(array $rows)
                    {
                        $this->rows = $rows;
                    }

                    /**
                     * Return the canned module rows.
                     *
                     * @return array
                     */
                    public function result(): array
                    {
                        return $this->rows;
                    }
                };
            }
        });

        $sql = new SqlStructureUtilityFixture();

        $this->assertFalse($sql->get_module_id());
        $this->assertSame(
            ["SELECT module_id FROM exp_modules WHERE module_name = 'Structure'"],
            $captured->queries
        );
        $this->assertSame(
            [['/Structure/module_id_query', $moduleRows]],
            $captured->saved
        );
    }

    public function testIsValidTemplateAndDuplicateListingUri()
    {
        $db = new class extends eeDbArMock {
            public $duplicateCount = 2;
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'templates') {
                    return new eeDbResultMock(isset($where['template_id']) && (int) $where['template_id'] === 9 ? [['template_id' => 9]] : []);
                }

                return new class($this->duplicateCount > 0 ? 1 : 0) {
                    public $num_rows;
                    public function __construct($count)
                    {
                        $this->num_rows = $count;
                    }
                };
            }
            public function query($sql)
            {
                return new class($this->duplicateCount) {
                    public $num_rows;
                    public function __construct($count)
                    {
                        $this->num_rows = $count;
                    }
                };
            }
        };

        ee()->setMock('db', $db);
        $sql = new SqlStructureUtilityFixture();

        $this->assertFalse($sql->is_valid_template('abc'));
        $this->assertTrue($sql->is_valid_template(9));
        $this->assertFalse($sql->is_valid_template(999));
        $this->assertSame(3, $sql->is_duplicate_listing_uri(10, 'child', 5));

        $db->duplicateCount = 0;
        $this->assertFalse($sql->is_duplicate_listing_uri(10, 'child', 5));
    }

    public function testIsDuplicateListingUriBuildsDuplicateLookupAndCountsRegexMatches()
    {
        $db = new class {
            public $getWhereCalls = [];
            public $queries = [];
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                $this->getWhereCalls[] = [$table, $where, $limit, $offset];

                return new class {
                    public $num_rows = 1;
                };
            }
            public function query($sql)
            {
                $this->queries[] = $sql;

                return new class {
                    public $num_rows = 2;
                };
            }
        };

        ee()->setMock('db', $db);
        $sql = new SqlStructureUtilityFixture();

        $this->assertSame(3, $sql->is_duplicate_listing_uri(10, 'child', 5));
        $this->assertSame([
            [
                'structure_listings',
                ['uri' => 'child', 'parent_id' => 5, 'entry_id !=' => 10],
                null,
                null,
            ],
        ], $db->getWhereCalls);
        $this->assertSame(
            ["SELECT * FROM exp_structure_listings WHERE parent_id=5 AND uri REGEXP '^child.[0-9]'"],
            $db->queries
        );
    }

    public function testIsDuplicateListingUriReturnsFalseWithoutRunningRegexCountWhenNoDuplicateExists()
    {
        $db = new class {
            public $getWhereCalls = [];
            public $queries = [];
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                $this->getWhereCalls[] = [$table, $where, $limit, $offset];

                return new class {
                    public $num_rows = 0;
                };
            }
            public function query($sql)
            {
                $this->queries[] = $sql;

                return new class {
                    public $num_rows = 99;
                };
            }
        };

        ee()->setMock('db', $db);
        $sql = new SqlStructureUtilityFixture();

        $this->assertFalse($sql->is_duplicate_listing_uri(10, 'child', 5));
        $this->assertSame([
            [
                'structure_listings',
                ['uri' => 'child', 'parent_id' => 5, 'entry_id !=' => 10],
                null,
                null,
            ],
        ], $db->getWhereCalls);
        $this->assertSame([], $db->queries);
    }

    public function testUserAccessCoversSettingsAndDbBranches()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                return 1;
            }
        });
        ee()->setMock('session', (object) ['userdata' => ['group_id' => 1]]);

        $sql = new SqlStructureUtilityFixture();
        $this->assertSame('all', $sql->user_access('perm_delete'));
        $this->assertTrue($sql->user_access('perm_publish'));

        ee()->setMock('session', (object) ['userdata' => ['group_id' => 7]]);
        $this->assertTrue($sql->user_access('perm_reorder', ['perm_reorder_7' => 'y']));
        $this->assertSame('n', $sql->user_access('perm_reorder', ['perm_reorder_7' => 'n']));
        $this->assertFalse($sql->user_access('perm_reorder', ['perm_edit_7' => 'y']));

        ee()->setMock('db', new class {
            public $rows = 1;
            public function select($field)
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function where($field, $value)
            {
                return $this;
            }
            public function or_where($field, $value)
            {
                return $this;
            }
            public function num_rows()
            {
                return $this->rows;
            }
        });

        $this->assertSame('all', $sql->user_access('perm_reorder'));
        $this->assertTrue($sql->user_access('perm_publish'));

        ee()->db->rows = 0;
        $this->assertFalse($sql->user_access('perm_publish'));
    }
}
