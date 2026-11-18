<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGetSettingsTest extends TestCase
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
     * It returns early when the Structure module is not installed.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetSettingsReturnsNullWhenModuleIsNotInstalled(): void
    {
        ee()->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return false;
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;

            public function __construct(TestCase $test)
            {
                $this->test = $test;
            }

            public function query($sql)
            {
                $this->test->fail('Database query should not run when Structure is not installed.');
            }
        });

        $sql = $this->makeSql();

        $this->assertNull($sql->get_settings());
    }

    /**
     * It returns the default settings set when no rows exist.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetSettingsReturnsDefaultSettingsWhenQueryHasNoRows(): void
    {
        $captured = (object) ['queries' => []];

        ee()->setMock('addons_model', new class {
            public function module_installed($name)
            {
                return true;
            }
        });
        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            public function __construct(object $captured, TestCase $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([]);
            }
        });

        $sql = $this->makeSql();

        $this->assertSame([
            'show_picker' => 'y',
            'show_view_page' => 'y',
            'show_status' => 'y',
            'show_page_type' => 'y',
            'show_global_add_page' => 'y',
            'redirect_on_login' => 'n',
            'redirect_on_publish' => 'n',
            'add_trailing_slash' => 'y',
        ], $sql->get_settings());
        $this->assertSame(
            "SELECT var_value, var FROM exp_structure_settings WHERE site_id IN (0,1)",
            $captured->queries[0]
        );
    }

    /**
     * It skips empty values and reuses the static cache on later calls.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetSettingsSkipsEmptyValuesAndReturnsCachedSettingsAcrossInstances(): void
    {
        $addons = new class {
            public $installed = true;
            public $calls = 0;

            public function module_installed($name)
            {
                $this->calls++;

                return $this->installed;
            }
        };
        $captured = (object) ['queries' => []];

        ee()->setMock('addons_model', $addons);
        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            public function __construct(object $captured, TestCase $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([
                    ['var' => 'show_picker', 'var_value' => 'n'],
                    ['var' => 'add_trailing_slash', 'var_value' => ''],
                    ['var' => 'hide_from_nav', 'var_value' => 'y'],
                ]);
            }
        });

        $firstInstance = $this->makeSql();
        $settings = $firstInstance->get_settings();

        $this->assertSame('n', $settings['show_picker']);
        $this->assertSame('y', $settings['add_trailing_slash']);
        $this->assertSame('y', $settings['hide_from_nav']);

        $addons->installed = false;
        $secondInstance = $this->makeSql();

        $this->assertSame($settings, $secondInstance->get_settings());
        $this->assertCount(1, $captured->queries);
        $this->assertSame(1, $addons->calls);
    }

    /**
     * Build a Sql_structure instance without running its constructor.
     *
     * @return Sql_structure
     */
    private function makeSql(): Sql_structure
    {
        $sql = (new ReflectionClass('Sql_structure'))->newInstanceWithoutConstructor();
        $sql->site_id = 1;
        $sql->cache = [];

        return $sql;
    }

    /**
     * Build a lightweight query result double for the test database mocks.
     *
     * @param array $rows Rows returned from the fake query.
     * @param int|null $numRows Optional row count override.
     * @return object
     */
    public function result(array $rows, ?int $numRows = null): object
    {
        return new class($rows, $numRows) {
            private $rows;
            public $num_rows;

            public function __construct(array $rows, ?int $numRows)
            {
                $this->rows = $rows;
                $this->num_rows = $numRows ?? count($rows);
            }

            public function num_rows()
            {
                return $this->num_rows;
            }

            public function result_array()
            {
                return $this->rows;
            }
        };
    }
}
