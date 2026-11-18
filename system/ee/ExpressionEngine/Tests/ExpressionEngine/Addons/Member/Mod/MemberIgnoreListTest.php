<?php

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
require_once PATH_ADDONS . 'member/mod.member.php';

class MemberIgnoreListTemplateMock
{
    public $tagdata = '{ignore_member_id}:{ignore_screen_name}';
    public $var_single = array(
        'ignore_member_id' => 'ignore_member_id',
        'ignore_screen_name' => 'ignore_screen_name',
    );
    public $noResultsCalls = 0;

    private $params;

    public function __construct(array $params = array())
    {
        $this->params = $params;
    }

    public function fetch_param($key, $default = false)
    {
        return array_key_exists($key, $this->params) ? $this->params[$key] : $default;
    }

    public function no_results()
    {
        $this->noResultsCalls++;

        return 'NO_RESULTS';
    }

    public function swap_var_single($key, $value, $tagdata)
    {
        return str_replace('{' . $key . '}', $value, $tagdata);
    }
}

class MemberIgnoreListSessionMock
{
    private $userdata;

    public function __construct(array $userdata = array())
    {
        $this->userdata = $userdata;
    }

    public function userdata($key, $default = false)
    {
        return array_key_exists($key, $this->userdata) ? $this->userdata[$key] : $default;
    }
}

class MemberIgnoreListFunctionsMock
{
    public function encode_ee_tags($value)
    {
        return $value;
    }
}

class MemberIgnoreListQueryResultMock
{
    private $rows;

    public function __construct(array $rows = array())
    {
        $this->rows = $rows;
    }

    public function num_rows()
    {
        return count($this->rows);
    }

    public function row($field)
    {
        return isset($this->rows[0][$field]) ? $this->rows[0][$field] : null;
    }

    public function result_array()
    {
        return $this->rows;
    }
}

class MemberIgnoreListDbMock
{
    public $queries = array();
    public $selects = array();
    public $wheres = array();
    public $froms = array();
    public $joins = array();
    public $whereIns = array();
    public $gets = array();

    private $queuedResults;

    public function __construct(array $queuedResults = array())
    {
        $this->queuedResults = $queuedResults;
    }

    public function query($sql, $binds = false)
    {
        $this->queries[] = array(
            'sql' => $sql,
            'binds' => $binds,
        );

        return new MemberIgnoreListQueryResultMock();
    }

    public function select($columns = '*', $escape = null)
    {
        $this->selects[] = array(
            'columns' => $columns,
            'escape' => $escape,
        );

        return $this;
    }

    public function where($field, $value = null)
    {
        $this->wheres[] = array(
            'field' => $field,
            'value' => $value,
        );

        return $this;
    }

    public function from($table)
    {
        $this->froms[] = $table;

        return $this;
    }

    public function join($table, $condition, $type = '', $alias = '')
    {
        $this->joins[] = array(
            'table' => $table,
            'condition' => $condition,
            'type' => $type,
            'alias' => $alias,
        );

        return $this;
    }

    public function where_in($field, $values, $binary = false)
    {
        $this->whereIns[] = array(
            'field' => $field,
            'values' => $values,
            'binary' => $binary,
        );

        return $this;
    }

    public function get($table = null, $limit = null, $offset = null)
    {
        $this->gets[] = array(
            'table' => $table,
            'limit' => $limit,
            'offset' => $offset,
        );

        return array_shift($this->queuedResults) ?: new MemberIgnoreListQueryResultMock();
    }
}

class MemberIgnoreListTest extends TestCase
{
    protected function setUp(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }

        ee()->setMock('functions', new MemberIgnoreListFunctionsMock());
    }

    protected function tearDown(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
    }

    public function testRejectsInvalidTemplateMemberIdBeforeQuerying()
    {
        $template = new MemberIgnoreListTemplateMock(array(
            'member_id' => "1' OR SLEEP(1)-- -",
        ));
        $db = new MemberIgnoreListDbMock();

        ee()->setMock('TMPL', $template);
        ee()->setMock('db', $db);
        ee()->setMock('session', new MemberIgnoreListSessionMock());

        $this->assertSame('NO_RESULTS', $this->makeMember()->ignore_list());
        $this->assertSame(1, $template->noResultsCalls);
        $this->assertSame(array(), $db->queries);
        $this->assertSame(array(), $db->gets);
    }

    public function testStoredIgnoreListIdsAreNormalizedBeforeWhereIn()
    {
        $template = new MemberIgnoreListTemplateMock(array(
            'member_id' => '42',
        ));
        $db = new MemberIgnoreListDbMock(array(
            new MemberIgnoreListQueryResultMock(array(
                array('ignore_list' => "7|bad|8' OR SLEEP(1)-- -|0|-2|9|7"),
            )),
            new MemberIgnoreListQueryResultMock(),
        ));

        ee()->setMock('TMPL', $template);
        ee()->setMock('db', $db);
        ee()->setMock('session', new MemberIgnoreListSessionMock());

        $this->assertSame('NO_RESULTS', $this->makeMember()->ignore_list());
        $this->assertSame(array(), $db->queries);
        $this->assertSame(array(
            array(
                'field' => 'member_id',
                'value' => 42,
            ),
        ), $db->wheres);
        $this->assertSame(array(
            array(
                'field' => 'm.member_id',
                'values' => array(7, 9),
                'binary' => false,
            ),
        ), $db->whereIns);
    }

    public function testSessionIgnoreListIdsAreNormalizedBeforeWhereIn()
    {
        $template = new MemberIgnoreListTemplateMock();
        $db = new MemberIgnoreListDbMock(array(
            new MemberIgnoreListQueryResultMock(),
        ));

        ee()->setMock('TMPL', $template);
        ee()->setMock('db', $db);
        ee()->setMock('session', new MemberIgnoreListSessionMock(array(
            'ignore_list' => array('3', "4' OR SLEEP(1)-- -", '5', 0, -6, '3'),
        )));

        $this->assertSame('NO_RESULTS', $this->makeMember()->ignore_list());
        $this->assertSame(array(), $db->queries);
        $this->assertSame(array(
            array(
                'field' => 'm.member_id',
                'values' => array(3, 5),
                'binary' => false,
            ),
        ), $db->whereIns);
    }

    private function makeMember()
    {
        $reflection = new ReflectionClass(Member::class);

        return $reflection->newInstanceWithoutConstructor();
    }
}
