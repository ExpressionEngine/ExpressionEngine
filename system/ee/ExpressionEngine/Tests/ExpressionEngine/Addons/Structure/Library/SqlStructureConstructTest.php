<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureConstructFixture extends Sql_structure
{
    public static $captured;

    /**
     * Return a deterministic site id for constructor assertions.
     *
     * @return int
     */
    public function get_site_id()
    {
        self::$captured->calls[] = ['method' => 'get_site_id'];

        return 42;
    }
}

class SqlStructureConstructTest extends TestCase
{
    /**
     * Reset singleton mocks before each constructor assertion.
     *
     * @return void
     */
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    /**
     * Confirm constructor bootstraps helpers and assigns site id.
     *
     * @return void
     */
    public function testConstructorAddsPackagePathLoadsHelpersAndAssignsSiteId()
    {
        $captured = (object) ['calls' => []];
        SqlStructureConstructFixture::$captured = $captured;

        ee()->setMock('load', new class($captured) {
            private $captured;

            /**
             * Store the shared call ledger.
             *
             * @param object $captured
             * @return void
             */
            public function __construct($captured)
            {
                $this->captured = $captured;
            }

            /**
             * Capture package-path registration.
             *
             * @param string $path
             * @return void
             */
            public function add_package_path($path)
            {
                $this->captured->calls[] = ['method' => 'add_package_path', 'arg' => $path];
            }

            /**
             * Capture requested library loading.
             *
             * @param string $name
             * @return void
             */
            public function library($name)
            {
                $this->captured->calls[] = ['method' => 'library', 'arg' => $name];
            }
        });

        $sql = new SqlStructureConstructFixture();

        $this->assertSame(42, $sql->site_id);
        $this->assertSame(
            [
                ['method' => 'add_package_path', 'arg' => PATH_ADDONS . 'structure/'],
                ['method' => 'library', 'arg' => 'sql_helper'],
                ['method' => 'library', 'arg' => 'general_helper'],
                ['method' => 'get_site_id'],
            ],
            $captured->calls
        );
    }
}
