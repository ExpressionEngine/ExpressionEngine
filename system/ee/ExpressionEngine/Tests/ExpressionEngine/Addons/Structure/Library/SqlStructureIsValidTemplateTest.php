<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureIsValidTemplateFixture extends Sql_structure
{
    /**
     * Avoid constructor side effects in focused method tests.
     *
     * @return void
     */
    public function __construct()
    {
    }
}

class SqlStructureIsValidTemplateTest extends TestCase
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
     * It rejects non-numeric values before touching the database collaborator.
     *
     * @return void
     */
    public function testIsValidTemplateReturnsFalseWithoutQueryingDatabaseForNonNumericTemplateId(): void
    {
        $captured = (object) [
            'calls' => [],
        ];

        ee()->setMock('db', new class($captured) {
            private $captured;

            /**
             * Store database call capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Record unexpected lookups for this guard-path assertion.
             *
             * @param string $table
             * @param array|null $where
             * @param int|null $limit
             * @param int|null $offset
             * @return eeDbResultMock
             */
            public function get_where($table, $where = null, $limit = null, $offset = null): eeDbResultMock
            {
                $this->captured->calls[] = [
                    'table' => $table,
                    'where' => $where,
                    'limit' => $limit,
                    'offset' => $offset,
                ];

                return new eeDbResultMock([]);
            }
        });

        $sql = $this->makeSql();

        $this->assertFalse($sql->is_valid_template('template-nine'));
        $this->assertSame([], $captured->calls);
    }

    /**
     * It accepts numeric strings and queries the templates table with the given ID.
     *
     * @return void
     */
    public function testIsValidTemplateAcceptsNumericStringTemplateIds(): void
    {
        $captured = (object) [
            'calls' => [],
        ];

        ee()->setMock('db', new class($captured) {
            private $captured;

            /**
             * Store database call capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Record the lookup and return a hit for the numeric-string scenario.
             *
             * @param string $table
             * @param array|null $where
             * @param int|null $limit
             * @param int|null $offset
             * @return eeDbResultMock
             */
            public function get_where($table, $where = null, $limit = null, $offset = null): eeDbResultMock
            {
                $this->captured->calls[] = [
                    'table' => $table,
                    'where' => $where,
                    'limit' => $limit,
                    'offset' => $offset,
                ];

                $rows = ($table === 'templates' && $where === ['template_id' => '9'])
                    ? [['template_id' => 9]]
                    : [];

                return new eeDbResultMock($rows);
            }
        });

        $sql = $this->makeSql();

        $this->assertTrue($sql->is_valid_template('9'));
        $this->assertSame([
            [
                'table' => 'templates',
                'where' => ['template_id' => '9'],
                'limit' => null,
                'offset' => null,
            ],
        ], $captured->calls);
    }

    /**
     * It returns false when the templates lookup finds no matching row.
     *
     * @return void
     */
    public function testIsValidTemplateReturnsFalseWhenLookupHasNoMatches(): void
    {
        $captured = (object) [
            'calls' => [],
        ];

        ee()->setMock('db', new class($captured) {
            private $captured;

            /**
             * Store database call capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Record the lookup and return an empty result set.
             *
             * @param string $table
             * @param array|null $where
             * @param int|null $limit
             * @param int|null $offset
             * @return eeDbResultMock
             */
            public function get_where($table, $where = null, $limit = null, $offset = null): eeDbResultMock
            {
                $this->captured->calls[] = [
                    'table' => $table,
                    'where' => $where,
                    'limit' => $limit,
                    'offset' => $offset,
                ];

                return new eeDbResultMock([]);
            }
        });

        $sql = $this->makeSql();

        $this->assertFalse($sql->is_valid_template(999));
        $this->assertSame([
            [
                'table' => 'templates',
                'where' => ['template_id' => 999],
                'limit' => null,
                'offset' => null,
            ],
        ], $captured->calls);
    }

    /**
     * It confirms exact line and branch coverage for the real method implementation.
     *
     * @return void
     */
    public function testIsValidTemplateCoverageSubprocessReportsFullCoverage(): void
    {
        $outputFile = sys_get_temp_dir() . '/sql-structure-is-valid-template-' . uniqid('', true) . '.json';
        $script = dirname(__DIR__, 4) . '/support/sql_structure_is_valid_template_subprocess.php';
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
        $this->assertSame([], $result['non_numeric_calls']);
        $this->assertTrue($result['numeric_string_result']);
        $this->assertFalse($result['missing_numeric_result']);
        $this->assertSame([
            [
                'table' => 'templates',
                'where' => ['template_id' => '9'],
                'limit' => null,
                'offset' => null,
            ],
            [
                'table' => 'templates',
                'where' => ['template_id' => 999],
                'limit' => null,
                'offset' => null,
            ],
        ], $result['lookup_calls']);

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
     * @return SqlStructureIsValidTemplateFixture
     */
    private function makeSql(): SqlStructureIsValidTemplateFixture
    {
        return new SqlStructureIsValidTemplateFixture();
    }
}
