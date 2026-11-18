<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGetTemplatesFixture extends Sql_structure
{
    public $settingsFixture = [];

    /**
     * Avoid constructor side effects in focused method tests.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Return the configured settings fixture for the method under test.
     *
     * @return array
     */
    public function get_settings(): array
    {
        return $this->settingsFixture;
    }
}

class SqlStructureGetTemplatesTest extends TestCase
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
     * It returns queried templates unchanged when hidden filtering is disabled.
     *
     * @return void
     */
    public function testGetTemplatesReturnsAllTemplatesWhenHiddenFilteringIsDisabled(): void
    {
        $captured = (object) [
            'queries' => [],
            'result_array_calls' => 0,
            'config_calls' => 0,
        ];

        ee()->setMock('db', $this->makeDbMock($captured, [
            ['group_name' => 'blog', 'template_id' => 7, 'template_name' => '.draft'],
            ['group_name' => 'blog', 'template_id' => 8, 'template_name' => 'index'],
        ]));
        ee()->setMock('config', new class($captured) {
            private $captured;

            /**
             * Store config call capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Record unexpected config access for the disabled-filter path.
             *
             * @param string $key
             * @return string
             */
            public function item($key): string
            {
                $this->captured->config_calls++;

                return '_';
            }
        });

        $sql = $this->makeSql();
        $sql->settingsFixture = ['hide_hidden_templates' => 'n'];

        $templates = $sql->get_templates();

        $this->assertSame(
            [
                ['group_name' => 'blog', 'template_id' => 7, 'template_name' => '.draft'],
                ['group_name' => 'blog', 'template_id' => 8, 'template_name' => 'index'],
            ],
            $templates
        );
        $this->assertSame(0, $captured->config_calls);
        $this->assertSame(1, $captured->result_array_calls);
        $this->assertSame(1, count($captured->queries));
        $this->assertStringContainsString("AND tg.site_id = '5'", $captured->queries[0]);
        $this->assertStringContainsString('ORDER BY tg.group_name, t.template_name', $captured->queries[0]);
    }

    /**
     * It skips hidden filtering entirely when the setting key is absent.
     *
     * @return void
     */
    public function testGetTemplatesReturnsAllTemplatesWhenHideHiddenSettingIsMissing(): void
    {
        $captured = (object) [
            'queries' => [],
            'result_array_calls' => 0,
            'config_calls' => 0,
        ];

        ee()->setMock('db', $this->makeDbMock($captured, [
            ['group_name' => 'blog', 'template_id' => 7, 'template_name' => '.draft'],
            ['group_name' => 'blog', 'template_id' => 8, 'template_name' => 'index'],
        ]));
        ee()->setMock('config', new class($captured) {
            private $captured;

            /**
             * Store config call capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Record unexpected config access for the missing-setting path.
             *
             * @param string $key
             * @return bool
             */
            public function item($key): bool
            {
                $this->captured->config_calls++;

                return false;
            }
        });

        $sql = $this->makeSql();

        $this->assertSame(
            [
                ['group_name' => 'blog', 'template_id' => 7, 'template_name' => '.draft'],
                ['group_name' => 'blog', 'template_id' => 8, 'template_name' => 'index'],
            ],
            $sql->get_templates()
        );
        $this->assertSame(0, $captured->config_calls);
        $this->assertSame(1, $captured->result_array_calls);
    }

    /**
     * It removes dot-prefixed templates when hidden filtering is enabled without a custom indicator.
     *
     * @return void
     */
    public function testGetTemplatesFiltersHiddenTemplatesUsingDefaultIndicator(): void
    {
        $captured = (object) [
            'queries' => [],
            'result_array_calls' => 0,
            'config_calls' => 0,
        ];

        ee()->setMock('db', $this->makeDbMock($captured, [
            ['group_name' => 'pages', 'template_id' => 2, 'template_name' => '.hidden'],
            ['group_name' => 'pages', 'template_id' => 3, 'template_name' => 'index'],
        ]));
        ee()->setMock('config', new class($captured) {
            private $captured;

            /**
             * Store config call capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Return a falsey indicator so the method falls back to a dot.
             *
             * @param string $key
             * @return bool
             */
            public function item($key): bool
            {
                $this->captured->config_calls++;

                return false;
            }
        });

        $sql = $this->makeSql();
        $sql->settingsFixture = ['hide_hidden_templates' => 'y'];

        $templates = $sql->get_templates();

        $this->assertSame(
            [
                1 => ['group_name' => 'pages', 'template_id' => 3, 'template_name' => 'index'],
            ],
            $templates
        );
        $this->assertSame(1, $captured->config_calls);
        $this->assertSame(1, $captured->result_array_calls);
    }

    /**
     * It honors a custom hidden indicator and preserves dot-prefixed names when they are no longer hidden.
     *
     * @return void
     */
    public function testGetTemplatesFiltersHiddenTemplatesUsingCustomIndicator(): void
    {
        $captured = (object) [
            'queries' => [],
            'result_array_calls' => 0,
            'config_calls' => 0,
        ];

        ee()->setMock('db', $this->makeDbMock($captured, [
            ['group_name' => 'pages', 'template_id' => 2, 'template_name' => '_hidden'],
            ['group_name' => 'pages', 'template_id' => 3, 'template_name' => '.visible-now'],
            ['group_name' => 'pages', 'template_id' => 4, 'template_name' => 'index'],
        ]));
        ee()->setMock('config', new class($captured) {
            private $captured;

            /**
             * Store config call capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Return the custom underscore indicator.
             *
             * @param string $key
             * @return string
             */
            public function item($key): string
            {
                $this->captured->config_calls++;

                return '_';
            }
        });

        $sql = $this->makeSql();
        $sql->settingsFixture = ['hide_hidden_templates' => 'y'];

        $templates = $sql->get_templates();

        $this->assertSame(
            [
                1 => ['group_name' => 'pages', 'template_id' => 3, 'template_name' => '.visible-now'],
                2 => ['group_name' => 'pages', 'template_id' => 4, 'template_name' => 'index'],
            ],
            $templates
        );
        $this->assertSame(2, $captured->config_calls);
        $this->assertSame(1, $captured->result_array_calls);
    }

    /**
     * It returns an empty array when filtering is enabled but the query produces no templates.
     *
     * @return void
     */
    public function testGetTemplatesReturnsEmptyArrayForEmptyTemplateResultSet(): void
    {
        $captured = (object) [
            'queries' => [],
            'result_array_calls' => 0,
            'config_calls' => 0,
        ];

        ee()->setMock('db', $this->makeDbMock($captured, []));
        ee()->setMock('config', new class($captured) {
            private $captured;

            /**
             * Store config call capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Return a falsey indicator so the method uses the default branch.
             *
             * @param string $key
             * @return bool
             */
            public function item($key): bool
            {
                $this->captured->config_calls++;

                return false;
            }
        });

        $sql = $this->makeSql();
        $sql->settingsFixture = ['hide_hidden_templates' => 'y'];

        $this->assertSame([], $sql->get_templates());
        $this->assertSame(1, $captured->config_calls);
        $this->assertSame(1, $captured->result_array_calls);
    }

    /**
     * It confirms method coverage and reports the remaining non-reachable branch edge from Xdebug.
     *
     * @return void
     */
    public function testGetTemplatesCoverageSubprocessReportsRemainingBranchGap(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-get-templates-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_get_templates_subprocess.php';
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
                [
                    ['group_name' => 'blog', 'template_id' => 7, 'template_name' => '.draft'],
                    ['group_name' => 'blog', 'template_id' => 8, 'template_name' => 'index'],
                ],
                [
                    ['group_name' => 'blog', 'template_id' => 7, 'template_name' => '.draft'],
                    ['group_name' => 'blog', 'template_id' => 8, 'template_name' => 'index'],
                ],
                [
                    ['group_name' => 'pages', 'template_id' => 2, 'template_name' => '.hidden'],
                    ['group_name' => 'pages', 'template_id' => 3, 'template_name' => 'index'],
                ],
                [
                    ['group_name' => 'pages', 'template_id' => 2, 'template_name' => '_hidden'],
                    ['group_name' => 'pages', 'template_id' => 3, 'template_name' => '.visible-now'],
                    ['group_name' => 'pages', 'template_id' => 4, 'template_name' => 'index'],
                ],
                [],
            ],
            $result['query_rows']
        );
        $this->assertSame(
            [
                ['group_name' => 'blog', 'template_id' => 7, 'template_name' => '.draft'],
                ['group_name' => 'blog', 'template_id' => 8, 'template_name' => 'index'],
            ],
            $result['missing_setting_result']
        );
        $this->assertSame(
            [
                ['group_name' => 'blog', 'template_id' => 7, 'template_name' => '.draft'],
                ['group_name' => 'blog', 'template_id' => 8, 'template_name' => 'index'],
            ],
            $result['unfiltered_result']
        );
        $this->assertSame(
            [
                1 => ['group_name' => 'pages', 'template_id' => 3, 'template_name' => 'index'],
            ],
            $result['default_indicator_result']
        );
        $this->assertSame(
            [
                1 => ['group_name' => 'pages', 'template_id' => 3, 'template_name' => '.visible-now'],
                2 => ['group_name' => 'pages', 'template_id' => 4, 'template_name' => 'index'],
            ],
            $result['custom_indicator_result']
        );
        $this->assertSame([], $result['empty_result']);
        $this->assertSame([0, 0, 1, 2, 1], $result['config_call_counts']);

        if ($result['xdebug_available'] ?? false) {
            $this->assertEquals(100.0, $result['line_percentage']);
            $this->assertEquals(94.44444444444444, $result['branch_percentage']);
            $this->assertSame([], $result['uncovered_lines']);
            $this->assertCount(1, $result['uncovered_branches']);
        }
    }

    /**
     * Build a Sql_structure fixture without running the production constructor.
     *
     * @return SqlStructureGetTemplatesFixture
     */
    private function makeSql(): SqlStructureGetTemplatesFixture
    {
        $sql = new SqlStructureGetTemplatesFixture();
        $sql->site_id = 5;
        $sql->cache = [];

        return $sql;
    }

    /**
     * Build a lightweight database mock for the method under test.
     *
     * @param object $captured
     * @param array $rows
     * @return object
     */
    private function makeDbMock(object $captured, array $rows): object
    {
        return new class($captured, $rows) {
            private $captured;
            private $rows;

            /**
             * Store query capture state and configured rows.
             *
             * @param object $captured
             * @param array $rows
             * @return void
             */
            public function __construct(object $captured, array $rows)
            {
                $this->captured = $captured;
                $this->rows = $rows;
            }

            /**
             * Return the configured template rows for the generated SQL.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql): object
            {
                $this->captured->queries[] = $sql;

                return new class($this->captured, $this->rows) {
                    private $captured;
                    private $rows;

                    /**
                     * Store row payload for the fake result.
                     *
                     * @param object $captured
                     * @param array $rows
                     * @return void
                     */
                    public function __construct(object $captured, array $rows)
                    {
                        $this->captured = $captured;
                        $this->rows = $rows;
                    }

                    /**
                     * Return the configured rows and record hydration.
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
        };
    }
}
