<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class SqlStructureGetListingEntryTest extends TestCase
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
     * It scopes the listing lookup by entry and site and returns the helper row unchanged.
     *
     * @return void
     */
    public function testGetListingEntryQueriesByEntryAndSiteAndReturnsRow(): void
    {
        $captured = (object) ['sql' => null];

        ee()->setMock('sql_helper', new class($captured) {
            private $captured;

            /**
             * Store captured SQL state for the test.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Capture the lookup SQL and return a listing row.
             *
             * @param string $sql
             * @return array
             */
            public function row($sql): array
            {
                $this->captured->sql = $sql;

                return ['entry_id' => 42, 'parent_id' => 7];
            }
        });

        $sql = $this->makeSql(9);

        $this->assertSame(['entry_id' => 42, 'parent_id' => 7], $sql->get_listing_entry(42));
        $this->assertStringContainsString("FROM exp_structure_listings WHERE entry_id = '42'", $captured->sql);
        $this->assertStringContainsString("AND site_id = '9'", $captured->sql);
    }

    /**
     * It returns false when the helper reports no listing row for the scoped lookup.
     *
     * @return void
     */
    public function testGetListingEntryReturnsFalseWhenHelperReturnsNull(): void
    {
        $captured = (object) ['sql' => null];

        ee()->setMock('sql_helper', new class($captured) {
            private $captured;

            /**
             * Store captured SQL state for the test.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Capture the lookup SQL and simulate a missing row.
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

        $sql = $this->makeSql(3);

        $this->assertFalse($sql->get_listing_entry(0));
        $this->assertStringContainsString("FROM exp_structure_listings WHERE entry_id = '0'", $captured->sql);
        $this->assertStringContainsString("AND site_id = '3'", $captured->sql);
    }

    /**
     * Build a Sql_structure instance without running its constructor.
     *
     * @param int $siteId
     * @return Sql_structure
     */
    private function makeSql(int $siteId): Sql_structure
    {
        $sql = (new ReflectionClass('Sql_structure'))->newInstanceWithoutConstructor();
        $sql->site_id = $siteId;
        $sql->cache = [];

        return $sql;
    }
}
