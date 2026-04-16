<?php

if (! function_exists('show_error')) {
    function show_error($message)
    {
        throw new RuntimeException($message);
    }
}

if (! function_exists('load_class')) {
    function load_class($class, $directory = 'libraries')
    {
        if ($class === 'Lang') {
            return new class {
                public function load($file)
                {
                }

                public function line($key)
                {
                    if ($key === 'db_error_heading') {
                        return 'Database Error';
                    }

                    return $key;
                }
            };
        }

        if ($class === 'Exceptions') {
            return new class {
                public function show_error($heading, $message)
                {
                    if (! empty($GLOBALS['db_driver_test_throw_show_error'])) {
                        throw new RuntimeException('show_error short-circuit');
                    }

                    return $heading . ':' . implode('|', (array) $message);
                }
            };
        }

        return new stdClass();
    }
}

require_once SYSPATH . 'ee/legacy/database/DB_driver.php';
require_once SYSPATH . 'ee/legacy/database/DB_active_rec.php';

use PHPUnit\Framework\TestCase;

class DBActiveRecTest extends TestCase
{
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testSelectAggregatesAliasDistinctFromAndJoinBranches(): void
    {
        $driver = new DBActiveRecHarness([]);
        $driver->start_cache();

        $this->assertSame($driver, $driver->select('id, title', false));
        $this->assertSame($driver, $driver->select(['body', '']));

        $driver->select_max('exp_posts.score');
        $driver->select_min('exp_posts.score', 'min_score');
        $driver->select_avg('exp_posts.score');
        $driver->select_sum('exp_posts.score');

        $load = new DBActiveRecLoadStub();
        $logger = new DBActiveRecLoggerStub();
        ee()->setMock('load', $load);
        ee()->setMock('logger', $logger);
        $driver->distinct('yes');
        $driver->distinct(false);

        $driver->from('exp_posts p, exp_members m');
        $driver->from(['exp_comments c']);

        $driver->join('exp_categories cat', 'cat.entry_id = p.entry_id', 'left', 'alias_cat');
        $driver->join('exp_statuses s', 's.status = p.status', 'nonsense');

        $this->assertSame(['logger'], $load->libraries);
        $this->assertSame(['3.2.0'], $logger->deprecatedCalls);
        $this->assertFalse($driver->ar_distinct);
        $this->assertNotEmpty($driver->ar_select);
        $this->assertCount(3, $driver->ar_from);
        $this->assertCount(2, $driver->ar_join);
        $this->assertNotEmpty($driver->ar_cache_select);
        $this->assertNotEmpty($driver->ar_cache_from);
        $this->assertNotEmpty($driver->ar_cache_join);
    }

    public function testMaxMinAvgSumInputAndTypeValidation(): void
    {
        $driver = new DBActiveRecHarness([]);

        try {
            $driver->_max_min_avg_sum([], '', 'MAX');
            $this->fail('Expected trim() TypeError when invalid select input is not short-circuited.');
        } catch (Throwable $exception) {
            $this->assertStringContainsString('trim()', $exception->getMessage());
            $this->assertContains('db_invalid_query', $driver->displayErrorKeys);
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid function type: BAD');
        $driver->_max_min_avg_sum('score', '', 'BAD');
    }

    public function testAliasCreationAndWhereFamilyBranches(): void
    {
        $driver = new DBActiveRecHarness([]);
        $driver->start_cache();

        $this->assertSame('table', $driver->_create_alias_from_table('db.table'));
        $this->assertSame('table', $driver->_create_alias_from_table('table'));

        $driver->start_group();
        $driver->where('status', 'open');
        $driver->or_where(['author >' => 5, 'closed' => null], null, true, true);
        $driver->end_group();
        $driver->or_start_group();
        $driver->where('cached_key', 'cached_value');
        $driver->end_group();

        $driver->where('raw = NOW()', null, false);
        $driver->where('category', 'news', false);
        $driver->_where('dynamic_escape', 'yes', 'AND ', null, false);

        $this->assertSame(' ( ', $driver->ar_where[0]);
        $this->assertStringContainsString('status', $driver->ar_where[1]);
        $this->assertStringContainsString('OR binary author >', implode(' ', $driver->ar_where));
        $this->assertStringContainsString('closed IS NULL', implode(' ', $driver->ar_where));
        $this->assertNotEmpty($driver->ar_cache_where);
    }

    public function testWhereInAndLikeGroupMethods(): void
    {
        $driver = new DBActiveRecHarness([]);
        $driver->start_cache();

        $this->assertNull($driver->_where_in(null, ['x']));
        $this->assertNull($driver->_where_in('id', null));

        $driver->where_in('id', [1, 2]);
        $driver->or_where_in('id', 3, true);
        $driver->where_not_in('id', [4]);
        $driver->or_where_not_in('id', [], false);

        $driver->start_like_group();
        $driver->like('title', 'abc', 'before');
        $driver->or_like('body', 'def', 'after');
        $driver->not_like('summary', 'ghi', 'both');
        $driver->or_not_like(['author' => 'tom'], '', 'both');
        $driver->or_start_like_group();
        $driver->end_like_group();
        $driver->end_like_group();

        $joinedWhere = implode(' ', $driver->ar_where);
        $joinedLike = implode(' ', $driver->ar_like);

        $this->assertStringContainsString('IN', $joinedWhere);
        $this->assertStringContainsString('NOT', $joinedWhere);
        $this->assertStringContainsString('1 = 2', $joinedWhere);
        $this->assertStringContainsString("LIKE '%ls-abc'", $joinedLike);
        $this->assertStringContainsString("LIKE 'ls-def%'", $joinedLike);
        $this->assertStringContainsString('ESCAPE', $joinedLike);
        $this->assertNotEmpty($driver->ar_cache_like);
    }

    public function testGroupByHavingOrderByLimitOffsetAndSet(): void
    {
        $driver = new DBActiveRecHarness([]);
        $driver->ar_aliased_tables = ['alias_table'];
        $driver->start_cache();

        $driver->group_by('status, type');
        $driver->group_by(['author']);
        $driver->having('status', 'open');
        $driver->or_having(['score >' => 10], '', false);

        $driver->order_by('alias_table, exp_posts.entry_id', 'desc', false);
        $driver->order_by('created_at', 'random');
        $driver->order_by('title', 'invalid');

        $driver->limit(50, 10);
        $driver->offset(25);

        $obj = new class {
            public $title = 'alpha';
            public $count = 2;
        };

        $driver->set($obj);
        $driver->set('updated_at', 'NOW()', false);

        $this->assertCount(3, $driver->ar_groupby);
        $this->assertCount(2, $driver->ar_having);
        $this->assertCount(3, $driver->ar_orderby);
        $this->assertSame(50, $driver->ar_limit);
        $this->assertSame(25, $driver->ar_offset);
        $this->assertArrayHasKey('[title]', $driver->ar_set);
        $this->assertSame('NOW()', $driver->ar_set['[updated_at]']);
        $this->assertNotEmpty($driver->ar_cache_groupby);
        $this->assertNotEmpty($driver->ar_cache_having);
        $this->assertNotEmpty($driver->ar_cache_orderby);
    }

    public function testGetCountAndGetWhereResetSelectState(): void
    {
        $driver = new DBActiveRecHarness([]);

        $driver->queryResponse = new DBActiveRecQueryResultStub(1, (object) ['numrows' => '7']);
        $result = $driver->get('exp_posts p', 5, 2);
        $this->assertSame($driver->queryResponse, $result);
        $this->assertSame([], $driver->ar_select);

        $driver->queryResponse = new DBActiveRecQueryResultStub(0, (object) ['numrows' => '9']);
        $this->assertSame(0, $driver->count_all_results('exp_posts'));

        $driver->queryResponse = new DBActiveRecQueryResultStub(1, (object) ['numrows' => '11']);
        $this->assertSame(11, $driver->count_all_results('exp_posts'));

        $driver->queryResponse = new DBActiveRecQueryResultStub(1, (object) ['numrows' => '1']);
        $whereResult = $driver->get_where('exp_posts', ['status' => 'open'], 1, 0);
        $this->assertSame($driver->queryResponse, $whereResult);
        $this->assertNotEmpty($driver->queries);
    }

    public function testInsertAndInsertBatchVariants(): void
    {
        $driver = new DBActiveRecHarness([]);

        $driver->db_debug = false;
        $this->assertFalse($driver->insert_batch('exp_posts'));

        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_use_set', $driver->insert_batch('exp_posts'));

        $driver->db_debug = false;
        $driver->set_insert_batch([
            ['title' => 'one', 'status' => 'open'],
            ['title' => 'two', 'status' => 'closed'],
        ]);
        $this->assertTrue($driver->insert_batch('exp_posts'));
        $this->assertNotEmpty($driver->insertBatchCalls);

        $driver->from('exp_default_posts');
        $this->assertTrue($driver->insert_batch('', [
            ['title' => 'fallback', 'status' => 'open'],
        ]));
        $driverNoTable = new DBActiveRecHarness([]);
        $driverNoTable->db_debug = false;
        $this->assertFalse($driverNoTable->insert_batch('', [
            ['title' => 'fallback', 'status' => 'open'],
        ]));
        $driverNoTable->db_debug = true;
        $this->assertSame('display_error:db_must_set_table', $driverNoTable->insert_batch('', [
            ['title' => 'fallback', 'status' => 'open'],
        ]));

        $driver->set_insert_batch([
            ['title' => 'one', 'status' => 'open'],
            ['title' => 'two'],
        ]);
        $this->assertTrue($driver->insert_batch('exp_posts'));

        try {
            $driver->set_insert_batch('title', 'alpha', false);
            $this->fail('Expected array_keys() TypeError for scalar batch input.');
        } catch (Throwable $exception) {
            $this->assertStringContainsString('array_keys', $exception->getMessage());
        }

        $driver->set_insert_batch([
            ['title' => 'raw', 'status' => 'raw_status'],
        ], '', false);
        $rawBatchRow = end($driver->ar_set);
        $this->assertStringContainsString('raw_status', $rawBatchRow);
        $this->assertStringContainsString('raw', $rawBatchRow);

        $driver->_reset_write();
        $driver->db_debug = false;
        $this->assertFalse($driver->insert());

        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_use_set', $driver->insert());

        $driver->db_debug = false;
        $this->assertFalse($driver->insert('', ['title' => 'needs_table']));
        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_set_table', $driver->insert('', ['title' => 'needs_table']));

        $driver->from('exp_posts');
        $driver->set(['title' => 'alpha']);
        $this->assertSame('QUERY_OK', $driver->insert());
        $this->assertSame('QUERY_OK', $driver->insert('exp_posts', ['title' => 'beta']));
    }

    public function testReplaceUpdateAndUpdateBatchVariants(): void
    {
        $driver = new DBActiveRecHarness([]);

        $driver->db_debug = false;
        $this->assertFalse($driver->replace('exp_posts'));
        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_use_set', $driver->replace('exp_posts'));

        $driver->set(['title' => 'alpha']);
        $this->assertSame('QUERY_OK', $driver->replace('exp_posts'));
        $driver->db_debug = false;
        $this->assertFalse($driver->replace('', ['title' => 'missing']));
        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_set_table', $driver->replace('', ['title' => 'missing']));
        $driver->from('exp_posts');
        $this->assertSame('QUERY_OK', $driver->replace('', ['title' => 'fallback_table']));
        $this->assertSame('QUERY_OK', $driver->replace('exp_posts', ['title' => 'inline']));

        $driver->db_debug = false;
        $this->assertFalse($driver->update('exp_posts'));
        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_use_set', $driver->update('exp_posts'));

        $driver->set(['title' => 'beta']);
        $this->assertSame('QUERY_OK', $driver->update('exp_posts', null, ['id' => 1], 1));
        $this->assertNotEmpty($driver->updateCalls);
        $driver->db_debug = false;
        $this->assertFalse($driver->update('', ['title' => 'needs_table']));
        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_set_table', $driver->update('', ['title' => 'needs_table']));
        $driver->from('exp_posts');
        $this->assertSame('QUERY_OK', $driver->update('', ['title' => 'fallback_table'], ['id' => 2]));
        $this->assertSame('QUERY_OK', $driver->update('exp_posts', ['title' => 'inline']));

        $driver->db_debug = false;
        $this->assertFalse($driver->update_batch('exp_posts', [['id' => 1]], null));
        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_use_index', $driver->update_batch('exp_posts', [['id' => 1]], null));

        $driver->db_debug = false;
        $this->assertFalse($driver->update_batch('exp_posts', [], 'id'));
        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_use_set', $driver->update_batch('exp_posts', [], 'id'));

        $driver->db_debug = false;
        $this->assertFalse($driver->update_batch('', [['id' => 1, 'title' => 'a']], 'id'));
        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_set_table', $driver->update_batch('', [['id' => 1, 'title' => 'a']], 'id'));

        $driver->from('exp_posts');
        $driver->set_update_batch([
            ['id' => 1, 'title' => 'alpha'],
            ['id' => 2, 'title' => 'beta'],
        ], 'id');
        $this->assertNull($driver->update_batch('', null, 'id'));
        $this->assertNotEmpty($driver->updateBatchCalls);

        $driver3 = new DBActiveRecHarness([]);
        $driver3->set_update_batch([
            ['id' => 10, 'title' => 'raw_title'],
        ], 'id', false);
        $this->assertSame('raw_title', $driver3->ar_set[0]['[title]']);

        $driver2 = new DBActiveRecHarness([]);
        $this->assertSame('display_error:db_batch_missing_index', $driver2->set_update_batch([['title' => 'x']], 'id'));
    }

    public function testDeleteEmptyTableTruncateAndDbprefixVariants(): void
    {
        $driver = new DBActiveRecHarness([]);

        $driver->db_debug = false;
        $this->assertFalse($driver->empty_table());
        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_set_table', $driver->empty_table());

        $driver->from('exp_posts');
        $this->assertSame('QUERY_OK', $driver->empty_table());
        $this->assertSame('QUERY_OK', $driver->empty_table('exp_members'));

        $driver->db_debug = false;
        $this->assertFalse($driver->truncate());
        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_set_table', $driver->truncate());

        $driver->from('exp_posts');
        $this->assertSame('QUERY_OK', $driver->truncate());
        $this->assertSame('QUERY_OK', $driver->truncate('exp_members'));

        $driver->db_debug = false;
        $this->assertFalse($driver->delete());
        $driver->db_debug = true;
        $this->assertSame('display_error:db_must_set_table', $driver->delete());

        $driver->from('exp_posts');
        $driver->db_debug = false;
        $this->assertFalse($driver->delete('exp_posts'));
        $driver->db_debug = true;
        $this->assertSame('display_error:db_del_must_use_where', $driver->delete('exp_posts'));

        $driver->where('id', 1);
        $this->assertSame('QUERY_OK', $driver->delete('exp_posts', '', 1));
        $driver->from('exp_posts');
        $this->assertSame('QUERY_OK', $driver->delete('', ['id' => 3]));

        $driver->from('exp_posts');
        $driver->where('id', 2);
        $this->assertNull($driver->delete(['exp_posts', 'exp_members'], '', null, false));

        $driver->dbprefix = 'exp_';
        $this->assertSame('exp_posts', $driver->dbprefix('posts'));
        $driver->dbprefix('');
        $this->assertContains('db_table_name_required', $driver->displayErrorKeys);
    }

    public function testCompileSelectTrackAliasesObjectsAndCacheHelpers(): void
    {
        $driver = new DBActiveRecHarness([]);
        $driver->ar_select = ['title'];
        $driver->ar_no_escape = [true];
        $driver->ar_from = ['[exp_posts p]'];
        $driver->ar_join = ['LEFT JOIN [exp_members m] ON m.id = p.author_id'];
        $driver->ar_where = ['[status] = \'e-open\''];
        $driver->ar_like = ["[title] LIKE '%ls-news%' ESCAPE '!'"];
        $driver->ar_groupby = ['[status]'];
        $driver->ar_having = ['COUNT(*) > 1'];
        $driver->ar_orderby = ['[entry_date] DESC'];
        $driver->ar_order = 'desc';
        $driver->ar_limit = 10;
        $driver->ar_offset = 5;

        $sql = $driver->_compile_select();
        $this->assertStringContainsString('SELECT', $sql);
        $this->assertStringContainsString('FROM', $sql);
        $this->assertStringContainsString('JOIN', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('GROUP BY', $sql);
        $this->assertStringContainsString('HAVING', $sql);
        $this->assertStringContainsString('ORDER BY', $sql);
        $this->assertStringContainsString('LIMIT 10 OFFSET 5', $sql);

        $overrideSql = $driver->_compile_select('SELECT 1', false);
        $this->assertStringStartsWith('SELECT 1', $overrideSql);

        $input = new class {
            public $title = 'hello';
            public $skip = [1, 2];
            public $child;
            public $_parent_name = 'ignore';

            public function __construct()
            {
                $this->child = new stdClass();
            }
        };

        $batch = new class {
            public $title = ['a', 'b'];
            public $status = ['open', 'closed'];
            public $_parent_name = 'ignore';
        };

        $this->assertSame(['title' => 'hello'], $driver->_object_to_array($input));
        $this->assertSame([
            ['title' => 'a', 'status' => 'open'],
            ['title' => 'b', 'status' => 'closed'],
        ], $driver->_object_to_array_batch($batch));
        $this->assertSame('already-array', $driver->_object_to_array('already-array'));
        $this->assertSame('already-array', $driver->_object_to_array_batch('already-array'));

        $driver->start_cache();
        $this->assertTrue($driver->ar_caching);
        $driver->stop_cache();
        $this->assertFalse($driver->ar_caching);

        $driver->ar_cache_where = ['cached'];
        $driver->ar_cache_like = ['cached_like'];
        $driver->ar_cache_exists = ['select', 'from', 'where', 'like'];
        $driver->ar_cache_select = ['cached_col'];
        $driver->ar_select = ['local_col'];
        $driver->ar_cache_from = ['exp_posts p'];
        $driver->ar_from = ['exp_posts p'];
        $driver->ar_cache_join = [];
        $driver->ar_cache_no_escape = [false];
        $driver->ar_cache_exists[] = 'join';
        $driver->_merge_cache();

        $this->assertContains('cached_col', $driver->ar_select);
        $this->assertContains(false, $driver->ar_no_escape);

        $driver->ar_store_array = ['ar_select'];
        $driver->_reset_run(['ar_select' => [], 'ar_from' => []]);
        $this->assertNotSame([], $driver->ar_select);
        $this->assertSame([], $driver->ar_from);

        $driver->ar_cache_select = ['cached'];
        $driver->flush_cache();
        $this->assertSame([], $driver->ar_cache_select);

        $driver->ar_select = ['x'];
        $driver->ar_set = ['k' => 'v'];
        $driver->_reset_select();
        $driver->_reset_write();
        $this->assertSame([], $driver->ar_set);

        $driver->_track_aliases(['exp_posts p', 'exp_members AS m']);
        $driver->_track_aliases('exp_comments c, exp_statuses s');
        $this->assertContains('p', array_map('trim', $driver->ar_aliased_tables));
        $this->assertContains('m', array_map('trim', $driver->ar_aliased_tables));

        $addonMock = new class {
            public function get($name)
            {
                return new class($name) {
                    private $name;

                    public function __construct($name)
                    {
                        $this->name = $name;
                    }

                    public function getName()
                    {
                        return strtoupper($this->name);
                    }
                };
            }
        };
        ee()->setMock('Addon', $addonMock);
        $load = new DBActiveRecLoadStub();
        $logger = new DBActiveRecLoggerStub();
        ee()->setMock('load', $load);
        ee()->setMock('logger', $logger);

        $dir = sys_get_temp_dir() . '/user/addons/demo';
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $file = $dir . '/Caller.php';
        file_put_contents($file, <<<'CODE'
<?php
class DemoAddonCaller {
    public function run($driver) {
        return $this->callTrackAliases($driver);
    }

    private function callTrackAliases($driver) {
        $driver->_track_aliases('member_groups');
    }
}
CODE
);
        require_once $file;

        $caught = null;
        try {
            $caller = new DemoAddonCaller();
            $caller->run($driver);
        } catch (Exception $exception) {
            $caught = $exception;
        }

        $this->assertInstanceOf(Exception::class, $caught);
        $this->assertStringContainsString('non-existent in ExpressionEngine 6', $caught->getMessage());

        $this->assertNotEmpty($logger->developerCalls);
    }
}

class DBActiveRecHarness extends CI_DB_active_record
{
    public $displayErrorKeys = [];
    public $queries = [];
    public $queryResponse;
    public $insertBatchCalls = [];
    public $insertCalls = [];
    public $replaceCalls = [];
    public $updateCalls = [];
    public $updateBatchCalls = [];
    public $deleteCalls = [];
    public $truncateCalls = [];

    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_protect_identifiers = true;
        $this->_like_escape_str = " ESCAPE '%s'";
        $this->_like_escape_chr = '!';
        $this->_random_keyword = 'RANDOM()';
        $this->_count_string = 'SELECT COUNT(*) AS ';
    }

    public function display_error($error = '', $swap = '', $native = false)
    {
        $this->displayErrorKeys[] = $error;

        return 'display_error:' . $error;
    }

    public function query($sql, $binds = false, $return_object = true)
    {
        $this->queries[] = $sql;

        return $this->queryResponse ?: 'QUERY_OK';
    }

    public function _protect_identifiers($item, $prefix_single = false, $protect_identifiers = null, $field_exists = true)
    {
        if (is_array($item)) {
            $escaped = [];
            foreach ($item as $key => $value) {
                $escaped[$this->_protect_identifiers($key)] = $this->_protect_identifiers($value);
            }

            return $escaped;
        }

        if ($item === '*' || strpos($item, '(') !== false || strpos($item, "'") !== false || strpos($item, ' ') !== false) {
            return $item;
        }

        $segments = explode('.', (string) $item);
        $segments = array_map(function ($segment) {
            return '[' . trim($segment) . ']';
        }, $segments);

        return implode('.', $segments);
    }

    public function escape($str)
    {
        if (is_array($str)) {
            return array_map([$this, 'escape'], $str);
        }

        if ($str === null) {
            return 'NULL';
        }

        if (is_bool($str)) {
            return $str ? 1 : 0;
        }

        if (is_numeric($str)) {
            return $str;
        }

        return "'e-" . $str . "'";
    }

    public function escape_str($str, $like = false)
    {
        return 'es-' . $str;
    }

    public function escape_like_str($str)
    {
        return 'ls-' . $str;
    }

    protected function _from_tables($tables)
    {
        return implode(', ', $tables);
    }

    protected function _limit($sql, $limit, $offset)
    {
        return $sql . ' LIMIT ' . $limit . ($offset ? ' OFFSET ' . $offset : '');
    }

    protected function _insert_batch($table, $keys, $values)
    {
        $this->insertBatchCalls[] = [$table, $keys, $values];

        return 'INSERT_BATCH ' . $table;
    }

    protected function _insert($table, $keys, $values)
    {
        $this->insertCalls[] = [$table, $keys, $values];

        return 'INSERT ' . $table;
    }

    protected function _replace($table, $keys, $values)
    {
        $this->replaceCalls[] = [$table, $keys, $values];

        return 'REPLACE ' . $table;
    }

    protected function _update($table, $values, $where, $orderby = [], $limit = false)
    {
        $this->updateCalls[] = [$table, $values, $where, $orderby, $limit];

        return 'UPDATE ' . $table;
    }

    protected function _update_batch($table, $values, $index, $where = null)
    {
        $this->updateBatchCalls[] = [$table, $values, $index, $where];

        return 'UPDATE_BATCH ' . $table;
    }

    protected function _delete($table, $where = [], $like = [], $limit = false, $reset_data = true)
    {
        $this->deleteCalls[] = [$table, $where, $like, $limit, $reset_data];

        return 'DELETE ' . $table;
    }

    protected function _truncate($table)
    {
        $this->truncateCalls[] = [$table];

        return 'TRUNCATE ' . $table;
    }
}

class DBActiveRecQueryResultStub
{
    private $numRows;
    private $row;

    public function __construct(int $numRows, $row)
    {
        $this->numRows = $numRows;
        $this->row = $row;
    }

    public function num_rows()
    {
        return $this->numRows;
    }

    public function row()
    {
        return $this->row;
    }
}

class DBActiveRecLoadStub
{
    public $libraries = [];

    public function library($name)
    {
        $this->libraries[] = $name;
    }
}

class DBActiveRecLoggerStub
{
    public $deprecatedCalls = [];
    public $developerCalls = [];

    public function deprecated($version, $message = '')
    {
        $this->deprecatedCalls[] = $version;
    }

    public function developer($message, $show = false)
    {
        $this->developerCalls[] = [$message, $show];
    }
}
