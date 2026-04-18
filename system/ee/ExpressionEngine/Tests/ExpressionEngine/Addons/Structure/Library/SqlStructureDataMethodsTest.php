<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';
require_once __DIR__ . '/../../../../../Addons/structure/Conduit/StaticCache.php';

use ExpressionEngine\Structure\Conduit\StaticCache;
use PHPUnit\Framework\TestCase;

class SqlStructureSettingsFixture extends Sql_structure
{
    public $settingsFixture = [];

    public function __construct()
    {
    }

    public function get_settings()
    {
        return $this->settingsFixture;
    }
}

class SqlStructureDataMethodsTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
        StaticCache::clear();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSettingsStatusAndSetChannelIds()
    {
        $addons = new class {
            public $installed = false;
            public function module_installed($name)
            {
                return $this->installed;
            }
        };
        $captured = (object) ['queries' => []];

        ee()->setMock('addons_model', $addons);
        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;
            public function __construct($captured, $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;
                if (strpos($sql, 'exp_structure_settings') !== false) {
                    return $this->test->result([
                        ['var' => 'show_picker', 'var_value' => 'n'],
                        ['var' => 'hide_hidden_templates', 'var_value' => 'y'],
                    ]);
                }
                if (strpos($sql, 'exp_statuses') !== false) {
                    return $this->test->result([
                        ['status' => 'open', 'highlight' => '#fff'],
                        ['status' => 'closed', 'highlight' => '#000'],
                    ]);
                }
                return $this->test->result([]);
            }
        });

        $sql = $this->makeSql();
        $this->assertNull($sql->get_settings());

        $addons->installed = true;
        $settings = $sql->get_settings();
        $this->assertSame('n', $settings['show_picker']);
        $this->assertSame('y', $settings['hide_hidden_templates']);

        $this->assertSame(['open' => '#fff', 'closed' => '#000'], $sql->get_status_colors());

        Sql_structure::set_channel_ids(7, 3);
        $queries = implode("\n", $captured->queries);
        $this->assertStringContainsString('UPDATE exp_structure SET channel_id = 3 WHERE entry_id = 7', $queries);
    }

    public function testOverviewAndMemberAndHomeRelatedMethods()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                return 1;
            }
        });
        ee()->setMock('session', new class {
            public function userdata($key)
            {
                return 9;
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });
        ee()->setMock('sql_helper', new class {
            public $calls = 0;
            public function row($sql)
            {
                if (strpos($sql, 'SELECT parent_id FROM exp_structure_listings') !== false) {
                    return ['parent_id' => 5];
                }
                if (strpos($sql, 'SELECT parent_id FROM exp_structure WHERE entry_id = 21') !== false) {
                    return ['parent_id' => 0];
                }
                if (strpos($sql, 'SELECT entry_id FROM exp_structure WHERE lft = 2') !== false) {
                    return ['entry_id' => 2];
                }
                if (strpos($sql, 'SELECT * FROM exp_structure WHERE entry_id = 0') !== false) {
                    return ['entry_id' => 0, 'lft' => 1, 'rgt' => 20];
                }
                return null;
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            private $table = null;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'FROM exp_structure AS node') !== false && strpos($sql, 'GROUP BY node.lft') !== false) {
                    return $this->test->result([['entry_id' => 12, 'title' => 'Overview']], 1);
                }
                if (strpos($sql, 'SELECT entry_id FROM exp_structure_listings') !== false) {
                    return $this->test->result([['entry_id' => 20]], 1);
                }
                return $this->test->result([]);
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'structure_members') {
                    return $this->test->result([[
                        'site_id' => 1,
                        'member_id' => 9,
                        'nav_state' => '{"closed":[1,2]}',
                    ]], 1);
                }
                return $this->test->result([]);
            }
            public function where($field, $value = null)
            {
                return $this;
            }
            public function limit($n)
            {
                return $this;
            }
            public function get($table = null)
            {
                if ($table === 'exp_channel_titles') {
                    return $this->test->result([['title' => 'Page Title']], 1);
                }
                return $this->test->result([]);
            }
        });

        $sql = $this->makeSql();
        $overview = $sql->get_overview(12);
        $this->assertSame('Overview', $overview['title']);
        $this->assertSame($overview, $sql->get_overview(12));

        $member = $sql->get_member_settings();
        $this->assertSame(9, $member['member_id']);
        $this->assertIsObject($member['nav_state']);

        $this->assertSame(5, $sql->get_parent_id(20));
        $this->assertSame(2, $sql->get_parent_id(21));
        $this->assertSame(2, $sql->get_home_page_id());
        $this->assertSame(['entry_id' => 0, 'lft' => 1, 'rgt' => 20], $sql->get_home_node());
        $this->assertSame('Page Title', $sql->get_page_title(21));
        $this->assertTrue($sql->is_listing_entry(20));
        $this->assertSame([20 => 20], $sql->get_listing_entry_ids());
    }

    public function testIsListingEntryReturnsFalseWhenEntryIdIsMissing()
    {
        StaticCache::set('listing_ids', [20 => 20]);
        StaticCache::set('listing_ids_empty', 'false');

        $sql = $this->makeSql();

        $this->assertFalse($sql->is_listing_entry(21));
    }

    public function testGetOverviewCachesEmptyResultsWithoutRepeatQueries()
    {
        $db = new class($this) {
            private $test;
            public $overviewQueries = 0;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'FROM exp_structure AS node') !== false && strpos($sql, 'GROUP BY node.lft') !== false) {
                    $this->overviewQueries++;
                }

                return $this->test->result([], 0);
            }
        };
        ee()->setMock('db', $db);

        $sql = $this->makeSql();

        $this->assertSame([], $sql->get_overview(999));
        $this->assertSame([], $sql->get_overview(999));
        $this->assertSame(1, $db->overviewQueries);
    }

    public function testGetHomeNodeCachesFetchedNode()
    {
        $sqlHelper = new class {
            public $calls = [];

            public function row($sql)
            {
                $this->calls[] = $sql;

                return ['entry_id' => 0, 'lft' => 1, 'rgt' => 20];
            }
        };
        ee()->setMock('sql_helper', $sqlHelper);

        $sql = $this->makeSql();

        $this->assertSame(['entry_id' => 0, 'lft' => 1, 'rgt' => 20], $sql->get_home_node());
        $this->assertSame(['entry_id' => 0, 'lft' => 1, 'rgt' => 20], StaticCache::get('get_home_node'));
        $this->assertSame(['SELECT * FROM exp_structure WHERE entry_id = 0'], $sqlHelper->calls);
    }

    public function testGetHomeNodeReturnsCachedNodeWithoutQueryingSqlHelper()
    {
        $sqlHelper = new class {
            public $calls = 0;

            public function row($sql)
            {
                $this->calls++;

                return ['entry_id' => 999];
            }
        };
        ee()->setMock('sql_helper', $sqlHelper);
        StaticCache::set('get_home_node', ['entry_id' => 0, 'lft' => 1, 'rgt' => 20]);

        $sql = $this->makeSql();

        $this->assertSame(['entry_id' => 0, 'lft' => 1, 'rgt' => 20], $sql->get_home_node());
        $this->assertSame(0, $sqlHelper->calls);
    }

    public function testGetPageTitleReturnsFalseForNonNumericEntryIdWithoutTouchingDb()
    {
        $db = new class {
            public $whereCalls = 0;
            public $limitCalls = 0;
            public $getCalls = 0;

            public function where($field, $value = null)
            {
                $this->whereCalls++;

                return $this;
            }

            public function limit($n)
            {
                $this->limitCalls++;

                return $this;
            }

            public function get($table = null)
            {
                $this->getCalls++;

                throw new RuntimeException('get_page_title() should not query the database for non-numeric entry IDs.');
            }
        };
        ee()->setMock('db', $db);

        $sql = $this->makeSql();

        $this->assertFalse($sql->get_page_title('not-an-id'));
        $this->assertSame(0, $db->whereCalls);
        $this->assertSame(0, $db->limitCalls);
        $this->assertSame(0, $db->getCalls);
        $this->assertFalse(StaticCache::get('structure_page_title_not-an-id'));
    }

    public function testGetParentIdUsesDistinctCacheKeysPerDefault()
    {
        $sqlHelper = new class {
            public $calls = [];
            public function row($sql)
            {
                $this->calls[] = $sql;

                if (strpos($sql, 'SELECT parent_id FROM exp_structure WHERE entry_id = 21') !== false) {
                    return ['parent_id' => 0];
                }

                if (strpos($sql, 'SELECT entry_id FROM exp_structure WHERE lft = 2') !== false) {
                    return ['entry_id' => 2];
                }

                return null;
            }
        };
        ee()->setMock('sql_helper', $sqlHelper);

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }

            public function get_listing_entry_ids()
            {
                return [];
            }
        };
        $sql->site_id = 1;

        $this->assertSame(2, $sql->get_parent_id(21));
        $this->assertSame(0, $sql->get_parent_id(21, 'root'));
        $this->assertSame([
            'SELECT parent_id FROM exp_structure WHERE entry_id = 21 AND site_id = 1',
            'SELECT entry_id FROM exp_structure WHERE lft = 2 AND site_id = 1',
            'SELECT parent_id FROM exp_structure WHERE entry_id = 21 AND site_id = 1',
        ], $sqlHelper->calls);
    }

    public function testGetParentIdReturnsCachedValueWithoutRequerying()
    {
        $sqlHelper = new class {
            public $calls = [];
            public function row($sql)
            {
                $this->calls[] = $sql;

                if (strpos($sql, 'SELECT parent_id FROM exp_structure WHERE entry_id = 31') !== false) {
                    return ['parent_id' => 9];
                }

                return null;
            }
        };
        ee()->setMock('sql_helper', $sqlHelper);

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }

            public function get_listing_entry_ids()
            {
                return [];
            }
        };
        $sql->site_id = 1;

        $this->assertSame(9, $sql->get_parent_id(31));
        $this->assertSame(9, $sql->get_parent_id(31));
        $this->assertSame([
            'SELECT parent_id FROM exp_structure WHERE entry_id = 31 AND site_id = 1',
        ], $sqlHelper->calls);
    }

    /**
     * Verify the home-page lookup queries the live Structure table for the current site.
     *
     * @return void
     */
    public function testGetHomePageIdUsesCurrentSiteHomeQuery()
    {
        $sqlHelper = new class {
            public $calls = [];

            public function row($sql)
            {
                $this->calls[] = $sql;

                return ['entry_id' => 42];
            }
        };
        ee()->setMock('sql_helper', $sqlHelper);

        $sql = $this->makeSql();
        $sql->site_id = 7;

        $this->assertSame(42, $sql->get_home_page_id());
        $this->assertSame([
            'SELECT entry_id FROM exp_structure WHERE lft = 2 AND site_id = 7',
        ], $sqlHelper->calls);
    }

    public function testStructureChannelsAndCategoryAndChannelLookupMethods()
    {
        ee()->setMock('config', new class {
            public $items = ['site_id' => 1, 'site_pages' => [1 => ['url' => '/', 'uris' => [9 => '/x/'], 'templates' => [9 => 3]]]];
            public function item($key)
            {
                return $this->items[$key] ?? null;
            }
        });
        ee()->setMock('sql_helper', new class {
            public function row($sql)
            {
                if (strpos($sql, 'FROM exp_structure_channels') !== false) {
                    return ['channel_id' => 2, 'template_id' => 3];
                }
                return null;
            }
        });

        ee()->setMock('db', new class($this) {
            private $test;
            private $table = null;
            private $where = [];
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'FROM exp_channels AS ec') !== false) {
                    return $this->test->result([
                        ['channel_id' => 2, 'channel_title' => 'Pages', 'site_id' => 1, 'template_id' => 3, 'type' => 'page', 'split_assets' => 'n', 'show_in_page_selector' => 'y']
                    ], 1);
                }
                if (strpos($sql, 'SELECT type FROM exp_structure_channels') !== false) {
                    return $this->test->result([['type' => 'page']], 1);
                }
                if (strpos($sql, 'SELECT template_id FROM exp_structure_channels') !== false) {
                    return $this->test->result([['template_id' => 3]], 1);
                }
                return $this->test->result([]);
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                $this->table = $table;
                return $this;
            }
            public function where($field, $value = null)
            {
                $this->where[$field] = $value;
                return $this;
            }
            public function get($table = null)
            {
                if ($table !== null) {
                    $this->table = $table;
                }

                $rows = [];
                if ($this->table === 'categories') {
                    $rows = [['cat_id' => 4]];
                } elseif ($this->table === 'category_posts') {
                    $rows = [['entry_id' => 31], ['entry_id' => 32]];
                } elseif ($this->table === 'channel_titles') {
                    $rows = [['channel_id' => 2]];
                } elseif ($this->table === 'channels') {
                    $rows = [['channel_name' => 'pages']];
                }

                $this->where = [];
                return $this->test->result($rows, count($rows));
            }
            public function count_all($table)
            {
                return 1;
            }
        });

        $sql = $this->makeSql();
        $channels = $sql->get_structure_channels('page', '', 'alpha', true);
        $this->assertSame('Pages', $channels[2]['channel_title']);
        $this->assertSame('page', $sql->get_channel_type(2));
        $this->assertFalse($sql->get_channel_type('x'));
        $this->assertSame(3, $sql->get_default_template(2));
        $this->assertFalse($sql->get_default_template('x'));
        $this->assertSame([['entry_id' => 31], ['entry_id' => 32]], $sql->get_entries_by_category('news'));
        $this->assertSame(2, $sql->get_channel_by_entry_id(31));
        $this->assertSame('pages', $sql->get_channel_name_by_channel_id(2));
        $this->assertSame(0, $sql->get_page_count());
    }

    public function testGetChannelTypeReturnsFalseWhenNumericChannelHasNoMatchingStructureChannel()
    {
        $captured = (object) ['queries' => []];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            public function __construct($captured, $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([], 0);
            }
        });

        $sql = $this->makeSql();
        $sql->site_id = 7;

        $this->assertFalse($sql->get_channel_type('0'));
        $this->assertSame([
            "SELECT type FROM exp_structure_channels WHERE channel_id = '0' AND site_id = '7' LIMIT 1",
        ], $captured->queries);
    }

    /**
     * Resolve a category slug before querying category posts.
     *
     * @return void
     */
    public function testGetEntriesByCategoryResolvesSlugToCategoryIdBeforeLoadingEntries()
    {
        $fixture = $this->makeGetEntriesByCategoryDb(
            [['cat_id' => 4]],
            [['entry_id' => 31], ['entry_id' => 32]],
            1
        );
        ee()->setMock('db', $fixture->db);

        $sql = $this->makeSql();

        $this->assertSame([['entry_id' => 31], ['entry_id' => 32]], $sql->get_entries_by_category('news'));
        $this->assertSame([
            [
                'table' => 'categories',
                'fields' => ['cat_id'],
                'where' => ['cat_url_title' => 'news'],
            ],
            [
                'table' => 'category_posts',
                'fields' => ['entry_id'],
                'where' => ['cat_id' => 4],
            ],
        ], $fixture->captured->gets);
    }

    /**
     * Keep the original slug when the category lookup misses.
     *
     * @return void
     */
    public function testGetEntriesByCategoryKeepsSlugWhenCategoryLookupHasNoMatch()
    {
        $fixture = $this->makeGetEntriesByCategoryDb(
            [],
            [['entry_id' => 77]],
            0
        );
        ee()->setMock('db', $fixture->db);

        $sql = $this->makeSql();

        $this->assertSame([['entry_id' => 77]], $sql->get_entries_by_category('missing-news'));
        $this->assertSame([
            [
                'table' => 'categories',
                'fields' => ['cat_id'],
                'where' => ['cat_url_title' => 'missing-news'],
            ],
            [
                'table' => 'category_posts',
                'fields' => ['entry_id'],
                'where' => ['cat_id' => 'missing-news'],
            ],
        ], $fixture->captured->gets);
    }

    /**
     * Skip the category lookup when the caller already provides a numeric value.
     *
     * @return void
     */
    public function testGetEntriesByCategorySkipsSlugLookupForNumericCategoryValues()
    {
        $fixture = $this->makeGetEntriesByCategoryDb(
            [['cat_id' => 999]],
            [['entry_id' => 88]]
        );
        ee()->setMock('db', $fixture->db);

        $sql = $this->makeSql();

        $this->assertSame([['entry_id' => 88]], $sql->get_entries_by_category('4'));
        $this->assertSame([
            [
                'table' => 'category_posts',
                'fields' => ['entry_id'],
                'where' => ['cat_id' => '4'],
            ],
        ], $fixture->captured->gets);
    }

    /**
     * Lock the channel-name lookup query contract for matching channels.
     *
     * @return void
     */
    public function testGetChannelNameByChannelIdBuildsChannelLookupQueryAndReturnsChannelName()
    {
        $fixture = $this->makeChannelNameByChannelIdDb([
            ['channel_name' => 'pages'],
        ]);
        ee()->setMock('db', $fixture->db);

        $sql = $this->makeSql();

        $this->assertSame('pages', $sql->get_channel_name_by_channel_id('2'));
        $this->assertSame([
            [
                'table' => 'channels',
                'fields' => ['channel_name'],
                'where' => ['channel_id' => '2'],
            ],
        ], $fixture->captured->gets);
    }

    /**
     * Preserve the null fallback when the channel lookup misses.
     *
     * @return void
     */
    public function testGetChannelNameByChannelIdReturnsNullWhenLookupMisses()
    {
        $fixture = $this->makeChannelNameByChannelIdDb([]);
        ee()->setMock('db', $fixture->db);

        $sql = $this->makeSql();

        $this->assertNull($sql->get_channel_name_by_channel_id(5));
        $this->assertSame([
            [
                'table' => 'channels',
                'fields' => ['channel_name'],
                'where' => ['channel_id' => 5],
            ],
        ], $fixture->captured->gets);
    }

    /**
     * Lock the structure table lookup and root-row subtraction contract.
     *
     * @return void
     */
    public function testGetPageCountCountsStructureTableAndSubtractsRootNode()
    {
        $captured = (object) ['tables' => []];

        ee()->setMock('db', new class($captured) {
            private $captured;

            public function __construct($captured)
            {
                $this->captured = $captured;
            }

            public function count_all($table)
            {
                $this->captured->tables[] = $table;

                return 4;
            }
        });

        $sql = $this->makeSql();

        $this->assertSame(3, $sql->get_page_count());
        $this->assertSame(['structure'], $captured->tables);
    }

    public function testGetChannelTypeReturnsFalseForNonNumericChannelWithoutQueryingDb()
    {
        $db = new class {
            public $queryCalls = 0;

            public function query($sql)
            {
                $this->queryCalls++;

                throw new RuntimeException('get_channel_type() should not query the database for non-numeric channel IDs.');
            }
        };
        ee()->setMock('db', $db);

        $sql = $this->makeSql();

        $this->assertFalse($sql->get_channel_type('not-a-channel'));
        $this->assertSame(0, $db->queryCalls);
    }

    public function testGetDefaultTemplateReturnsFalseWhenNumericChannelHasNoMatchingStructureChannel()
    {
        $captured = (object) ['queries' => []];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            public function __construct($captured, $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([], 0);
            }
        });

        $sql = $this->makeSql();
        $sql->site_id = 7;

        $this->assertFalse($sql->get_default_template('0'));
        $this->assertSame([
            "SELECT template_id FROM exp_structure_channels WHERE channel_id = '0' AND site_id = '7' LIMIT 1",
        ], $captured->queries);
    }

    public function testGetDefaultTemplateReturnsFalseForNonNumericChannelWithoutQueryingDb()
    {
        $db = new class {
            public $queryCalls = 0;

            public function query($sql)
            {
                $this->queryCalls++;

                throw new RuntimeException('get_default_template() should not query the database for non-numeric channel IDs.');
            }
        };
        ee()->setMock('db', $db);

        $sql = $this->makeSql();

        $this->assertFalse($sql->get_default_template('not-a-channel'));
        $this->assertSame(0, $db->queryCalls);
    }

    public function testGetStructureChannelsBuildsExpectedQueryForAllOptionalFilters()
    {
        $captured = (object) ['queries' => []];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 7;
                }

                return null;
            }
        });
        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            public function __construct($captured, $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([
                    ['channel_id' => 9, 'channel_title' => 'Alpha', 'site_id' => 7, 'template_id' => 11, 'type' => 'page', 'split_assets' => 'n', 'show_in_page_selector' => 'y'],
                    ['channel_id' => 12, 'channel_title' => 'Beta', 'site_id' => 7, 'template_id' => 15, 'type' => 'page', 'split_assets' => 'y', 'show_in_page_selector' => 'y'],
                ], 2);
            }
        });

        $channels = $this->makeSql()->get_structure_channels('page', 9, 'alpha', true);
        $queryText = implode("\n", $captured->queries);

        $this->assertSame([9, 12], array_keys($channels));
        $this->assertSame('Alpha', $channels[9]['channel_title']);
        $this->assertSame('y', $channels[12]['split_assets']);
        $this->assertStringContainsString("WHERE ec.site_id = '7'", $queryText);
        $this->assertStringContainsString("AND esc.type = 'page'", $queryText);
        $this->assertStringContainsString("AND esc.channel_id = '9'", $queryText);
        $this->assertStringContainsString("AND esc.show_in_page_selector = 'y'", $queryText);
        $this->assertStringContainsString('ORDER BY ec.channel_title', $queryText);
    }

    public function testGetStructureChannelsUsesBaseQueryWhenFiltersAreEmpty()
    {
        $captured = (object) ['queries' => []];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 3;
                }

                return null;
            }
        });
        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;

            public function __construct($captured, $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }

            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([], 0);
            }
        });

        $result = $this->makeSql()->get_structure_channels('', '', '', false);
        $queryText = implode("\n", $captured->queries);

        $this->assertFalse($result);
        $this->assertStringContainsString("WHERE ec.site_id = '3'", $queryText);
        $this->assertStringNotContainsString('AND esc.type =', $queryText);
        $this->assertStringNotContainsString('AND esc.channel_id =', $queryText);
        $this->assertStringNotContainsString('AND esc.show_in_page_selector =', $queryText);
        $this->assertStringNotContainsString('ORDER BY ec.channel_title', $queryText);
    }

    public function testSitePagesTemplateAndListingMethods()
    {
        ee()->setMock('config', new class {
            public $items = [
                'site_id' => 1,
                'site_pages' => [1 => ['url' => '/', 'uris' => [10 => '/news/'], 'templates' => [10 => 2]]],
                'hidden_template_indicator' => '.'
            ];
            public function item($key)
            {
                return $this->items[$key] ?? null;
            }
        });
        ee()->setMock('sql_helper', new class {
            public function row($sql)
            {
                if (strpos($sql, 'SELECT site_pages FROM exp_sites') !== false) {
                    return ['site_pages' => base64_encode(serialize([1 => ['url' => '/', 'uris' => [12 => '/cached'], 'templates' => [12 => 7]]]))];
                }
                if (strpos($sql, 'SELECT * FROM exp_structure_listings WHERE entry_id') !== false) {
                    return ['entry_id' => 99, 'parent_id' => 5];
                }
                if (strpos($sql, 'SELECT channel_name FROM exp_channels') !== false) {
                    return ['channel_name' => 'blog'];
                }
                if (strpos($sql, 'SELECT listing_cid FROM exp_structure') !== false) {
                    return ['listing_cid' => 8];
                }
                return null;
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            private $where = [];
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'FROM   exp_template_groups') !== false) {
                    return $this->test->result([
                        ['group_name' => 'pages', 'template_id' => 2, 'template_name' => '.hidden'],
                        ['group_name' => 'pages', 'template_id' => 3, 'template_name' => 'index'],
                    ], 2);
                }
                return $this->test->result([]);
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function where($field, $value = null)
            {
                $this->where[$field] = $value;
                return $this;
            }
            public function get($table = null)
            {
                $rows = [];
                if ($table === 'structure') {
                    $rows = [['listing_cid' => 8]];
                }
                $this->where = [];
                return $this->test->result($rows, count($rows));
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'channels') {
                    return $this->test->result([['channel_id' => 8]], 1);
                }
                if ($table === 'structure') {
                    return $this->test->result([['listing_cid' => 8]], 1);
                }
                return $this->test->result([]);
            }
        });

        $sql = new SqlStructureSettingsFixture();
        $sql->site_id = 1;
        $sql->settingsFixture = ['add_trailing_slash' => 'y', 'hide_hidden_templates' => 'y'];
        $this->assertSame('/news/', $sql->get_site_pages()['uris'][10]);
        $this->assertSame('/cached/', $sql->get_site_pages(true)['uris'][12]);

        $templates = $sql->get_templates();
        $this->assertCount(1, $templates);
        $this->assertSame('index', $templates[1]['template_name']);

        $this->assertSame(['entry_id' => 99, 'parent_id' => 5], $sql->get_listing_entry(99));
        $this->assertSame(8, $sql->get_listing_channel_by_id(10));
        $this->assertSame('blog', $sql->get_listing_channel_short_name(8));
        $this->assertSame(8, $sql->get_listing_channel(10));
    }

    public function testEntryAndListingCollectionsAndHiddenState()
    {
        ee()->setMock('db', new class($this) {
            private $test;
            public $table;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'SELECT title FROM exp_channel_titles') !== false) {
                    return $this->test->result([['title' => 'My Title']], 1);
                }
                if (strpos($sql, 'SELECT entry_id FROM exp_channel_titles WHERE channel_id') !== false) {
                    return $this->test->result([['entry_id' => 101], ['entry_id' => 102]], 2);
                }
                if (strpos($sql, 'SELECT entry_id, title FROM exp_channel_titles') !== false) {
                    return $this->test->result([['entry_id' => 101, 'title' => 'A'], ['entry_id' => 102, 'title' => 'B']], 2);
                }
                if (strpos($sql, 'SELECT channel_id FROM exp_structure_channels WHERE type = \'asset\'') !== false) {
                    return $this->test->result([['channel_id' => 4]], 1);
                }
                if (strpos($sql, 'FROM exp_structure_listings WHERE channel_id') !== false) {
                    return $this->test->result([['entry_id' => 7, 'uri' => 'x']], 1);
                }
                if (strpos($sql, 'SELECT channel_id') !== false && strpos($sql, 'FROM exp_channel_titles') !== false) {
                    return $this->test->result([['channel_id' => 4]], 1);
                }
                if (strpos($sql, 'SELECT entry_id') !== false && strpos($sql, 'FROM exp_structure') !== false) {
                    return $this->test->result([['entry_id' => 55]], 1);
                }
                if (strpos($sql, 'SELECT MAX(rgt)') !== false) {
                    return $this->test->result([['max_right' => 20]], 1);
                }
                return $this->test->result([]);
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                $this->table = $table;
                return $this;
            }
            public function where($field, $value = null)
            {
                return $this;
            }
            public function get()
            {
                if ($this->table === 'channel_titles') {
                    return $this->test->result([['entry_id' => 7, 'uri' => 'x']], 1);
                }
                return $this->test->result([['hidden' => 'y']], 1);
            }
        });

        $sql = $this->makeSql();
        $sql->site_id = 1;

        $this->assertSame('My Title', $sql->get_entry_title(7));
        $this->assertNull($sql->get_entry_title('bad'));
        $this->assertSame([101, 102], $sql->get_entries_by_channel(4));
        $this->assertFalse($sql->get_entries_by_channel('bad'));
        $this->assertSame([['entry_id' => 101, 'title' => 'A'], ['entry_id' => 102, 'title' => 'B']], $sql->get_entry_titles_by_channel(4));
        $this->assertFalse($sql->get_entry_titles_by_channel('bad'));

        $sqlSplit = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_entry_titles_by_channel($channel_id)
            {
                return [['entry_id' => 10, 'title' => 'Asset']];
            }
        };
        $sqlSplit->site_id = 1;
        $this->assertSame([4 => [['entry_id' => 10, 'title' => 'Asset']]], $sqlSplit->get_split_assets());

        $this->assertSame([['entry_id' => 7, 'uri' => 'x']], $sql->get_listing_channel_data(4));
        $this->assertSame(55, $sql->get_pid_for_listing_entry(123));
        $this->assertSame([7 => ['entry_id' => 7, 'uri' => 'x']], $sql->get_channel_listing_entries(4));
        $this->assertSame('y', $sql->get_hidden_state(7));
        $sql->update_root_node();
    }

    public function testNegativeBranchesForLookupMethods()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['url' => '/', 'uris' => ['/'], 'templates' => []]];
                }
                return null;
            }
        });
        ee()->setMock('session', new class {
            public function userdata($key)
            {
                return 99;
            }
        });
        ee()->setMock('sql_helper', new class {
            public function row($sql)
            {
                return null;
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            private $table = null;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([]);
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                $this->table = $table;
                return $this;
            }
            public function where($field, $value = null)
            {
                return $this;
            }
            public function limit($n)
            {
                return $this;
            }
            public function get($table = null)
            {
                if ($table !== null) {
                    $this->table = $table;
                }
                return $this->test->result([]);
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                return $this->test->result([]);
            }
            public function count_all($table)
            {
                return 1;
            }
        });

        $sql = $this->makeSql();
        $this->assertSame([], $sql->get_overview(999));
        $this->assertNull($sql->get_member_settings());
        $this->assertFalse($sql->get_parent_id('bad'));
        $this->assertFalse($sql->get_parent_id(123));
        $this->assertFalse($sql->get_page_title(123));
        $this->assertNull($sql->get_channel_by_entry_id(123));
        $this->assertNull($sql->get_channel_name_by_channel_id(5));
        $this->assertFalse($sql->get_structure_channels('page'));
        $this->assertFalse($sql->get_listing_entry(5));
        $this->assertNull($sql->get_listing_parent(8));
        $this->assertFalse($sql->get_listing_channel_by_id(5));
        $this->assertFalse($sql->get_listing_channel_short_name(8));
        $this->assertNull($sql->get_split_assets());
        $this->assertFalse($sql->get_channel_listing_entries('bad'));
        $this->assertFalse($sql->get_channel_listing_entries(8));
        $this->assertSame('n', $sql->get_hidden_state(5));
        $this->assertNull($sql->get_entry_title(5));
        $this->assertFalse($sql->get_pid_for_listing_entry(5));
    }

    public function testSitePagesAndListingChannelAdditionalBranches()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['url' => '/', 'uris' => [1 => '/a/', 2 => '/'], 'templates' => []]];
                }
                return null;
            }
        });
        ee()->setMock('sql_helper', new class {
            public function row($sql)
            {
                if (strpos($sql, 'SELECT site_pages FROM exp_sites') !== false) {
                    return null;
                }
                if (strpos($sql, 'SELECT listing_cid FROM exp_structure') !== false) {
                    return ['listing_cid' => 0];
                }
                if (strpos($sql, 'SELECT channel_id') !== false && strpos($sql, 'FROM exp_channel_titles') !== false) {
                    return null;
                }
                return null;
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'channels') {
                    return $this->test->result([]);
                }
                return $this->test->result([]);
            }
            public function where($field, $value = null)
            {
                return $this;
            }
            public function update($table, $data = null)
            {
                return true;
            }
            public function query($sql)
            {
                return $this->test->result([]);
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function where_in($field, $values)
            {
                return $this;
            }
            public function get($table = null)
            {
                return $this->test->result([]);
            }
        });

        $sql = new SqlStructureSettingsFixture();
        $sql->site_id = 1;
        $sql->settingsFixture = ['add_trailing_slash' => 'n'];

        $pages = $sql->get_site_pages();
        $this->assertSame('/a', $pages['uris'][1]);
        $this->assertSame('/', $pages['uris'][2]);
        $this->assertSame(['url' => '', 'uris' => [], 'templates' => []], $sql->get_site_pages(true));
        $this->assertFalse($sql->get_listing_channel('abc'));
        $this->assertFalse($sql->get_listing_channel(10));
    }

    public function testGetSitePagesOverrideSlashBypassesTrailingSlashNormalization()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'site_pages') {
                    return [1 => ['url' => '/', 'uris' => [4 => '//double//slash//', 5 => '/'], 'templates' => []]];
                }

                return null;
            }
        });

        $sql = new SqlStructureSettingsFixture();
        $sql->site_id = 1;
        $sql->settingsFixture = ['add_trailing_slash' => 'y'];

        $normalizedPages = $sql->get_site_pages();
        $overridePages = $sql->get_site_pages(false, true);

        $this->assertSame('/double/slash/', $normalizedPages['uris'][4]);
        $this->assertSame('//double//slash', $overridePages['uris'][4]);
        $this->assertSame('/', $overridePages['uris'][5]);
    }

    public function testAdditionalBranchesForHighThresholdSqlMethods()
    {
        StaticCache::clear();

        ee()->setMock('config', new class {
            public $items = ['site_id' => 1];
            public function item($key)
            {
                return $this->items[$key] ?? null;
            }
        });
        ee()->setMock('uri', new class {
            public $value = 'alpha/beta/P12';
            public function uri_string()
            {
                return $this->value;
            }
        });
        ee()->setMock('sql_helper', new class {
            public $rows = [['listing_cid' => 8], ['listing_cid' => 9]];
            public function row($sql)
            {
                if (strpos($sql, 'SELECT listing_cid FROM exp_structure') !== false) {
                    return array_shift($this->rows);
                }
                if (strpos($sql, 'SELECT * FROM exp_structure WHERE entry_id = 0') !== false) {
                    return ['entry_id' => 0];
                }
                return null;
            }
        });

        $db = new class($this) {
            private $test;
            public $channelCalls = 0;
            public $pageTitleCalls = 0;
            private $table = null;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                $this->table = $table;
                return $this;
            }
            public function where($field, $value = null)
            {
                return $this;
            }
            public function limit($n)
            {
                return $this;
            }
            public function get($table = null)
            {
                if ($table === 'exp_channel_titles') {
                    $this->pageTitleCalls++;
                    if ($this->pageTitleCalls === 1) {
                        return $this->test->result([['title' => 'Cached Title']], 1);
                    }
                    return $this->test->result([], 0);
                }
                if ($table === null && $this->table === 'structure') {
                    return $this->test->result([['entry_id' => 5]], 1);
                }
                return $this->test->result([]);
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'channels') {
                    $this->channelCalls++;
                    if ($this->channelCalls === 1) {
                        return $this->test->result([['channel_id' => 8]], 1);
                    }
                    return $this->test->result([], 0);
                }
                if ($table === 'structure') {
                    return $this->test->result([['entry_id' => 5]], 1);
                }
                return $this->test->result([]);
            }
            public function update($table, $data = null)
            {
                return true;
            }
            public function query($sql)
            {
                if (strpos($sql, 'FROM exp_channels AS ec') !== false) {
                    return $this->test->result([
                        ['channel_id' => 2, 'channel_title' => 'Pages', 'site_id' => 1, 'template_id' => 3, 'type' => 'page', 'split_assets' => 'n', 'show_in_page_selector' => 'y']
                    ], 1);
                }
                return $this->test->result([]);
            }
        };
        ee()->setMock('db', $db);

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'n'];
            }
            public function get_listing_entry_ids()
            {
                return [20 => 20];
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 5;
            }
        };
        $sql->site_id = 1;

        $this->assertSame('Cached Title', $sql->get_page_title(88));
        $this->assertSame('Cached Title', $sql->get_page_title(88));

        $this->assertSame('/alpha/beta', $sql->get_uri());
        ee()->uri->value = 'P3';
        $this->assertSame('/', $sql->get_uri());

        $this->assertSame(['entry_id' => 0], $sql->get_home_node());
        $this->assertSame(['entry_id' => 0], $sql->get_home_node());

        $channels = $sql->get_structure_channels('', 2, '', false);
        $this->assertSame('Pages', $channels[2]['channel_title']);

        $this->assertSame(5, $sql->get_listing_parent(8));
        $this->assertSame(8, $sql->get_listing_channel(20));
        $this->assertSame(8, $sql->get_listing_channel(20));

        StaticCache::clear();
        $this->assertFalse($sql->get_listing_channel(20));
    }

    /**
     * Verifies get_listing_parent() scopes the lookup to Structure rows for the site.
     *
     * @return void
     */
    public function testGetListingParentUsesStructureTableAndSiteFilter()
    {
        $captured = (object) [
            'selects' => [],
            'tables' => [],
            'where' => [],
            'get_calls' => 0,
            'get_args' => [],
        ];

        ee()->setMock('db', new class($this, $captured) {
            private $test;
            private $captured;

            public function __construct($test, $captured)
            {
                $this->test = $test;
                $this->captured = $captured;
            }

            public function select($fields = '*')
            {
                $this->captured->selects[] = $fields;

                return $this;
            }

            public function from($table)
            {
                $this->captured->tables[] = $table;

                return $this;
            }

            public function where($field, $value = null)
            {
                $this->captured->where[] = [$field, $value];

                return $this;
            }

            public function get($table = null)
            {
                $this->captured->get_calls++;
                $this->captured->get_args[] = $table;

                return $this->test->result([
                    ['entry_id' => 42],
                ], 1);
            }
        });

        $sql = $this->makeSql();
        $sql->site_id = 9;

        $this->assertSame(42, $sql->get_listing_parent(77));
        $this->assertSame(['entry_id'], $captured->selects);
        $this->assertSame(['structure'], $captured->tables);
        $this->assertSame([
            ['listing_cid', 77],
            ['site_id', 9],
        ], $captured->where);
        $this->assertSame(1, $captured->get_calls);
        $this->assertSame([null], $captured->get_args);
    }

    public function testCreateCustomTitlesExercisesPrivateTitleHelpers()
    {
        StaticCache::clear();

        ee()->setMock('TMPL', new class {
            public function fetch_param($key, $default = false)
            {
                if ($key === 'channel:title') {
                    return 'blog:headline|news:subhead';
                }
                return $default;
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, $value)
            {
                return $value;
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });
        ee()->setMock('legacy_api', new class {
            public function instantiate($name)
            {
            }
        });
        ee()->setMock('api_channel_fields', new class {
            public function fetch_custom_channel_fields($customTitles)
            {
                return [
                    'custom_channel_fields' => [
                        0 => ['headline' => 12],
                        1 => ['subhead' => 13],
                    ]
                ];
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'SELECT channel_id, channel_name FROM exp_channels') !== false) {
                    return $this->test->result([
                        ['channel_id' => 2, 'channel_name' => 'blog'],
                        ['channel_id' => 3, 'channel_name' => 'news'],
                    ], 2);
                }
                return $this->test->result([]);
            }
        });
        ee()->setMock('Model', new class {
            public function get($model)
            {
                if ($model !== 'ChannelEntry') {
                    return new class {
                        public function __call($name, $args)
                        {
                            return $this;
                        }
                        public function all()
                        {
                            return [];
                        }
                    };
                }

                return new class {
                    public function fields(...$args)
                    {
                        return $this;
                    }
                    public function filter($field, $operator, $value)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return [
                            (object) ['entry_id' => 10, 'channel_id' => 2, 'title' => 'Default Blog', 'field_id_12' => 'Blog Headline'],
                            (object) ['entry_id' => 20, 'channel_id' => 3, 'title' => 'Default News', 'field_id_13' => 'News Subhead'],
                        ];
                    }
                };
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                if ($type === 'listing') {
                    return [9 => ['channel_id' => 9]];
                }
                return [
                    2 => ['channel_id' => 2],
                    3 => ['channel_id' => 3],
                ];
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $titles = $sql->create_custom_titles(true);
        $this->assertSame('Blog Headline', $titles[10]);
        $this->assertSame('News Subhead', $titles[20]);
    }

    public function testCreateCustomTitlesExcludesListingsUnlessRequested()
    {
        StaticCache::clear();

        ee()->setMock('TMPL', new class {
            public function fetch_param($key, $default = false)
            {
                if ($key === 'channel:title') {
                    return 'blog:headline|listing:lede';
                }

                return $default;
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, $value)
            {
                return $value;
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });
        ee()->setMock('legacy_api', new class {
            public function instantiate($name)
            {
            }
        });
        ee()->setMock('api_channel_fields', new class {
            public function fetch_custom_channel_fields($customTitles)
            {
                return [
                    'custom_channel_fields' => [
                        0 => ['headline' => 12, 'lede' => 14],
                    ]
                ];
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'SELECT channel_id, channel_name FROM exp_channels') !== false) {
                    return $this->test->result([
                        ['channel_id' => 2, 'channel_name' => 'blog'],
                        ['channel_id' => 9, 'channel_name' => 'listing'],
                    ], 2);
                }

                return $this->test->result([]);
            }
        });
        ee()->setMock('Model', new class {
            public $channelIds = [];
            public function get($model)
            {
                if ($model !== 'ChannelEntry') {
                    return new class {
                        public function __call($name, $args)
                        {
                            return $this;
                        }
                        public function all()
                        {
                            return [];
                        }
                    };
                }

                return new class($this) {
                    private $parent;
                    public function __construct($parent)
                    {
                        $this->parent = $parent;
                    }
                    public function fields(...$args)
                    {
                        return $this;
                    }
                    public function filter($field, $operator, $value)
                    {
                        if ($field === 'channel_id' && $operator === 'IN') {
                            $this->parent->channelIds = $value;
                        }

                        return $this;
                    }
                    public function all()
                    {
                        $entries = [
                            (object) ['entry_id' => 10, 'channel_id' => 2, 'site_id' => 1, 'title' => 'Default Blog', 'field_id_12' => 'Blog Headline'],
                            (object) ['entry_id' => 30, 'channel_id' => 9, 'site_id' => 1, 'title' => 'Default Listing', 'field_id_14' => 'Listing Lede'],
                        ];

                        return array_values(array_filter($entries, function ($entry) {
                            return in_array($entry->channel_id, $this->parent->channelIds, true);
                        }));
                    }
                };
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                if ($type === 'listing') {
                    return [9 => ['channel_id' => 9]];
                }

                return [2 => ['channel_id' => 2]];
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $withoutListings = $sql->create_custom_titles();
        $this->assertSame([10 => 'Blog Headline'], $withoutListings);

        $withListings = $sql->create_custom_titles(true);
        $this->assertSame([10 => 'Blog Headline', 30 => 'Listing Lede'], $withListings);
    }

    public function testCreateCustomTitlesIgnoresMissingListingChannelsWhenListingsRequested()
    {
        StaticCache::clear();

        ee()->setMock('TMPL', new class {
            public function fetch_param($key, $default = false)
            {
                if ($key === 'channel:title') {
                    return 'blog:headline|listing:lede';
                }

                return $default;
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, $value)
            {
                return $value;
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });
        ee()->setMock('legacy_api', new class {
            public function instantiate($name)
            {
            }
        });
        ee()->setMock('api_channel_fields', new class {
            public function fetch_custom_channel_fields($customTitles)
            {
                return [
                    'custom_channel_fields' => [
                        0 => ['headline' => 12, 'lede' => 14],
                    ]
                ];
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'SELECT channel_id, channel_name FROM exp_channels') !== false) {
                    return $this->test->result([
                        ['channel_id' => 2, 'channel_name' => 'blog'],
                        ['channel_id' => 9, 'channel_name' => 'listing'],
                    ], 2);
                }

                return $this->test->result([]);
            }
        });
        ee()->setMock('Model', new class {
            public $channelIds = [];
            public function get($model)
            {
                if ($model !== 'ChannelEntry') {
                    return new class {
                        public function __call($name, $args)
                        {
                            return $this;
                        }
                        public function all()
                        {
                            return [];
                        }
                    };
                }

                return new class($this) {
                    private $parent;
                    public function __construct($parent)
                    {
                        $this->parent = $parent;
                    }
                    public function fields(...$args)
                    {
                        return $this;
                    }
                    public function filter($field, $operator, $value)
                    {
                        if ($field === 'channel_id' && $operator === 'IN') {
                            $this->parent->channelIds = $value;
                        }

                        return $this;
                    }
                    public function all()
                    {
                        $entries = [
                            (object) ['entry_id' => 10, 'channel_id' => 2, 'site_id' => 1, 'title' => 'Default Blog', 'field_id_12' => 'Blog Headline'],
                            (object) ['entry_id' => 30, 'channel_id' => 9, 'site_id' => 1, 'title' => 'Default Listing', 'field_id_14' => 'Listing Lede'],
                        ];

                        return array_values(array_filter($entries, function ($entry) {
                            return in_array($entry->channel_id, $this->parent->channelIds, true);
                        }));
                    }
                };
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                if ($type === 'listing') {
                    return false;
                }

                return [2 => ['channel_id' => 2]];
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $titles = $sql->create_custom_titles(true);

        $this->assertSame([10 => 'Blog Headline'], $titles);
        $this->assertSame([2], ee()->Model->channelIds);
    }

    public function testCreateCustomTitlesReturnsFalseWhenNoSqlFieldsMatch()
    {
        StaticCache::clear();

        ee()->setMock('TMPL', new class {
            public function fetch_param($key, $default = false)
            {
                if ($key === 'channel:title') {
                    return 'blog:headline';
                }

                return $default;
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, $value)
            {
                return $value;
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });
        ee()->setMock('legacy_api', new class {
            public function instantiate($name)
            {
            }
        });
        ee()->setMock('api_channel_fields', new class {
            public function fetch_custom_channel_fields($customTitles)
            {
                return [
                    'custom_channel_fields' => [
                        0 => ['other_field' => 99],
                    ]
                ];
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'SELECT channel_id, channel_name FROM exp_channels') !== false) {
                    return $this->test->result([
                        ['channel_id' => 2, 'channel_name' => 'blog'],
                    ], 1);
                }

                return $this->test->result([]);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $this->assertFalse($sql->create_custom_titles());
    }

    public function testCreateCustomTitlesReturnsFalseForMalformedCustomTitlePair()
    {
        StaticCache::clear();

        ee()->setMock('TMPL', new class {
            public function fetch_param($key, $default = false)
            {
                if ($key === 'channel:title') {
                    return 'blogheadline|news:lede';
                }

                return $default;
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, $value)
            {
                return $value;
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public $queryCount = 0;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                $this->queryCount++;

                if (strpos($sql, 'SELECT channel_id, channel_name FROM exp_channels') !== false) {
                    return $this->test->result([
                        ['channel_id' => 2, 'channel_name' => 'blog'],
                        ['channel_id' => 3, 'channel_name' => 'news'],
                    ], 2);
                }

                return $this->test->result([]);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $this->assertFalse($sql->create_custom_titles());
        $this->assertSame(1, ee()->db->queryCount);
    }

    public function testCreateCustomTitlesReusesCustomTitleCacheAndIgnoresUnknownChannels()
    {
        StaticCache::clear();

        ee()->setMock('TMPL', new class {
            public function fetch_param($key, $default = false)
            {
                if ($key === 'channel:title') {
                    return 'unknown:headline|blog:headline';
                }

                return $default;
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, $value)
            {
                return $value;
            }
        });
        ee()->setMock('load', new class {
            public function library($name)
            {
            }
        });
        ee()->setMock('legacy_api', new class {
            public function instantiate($name)
            {
            }
        });
        ee()->setMock('api_channel_fields', new class {
            public function fetch_custom_channel_fields($customTitles)
            {
                return [
                    'custom_channel_fields' => [
                        0 => ['headline' => 12],
                    ]
                ];
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public $queryCount = 0;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                $this->queryCount++;

                if (strpos($sql, 'SELECT channel_id, channel_name FROM exp_channels') !== false) {
                    return $this->test->result([
                        ['channel_id' => 2, 'channel_name' => 'blog'],
                    ], 1);
                }

                return $this->test->result([]);
            }
        });
        ee()->setMock('Model', new class {
            public function get($model)
            {
                if ($model !== 'ChannelEntry') {
                    return new class {
                        public function __call($name, $args)
                        {
                            return $this;
                        }
                        public function all()
                        {
                            return [];
                        }
                    };
                }

                return new class {
                    public function fields(...$args)
                    {
                        return $this;
                    }
                    public function filter($field, $operator, $value)
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return [
                            (object) ['entry_id' => 10, 'channel_id' => 2, 'site_id' => 1, 'title' => 'Default Blog', 'field_id_12' => 'Blog Headline'],
                        ];
                    }
                };
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                return [2 => ['channel_id' => 2]];
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $this->assertSame([10 => 'Blog Headline'], $sql->create_custom_titles());
        $this->assertSame([10 => 'Blog Headline'], $sql->create_custom_titles());
        $this->assertSame(1, ee()->db->queryCount);
    }

    public function testGetDataSinglePathAndGetChannelDataMethods()
    {
        StaticCache::clear();

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'base_url') {
                    return 'https://base.example/';
                }
                return null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing = false)
            {
                return rtrim($base, '/') . '/' . ltrim($uri, '/');
            }
        });
        ee()->setMock('extensions', new class {
            public $last_call = null;
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, $value)
            {
                return $value;
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'FROM (SELECT node.*, (COUNT(parent.entry_id) - 1) AS depth') !== false) {
                    return $this->test->result([
                        ['entry_id' => 10, 'parent_id' => 0, 'title' => 'Home', 'status' => 'open', 'depth' => 1]
                    ], 1);
                }
                if (strpos($sql, 'FROM exp_structure AS node,') !== false) {
                    return $this->test->result([
                        ['entry_id' => 10, 'title' => 'Home', 'lft' => 2, 'rgt' => 3]
                    ], 1);
                }
                return $this->test->result([]);
            }
        });
        ee()->setMock('sql_helper', new class {
            public function row($sql)
            {
                if (strpos($sql, 'SELECT * FROM exp_structure_channels') !== false) {
                    return ['channel_id' => 7, 'template_id' => 21];
                }
                return null;
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return ['url' => '{base_url}/', 'uris' => [10 => '/home/'], 'templates' => [10 => 11]];
            }
            public function get_listing_entry_ids()
            {
                return [];
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $data = $sql->get_data();
        $this->assertArrayHasKey(10, $data);
        $this->assertSame('Home', $data[10]['title']);

        $path = $sql->get_single_path(10);
        $this->assertArrayHasKey(10, $path);
        $this->assertStringContainsString('/home/', $path[10]['uri']);

        $this->assertSame(['channel_id' => 7, 'template_id' => 21], $sql->get_channel_data(7));
    }

    public function testGetChannelDataBuildsExpectedQueryAndReturnsSqlHelperRow()
    {
        $captured = (object) ['sql' => null];

        ee()->setMock('sql_helper', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function row($sql)
            {
                $this->captured->sql = $sql;

                return ['channel_id' => 42, 'template_id' => 88];
            }
        });

        $sql = $this->makeSql();
        $sql->site_id = 9;

        $this->assertSame(['channel_id' => 42, 'template_id' => 88], $sql->get_channel_data(42));
        $this->assertStringContainsString('SELECT * FROM exp_structure_channels', $captured->sql);
        $this->assertStringContainsString('WHERE channel_id = 42', $captured->sql);
        $this->assertStringContainsString('AND site_id = 9', $captured->sql);
    }

    public function testGetChannelDataReturnsNullWhenSqlHelperHasNoMatch()
    {
        $captured = (object) ['sql' => null];

        ee()->setMock('sql_helper', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function row($sql)
            {
                $this->captured->sql = $sql;

                return null;
            }
        });

        $sql = $this->makeSql();
        $sql->site_id = 4;

        $this->assertNull($sql->get_channel_data(0));
        $this->assertStringContainsString('WHERE channel_id = 0', $captured->sql);
        $this->assertStringContainsString('AND site_id = 4', $captured->sql);
    }

    public function testGetSinglePathUsesListingParentAndUrlHookOverride()
    {
        $captured = (object) ['queries' => [], 'hookUrls' => []];

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'base_url') {
                    return 'https://example.test/';
                }
                return null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing = false)
            {
                return rtrim($base, '/') . '/' . ltrim($uri, '/');
            }
        });
        ee()->setMock('extensions', new class($captured) {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function active_hook($name)
            {
                return $name === 'structure_generate_page_url_end';
            }
            public function call($name, $url)
            {
                $this->captured->hookUrls[] = $url;

                return str_replace('example.test', 'fr.example.test', $url);
            }
        });
        ee()->setMock('db', new class($this, $captured) {
            private $test;
            private $captured;
            public function __construct($test, $captured)
            {
                $this->test = $test;
                $this->captured = $captured;
            }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;

                return $this->test->result([
                    ['entry_id' => 10, 'title' => 'Parent', 'lft' => 2, 'rgt' => 5]
                ], 1);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return ['url' => '{base_url}/', 'uris' => [10 => '/parent/'], 'templates' => []];
            }
            public function get_listing_entry_ids()
            {
                return [99 => 99];
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 10;
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $path = $sql->get_single_path(99);

        $this->assertStringContainsString("node.entry_id = '10'", $captured->queries[0]);
        $this->assertSame(['https://example.test/parent/'], $captured->hookUrls);
        $this->assertSame('https://fr.example.test/parent/', $path[10]['uri']);
        $this->assertSame('Parent', $path[10]['title']);
    }

    public function testGetSinglePathSkipsRowsMissingFromSitePages()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'base_url') {
                    return 'https://example.test/';
                }
                return null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing = false)
            {
                return rtrim($base, '/') . '/' . ltrim($uri, '/');
            }
        });
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, $url)
            {
                return $url;
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([
                    ['entry_id' => 77, 'title' => 'Missing', 'lft' => 2, 'rgt' => 3]
                ], 1);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return ['url' => '{base_url}/', 'uris' => [], 'templates' => []];
            }
            public function get_listing_entry_ids()
            {
                return [];
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $this->assertSame([], $sql->get_single_path(77));
    }

    public function testGetSinglePathReturnsEmptyArrayWhenQueryHasNoRows()
    {
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([], 0);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return ['url' => '{base_url}/', 'uris' => [10 => '/unused/'], 'templates' => []];
            }
            public function get_listing_entry_ids()
            {
                return [];
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $this->assertSame([], $sql->get_single_path(10));
    }

    public function testGetDataCoversExcludeCacheEmptyAndHookBranches()
    {
        StaticCache::clear();

        $extensions = new class {
            public $active = false;
            public function active_hook($name)
            {
                return $this->active && $name === 'structure_get_data_end';
            }
            public function call($name, $data)
            {
                $data['hooked'] = ['entry_id' => 999, 'title' => 'Hooked'];
                return $data;
            }
        };
        ee()->setMock('extensions', $extensions);
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'AND expt.entry_id != 99') !== false) {
                    return $this->test->result([
                        ['entry_id' => 10, 'parent_id' => 0, 'title' => 'Home', 'status' => 'open', 'depth' => 1]
                    ], 1);
                }
                if (strpos($sql, 'AND expt.entry_id != 123') !== false) {
                    return $this->test->result([], 0);
                }
                return $this->test->result([], 0);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $first = $sql->get_data(99);
        $this->assertArrayHasKey(10, $first);

        // second call uses cached non-empty data branch
        $cached = $sql->get_data(99);
        $this->assertSame('Home', $cached[10]['title']);

        // empty result branch writes EMPTY sentinel
        $this->assertSame([], $sql->get_data(123));

        // hook branch executes after cache/data resolution
        $extensions->active = true;
        $hooked = $sql->get_data(99);
        $this->assertArrayHasKey('hooked', $hooked);
    }

    public function testCreateCustomTitlesEdgeBranches()
    {
        ee()->setMock('TMPL', new class {
            public $param = false;
            public function fetch_param($name, $default = false)
            {
                if ($name === 'channel:title') {
                    return $this->param;
                }
                return $default;
            }
        });
        ee()->setMock('extensions', new class {
            public $active = false;
            public function active_hook($name)
            {
                return $this->active && $name === 'structure_create_custom_titles';
            }
            public function call($name, $customTitles)
            {
                return [42 => 'Hooked Title'];
            }
        });

        $sql = new class extends Sql_structure {
            public $titleFields = [];
            public $sqlFields = [];
            public function __construct()
            {
            }
            protected function _get_custom_title_fields($custom_titles)
            {
                return $this->titleFields;
            }
            protected function _get_sql_fields($custom_titles, $title_fields)
            {
                return $this->sqlFields;
            }
            protected function _get_page_titles($sql_fields, $include_listings)
            {
                return [10 => 'Fallback'];
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        // fetch_param false branch
        $this->assertFalse($sql->create_custom_titles(true));

        // extension override branch
        ee()->TMPL->param = 'blog:title';
        ee()->extensions->active = true;
        $this->assertSame([42 => 'Hooked Title'], $sql->create_custom_titles(true));

        // no title fields branch
        ee()->extensions->active = false;
        $sql->titleFields = false;
        $this->assertFalse($sql->create_custom_titles(true));

        // no sql fields branch
        $sql->titleFields = ['blog' => ['field_id' => 10]];
        $sql->sqlFields = [];
        $this->assertFalse($sql->create_custom_titles(true));
    }

    public function testGetChildEntriesAssetDataDuplicateUriAndSetListingData()
    {
        StaticCache::clear();

        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'word_separator') {
                    return 'dash';
                }
                return null;
            }
        });

        $captured = (object) [
            'queries' => [],
            'updatedSql' => null,
            'insertedSql' => null,
            'sitePagesSet' => [],
            'rootUpdated' => 0,
        ];

        ee()->setMock('db', new class($this, $captured) {
            private $test;
            private $captured;
            public $listingExists = true;
            public function __construct($test, $captured)
            {
                $this->test = $test;
                $this->captured = $captured;
            }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;
                if (strpos($sql, 'select entry_id from exp_structure where') !== false) {
                    return $this->test->result([
                        ['entry_id' => 30],
                        ['entry_id' => 31]
                    ], 2);
                }
                if (strpos($sql, 'SELECT * FROM exp_structure_listings WHERE parent_id=') !== false) {
                    return $this->test->result([['entry_id' => 1]], 1);
                }
                return $this->test->result([]);
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'structure_listings') {
                    return $this->test->result($this->listingExists ? [['entry_id' => 55]] : [], $this->listingExists ? 1 : 0);
                }
                return $this->test->result([]);
            }
            public function update_string($table, $data, $where)
            {
                $this->captured->updatedSql = [$table, $data, $where];
                return 'UPDATE_SQL';
            }
            public function insert_string($table, $data)
            {
                $this->captured->insertedSql = [$table, $data];
                return 'INSERT_SQL';
            }
        });

        $sql = new class($captured) extends Sql_structure {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get_entries_by_category($cat)
            {
                return [['entry_id' => 30], ['entry_id' => 31]];
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                if ($type === 'asset') {
                    return [
                        7 => ['channel_id' => 7, 'channel_title' => 'Assets', 'split_assets' => 'y'],
                    ];
                }
                return [];
            }
            public function get_split_assets()
            {
                return [
                    7 => [
                        100 => ['title' => 'Asset A', 'entry_id' => 100],
                    ]
                ];
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return ['url' => '/', 'uris' => [10 => '/parent/', 99 => '/parent/page-1/'], 'templates' => [99 => 9]];
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function set_site_pages($site_id, $site_pages)
            {
                $this->captured->sitePagesSet[] = [$site_id, $site_pages];
            }
            public function update_root_node()
            {
                $this->captured->rootUpdated++;
            }
        };
        $sql->site_id = 1;
        $sql->cache = [];

        $children = $sql->get_child_entries(5, 'news', 'n');
        $this->assertSame([30, 31], $children);

        $assets = $sql->get_cp_asset_data();
        $this->assertArrayHasKey('Asset A', $assets);
        $this->assertSame('y', $assets['Asset A']['split_assets']);
        $this->assertFalse($sql->is_duplicate_page_uri(55, '/no-duplicate-uri'));

        $this->assertSame('/parent/page-1-1/', $sql->is_duplicate_page_uri(55, '/parent/page-1'));

        $payload = [
            'site_id' => 1,
            'entry_id' => 55,
            'channel_id' => 7,
            'parent_id' => 10,
            'parent_uri' => '/parent/',
            'uri' => '/child/',
            'template_id' => 22,
            'listing_cid' => 0,
            'hidden' => 'n',
        ];

        ee()->db->listingExists = true;
        $sql->set_listing_data($payload);
        ee()->db->listingExists = false;
        $sql->set_listing_data($payload);

        $this->assertNotEmpty($captured->sitePagesSet);
        $this->assertNotEmpty($captured->updatedSql);
        $this->assertNotEmpty($captured->insertedSql);
        $this->assertSame(2, $captured->rootUpdated);
    }

    public function testGetCpAssetDataHandlesFalseAndSplitAssetsNoMode()
    {
        $sqlFalse = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                return false;
            }
            public function get_split_assets()
            {
                return [];
            }
        };
        $this->assertFalse($sqlFalse->get_cp_asset_data());

        $sqlNoSplit = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                if ($type === 'asset') {
                    return [
                        12 => ['channel_id' => 12, 'channel_title' => 'Flat Assets', 'split_assets' => 'n'],
                    ];
                }
                return [];
            }
            public function get_split_assets()
            {
                return [];
            }
        };

        $assets = $sqlNoSplit->get_cp_asset_data();
        $this->assertArrayHasKey('Flat Assets', $assets);
        $this->assertSame('n', $assets['Flat Assets']['split_assets']);
    }

    public function testGetCpAssetDataReturnsSortedMixedAssetRows()
    {
        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                if ($type === 'asset') {
                    return [
                        7 => ['channel_id' => 7, 'channel_title' => 'Zeta Assets', 'split_assets' => 'n'],
                        9 => ['channel_id' => 9, 'channel_title' => 'Split Assets', 'split_assets' => 'y'],
                    ];
                }

                return [];
            }
            public function get_split_assets()
            {
                return [
                    9 => [
                        201 => ['title' => 'Beta Asset', 'entry_id' => 201],
                        305 => ['title' => 'Gamma Asset', 'entry_id' => 305],
                    ],
                ];
            }
        };

        $assets = $sql->get_cp_asset_data();

        $this->assertSame(['Beta Asset', 'Gamma Asset', 'Zeta Assets'], array_keys($assets));
        $this->assertSame([
            'title' => 'Beta Asset',
            'channel_id' => 9,
            'entry_id' => 201,
            'split_assets' => 'y',
        ], $assets['Beta Asset']);
        $this->assertSame([
            'title' => 'Zeta Assets',
            'channel_id' => 7,
            'split_assets' => 'n',
        ], $assets['Zeta Assets']);
    }

    public function testGetCpAssetDataReturnsEmptyArrayWhenAssetChannelsAreEmpty()
    {
        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                return [];
            }
            public function get_split_assets()
            {
                return [];
            }
        };

        $this->assertSame([], $sql->get_cp_asset_data());
    }

    public function testGetCpAssetDataReturnsEmptyArrayWhenAssetChannelsAreEmptyTraversable()
    {
        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                return new ArrayIterator([]);
            }
            public function get_split_assets()
            {
                return [];
            }
        };

        $this->assertSame([], $sql->get_cp_asset_data());
    }

    public function testGetCpAssetDataReturnsEmptyArrayWhenSplitChannelHasNoEntries()
    {
        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                if ($type === 'asset') {
                    return [
                        14 => ['channel_id' => 14, 'channel_title' => 'Split Assets', 'split_assets' => 'y'],
                    ];
                }

                return [];
            }
            public function get_split_assets()
            {
                return [
                    14 => [],
                ];
            }
        };

        $this->assertSame([], $sql->get_cp_asset_data());
    }

    public function testGetCpAssetDataReturnsEmptyArrayWhenSplitChannelHasEmptyTraversableEntries()
    {
        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                if ($type === 'asset') {
                    return [
                        21 => ['channel_id' => 21, 'channel_title' => 'Split Assets', 'split_assets' => 'y'],
                    ];
                }

                return [];
            }
            public function get_split_assets()
            {
                return [
                    21 => new ArrayIterator([]),
                ];
            }
        };

        $this->assertSame([], $sql->get_cp_asset_data());
    }

    public function testGetStructureChannelIdsReturnsStringWhenRequested()
    {
        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                if ($type === 'listing') {
                    return [9 => ['channel_id' => 9]];
                }
                return [
                    2 => ['channel_id' => 2],
                    3 => ['channel_id' => 3],
                ];
            }
        };

        $rm = new ReflectionMethod($sql, '_get_structure_channel_ids');
        \TestReflectionHelper::makeAccessible($rm);
        $ids = $rm->invoke($sql, true, true);

        $this->assertSame('2,3,9', $ids);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIsDuplicatePageUriCoversCloningModeBranch()
    {
        if (!defined('CLONING_MODE')) {
            define('CLONING_MODE', true);
        }

        ee()->resetMocks();
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'word_separator') {
                    return 'dash';
                }
                return null;
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return ['url' => '/', 'uris' => [1 => '/parent/page/'], 'templates' => [1 => 2]];
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
        };

        $this->assertSame('/parent/copy-page/', $sql->is_duplicate_page_uri(99, '/parent/page'));
    }

    /**
     * It uses the dash separator without a trailing slash in normal duplicate resolution.
     *
     * @return void
     */
    public function testIsDuplicatePageUriUsesDashSeparatorWithoutTrailingSlashInNormalMode()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'word_separator') {
                    return 'dash';
                }

                return null;
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => '/',
                    'uris' => [
                        10 => '/parent/page',
                        11 => '/parent/page-1',
                    ],
                    'templates' => [
                        10 => 2,
                        11 => 3,
                    ],
                ];
            }
            public function get_settings()
            {
                return [];
            }
        };

        $this->assertSame('/parent/page-2', $sql->is_duplicate_page_uri(99, '/parent/page'));
    }

    /**
     * It ignores the current entry URI before checking the remaining page collisions.
     *
     * @return void
     */
    public function testIsDuplicatePageUriIgnoresCurrentEntryUriWhenCheckingDuplicates()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'word_separator') {
                    return 'dash';
                }

                return null;
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => '/',
                    'uris' => [
                        99 => '/parent/page',
                    ],
                    'templates' => [
                        99 => 2,
                    ],
                ];
            }
            public function get_settings()
            {
                return [];
            }
        };

        $this->assertFalse($sql->is_duplicate_page_uri(99, '/parent/page'));
    }

    public function testRetrieveStructureUrlTitleBuildsParentChainRecursively()
    {
        $sql = $this->makeSql();

        ee()->setMock('db', new class {
            private $entryId = null;
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function where($field, $value = null)
            {
                if ($field === 'structure.entry_id') {
                    $this->entryId = (int) $value;
                }
                return $this;
            }
            public function join($table, $condition, $type = '')
            {
                return $this;
            }
            public function limit($count)
            {
                return $this;
            }
            public function get($table = null)
            {
                $rows = [
                    3 => ['structure_url_title' => 'child', 'parent_id' => 2],
                    2 => ['structure_url_title' => 'parent', 'parent_id' => 1],
                    1 => ['structure_url_title' => 'root', 'parent_id' => 0],
                ];

                return new class($rows[$this->entryId] ?? []) {
                    private $row;
                    public function __construct($row)
                    {
                        $this->row = $row;
                    }
                    public function row_array()
                    {
                        return $this->row;
                    }
                };
            }
        });

        $this->assertSame('root/parent/child', $sql->retrieve_structure_url_title(3));
    }

    public function testDeprecatedRestoreSitePagesMethodsPopulateMissingTemplates()
    {
        $captured = (object) ['updates' => []];

        ee()->setMock('functions', new class {
            public function fetch_site_index($includeIndex = 1, $includeQuery = 0)
            {
                return 'https://example.com/';
            }
        });
        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;
            public function __construct($captured, $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }
            public function query($sql)
            {
                if (strpos($sql, 'SELECT channel_id FROM exp_channel_titles') !== false) {
                    return $this->test->result([['channel_id' => 9]], 1);
                }
                return $this->test->result([]);
            }
            public function where($field, $value = null)
            {
                return $this;
            }
            public function update($table, $data = null, $where = null)
            {
                $this->captured->updates[] = [$table, $data];
                return true;
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'uris' => [10 => '/existing/', 11 => '/needs-template/'],
                    'templates' => [10 => 2]
                ];
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                return [
                    9 => ['template_id' => 7]
                ];
            }
        };
        $sql->site_id = 1;

        $sql->restore_site_pages_from_structure();
        $sql->restore_site_pages_templates();

        $this->assertCount(2, $captured->updates);
        $firstUpdate = $captured->updates[0][1]['site_pages'];
        $decoded = unserialize(base64_decode($firstUpdate));
        $this->assertSame(7, $decoded[1]['templates'][11]);
        $this->assertSame('https://example.com/', $decoded[1]['url']);
    }

    public function testGetMemberGroupsCoversAllowedAndEmptyPermissionPaths()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                return 1;
            }
        });

        $model = new class {
            public $permissionRows = [
                'can_create_entries' => [2, 3],
                'can_edit_other_entries' => [2, 3],
                'can_edit_self_entries' => [2, 3],
            ];
            public function get($name)
            {
                return new class($name, $this) {
                    private $name;
                    private $owner;
                    private $permissionFilter = null;
                    public function __construct($name, $owner)
                    {
                        $this->name = $name;
                        $this->owner = $owner;
                    }
                    public function fields($field)
                    {
                        return $this;
                    }
                    public function filter($field, $operatorOrValue = null, $value = null)
                    {
                        if ($this->name === 'Permission' && $field === 'permission') {
                            $needle = is_string($value) ? $value : (string) $operatorOrValue;
                            $this->permissionFilter = rtrim($needle, '%');
                        }
                        return $this;
                    }
                    public function order($field, $direction = 'asc')
                    {
                        return $this;
                    }
                    public function all()
                    {
                        return $this;
                    }
                    public function pluck($field)
                    {
                        if ($this->name === 'Permission') {
                            return $this->owner->permissionRows[$this->permissionFilter] ?? [];
                        }
                        return [];
                    }
                    public function toArray()
                    {
                        if ($this->name === 'Role') {
                            return [
                                ['role_id' => 2, 'name' => 'Editors'],
                                ['role_id' => 3, 'name' => 'Authors'],
                            ];
                        }
                        return [];
                    }
                    public function first()
                    {
                        if ($this->name === 'Module') {
                            return new class {
                                public $AssignedRoles;
                                public function __construct()
                                {
                                    $this->AssignedRoles = new class {
                                        public function pluck($field)
                                        {
                                            return [2];
                                        }
                                    };
                                }
                            };
                        }
                        return null;
                    }
                };
            }
        };
        ee()->setMock('Model', $model);

        $sql = $this->makeSql();
        $groups = $sql->get_member_groups();
        $this->assertCount(1, $groups);
        $this->assertSame(2, $groups[0]['id']);
        $this->assertSame('Editors', $groups[0]['title']);

        $model->permissionRows['can_edit_self_entries'] = [];
        $this->assertFalse($sql->get_member_groups());
    }

    public function testUpdateIntegrityDataUsesSitePagesAndChannelDefaults()
    {
        $captured = (object) ['updates' => []];

        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;
            private $entryIdWhere = null;
            public function __construct($captured, $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function where($field, $value = null)
            {
                if ($field === 'entry_id') {
                    $this->entryIdWhere = (int) $value;
                }
                return $this;
            }
            public function join($table, $condition, $type = '')
            {
                return $this;
            }
            public function get($table = null)
            {
                return $this->test->result([
                    ['entry_id' => 10, 'channel_id' => 2, 'url_title' => 'from-title', 'parent_id' => 0],
                    ['entry_id' => 11, 'channel_id' => 3, 'url_title' => 'fallback-title', 'parent_id' => 0],
                    ['entry_id' => 12, 'channel_id' => 4, 'url_title' => '', 'parent_id' => 0],
                ]);
            }
            public function update($table, $data = null, $where = null)
            {
                $this->captured->updates[] = [$table, $this->entryIdWhere, $data];
                return true;
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'uris' => [10 => '/a/', 11 => '/b/', 12 => '/c/'],
                    'templates' => [10 => 20],
                ];
            }
            public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
            {
                return [
                    3 => ['template_id' => 30],
                    4 => ['template_id' => 0],
                ];
            }
        };
        $sql->site_id = 1;

        $sql->update_integrity_data();

        $updatesByEntry = [];
        foreach ($captured->updates as $update) {
            $updatesByEntry[$update[1]][] = $update[2];
        }

        $this->assertSame(20, $updatesByEntry[10][0]['template_id']);
        $this->assertSame('from-title', $updatesByEntry[10][1]['structure_url_title']);
        $this->assertSame(30, $updatesByEntry[11][0]['template_id']);
        $this->assertSame('fallback-title', $updatesByEntry[11][1]['structure_url_title']);
        $this->assertArrayNotHasKey(12, $updatesByEntry);
    }

    public function testAddAttributesCoversHiddenLastAndIdBranches()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->setMap([
            'css_id' => 'root',
            'add_level_classes' => 'yes',
            'current_class' => 'here',
            'has_children_class' => 'yes',
            'add_unique_ids' => 'entry_id',
        ]);

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_single_path($entry_id)
            {
                return [1 => 1];
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 1;
            }
            public function get_listing_entry_ids()
            {
                return [99 => 1];
            }
        };

        $pages = [
            1 => [
                'entry_id' => 1, 'depth' => 1, 'parent_id' => 0, 'lft' => 2, 'rgt' => 5,
                'slug' => '/home/', 'classes' => [], 'ids' => [], 'hidden' => 'n',
            ],
            2 => [
                'entry_id' => 2, 'depth' => 2, 'parent_id' => 1, 'lft' => 3, 'rgt' => 4,
                'slug' => '/home/child/', 'classes' => [], 'ids' => [], 'hidden' => 'y',
            ],
        ];

        $result = $sql->add_attributes($pages, 99, 'sub', 'no');

        $this->assertArrayHasKey(1, $result);
        $this->assertArrayNotHasKey(2, $result);
        $this->assertContains('level_1', $result[1]['classes']);
        $this->assertContains('here', $result[1]['classes']);
        $this->assertContains('parent_here', $result[1]['classes']);
        $this->assertContains('has_children', $result[1]['classes']);
        $this->assertContains('first', $result[1]['classes']);
        $this->assertContains('last', $result[1]['classes']);
        $this->assertContains('root_1', $result[1]['ids']);
    }

    public function testGenerateNavCoversOverviewEqualDepthAndWrappedOutput()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->setMap([
            'encode_titles' => 'yes',
            'add_span' => 'yes',
            'include_ul' => 'yes',
            'css_class' => 'nav-class',
            'css_id' => 'nav_sub',
            'wrap_start' => '<nav>',
            'wrap_end' => '</nav>',
        ]);

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function add_attributes($pages, $entry_id, $mode, $override_hidden_state = "no")
            {
                return [
                    1 => ['entry_id' => 1, 'title' => 'Top', 'uri' => '/top/', 'depth' => 1, 'classes' => ['first'], 'ids' => ['id1']],
                    2 => ['entry_id' => 2, 'title' => 'Child', 'uri' => '/top/child/', 'depth' => 2, 'classes' => [], 'ids' => []],
                    3 => ['entry_id' => 3, 'title' => 'Second', 'uri' => '/second/', 'depth' => 1, 'classes' => [], 'ids' => []],
                    4 => ['entry_id' => 4, 'title' => 'Third', 'uri' => '/third/', 'depth' => 1, 'classes' => [], 'ids' => []],
                ];
            }
            public function create_custom_titles($include_listings = false)
            {
                return false;
            }
        };

        $html = $sql->generate_nav([], false, 1, 'sub', true, 'Overview', 'no', 'no', 1);

        $this->assertStringContainsString('<nav><ul id="nav_sub" class="nav-class">', $html);
        $this->assertStringContainsString('<li class="first"><a href="/top/">Overview</a></li>', $html);
        $this->assertStringContainsString('<li class="" id="id1"><a href="/top/"><span>Top</span></a>', $html);
        $this->assertStringContainsString('<a href="/second/"><span>Second</span></a>', $html);
        $this->assertStringContainsString('</ul></nav>', $html);
    }

    public function testGenerateNavReturnsNullForEmptyTree()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                return 'underscore';
            }
        });
        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->setMap([]);

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function add_attributes($pages, $entry_id, $mode, $override_hidden_state = "no")
            {
                return [];
            }
            public function create_custom_titles($include_listings = false)
            {
                return false;
            }
        };

        $this->assertNull($sql->generate_nav([], 1, 1, 'sub', false, 'Overview'));
    }

    public function testAddAttributesCoversCurrentOverviewSlugAndTopLastBranches()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->setMap([
            'css_id' => 'root',
            'add_level_classes' => 'yes',
            'current_class' => 'active',
            'has_children_class' => 'kids',
            'add_unique_ids' => 'yes',
        ]);

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_single_path($entry_id)
            {
                return [2 => 2];
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 1;
            }
            public function get_listing_entry_ids()
            {
                return [];
            }
        };

        $pages = [
            1 => ['entry_id' => 1, 'depth' => 1, 'parent_id' => 0, 'lft' => 1, 'rgt' => 4, 'slug' => '/home/', 'classes' => [], 'ids' => [], 'hidden' => 'n'],
            2 => ['entry_id' => 2, 'depth' => 2, 'parent_id' => 1, 'lft' => 2, 'rgt' => 3, 'slug' => '/home/child/', 'classes' => [], 'ids' => [], 'hidden' => 'n'],
            3 => ['entry_id' => 3, 'depth' => 1, 'parent_id' => 0, 'lft' => 5, 'rgt' => 6, 'slug' => '/other/', 'classes' => [], 'ids' => [], 'hidden' => 'n', 'overview' => true],
        ];

        $result = $sql->add_attributes($pages, 1, 'main', 'no');

        $this->assertContains('active', $result[1]['classes']);
        $this->assertContains('kids', $result[1]['classes']);
        $this->assertContains('active', $result[2]['classes']);
        $this->assertContains('last', $result[2]['classes']);
        $this->assertContains('overview', $result[3]['classes']);
        $this->assertContains('first', $result[3]['classes']);
        $this->assertContains('last', $result[3]['classes']);
        $this->assertContains('root_home', $result[1]['ids']);
    }

    public function testAddAttributesUsesDashSeparatorCssIdFallbackAndHomeSlugId()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'word_separator') {
                    return 'dash';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->setMap([
            'css_id' => 'none',
            'current_class' => 'off',
            'has_children_class' => 'no',
            'add_unique_ids' => 'on',
        ]);

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_single_path($entry_id)
            {
                return [];
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 0;
            }
            public function get_listing_entry_ids()
            {
                return [];
            }
        };

        $pages = [
            1 => [
                'entry_id' => 1,
                'depth' => 1,
                'parent_id' => 0,
                'lft' => 1,
                'rgt' => 2,
                'slug' => '/',
                'classes' => [],
                'ids' => [],
                'hidden' => 'n',
            ],
        ];

        $result = $sql->add_attributes($pages, 1, 'main', 'no');

        $this->assertContains('first', $result[1]['classes']);
        $this->assertContains('last', $result[1]['classes']);
        $this->assertContains('nav-home', $result[1]['ids']);
        $this->assertNotContains('here', $result[1]['classes']);
        $this->assertNotContains('level-1', $result[1]['classes']);
    }

    public function testAddAttributesKeepsHiddenLastChildWhenOverrideEnabled()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->setMap([
            'css_id' => 'root',
            'add_level_classes' => 'yes',
            'current_class' => 'here',
            'has_children_class' => 'no',
            'add_unique_ids' => 'entry_id',
        ]);

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_single_path($entry_id)
            {
                return [];
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 0;
            }
            public function get_listing_entry_ids()
            {
                return [];
            }
        };

        $pages = [
            1 => [
                'entry_id' => 1,
                'depth' => 1,
                'parent_id' => 0,
                'lft' => 1,
                'rgt' => 6,
                'slug' => '/parent/',
                'classes' => [],
                'ids' => [],
                'hidden' => 'n',
            ],
            2 => [
                'entry_id' => 2,
                'depth' => 2,
                'parent_id' => 1,
                'lft' => 2,
                'rgt' => 3,
                'slug' => '/parent/visible/',
                'classes' => [],
                'ids' => [],
                'hidden' => 'n',
            ],
            3 => [
                'entry_id' => 3,
                'depth' => 2,
                'parent_id' => 1,
                'lft' => 4,
                'rgt' => 5,
                'slug' => '/parent/hidden/',
                'classes' => [],
                'ids' => [],
                'hidden' => 'y',
            ],
        ];

        $result = $sql->add_attributes($pages, 99, 'sub', 'yes');

        $this->assertArrayHasKey(3, $result);
        $this->assertContains('last', $result[3]['classes']);
        $this->assertContains('level_2', $result[3]['classes']);
        $this->assertContains('root_3', $result[3]['ids']);
        $this->assertNotContains('last', $result[2]['classes']);
    }

    public function testGenerateNavCoversRenameOverviewAndCssIdNoneBranches()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'word_separator') {
                    return 'underscore';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->setMap([
            'encode_titles' => 'no',
            'add_span' => 'no',
            'include_ul' => 'yes',
            'css_id' => 'none',
            'wrap_start' => '',
            'wrap_end' => '',
        ]);

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function add_attributes($pages, $entry_id, $mode, $override_hidden_state = "no")
            {
                return [
                    1 => ['entry_id' => 1, 'title' => 'Top', 'uri' => '/top/', 'depth' => 1, 'classes' => [], 'ids' => []],
                    2 => ['entry_id' => 2, 'title' => 'Child', 'uri' => '/top/child/', 'depth' => 2, 'classes' => [], 'ids' => []],
                ];
            }
            public function create_custom_titles($include_listings = false)
            {
                return false;
            }
        };

        $htmlRecursiveNo = $sql->generate_nav([], 1, 1, 'sub', true, 'title', 'no', 'no', 1);
        $this->assertStringContainsString('<li class="first"><a href="/top/">Top</a></li>', $htmlRecursiveNo);
        $this->assertStringNotContainsString('id="none"', $htmlRecursiveNo);

        $htmlRecursiveYes = $sql->generate_nav([], 1, 1, 'sub', true, 'title', 'no', 'yes', 1);
        $this->assertStringContainsString('<li class="first"><a href="/top/">Top</a></li>', $htmlRecursiveYes);
    }

    public function testGenerateNavUsesCustomTitlesWithoutWrapperAndFallsBackToEntryId()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'word_separator') {
                    return 'dash';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->setMap([
            'encode_titles' => 'no',
            'add_span' => 'no',
            'include_ul' => 'no',
        ]);

        $sql = new class extends Sql_structure {
            public $capturedEntryId;
            public function __construct()
            {
            }
            public function add_attributes($pages, $entry_id, $mode, $override_hidden_state = "no")
            {
                $this->capturedEntryId = $entry_id;

                return [
                    10 => ['entry_id' => 10, 'title' => 'Parent', 'uri' => '/parent/', 'depth' => 1, 'classes' => [], 'ids' => []],
                    11 => ['entry_id' => 11, 'title' => 'Child', 'uri' => '/parent/child/', 'depth' => 2, 'classes' => [], 'ids' => []],
                ];
            }
            public function create_custom_titles($include_listings = false)
            {
                return [
                    10 => 'Custom <Parent>',
                    11 => 'Custom Child',
                ];
            }
        };

        $html = $sql->generate_nav([], false, 42, 'sub', false, 'Overview', 'yes', 'no', 1);

        $this->assertSame(42, $sql->capturedEntryId);
        $this->assertStringNotContainsString('<ul id=', $html);
        $this->assertStringContainsString('<li><a href="/parent/">Custom <Parent></a>', $html);
        $this->assertStringContainsString('<li><a href="/parent/child/">Custom Child</a></li>', $html);
    }

    public function testGenerateNavCoversLevelMismatchAndRecursiveOverviewRenameString()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'word_separator') {
                    return 'dash';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new FakeTemplate());
        ee()->TMPL->setMap([
            'encode_titles' => 'yes',
            'add_span' => 'no',
            'include_ul' => 'yes',
            'wrap_start' => '',
            'wrap_end' => '',
        ]);

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function add_attributes($pages, $entry_id, $mode, $override_hidden_state = "no")
            {
                return [
                    1 => ['entry_id' => 1, 'title' => 'Top', 'uri' => '/top/', 'depth' => 1, 'classes' => [], 'ids' => []],
                    2 => ['entry_id' => 2, 'title' => 'Child', 'uri' => '/top/child/', 'depth' => 2, 'classes' => [], 'ids' => []],
                ];
            }
            public function create_custom_titles($include_listings = false)
            {
                return false;
            }
        };

        $htmlLevelMismatch = $sql->generate_nav([], 1, 1, 'sub', true, 'Overview', 'no', 'no', 99);
        $this->assertStringNotContainsString('<li class="first"><a href="/top/">Overview</a></li>', $htmlLevelMismatch);

        $htmlRecursiveRename = $sql->generate_nav([], 1, 1, 'sub', true, 'Browse', 'no', 'yes', 99);
        $this->assertStringContainsString('<ul id="nav-sub">', $htmlRecursiveRename);
        $this->assertStringContainsString('<li class="first"><a href="/top/">Browse</a></li>', $htmlRecursiveRename);
    }

    public function testGetSelectiveDataCoversResultFlowAndUrlGeneration()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'base_url') {
                    return 'https://example.test/';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new class {
            public $cache_timestamp = '';
        });
        ee()->setMock('localize', (object) ['now' => 1700000000]);
        ee()->setMock('session', (object) ['cache' => ['structure' => []]]);
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing_slash = false)
            {
                return rtrim($base, '/') . '/' . trim($uri, '/');
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([
                    [
                        'entry_id' => 0,
                        'parent_id' => 0,
                        'lft' => 1,
                        'rgt' => 4,
                        'title' => 'root',
                        'entry_date' => 0,
                        'expiration_date' => 0,
                        'status' => 'open',
                        'hidden' => 'n',
                    ],
                    [
                        'entry_id' => 10,
                        'parent_id' => 0,
                        'lft' => 2,
                        'rgt' => 3,
                        'title' => 'Home',
                        'entry_date' => 0,
                        'expiration_date' => 0,
                        'status' => 'open',
                        'hidden' => 'n',
                    ],
                ]);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 0;
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [10 => '/home/'],
                    'templates' => [10 => 5],
                ];
            }
            public function is_listing_entry($entry_id)
            {
                return false;
            }
            public function get_overview($branch_entry_id)
            {
                return ['entry_id' => 99, 'title' => 'Overview', 'lft' => 0, 'rgt' => 0];
            }
        };
        $sql->site_id = 1;

        $data = $sql->get_selective_data(
            1,
            10,
            0,
            'full',
            1,
            -1,
            'open',
            [],
            [],
            false,
            'Overview',
            'yes',
            'yes',
            'no',
            'no',
            'yes'
        );

        $this->assertArrayHasKey(10, $data);
        $this->assertSame('/home/', $data[10]['slug']);
        $this->assertSame('https://example.test/home', $data[10]['uri']);
    }

    public function testCleanupCoversSitePagesAndStructureModes()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });

        $captured = (object) ['queries' => [], 'setSitePages' => []];
        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;
            public function __construct($captured, $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;
                if (strpos($sql, 'SELECT MAX(rgt) AS max_right') !== false) {
                    return $this->test->result([['max_right' => 7]], 1);
                }
                return $this->test->result([]);
            }
        });

        $sql = new class($captured) extends Sql_structure {
            private $captured;
            public function __construct($captured)
            {
                $this->captured = $captured;
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return ['url' => 'https://example.test/', 'uris' => [], 'templates' => []];
            }
            public function generate_site_pages_array()
            {
                return ['url' => 'https://example.test/', 'uris' => [11 => '/from-structure/'], 'templates' => [11 => 4]];
            }
            public function set_site_pages($site_id, $site_pages)
            {
                $this->captured->setSitePages[] = [$site_id, $site_pages];
                return true;
            }
        };
        $sql->site_id = 1;

        $this->assertTrue($sql->cleanup('site_pages'));
        $this->assertTrue($sql->cleanup('structure'));

        $queryText = implode("\n", $captured->queries);
        $this->assertStringContainsString('DELETE FROM exp_structure WHERE site_id = 1', $queryText);
        $this->assertStringContainsString('DELETE FROM exp_structure_listings WHERE site_id = 1', $queryText);
        $this->assertStringContainsString('UPDATE exp_structure SET rgt = 8 WHERE site_id = 0', $queryText);
        $this->assertCount(1, $captured->setSitePages);
        $this->assertSame(1, $captured->setSitePages[0][0]);
        $this->assertSame('/from-structure/', $captured->setSitePages[0][1]['uris'][11]);
    }

    public function testGenerateSitePagesArrayCoversParentAndListingBranches()
    {
        $originalGet = $_GET;
        $_GET = [];

        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function where($field, $value = null)
            {
                return $this;
            }
            public function join($table, $condition, $type = '')
            {
                return $this;
            }
            public function get()
            {
                return $this->test->result([
                    ['entry_id' => 1, 'structure_url_title' => 'Parent', 'parent_id' => 0, 'channel_id' => 2, 'listing_cid' => 9, 'template_id' => 10],
                    ['entry_id' => 2, 'structure_url_title' => 'Child', 'parent_id' => 1, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 11],
                    ['entry_id' => 3, 'structure_url_title' => 'Leaf', 'parent_id' => 99, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 12],
                    ['entry_id' => 4, 'structure_url_title' => '', 'parent_id' => 0, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 13],
                ], 4);
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'channel_titles') {
                    return $this->test->result([
                        ['entry_id' => 20, 'url_title' => 'Listing'],
                    ], 1);
                }
                return $this->test->result([]);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [1 => '/parent/'],
                    'templates' => [1 => 10],
                ];
            }
            public function retrieve_structure_url_title($entry_id)
            {
                return 'generated-parent';
            }
        };
        $sql->site_id = 1;

        $sitePages = $sql->generate_site_pages_array();
        $_GET = $originalGet;

        $this->assertSame('/parent/', $sitePages['uris'][1]);
        $this->assertSame('/parent/child/', $sitePages['uris'][2]);
        $this->assertSame('/generated-parent/leaf/', $sitePages['uris'][3]);
        $this->assertSame('/', $sitePages['uris'][4]);
        $this->assertSame('/parent/listing/', $sitePages['uris'][20]);
        $this->assertSame(11, $sitePages['templates'][2]);
        $this->assertSame(10, $sitePages['templates'][20]);
    }

    public function testCleanupSitePagesModeCoversNotInDeleteBranch()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'structure_nav_history') {
                    return 'n';
                }
                return null;
            }
        });

        $captured = (object) ['queries' => []];
        ee()->setMock('db', new class($captured, $this) {
            private $captured;
            private $test;
            public function __construct($captured, $test)
            {
                $this->captured = $captured;
                $this->test = $test;
            }
            public function query($sql)
            {
                $this->captured->queries[] = $sql;
                if (strpos($sql, 'SELECT MAX(rgt) AS max_right') !== false) {
                    return $this->test->result([['max_right' => 12]], 1);
                }
                return $this->test->result([]);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [10 => '/a/', 11 => '/b/'],
                    'templates' => [10 => 1, 11 => 2],
                ];
            }
        };
        $sql->site_id = 1;

        $this->assertTrue($sql->cleanup('site_pages'));
        $queryText = implode("\n", $captured->queries);
        $this->assertStringContainsString('DELETE FROM exp_structure WHERE site_id = 1 AND entry_id NOT IN (10,11)', $queryText);
        $this->assertStringContainsString('DELETE FROM exp_structure_listings WHERE site_id = 1 AND entry_id NOT IN (10,11)', $queryText);
        $this->assertStringContainsString('UPDATE exp_structure SET rgt = 13 WHERE site_id = 0', $queryText);
    }

    public function testGenerateSitePagesArrayCoversRootListingAndTemplatePreserveBranch()
    {
        $originalGet = $_GET;
        $_GET = [];

        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function where($field, $value = null)
            {
                return $this;
            }
            public function join($table, $condition, $type = '')
            {
                return $this;
            }
            public function get()
            {
                return $this->test->result([
                    ['entry_id' => 30, 'structure_url_title' => '', 'parent_id' => 0, 'channel_id' => 2, 'listing_cid' => 8, 'template_id' => 14],
                ], 1);
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'channel_titles') {
                    return $this->test->result([
                        ['entry_id' => 31, 'url_title' => 'List-A'],
                    ], 1);
                }
                return $this->test->result([]);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [30 => '/'],
                    'templates' => [30 => 14, 31 => 99],
                ];
            }
            public function retrieve_structure_url_title($entry_id)
            {
                return '';
            }
        };
        $sql->site_id = 1;

        $sitePages = $sql->generate_site_pages_array();
        $_GET = $originalGet;

        $this->assertSame('/', $sitePages['uris'][30]);
        $this->assertSame('list-a/', $sitePages['uris'][31]);
        $this->assertSame(99, $sitePages['templates'][31]);
    }

    public function testGenerateSitePagesArrayDebugModeCoversDebugBranchesBeforeTerminalDie()
    {
        $originalGet = $_GET;
        $_GET = ['debug' => '1'];

        ee()->setMock('db', new class($this) {
            private $test;
            private $listingLookups = 0;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function select($fields = '*')
            {
                return $this;
            }
            public function from($table)
            {
                return $this;
            }
            public function where($field, $value = null)
            {
                return $this;
            }
            public function join($table, $condition, $type = '')
            {
                return $this;
            }
            public function get()
            {
                return $this->test->result([
                    ['entry_id' => 40, 'structure_url_title' => 'Parent', 'parent_id' => 90, 'channel_id' => 2, 'listing_cid' => 0, 'template_id' => 10],
                    ['entry_id' => 41, 'structure_url_title' => 'Second', 'parent_id' => 0, 'channel_id' => 2, 'listing_cid' => 9, 'template_id' => 11],
                ], 2);
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'channel_titles') {
                    $this->listingLookups++;
                    if ($this->listingLookups >= 1) {
                        throw new RuntimeException('stop-before-terminal-die');
                    }
                }
                return $this->test->result([]);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [],
                    'templates' => [],
                ];
            }
            public function retrieve_structure_url_title($entry_id)
            {
                return 'generated-parent';
            }
        };
        $sql->site_id = 1;

        ob_start();
        try {
            $sql->generate_site_pages_array();
            $this->fail('Expected debug run to be interrupted before terminal die');
        } catch (RuntimeException $e) {
            $this->assertSame('stop-before-terminal-die', $e->getMessage());
        } finally {
            $output = ob_get_clean();
            $_GET = $originalGet;
        }

        $this->assertStringContainsString('Final Title', $output);
        $this->assertStringContainsString('Gen Parent', $output);
    }

    public function testGetSelectiveDataCoversMainModeStringFiltersAndEmptyResults()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new class {
            public $cache_timestamp = '';
        });
        ee()->setMock('localize', (object) ['now' => 1700000000]);
        ee()->setMock('session', (object) ['cache' => ['structure' => []]]);
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([]);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 0;
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [10 => '/alpha/'],
                    'templates' => [10 => 1],
                ];
            }
        };
        $sql->site_id = 1;

        $result = $sql->get_selective_data(
            1,
            10,
            10,
            'main',
            'all',
            1,
            'not open',
            '10|abc',
            '10|xyz',
            false,
            'Overview',
            'no',
            'no',
            'no',
            'no',
            'yes'
        );

        $this->assertSame([], $result);
    }

    public function testGetSelectiveDataCoversSubModeListingAndTreePruningFlow()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'base_url') {
                    return 'https://example.test/';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new class {
            public $cache_timestamp = '';
        });
        ee()->setMock('localize', (object) ['now' => 1700000000]);
        ee()->setMock('session', (object) ['cache' => ['structure' => []]]);
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing_slash = false)
            {
                return rtrim($base, '/') . '/' . trim($uri, '/');
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([
                    ['entry_id' => 0, 'parent_id' => 0, 'lft' => 1, 'rgt' => 10, 'title' => 'root', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 10, 'parent_id' => 0, 'lft' => 2, 'rgt' => 7, 'title' => 'Parent', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 11, 'parent_id' => 10, 'lft' => 3, 'rgt' => 4, 'title' => 'Child One', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 12, 'parent_id' => 10, 'lft' => 5, 'rgt' => 6, 'title' => 'Child Two', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'closed', 'hidden' => 'n'],
                    ['entry_id' => 13, 'parent_id' => 0, 'lft' => 8, 'rgt' => 9, 'title' => 'Sibling', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                ], 5);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 10;
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [10 => '/parent/', 11 => '/parent/one/', 12 => '/parent/two/', 13 => '/sibling/'],
                    'templates' => [10 => 1, 11 => 1, 12 => 1, 13 => 1],
                ];
            }
            public function is_listing_entry($entry_id)
            {
                return true;
            }
        };
        $sql->site_id = 1;

        $data = $sql->get_selective_data(
            1,
            20,
            0,
            'sub',
            1,
            2,
            'open',
            '11|13',
            [],
            false,
            'Overview',
            'yes',
            'yes',
            'no',
            'no',
            'yes'
        );

        $this->assertNotEmpty($data);
        $this->assertTrue(isset($data[11]) || isset($data[13]));
    }

    public function testGetSelectiveDataCoversStatusRootRemovalBranch()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new class {
            public $cache_timestamp = '';
        });
        ee()->setMock('localize', (object) ['now' => 1700000000]);
        ee()->setMock('session', (object) ['cache' => ['structure' => []]]);
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([
                    ['entry_id' => 10, 'parent_id' => 0, 'lft' => 1, 'rgt' => 2, 'title' => 'Closed Root', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'closed', 'hidden' => 'n'],
                ], 1);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 0;
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [10 => '/closed/'],
                    'templates' => [10 => 1],
                ];
            }
            public function is_listing_entry($entry_id)
            {
                return false;
            }
        };
        $sql->site_id = 1;

        $data = $sql->get_selective_data(
            1,
            10,
            10,
            'sub',
            1,
            1,
            'open',
            [],
            [],
            false,
            'Overview',
            'yes',
            'yes',
            'no',
            'no',
            'yes'
        );

        $this->assertSame([], $data);
    }

    public function testGetSelectiveDataCoversOverviewAndHookBranches()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'base_url') {
                    return 'https://example.test/';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new class {
            public $cache_timestamp = '';
        });
        ee()->setMock('localize', (object) ['now' => 1700000000]);
        ee()->setMock('session', (object) ['cache' => ['structure' => []]]);
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return in_array($name, ['structure_get_selective_data_results', 'structure_generate_page_url_end'], true);
            }
            public function call($name, ...$args)
            {
                if ($name === 'structure_generate_page_url_end') {
                    return $args[0] . '?hook=1';
                }
                return $args[0] ?? null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing_slash = false)
            {
                return rtrim($base, '/') . '/' . trim($uri, '/');
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([
                    ['entry_id' => 0, 'parent_id' => 0, 'lft' => 1, 'rgt' => 4, 'title' => 'root', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 5, 'parent_id' => 0, 'lft' => 2, 'rgt' => 3, 'title' => 'Page', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                ], 2);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 0;
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [5 => '/page/'],
                    'templates' => [5 => 2],
                ];
            }
            public function is_listing_entry($entry_id)
            {
                return false;
            }
            public function get_overview($branch_entry_id)
            {
                return ['title' => 'Overview Row', 'lft' => 0, 'rgt' => 0];
            }
        };
        $sql->site_id = 1;

        $data = $sql->get_selective_data(
            1,
            5,
            0,
            'full',
            1,
            -1,
            'open',
            [],
            [],
            true,
            'title',
            'yes',
            'yes',
            'no',
            'no',
            'yes'
        );

        $this->assertArrayHasKey(5, $data);
        $this->assertSame('https://example.test/page?hook=1', $data[5]['uri']);
    }

    public function testGetSelectiveDataCoversSubModeAllDepthAndSiteUrlOverride()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'base_url') {
                    return 'https://example.test/';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new class {
            public $cache_timestamp = '';
        });
        ee()->setMock('localize', (object) ['now' => 1700000000]);
        ee()->setMock('session', (object) ['cache' => ['structure' => []]]);
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing_slash = false)
            {
                $prefix = $base !== '' ? rtrim($base, '/') . '/' : '{base_url}/';

                return $prefix . trim($uri, '/');
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([
                    ['entry_id' => 10, 'parent_id' => 0, 'lft' => 1, 'rgt' => 4, 'title' => 'Branch', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 11, 'parent_id' => 10, 'lft' => 2, 'rgt' => 3, 'title' => 'Leaf', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                ], 2);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 10;
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://ignored.test/',
                    'uris' => [10 => '/branch/', 11 => '/branch/leaf/'],
                    'templates' => [10 => 1, 11 => 1],
                ];
            }
            public function is_listing_entry($entry_id)
            {
                return false;
            }
        };
        $sql->site_id = 1;

        $data = $sql->get_selective_data(
            1,
            11,
            10,
            'sub',
            'all',
            -1,
            'open',
            [],
            [],
            false,
            'Overview',
            'yes',
            'yes',
            'no',
            'no',
            'no'
        );

        $this->assertArrayHasKey(11, $data);
        $this->assertSame('https://example.test/branch/leaf', $data[11]['uri']);
    }

    public function testGetSelectiveDataCoversActiveBranchPruneBeforeExpansion()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'base_url') {
                    return 'https://example.test/';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new class {
            public $cache_timestamp = '';
        });
        ee()->setMock('localize', (object) ['now' => 1700000000]);
        ee()->setMock('session', (object) ['cache' => ['structure' => []]]);
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing_slash = false)
            {
                return rtrim($base, '/') . '/' . trim($uri, '/');
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([
                    ['entry_id' => 10, 'parent_id' => 0, 'lft' => 1, 'rgt' => 8, 'title' => 'Root', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 11, 'parent_id' => 10, 'lft' => 2, 'rgt' => 7, 'title' => 'Active', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 12, 'parent_id' => 11, 'lft' => 3, 'rgt' => 6, 'title' => 'Grandchild', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 13, 'parent_id' => 12, 'lft' => 4, 'rgt' => 5, 'title' => 'Great Grandchild', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                ], 4);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 0;
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [10 => '/root/', 11 => '/root/active/', 12 => '/root/active/grandchild/', 13 => '/root/active/grandchild/great-grandchild/'],
                    'templates' => [10 => 1, 11 => 1, 12 => 1, 13 => 1],
                ];
            }
            public function is_listing_entry($entry_id)
            {
                return false;
            }
        };
        $sql->site_id = 1;

        $data = $sql->get_selective_data(
            1,
            11,
            10,
            'sub',
            2,
            -1,
            'open',
            [],
            [],
            false,
            'Overview',
            'yes',
            'yes',
            'no',
            'no',
            'yes'
        );

        $this->assertArrayHasKey(11, $data);
        $this->assertArrayHasKey(12, $data);
        $this->assertArrayNotHasKey(13, $data);
    }

    public function testGetSelectiveDataCoversActiveGrandchildExpansionPath()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                if ($key === 'base_url') {
                    return 'https://example.test/';
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new class {
            public $cache_timestamp = '';
        });
        ee()->setMock('localize', (object) ['now' => 1700000000]);
        ee()->setMock('session', (object) ['cache' => ['structure' => []]]);
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });
        ee()->setMock('functions', new class {
            public function create_page_url($base, $uri, $trailing_slash = false)
            {
                return rtrim($base, '/') . '/' . trim($uri, '/');
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([
                    ['entry_id' => 10, 'parent_id' => 0, 'lft' => 1, 'rgt' => 10, 'title' => 'Root', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 11, 'parent_id' => 10, 'lft' => 2, 'rgt' => 9, 'title' => 'Branch', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 12, 'parent_id' => 11, 'lft' => 3, 'rgt' => 6, 'title' => 'Current', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 14, 'parent_id' => 12, 'lft' => 4, 'rgt' => 5, 'title' => 'Expanded Child', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 13, 'parent_id' => 11, 'lft' => 7, 'rgt' => 8, 'title' => 'Sibling Child', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                ], 4);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 10;
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [10 => '/root/', 11 => '/root/branch/', 12 => '/root/branch/current/', 13 => '/root/branch/sibling-child/', 14 => '/root/branch/current/expanded-child/'],
                    'templates' => [10 => 1, 11 => 1, 12 => 1, 13 => 1, 14 => 1],
                ];
            }
            public function is_listing_entry($entry_id)
            {
                return false;
            }
        };
        $sql->site_id = 1;

        $data = $sql->get_selective_data(
            1,
            12,
            10,
            'sub',
            1,
            -1,
            'open',
            [],
            [],
            false,
            'Overview',
            'yes',
            'yes',
            'no',
            'no',
            'yes'
        );

        $this->assertArrayHasKey(12, $data);
        $this->assertArrayHasKey(13, $data);
        $this->assertArrayHasKey(14, $data);
    }

    public function testGetSelectiveDataReturnsEmptyWhenIncludeFilterRemovesRoot()
    {
        ee()->setMock('config', new class {
            public function item($key)
            {
                if ($key === 'site_id') {
                    return 1;
                }
                return null;
            }
        });
        ee()->setMock('TMPL', new class {
            public $cache_timestamp = '';
        });
        ee()->setMock('localize', (object) ['now' => 1700000000]);
        ee()->setMock('session', (object) ['cache' => ['structure' => []]]);
        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
            public function call($name, ...$args)
            {
                return $args[0] ?? null;
            }
        });
        ee()->setMock('db', new class($this) {
            private $test;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function query($sql)
            {
                return $this->test->result([
                    ['entry_id' => 0, 'parent_id' => 0, 'lft' => 1, 'rgt' => 4, 'title' => 'Global Root', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                    ['entry_id' => 10, 'parent_id' => 0, 'lft' => 2, 'rgt' => 3, 'title' => 'Branch Root', 'entry_date' => 0, 'expiration_date' => 0, 'status' => 'open', 'hidden' => 'n'],
                ], 2);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_parent_id($entry_id, $default = 'home')
            {
                return 0;
            }
            public function get_settings()
            {
                return ['add_trailing_slash' => 'y'];
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [10 => '/branch-root/'],
                    'templates' => [10 => 1],
                ];
            }
            public function is_listing_entry($entry_id)
            {
                return false;
            }
        };
        $sql->site_id = 1;

        $data = $sql->get_selective_data(
            1,
            10,
            10,
            'sub',
            1,
            -1,
            'open',
            [11],
            [],
            false,
            'Overview',
            'yes',
            'yes',
            'no',
            'no',
            'yes'
        );

        $this->assertSame([], $data);
    }

    public function testCleanupCheckCoversOrphanMismatchListingAndDuplicateFlows()
    {
        ee()->setMock('general_helper', new class {
            public function cpURL($section, $method, $params = [])
            {
                return 'cp://' . $section . '/' . $method . '/' . ($params['entry_id'] ?? '0');
            }
        });

        ee()->setMock('db', new class($this) {
            private $test;
            private $selected = null;
            public function __construct($test)
            {
                $this->test = $test;
            }
            public function select($fields = '*')
            {
                $this->selected = $fields;
                return $this;
            }
            public function get_where($table, $where = null, $limit = null, $offset = null)
            {
                if ($table === 'structure') {
                    return $this->test->result([
                        ['entry_id' => 100, 'listing_cid' => 9, 'structure_url_title' => 'parent-mismatch', 'template_id' => 5],
                        ['entry_id' => 200, 'listing_cid' => 0, 'structure_url_title' => 'missing', 'template_id' => 99],
                    ], 2);
                }

                if ($table === 'channel_titles' && isset($where['channel_id'])) {
                    if ((int) $where['channel_id'] === 9) {
                        return $this->test->result([
                            ['entry_id' => 101, 'title' => 'Listing One', 'url_title' => 'list1'],
                            ['entry_id' => 105, 'title' => 'Listing Two', 'url_title' => 'list2'],
                        ], 2);
                    }
                }

                if ($table === 'channel_titles' && isset($where['entry_id'])) {
                    $entryId = (int) $where['entry_id'];
                    if ($entryId === 100) {
                        return $this->test->result([['entry_id' => 100, 'title' => 'Parent', 'url_title' => 'parent', 'channel_id' => 2]], 1);
                    }
                    if ($entryId === 102) {
                        return $this->test->result([['entry_id' => 102, 'title' => 'Site Only', 'url_title' => 'site-only', 'channel_id' => 3]], 1);
                    }
                    return $this->test->result([], 0);
                }

                if ($table === 'structure_listings' && isset($where['entry_id'])) {
                    $entryId = (int) $where['entry_id'];
                    if ($entryId === 101) {
                        return $this->test->result([['entry_id' => 101, 'uri' => 'wrong-uri', 'template_id' => 0]], 1);
                    }
                    return $this->test->result([], 0);
                }

                if ($table === 'structure_channels') {
                    return $this->test->result([['channel_id' => 3]], 1);
                }

                return $this->test->result([], 0);
            }
            public function query($sql)
            {
                if (strpos($sql, 'GROUP BY rgt') !== false) {
                    return $this->test->result([['rgt' => 10], ['rgt' => 10]], 2);
                }
                if (strpos($sql, 'GROUP BY lft') !== false) {
                    return $this->test->result([['lft' => 2]], 1);
                }
                return $this->test->result([], 0);
            }
        });

        $sql = new class extends Sql_structure {
            public function __construct()
            {
            }
            public function get_site_pages($cache_bust = false, $override_slash = false)
            {
                return [
                    'url' => 'https://example.test/',
                    'uris' => [
                        100 => '/parent/',
                        101 => '/parent/list1/',
                        102 => '/site-only/',
                        103 => '/dup/',
                        104 => '/dup/',
                    ],
                    'templates' => [100 => 5, 101 => 6, 102 => 7, 103 => 8, 104 => 9],
                ];
            }
            public function is_valid_template($template_id)
            {
                return in_array((int) $template_id, [6, 7, 8, 9], true);
            }
        };
        $sql->site_id = 1;

        $vals = $sql->cleanup_check();

        $this->assertNotEmpty($vals['site_pages_uri_duplicates']);
        $this->assertNotEmpty($vals['mismatch_url_entries']);
        $this->assertNotEmpty($vals['template_id_errors']);
        $this->assertArrayHasKey(200, $vals['orphaned_entries']);
        $this->assertGreaterThan(0, $vals['duplicate_rights']);
        $this->assertGreaterThan(0, $vals['duplicate_lefts']);
        $this->assertTrue($vals['validation_action_enabled']);
    }

    private function makeSql()
    {
        $sql = (new ReflectionClass('Sql_structure'))->newInstanceWithoutConstructor();
        $sql->site_id = 1;
        $sql->cache = [];
        return $sql;
    }

    /**
     * Build a fluent DB mock for get_entries_by_category() scenarios.
     *
     * @param array $categoryRows
     * @param array $categoryPostRows
     * @param int|null $categoryNumRows
     * @return object
     */
    private function makeGetEntriesByCategoryDb(array $categoryRows, array $categoryPostRows, ?int $categoryNumRows = null)
    {
        $captured = (object) ['gets' => []];

        $db = new class($this, $captured, $categoryRows, $categoryPostRows, $categoryNumRows) {
            private $test;
            private $captured;
            private $categoryRows;
            private $categoryPostRows;
            private $categoryNumRows;
            private $table;
            private $fields = [];
            private $where = [];

            public function __construct($test, $captured, $categoryRows, $categoryPostRows, $categoryNumRows)
            {
                $this->test = $test;
                $this->captured = $captured;
                $this->categoryRows = $categoryRows;
                $this->categoryPostRows = $categoryPostRows;
                $this->categoryNumRows = $categoryNumRows;
            }

            public function select($field)
            {
                $this->fields[] = $field;

                return $this;
            }

            public function from($table)
            {
                $this->table = $table;

                return $this;
            }

            public function where($field, $value = null)
            {
                $this->where[$field] = $value;

                return $this;
            }

            public function get($table = null)
            {
                if ($table !== null) {
                    $this->table = $table;
                }

                $this->captured->gets[] = [
                    'table' => $this->table,
                    'fields' => $this->fields,
                    'where' => $this->where,
                ];

                $rows = [];
                $numRows = null;

                if ($this->table === 'categories') {
                    $rows = $this->categoryRows;
                    $numRows = $this->categoryNumRows;
                }

                if ($this->table === 'category_posts') {
                    $rows = $this->categoryPostRows;
                }

                $this->table = null;
                $this->fields = [];
                $this->where = [];

                return $this->test->result($rows, $numRows);
            }
        };

        return (object) [
            'captured' => $captured,
            'db' => $db,
        ];
    }

    /**
     * Build a fluent DB mock for get_channel_name_by_channel_id() lookups.
     *
     * @param array $rows
     * @return object
     */
    private function makeChannelNameByChannelIdDb(array $rows)
    {
        $captured = (object) ['gets' => []];

        $db = new class($this, $captured, $rows) {
            private $test;
            private $captured;
            private $rows;
            private $table;
            private $fields = [];
            private $where = [];

            public function __construct($test, $captured, $rows)
            {
                $this->test = $test;
                $this->captured = $captured;
                $this->rows = $rows;
            }

            public function select($field)
            {
                $this->fields[] = $field;

                return $this;
            }

            public function from($table)
            {
                $this->table = $table;

                return $this;
            }

            public function where($field, $value = null)
            {
                $this->where[$field] = $value;

                return $this;
            }

            public function get($table = null)
            {
                if ($table !== null) {
                    $this->table = $table;
                }

                $this->captured->gets[] = [
                    'table' => $this->table,
                    'fields' => $this->fields,
                    'where' => $this->where,
                ];

                $this->table = null;
                $this->fields = [];
                $this->where = [];

                return $this->test->result($this->rows);
            }
        };

        return (object) [
            'captured' => $captured,
            'db' => $db,
        ];
    }

    public function result(array $rows, ?int $numRows = null)
    {
        return new class($rows, $numRows) {
            private $rows;
            public $num_rows;
            public function __construct($rows, $numRows)
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
            public function result()
            {
                return array_map(function ($row) {
                    return (object) $row;
                }, $this->rows);
            }
            public function row($column = null)
            {
                $row = (object) ($this->rows[0] ?? []);
                if ($column !== null) {
                    return $row->$column ?? null;
                }
                return $row;
            }
            public function row_array()
            {
                return $this->rows[0] ?? [];
            }
            public function free_result()
            {
            }
        };
    }
}
