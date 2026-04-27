<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';
require_once __DIR__ . '/../../../../../Addons/structure/Conduit/StaticCache.php';

use ExpressionEngine\Structure\Conduit\StaticCache;
use PHPUnit\Framework\TestCase;

class SqlStructureGetChildEntriesTest extends TestCase
{
    /**
     * Reset shared mocks and static cache between test runs.
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
     * It returns populated cached children without consulting the database or category lookup.
     *
     * @return void
     */
    public function testGetChildEntriesReturnsCachedEntriesWithoutQueryingCollaborators(): void
    {
        $captured = (object) [
            'category_calls' => [],
            'query_calls' => 0,
        ];

        StaticCache::set([7, 'news', 'n'], [90, 91]);

        ee()->setMock('db', new class($captured) {
            private $captured;

            /**
             * Store collaborator capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Fail if the cached path ever falls through to the database.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->query_calls++;

                throw new RuntimeException('Database should not be queried for cached children.');
            }
        });

        $sql = $this->makeSql($captured, ['news' => [['entry_id' => 1]]]);

        $this->assertSame([90, 91], $sql->get_child_entries(7, 'news', 'n'));
        $this->assertSame([], $captured->category_calls);
        $this->assertSame(0, $captured->query_calls);
    }

    /**
     * It filters hidden children and category membership before returning entry ids in tree order.
     *
     * @return void
     */
    public function testGetChildEntriesFiltersHiddenChildrenAndCategoryMatches(): void
    {
        $captured = (object) [
            'category_calls' => [],
            'queries' => [],
        ];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store collaborator capture state and the parent test helper.
             *
             * @param object $captured
             * @param TestCase $test
             * @return void
             */
            public function __construct(object $captured, TestCase $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            /**
             * Return a populated result set for the filtered query path.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return $this->test->result(
                    [
                        ['entry_id' => 30],
                        ['entry_id' => 31],
                    ]
                );
            }
        });

        $sql = $this->makeSql($captured, [
            'news' => [
                ['entry_id' => 30],
                ['entry_id' => 31],
                ['entry_id' => 45],
            ],
        ]);

        $children = $sql->get_child_entries(5, 'news', 'n');

        $this->assertSame([30, 31], $children);
        $this->assertSame(['news'], $captured->category_calls);
        $this->assertCount(1, $captured->queries);
        $this->assertStringContainsString('parent_id = 5', $captured->queries[0]);
        $this->assertStringContainsString('site_id = 1', $captured->queries[0]);
        $this->assertStringContainsString("hidden != 'y'", $captured->queries[0]);
        $this->assertStringContainsString('entry_id IN(30,31,45)', $captured->queries[0]);
        $this->assertStringContainsString('order by lft asc', $captured->queries[0]);
        $this->assertSame([30, 31], StaticCache::get([5, 'news', 'n']));
    }

    /**
     * It treats parent id zero as valid, keeps hidden children when requested, and skips an empty category filter.
     *
     * @return void
     */
    public function testGetChildEntriesIncludesHiddenChildrenAndSkipsEmptyCategoryFilter(): void
    {
        $captured = (object) [
            'category_calls' => [],
            'queries' => [],
        ];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store collaborator capture state and the parent test helper.
             *
             * @param object $captured
             * @param TestCase $test
             * @return void
             */
            public function __construct(object $captured, TestCase $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            /**
             * Return an empty result set for the boundary parent id path.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([], 0);
            }
        });

        $sql = $this->makeSql($captured, [
            'empty-slug' => [],
        ]);

        $children = $sql->get_child_entries(0, 'empty-slug', 'y');

        $this->assertSame([], $children);
        $this->assertSame(['empty-slug'], $captured->category_calls);
        $this->assertCount(1, $captured->queries);
        $this->assertStringContainsString('parent_id = 0', $captured->queries[0]);
        $this->assertStringNotContainsString("hidden != 'y'", $captured->queries[0]);
        $this->assertStringNotContainsString('entry_id IN(', $captured->queries[0]);
        $this->assertTrue(StaticCache::has([0, 'empty-slug', 'y']));
    }

    /**
     * It returns an empty array without querying when the parent id is not numeric.
     *
     * @return void
     */
    public function testGetChildEntriesReturnsEmptyArrayWithoutQueryingForInvalidParent(): void
    {
        $captured = (object) [
            'query_calls' => 0,
        ];

        ee()->setMock('db', new class($captured) {
            private $captured;

            /**
             * Store collaborator capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Record any unexpected query.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->query_calls++;

                throw new RuntimeException('Database should not be queried for invalid parent ids.');
            }
        });

        $sql = $this->makeSql();

        $this->assertSame([], $sql->get_child_entries('invalid'));
        $this->assertSame(0, $captured->query_calls);
        $this->assertTrue(StaticCache::has(['invalid', '', 'n']));
        $this->assertFalse(StaticCache::get(['invalid', '', 'n']));
    }

    /**
     * It returns an empty array without querying when the parent id is explicitly false.
     *
     * @return void
     */
    public function testGetChildEntriesReturnsEmptyArrayWithoutQueryingForFalseParent(): void
    {
        $captured = (object) [
            'query_calls' => 0,
        ];

        ee()->setMock('db', new class($captured) {
            private $captured;

            /**
             * Store collaborator capture state.
             *
             * @param object $captured
             * @return void
             */
            public function __construct(object $captured)
            {
                $this->captured = $captured;
            }

            /**
             * Record any unexpected query.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->query_calls++;

                throw new RuntimeException('Database should not be queried when the parent id is false.');
            }
        });

        $sql = $this->makeSql();

        $this->assertSame([], $sql->get_child_entries(false));
        $this->assertSame(0, $captured->query_calls);
        $this->assertTrue(StaticCache::has([false, '', 'n']));
        $this->assertFalse(StaticCache::get([false, '', 'n']));
    }

    /**
     * It returns an empty array when the query reports rows but yields no child payload.
     *
     * @return void
     */
    public function testGetChildEntriesReturnsEmptyArrayWhenQueryReportsRowsWithoutPayload(): void
    {
        $captured = (object) [
            'queries' => [],
        ];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store collaborator capture state and the parent test helper.
             *
             * @param object $captured
             * @param TestCase $test
             * @return void
             */
            public function __construct(object $captured, TestCase $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            /**
             * Return a positive row count paired with an empty payload.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([], 1);
            }
        });

        $sql = $this->makeSql();

        $this->assertSame([], $sql->get_child_entries(12, '', 'y'));
        $this->assertCount(1, $captured->queries);
        $this->assertStringContainsString('parent_id = 12', $captured->queries[0]);
        $this->assertStringNotContainsString("hidden != 'y'", $captured->queries[0]);
        $this->assertTrue(StaticCache::has([12, '', 'y']));
        $this->assertFalse(StaticCache::get([12, '', 'y']));
    }

    /**
     * It re-queries after caching an empty result because the static cache treats empty arrays as misses.
     *
     * @return void
     */
    public function testGetChildEntriesRequeriesWhenCachedResultIsEmptyArray(): void
    {
        $captured = (object) [
            'queries' => [],
        ];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            /**
             * Store collaborator capture state and the parent test helper.
             *
             * @param object $captured
             * @param TestCase $test
             * @return void
             */
            public function __construct(object $captured, TestCase $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            /**
             * Return an empty result set on every query.
             *
             * @param string $sql
             * @return object
             */
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([], 0);
            }
        });

        $sql = $this->makeSql();

        $this->assertSame([], $sql->get_child_entries(9, '', 'n'));
        $this->assertSame([], $sql->get_child_entries(9, '', 'n'));
        $this->assertCount(2, $captured->queries);
        $this->assertStringContainsString("hidden != 'y'", $captured->queries[0]);
        $this->assertTrue(StaticCache::has([9, '', 'n']));
        $this->assertFalse(StaticCache::get([9, '', 'n']));
    }

    /**
     * Build a Sql_structure instance without running its constructor.
     *
     * @param object|null $captured
     * @param array $categoryRowsBySlug
     * @return Sql_structure
     */
    private function makeSql(object $captured = null, array $categoryRowsBySlug = []): Sql_structure
    {
        $captured = $captured ?? (object) [
            'category_calls' => [],
        ];

        return new class($captured, $categoryRowsBySlug) extends Sql_structure {
            private $captured;
            private $categoryRowsBySlug;

            /**
             * Store collaborator capture state and category fixtures.
             *
             * @param object $captured
             * @param array $categoryRowsBySlug
             * @return void
             */
            public function __construct(object $captured, array $categoryRowsBySlug)
            {
                $this->captured = $captured;
                $this->categoryRowsBySlug = $categoryRowsBySlug;
                $this->site_id = 1;
                $this->cache = [];
            }

            /**
             * Return the configured category rows for the requested slug.
             *
             * @param string|int $cat
             * @return array
             */
            public function get_entries_by_category($cat)
            {
                $this->captured->category_calls[] = $cat;

                return $this->categoryRowsBySlug[$cat] ?? [];
            }
        };
    }

    /**
     * Build a database result mock with the row-shape APIs used by get_child_entries.
     *
     * @param array $rows
     * @param int|null $numRows
     * @return object
     */
    public function result(array $rows, ?int $numRows = null)
    {
        return new class($rows, $numRows) {
            private $rows;
            private $numRows;

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
                $this->numRows = $numRows ?? count($rows);
            }

            /**
             * Return the configured row count.
             *
             * @return int
             */
            public function num_rows(): int
            {
                return $this->numRows;
            }

            /**
             * Return the configured result rows.
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
