<?php

use PHPUnit\Framework\TestCase;

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
require_once PATH_ADDONS . 'member/mod.member.php';

class MemberBreadcrumbConfigMock
{
    private $items;

    public function __construct(array $items = array())
    {
        $this->items = $items;
    }

    public function item($key)
    {
        return array_key_exists($key, $this->items) ? $this->items[$key] : null;
    }
}

class MemberBreadcrumbUriMock
{
    private $segments;

    public function __construct(array $segments = array())
    {
        $this->segments = $segments;
    }

    public function segment($index)
    {
        return array_key_exists($index, $this->segments) ? $this->segments[$index] : null;
    }
}

class MemberBreadcrumbLangMock
{
    public function line($key)
    {
        return $key;
    }
}

class MemberBreadcrumbSessionMock
{
    public function userdata($key, $default = false)
    {
        if ($key === 'screen_name') {
            return 'Current Member';
        }

        return $default;
    }
}

class MemberBreadcrumbQueryResultMock
{
    private $row;

    public function __construct(array $row = array())
    {
        $this->row = $row;
    }

    public function row($field)
    {
        return array_key_exists($field, $this->row) ? $this->row[$field] : null;
    }
}

class MemberBreadcrumbDbMock
{
    public $queries = array();
    public $selects = array();
    public $wheres = array();
    public $gets = array();

    private $result;

    public function __construct(MemberBreadcrumbQueryResultMock $result = null)
    {
        $this->result = $result ?: new MemberBreadcrumbQueryResultMock();
    }

    public function query($sql, $binds = false)
    {
        $this->queries[] = array(
            'sql' => $sql,
            'binds' => $binds,
        );

        return $this->result;
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

    public function get($table = null, $limit = null, $offset = null)
    {
        $this->gets[] = array(
            'table' => $table,
            'limit' => $limit,
            'offset' => $offset,
        );

        return $this->result;
    }
}

class MemberBreadcrumbHarness extends Member
{
    public function __construct()
    {
        $this->basepath = '/member';
    }

    public function _load_element($which)
    {
        switch ($which) {
            case 'breadcrumb_trail':
                return '[{crumb_title}:{crumb_link}]';
            case 'breadcrumb_current_page':
                return '<current>{crumb_title}</current>';
            case 'breadcrumb':
                return '{name}:{breadcrumb_links}';
        }

        return '';
    }

    public function _member_path($uri = '')
    {
        return rtrim($this->basepath, '/') . '/' . ltrim($uri, '/');
    }
}

class MemberBreadcrumbTest extends TestCase
{
    protected function setUp(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }

        ee()->setMock('config', new MemberBreadcrumbConfigMock(array(
            'site_url' => 'https://example.test/',
            'site_name' => 'Example Site',
        )));
        ee()->setMock('lang', new MemberBreadcrumbLangMock());
        ee()->setMock('session', new MemberBreadcrumbSessionMock());
    }

    protected function tearDown(): void
    {
        if (function_exists('ee') && method_exists(ee(), 'resetMocks')) {
            ee()->resetMocks();
        }
    }

    public function testBreadcrumbUsesQueryBuilderForDigitMemberId()
    {
        $db = new MemberBreadcrumbDbMock(new MemberBreadcrumbQueryResultMock(array(
            'screen_name' => 'Secure Member',
        )));

        ee()->setMock('uri', new MemberBreadcrumbUriMock(array(
            2 => '42',
        )));
        ee()->setMock('db', $db);

        $breadcrumb = $this->makeMember()->breadcrumb();

        $this->assertStringContainsString('Secure Member', $breadcrumb);
        $this->assertSame(array(), $db->queries);
        $this->assertSame(array(
            array(
                'field' => 'member_id',
                'value' => 42,
            ),
        ), $db->wheres);
        $this->assertSame(array(
            array(
                'table' => 'members',
                'limit' => null,
                'offset' => null,
            ),
        ), $db->gets);
    }

    public function testBreadcrumbDoesNotLookupNonDigitNumericMemberId()
    {
        $db = new MemberBreadcrumbDbMock(new MemberBreadcrumbQueryResultMock(array(
            'screen_name' => 'Should Not Load',
        )));

        ee()->setMock('uri', new MemberBreadcrumbUriMock(array(
            2 => '1e2',
        )));
        ee()->setMock('db', $db);

        $this->assertNull($this->makeMember()->breadcrumb());
        $this->assertSame(array(), $db->queries);
        $this->assertSame(array(), $db->wheres);
        $this->assertSame(array(), $db->gets);
    }

    private function makeMember()
    {
        return new MemberBreadcrumbHarness();
    }
}
